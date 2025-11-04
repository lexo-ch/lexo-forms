<?php

namespace LEXO\LF\Core\Services;

use LEXO\LF\Core\Abstracts\EmailHandler;
use LEXO\LF\Core\Abstracts\Singleton;
use LEXO\LF\Core\Utils\FormMessages;
use LEXO\LF\Core\Utils\FormHelpers;
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
        $message = $this->generateEmailContent($form_id, $template, $form_data);

        // Send email
        return $this->sendEmail($form_id, $form_data, $message);
    }

    /**
     * Generate email content from template and form data
     *
     * @param int $form_id
     * @param array $template
     * @param array $form_data
     * @return string
     */
    private function generateEmailContent(int $form_id, array $template, array $form_data): string
    {
        ob_start();
        ?>
            <table>
                <tr>
                    <td colspan='2' style="padding-bottom: 15px;"><b><?php echo esc_html($this->getTemplateNameForEmail($template['name'] ?? '')); ?></b></td>
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
                    <?php echo esc_html(FormMessages::getFormSubmittedFromMessage($_POST['page'])) ?>
                </p>
            <?php } ?>

            <?php if (function_exists('get_lexo_webdata_html')) { ?>
                <?php echo get_lexo_webdata_html() ?>
            <?php } ?>
        <?php

        return ob_get_clean();
    }

    /**
     * Get template name for email using site language
     *
     * @param string|array $name Template name (string or multilingual array)
     * @return string Translated name
     */
    private function getTemplateNameForEmail($name): string
    {
        // If already a string, return as-is
        if (is_string($name)) {
            return $name;
        }

        // If not an array, return empty string
        if (!is_array($name)) {
            return '';
        }

        // Get site language for admin emails
        $language = FormMessages::getSiteLanguage();

        // Try current language, fallback to 'de', then first available
        return $name[$language] ?? $name['de'] ?? reset($name);
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
        // Get site language for admin emails, but allow override via filter
        $language = apply_filters('lexo-forms/forms/email/label-language', FormMessages::getSiteLanguage());

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
        $language = apply_filters('lexo-forms/cr/email/admin-notification/label-language', FormMessages::getSiteLanguage());

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
     * Separate form data into table fields and additional info fields
     *
     * @param array $form_data Submitted form data
     * @param array $template Template configuration
     * @return array ['table' => [...], 'additional' => [...]]
     */
    private function separateFieldsForDisplay(array $form_data, array $template): array
    {
        $table_fields = [];
        $additional_fields = [];

        $fields_config = $template['fields'] ?? [];

        // Create a map of field configurations by name
        $field_config_map = [];
        foreach ($fields_config as $field_config) {
            $field_name = $field_config['name'] ?? '';
            if ($field_name) {
                $field_config_map[$field_name] = $field_config;
            }
        }

        foreach ($form_data as $field_name => $value) {
            $field_config = $field_config_map[$field_name] ?? null;

            // Check if field should go to table (has send_to_cr flag)
            $send_to_cr = $field_config['send_to_cr'] ?? false;

            // Allow filtering of field placement
            $should_be_in_table = apply_filters(
                'lexo-forms/cr/email/admin-notification/field-in-table',
                $send_to_cr,
                $field_name,
                $field_config,
                $value
            );

            if ($should_be_in_table) {
                $table_fields[$field_name] = $value;
            } else {
                $additional_fields[$field_name] = $value;
            }
        }

        $result = [
            'table' => $table_fields,
            'additional' => $additional_fields
        ];

        // Allow complete override of separation logic
        return apply_filters('lexo-forms/cr/email/admin-notification/separated-fields', $result, $form_data, $template);
    }

    /**
     * Send failure notification to admin when form submission fails completely
     *
     * @param int $form_id
     * @param array $form_data
     * @param array $template
     * @param string $error_message Error message already in site locale
     * @param string $handler_type Handler type to determine if email_settings should be loaded
     * @return bool
     */
    public function sendFailureNotification(int $form_id, array $form_data, array $template, string $error_message, string $handler_type = ''): bool
    {
        $admin_email = get_option('cleverreach_fallback_admin_email', '');
        if (empty($admin_email)) {
            $admin_email = get_option('admin_email');
        }

        if (empty($admin_email)) {
            return false;
        }

        // Get email config (uses same hierarchy as regular form emails: defaults -> filter -> ACF)
        $email_config = $this->getEmailConfig($form_id, $form_data, $handler_type);

        $site_name = get_bloginfo('name');
        $form_title = get_the_title($form_id);
        $subject = FormMessages::getFailureNotificationSubject($site_name, $form_title);

        $field_labels = $this->buildFieldLabelMap($template['fields'] ?? []);
        $separated_fields = $this->separateFieldsForDisplay($form_data, $template);

        ob_start();
        ?>
        <h2><?php echo FormMessages::getFailureNotificationHeading(); ?></h2>
        <p><strong><?php echo FormMessages::getFailureNotificationErrorLabel(); ?></strong> <span style="text-decoration: underline;"><?php echo esc_html($error_message); ?></span></p>

        <h3><?php echo FormMessages::getFormInformationHeading(); ?></h3>
        <ul>
            <li><strong><?php echo FormMessages::getFormLabel(); ?></strong> <?php echo esc_html($form_title); ?></li>
            <li><strong><?php echo FormMessages::getFormIdLabel(); ?></strong> <?php echo esc_html($form_id); ?></li>
            <li><strong><?php echo FormMessages::getDateLabel(); ?></strong> <?php echo esc_html(current_time('Y-m-d H:i:s')); ?></li>
        </ul>

        <h3><?php echo FormMessages::getSubmittedDataHeading(); ?></h3>
        <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
            <thead>
                <tr>
                    <th><?php echo FormMessages::getFieldTableHeader(); ?></th>
                    <th><?php echo FormMessages::getValueTableHeader(); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($separated_fields['table'] as $field_name => $value) { ?>
                    <tr>
                        <td><strong><?php echo esc_html($field_labels[$field_name] ?? ucwords(str_replace(['_', '-'], ' ', $field_name))); ?></strong></td>
                        <td><?php echo nl2br(esc_html($value)); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <?php if (!empty($separated_fields['additional'])) { ?>
            <?php foreach ($separated_fields['additional'] as $field_name => $value) { ?>
                <div style="margin-top: 15px;">
                    <strong><?php echo esc_html($field_labels[$field_name] ?? ucwords(str_replace(['_', '-'], ' ', $field_name))); ?>:</strong>
                    <p style="margin: 5px 0; padding: 10px; background-color: #f5f5f5; border-left: 3px solid #0073aa;">
                        <?php echo nl2br(esc_html($value)); ?>
                    </p>
                </div>
            <?php } ?>
        <?php } ?>

        <p style="margin-top: 20px; color: #666;">
            <?php echo FormMessages::getFailureNotificationFooter(); ?>
        </p>
        <?php
        $message = ob_get_clean();

        // Allow complete override of email HTML
        $message = apply_filters('lexo-forms/cr/email/failure-notification/html', $message, $form_id, $form_data, $template, $error_message);

        $result = $this->sendSimpleEmail(
            $admin_email,
            $subject,
            $message,
            $email_config['from_email'],
            $email_config['from_name']
        );

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
     * @param string $error_message Error message already in site locale
     * @param string $handler_type Handler type to determine if email_settings should be loaded
     * @return bool
     */
    public function sendPartialFailureNotification(
        int $form_id,
        array $form_data,
        array $template,
        string $failed_system,
        string $error_message,
        string $handler_type = ''
    ): bool {
        $admin_email = get_option('cleverreach_fallback_admin_email', '');
        if (empty($admin_email)) {
            $admin_email = get_option('admin_email');
        }

        if (empty($admin_email)) {
            return false;
        }

        // Get email config (uses same hierarchy as regular form emails: defaults -> filter -> ACF)
        $email_config = $this->getEmailConfig($form_id, $form_data, $handler_type);

        $site_name = get_bloginfo('name');
        $form_title = get_the_title($form_id);
        $subject = FormMessages::getPartialFailureNotificationSubject($site_name, $form_title, $failed_system);

        $field_labels = $this->buildFieldLabelMap($template['fields'] ?? []);
        $separated_fields = $this->separateFieldsForDisplay($form_data, $template);

        ob_start();
        ?>
        <h2><?php echo FormMessages::getPartialFailureNotificationHeading(); ?></h2>
        <p style="color: #d63638;">
            <strong><?php echo FormMessages::getPartialFailureOneSystemFailedLabel(); ?></strong>
            <?php
            if ($failed_system === 'cleverreach') {
                echo FormMessages::getPartialFailureCleverReachFailedMessage();
            } else {
                echo FormMessages::getPartialFailureEmailFailedMessage();
            }
            ?>
        </p>
        <p><strong><?php echo FormMessages::getFailureNotificationErrorLabel(); ?></strong> <span style="text-decoration: underline;"><?php echo esc_html($error_message); ?></span></p>

        <h3><?php echo FormMessages::getFormInformationHeading(); ?></h3>
        <ul>
            <li><strong><?php echo FormMessages::getFormLabel(); ?></strong> <?php echo esc_html($form_title); ?></li>
            <li><strong><?php echo FormMessages::getFormIdLabel(); ?></strong> <?php echo esc_html($form_id); ?></li>
            <li><strong><?php echo FormMessages::getDateLabel(); ?></strong> <?php echo esc_html(current_time('Y-m-d H:i:s')); ?></li>
        </ul>

        <h3><?php echo FormMessages::getSubmittedDataHeading(); ?></h3>
        <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
            <thead>
                <tr>
                    <th><?php echo FormMessages::getFieldTableHeader(); ?></th>
                    <th><?php echo FormMessages::getValueTableHeader(); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($separated_fields['table'] as $field_name => $value) { ?>
                    <tr>
                        <td><strong><?php echo esc_html($field_labels[$field_name] ?? ucwords(str_replace(['_', '-'], ' ', $field_name))); ?></strong></td>
                        <td><?php echo nl2br(esc_html($value)); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <?php if (!empty($separated_fields['additional'])) { ?>
            <?php foreach ($separated_fields['additional'] as $field_name => $value) { ?>
                <div style="margin-top: 15px;">
                    <strong><?php echo esc_html($field_labels[$field_name] ?? ucwords(str_replace(['_', '-'], ' ', $field_name))); ?>:</strong>
                    <p style="margin: 5px 0; padding: 10px; background-color: #f5f5f5; border-left: 3px solid #0073aa;">
                        <?php echo nl2br(esc_html($value)); ?>
                    </p>
                </div>
            <?php } ?>
        <?php } ?>

        <p style="margin-top: 20px; color: #666;">
            <?php
            if ($failed_system === 'cleverreach') {
                echo FormMessages::getPartialFailureCleverReachFailedFooter();
            } else {
                echo FormMessages::getPartialFailureEmailFailedFooter();
            }
            ?>
        </p>
        <?php
        $message = ob_get_clean();

        // Allow complete override of email HTML
        $message = apply_filters('lexo-forms/cr/email/partial-failure-notification/html', $message, $form_id, $form_data, $template, $failed_system, $error_message);

        $result = $this->sendSimpleEmail(
            $admin_email,
            $subject,
            $message,
            $email_config['from_email'],
            $email_config['from_name']
        );

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