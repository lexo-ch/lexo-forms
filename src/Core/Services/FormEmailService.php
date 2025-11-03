<?php

namespace LEXO\LF\Core\Services;

use LEXO\LF\Core\Abstracts\EmailHandler;
use LEXO\LF\Core\Abstracts\Singleton;
use LEXO\LF\Core\Utils\FormMessages;
use LEXO\LF\Core\Utils\Logger;

/**
 * Form Email Service
 *
 * Handles sending emails for form submissions using WordPress wp_mail function.
 * Email configuration is provided by theme through filters.
 */
class FormEmailService extends EmailHandler
{
    protected static $instance = null;

    /**
     * Get singleton instance
     *
     * @return static
     */
    public static function getInstance(): self
    {
        return (static::$instance === null)
            ? static::$instance = new static()
            : static::$instance;
    }

    /**
     * Send form submission email
     *
     * @param int $form_id
     * @param array $template
     * @param array $form_data
     * @return bool
     */
    public function sendFormEmail(int $form_id, array $template, array $form_data): bool
    {
        // Generate email content
        $message = $this->generateEmailContent($template, $form_data);

        // Send email
        return $this->sendEmail($form_id, $form_data, $message);
    }

    /**
     * Generate email content from template and form data
     *
     * @param array $template
     * @param array $form_data
     * @return string
     */
    private function generateEmailContent(array $template, array $form_data): string
    {
        ob_start();
        ?>
            <table>
                <tr>
                    <td colspan='2'><b><?php echo esc_html($template['name'] ?? ''); ?></b></td>
                </tr>
                <?php foreach ($template['fields'] as $field_config) { ?>
                    <?php
                    $field_name = $field_config['name'];
                    if (isset($form_data[$field_name]) && !empty($form_data[$field_name])) { ?>
                        <tr>
                            <td>
                                <?php echo esc_html($this->getEmailLabel($field_config, $field_name)) ?>:
                            </td>
                            <td>
                                <?php echo stripslashes($form_data[$field_name]) ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } ?>
            </table>

            <?php if (!empty($_POST['page'])) { ?>
                <p>
                    <?php echo esc_html(sprintf(__('Form submitted from: %s', 'lexoforms'), $_POST['page'])) ?>
                </p>
            <?php } ?>

        <?php if (function_exists('get_lexo_webdata_html')) { ?>
            <?php echo get_lexo_webdata_html() ?>
        <?php } ?>
        <?php

        return ob_get_clean();
    }

    /**
     * Format field name for display
     *
     * @param string $field_name
     * @return string
     */
    private function formatFieldName(string $field_name): string
    {
        // Convert snake_case to Title Case
        return ucwords(str_replace(['_', '-'], ' ', $field_name));
    }

    /**
     * Validate required fields
     *
     * @param array $fields_config
     * @param array $form_data
     * @return bool
     * @throws \Exception
     */
    public function validateRequiredFields(array $fields_config, array $form_data): bool
    {
        $missing_fields = [];

        foreach ($fields_config as $field_config) {
            $field_name = $field_config['name'];
            // Check required fields (assuming required fields are those marked as 'global' in templates)
            if (!empty($field_config['global']) || in_array($field_name, ['email', 'firstname', 'lastname'])) {
                if (empty($form_data[$field_name])) {
                    $missing_fields[] = $this->getEmailLabel($field_config, $field_name);
                }
            }
        }

        if (!empty($missing_fields)) {
            $error_message = FormMessages::getRequiredFieldsMissingError(
                implode(', ', $missing_fields)
            );
            throw new \Exception($error_message);
        }

        return true;
    }

    /**
     * Sanitize form data based on field types
     *
     * @param array $fields_config
     * @param array $raw_data
     * @return array
     */
    public function sanitizeFormData(array $fields_config, array $raw_data): array
    {
        $sanitized_data = [];

        foreach ($fields_config as $field_config) {
            $field_name = $field_config['name'];
            if (!isset($raw_data[$field_name])) {
                continue;
            }

            $value = $raw_data[$field_name];
            $field_type = $field_config['type'] ?? 'text';

            switch ($field_type) {
                case 'email':
                    $sanitized_data[$field_name] = sanitize_email($value);
                    break;
                case 'textarea':
                    $sanitized_data[$field_name] = sanitize_textarea_field($value);
                    break;
                case 'url':
                    $sanitized_data[$field_name] = esc_url_raw($value);
                    break;
                default:
                    $sanitized_data[$field_name] = sanitize_text_field($value);
                    break;
            }
        }

        return $sanitized_data;
    }

    /**
     * Get email label from field config
     * Uses email_label field with language support, fallback to formatted field name
     *
     * @param array $field_config Field configuration
     * @param string $field_name Field name for fallback formatting
     * @return string Email label string
     */
    private function getEmailLabel(array $field_config, string $field_name): string
    {
        // Get default language from filter (default: 'de')
        $language = apply_filters('lexo-forms/forms/email/label-language', 'de');

        // Check if email_label exists
        if (!isset($field_config['email_label'])) {
            return $this->formatFieldName($field_name);
        }

        $email_label = $field_config['email_label'];

        // If it's an array, get the value by language key
        if (is_array($email_label)) {
            // Try current language, fallback to 'de', then formatFieldName
            return $email_label[$language] ?? $email_label['de'] ?? $this->formatFieldName($field_name);
        }

        // If it's a string, return it directly
        return $email_label;
    }

    /**
     * Build field label map from template fields
     *
     * @param array $fields Template fields configuration
     * @return array Map of field_name => label
     */
    private function buildFieldLabelMap(array $fields): array
    {
        $field_labels = [];
        $language = apply_filters('lexo-forms/cr/email/admin-notification/label-language', 'de');

        foreach ($fields as $field_config) {
            $field_name = $field_config['name'] ?? '';

            if (empty($field_name) || !isset($field_config['email_label'])) {
                continue;
            }

            $email_label = $field_config['email_label'];

            if (is_array($email_label)) {
                $label = $email_label[$language] ?? $email_label['de'] ?? null;
            } else {
                $label = $email_label;
            }

            if (!empty($label)) {
                $label = apply_filters('lexo-forms/cr/email/admin-notification/field-label', $label, $field_name, $field_config);
                $field_labels[$field_name] = $label;
            }
        }

        return $field_labels;
    }

    /**
     * Send failure notification to admin when form submission fails completely
     *
     * @param int $form_id
     * @param array $form_data
     * @param array $template
     * @param string $error_message
     * @return bool
     */
    public function sendFailureNotification(int $form_id, array $form_data, array $template, string $error_message): bool
    {
        $admin_email = get_option('cleverreach_fallback_admin_email', '');
        if (empty($admin_email)) {
            $admin_email = get_option('admin_email');
        }

        if (empty($admin_email)) {
            return false;
        }

        $site_name = get_bloginfo('name');
        $form_title = get_the_title($form_id);
        $subject = sprintf(
            __('[%s] CleverReach Submission Failed - %s', 'lexoforms'),
            $site_name,
            $form_title
        );

        $field_labels = $this->buildFieldLabelMap($template['fields'] ?? []);

        ob_start();
        ?>
        <h2><?php echo __('CleverReach Submission Failed', 'lexoforms'); ?></h2>
        <p><strong><?php echo __('Error:', 'lexoforms'); ?></strong> <?php echo esc_html($error_message); ?></p>

        <h3><?php echo __('Form Information', 'lexoforms'); ?></h3>
        <ul>
            <li><strong><?php echo __('Form:', 'lexoforms'); ?></strong> <?php echo esc_html($form_title); ?></li>
            <li><strong><?php echo __('Form ID:', 'lexoforms'); ?></strong> <?php echo esc_html($form_id); ?></li>
            <li><strong><?php echo __('Date:', 'lexoforms'); ?></strong> <?php echo esc_html(current_time('Y-m-d H:i:s')); ?></li>
        </ul>

        <h3><?php echo __('Submitted Data', 'lexoforms'); ?></h3>
        <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
            <thead>
                <tr>
                    <th><?php echo __('Field', 'lexoforms'); ?></th>
                    <th><?php echo __('Value', 'lexoforms'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($form_data as $field_name => $value) { ?>
                    <tr>
                        <td><strong><?php echo esc_html($field_labels[$field_name] ?? ucwords(str_replace(['_', '-'], ' ', $field_name))); ?></strong></td>
                        <td><?php echo esc_html($value); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <p style="margin-top: 20px; color: #666;">
            <?php echo __('This email was sent because CleverReach integration failed. Please review the error and try to resubmit the data manually if needed.', 'lexoforms'); ?>
        </p>
        <?php
        $message = ob_get_clean();

        $result = $this->sendSimpleEmail($admin_email, $subject, $message);

        if (!$result) {
            Logger::emailError('Failed to send failure notification to admin: ' . $admin_email, $form_id);
        } else {
            Logger::debug('Failure notification sent to admin: ' . $admin_email, Logger::CATEGORY_EMAIL);
        }

        return $result;
    }

    /**
     * Send partial failure notification to admin when one system succeeds but another fails
     *
     * @param int $form_id
     * @param array $form_data
     * @param array $template
     * @param string $failed_system Either 'cleverreach' or 'email'
     * @param string $error_message
     * @return bool
     */
    public function sendPartialFailureNotification(
        int $form_id,
        array $form_data,
        array $template,
        string $failed_system,
        string $error_message
    ): bool {
        $admin_email = get_option('cleverreach_fallback_admin_email', '');
        if (empty($admin_email)) {
            $admin_email = get_option('admin_email');
        }

        if (empty($admin_email)) {
            return false;
        }

        $site_name = get_bloginfo('name');
        $form_title = get_the_title($form_id);
        $failed_label = $failed_system === 'cleverreach' ? 'CleverReach' : 'Email';
        $subject = sprintf(
            __('[%s] Partial Form Submission Failure - %s (%s Failed)', 'lexoforms'),
            $site_name,
            $form_title,
            $failed_label
        );

        $field_labels = $this->buildFieldLabelMap($template['fields'] ?? []);

        ob_start();
        ?>
        <h2><?php echo __('Partial Form Submission Failure', 'lexoforms'); ?></h2>
        <p style="color: #d63638;">
            <strong><?php echo __('One system failed:', 'lexoforms'); ?></strong>
            <?php
            if ($failed_system === 'cleverreach') {
                echo __('CleverReach integration failed, but email was sent successfully.', 'lexoforms');
            } else {
                echo __('Email notification failed, but CleverReach integration succeeded.', 'lexoforms');
            }
            ?>
        </p>
        <p><strong><?php echo __('Error:', 'lexoforms'); ?></strong> <?php echo esc_html($error_message); ?></p>

        <h3><?php echo __('Form Information', 'lexoforms'); ?></h3>
        <ul>
            <li><strong><?php echo __('Form:', 'lexoforms'); ?></strong> <?php echo esc_html($form_title); ?></li>
            <li><strong><?php echo __('Form ID:', 'lexoforms'); ?></strong> <?php echo esc_html($form_id); ?></li>
            <li><strong><?php echo __('Date:', 'lexoforms'); ?></strong> <?php echo esc_html(current_time('Y-m-d H:i:s')); ?></li>
        </ul>

        <h3><?php echo __('Submitted Data', 'lexoforms'); ?></h3>
        <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
            <thead>
                <tr>
                    <th><?php echo __('Field', 'lexoforms'); ?></th>
                    <th><?php echo __('Value', 'lexoforms'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($form_data as $field_name => $value) { ?>
                    <tr>
                        <td><strong><?php echo esc_html($field_labels[$field_name] ?? ucwords(str_replace(['_', '-'], ' ', $field_name))); ?></strong></td>
                        <td><?php echo esc_html($value); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <p style="margin-top: 20px; color: #666;">
            <?php
            if ($failed_system === 'cleverreach') {
                echo __('The form submission was successful from the user\'s perspective (email was sent), but the CleverReach integration failed. You may need to manually add this contact to CleverReach.', 'lexoforms');
            } else {
                echo __('The form submission was successful from the user\'s perspective (added to CleverReach), but the email notification failed. You may not have received the standard notification email.', 'lexoforms');
            }
            ?>
        </p>
        <?php
        $message = ob_get_clean();

        $result = $this->sendSimpleEmail($admin_email, $subject, $message);

        if (!$result) {
            Logger::emailError('Failed to send partial failure notification to admin: ' . $admin_email, $form_id);
        } else {
            Logger::debug('Partial failure notification sent to admin: ' . $admin_email, Logger::CATEGORY_EMAIL);
        }

        return $result;
    }

    /**
     * Send confirmation email to visitor
     *
     * @param string $visitor_email
     * @param string $subject
     * @param string $email_body
     * @param string $sender_email
     * @param string $sender_name
     * @param array $attachments
     * @return bool
     */
    public function sendConfirmationEmail(
        string $visitor_email,
        string $subject,
        string $email_body,
        string $sender_email = '',
        string $sender_name = '',
        array $attachments = []
    ): bool {
        if (empty($visitor_email) || !is_email($visitor_email)) {
            return false;
        }

        return $this->sendSimpleEmail(
            $visitor_email,
            $subject,
            $email_body,
            $sender_email,
            $sender_name,
            $attachments
        );
    }
}