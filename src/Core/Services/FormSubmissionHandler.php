<?php

namespace LEXO\LF\Core\Services;

use Exception;
use LEXO\LF\Core\Abstracts\Singleton;
use LEXO\LF\Core\PostTypes\FormsPostType;
use LEXO\LF\Core\Templates\TemplateLoader;
use LEXO\LF\Core\Utils\Logger;
use LEXO\LF\Core\Utils\FormMessages;

use const LEXO\LF\{
    EMAIL_FROM_NAME,
    EMAIL_FROM_EMAIL,
    FIELD_PREFIX
};

/**
 * Form Submission Handler
 *
 * Handles AJAX form submissions for lexo-form action.
 * Integrates with existing LEXO_Captcha system and uses data-action="lexo-form".
 */
class FormSubmissionHandler extends Singleton
{
    protected static $instance = null;

    private TemplateLoader $template_loader;
    private FormEmailService $email_service;

    /**
     * Initialize the handler
     */
    public function __construct()
    {
        $this->template_loader = TemplateLoader::getInstance();
        $this->email_service = FormEmailService::getInstance();
    }

    /**
     * Register hooks
     *
     * @return void
     */
    public function register(): void
    {
        add_action('wp_ajax_lexo-form', [$this, 'handleSubmission']);
        add_action('wp_ajax_nopriv_lexo-form', [$this, 'handleSubmission']);
    }

    /**
     * Handle form submission
     *
     * @return void
     */
    public function handleSubmission(): void
    {
        try {

            // Validate captcha using existing LEXO system
            $this->validateCaptcha();

            // Get form ID
            $form_id = $this->getFormId();

            // OPTIMIZATION: Load ACF field groups (3 DB queries instead of 16+)
            // Group 1: General settings
            $general_settings = get_field(FIELD_PREFIX . 'general_settings', $form_id) ?: [];
            // Group 2: Email settings
            $email_settings = get_field(FIELD_PREFIX . 'email_settings', $form_id) ?: [];
            // Group 3: CleverReach integration settings
            $cr_settings = get_field(FIELD_PREFIX . 'cr_integration', $form_id) ?: [];

            // Get and validate template (from general_settings group)
            $template = $this->getTemplate($form_id, $general_settings);

            // Process form data
            $form_data = $this->processFormData($template);

            // Get handler type (from general_settings group)
            $handler_type = $general_settings[FIELD_PREFIX . 'handler_type'] ?? '';

            // Track results
            $cr_success = false;
            $email_success = false;
            $cr_error = null;
            $email_error = null;

            // Try CleverReach if enabled
            if ($handler_type === 'cr_only' || $handler_type === 'email_and_cr') {
                $cr_result = $this->sendToCleverReach($form_id, $form_data, $template);
                $cr_success = $cr_result['success'];
                $cr_error = $cr_result['error'];
            }

            // Try Email if enabled
            if ($handler_type === 'email_only' || $handler_type === 'email_and_cr') {
                $email_success = $this->email_service->sendFormEmail($form_id, $template, $form_data);
                if (!$email_success) {
                    $email_error = FormMessages::getEmailFailMessage();
                }
            }

            // Handle scenarios based on handler_type
            switch ($handler_type) {
                case 'email_only':
                    // Email must succeed
                    if (!$email_success) {
                        throw new Exception($email_error);
                    }
                    break;

                case 'cr_only':
                    // CR must succeed
                    if (!$cr_success) {
                        $this->email_service->sendFailureNotification($form_id, $form_data, $template, $cr_error);
                        throw new Exception(FormMessages::getCleverReachErrorMessage());
                    }
                    break;

                case 'email_and_cr':
                    // Both failed - error to user
                    if (!$cr_success && !$email_success) {
                        $this->email_service->sendFailureNotification($form_id, $form_data, $template, "CR Error: $cr_error | Email Error: $email_error");
                        throw new Exception(FormMessages::getEmailFailMessage());
                    }

                    // CR failed, Email succeeded - success to user, admin notified
                    if (!$cr_success && $email_success) {
                        $this->email_service->sendPartialFailureNotification($form_id, $form_data, $template, 'cleverreach', $cr_error);
                        // Continue to success
                    }

                    // CR succeeded, Email failed - success to user, admin notified
                    if ($cr_success && !$email_success) {
                        $this->email_service->sendPartialFailureNotification($form_id, $form_data, $template, 'email', $email_error);
                        // Continue to success
                    }

                    // Both succeeded - continue to success
                    break;
            }

            // Send confirmation email to visitor if enabled
            $this->sendConfirmationEmail($form_id, $form_data, $template, $email_settings);

            // Get custom success message or use default (from general_settings group)
            $success_message = $general_settings[FIELD_PREFIX . 'success_message'] ?? '';
            if (empty($success_message)) {
                $success_message = FormMessages::getSuccessMessage();
            }

            // Success response
            wp_send_json_success($success_message);

        } catch (Exception $e) {
            // Log error with form ID if available
            if (isset($form_id)) {
                Logger::formError('Form submission error: ' . $e->getMessage(), $form_id);
            } else {
                Logger::formError('Form submission error: ' . $e->getMessage());
            }

            // Get custom fail message or use the exception message (from general_settings group)
            $fail_message = '';
            if (isset($form_id) && isset($general_settings)) {
                $fail_message = $general_settings[FIELD_PREFIX . 'fail_message'] ?? '';
            }

            if (empty($fail_message)) {
                // Use exception message (which can be user-friendly)
                $fail_message = $e->getMessage();
            }

            wp_send_json_error($fail_message);
        }
    }

    /**
     * Validate captcha using existing LEXO system
     *
     * @return void
     * @throws Exception
     */
    private function validateCaptcha(): void
    {
        if (!function_exists('lexo_captcha_evaluate_data') || !lexo_captcha_evaluate_data($_POST['lexo_captcha_data'])) {
            throw new Exception(FormMessages::getCaptchaFailMessage());
        }
    }

    /**
     * Get form ID from POST data
     *
     * @return int
     * @throws Exception
     */
    private function getFormId(): int
    {
        $form_id = intval($_POST['form_id'] ?? 0);

        if (empty($form_id)) {
            throw new Exception(FormMessages::getFormIdRequiredError());
        }

        // Verify form exists and is published
        $form = get_post($form_id);
        if (!$form || $form->post_type !== FormsPostType::getPostType() || $form->post_status !== 'publish') {
            throw new Exception(FormMessages::getFormNotFoundError());
        }

        return $form_id;
    }

    /**
     * Get template for form
     *
     * @param int $form_id
     * @param array $general_settings General settings group
     * @return array
     * @throws Exception
     */
    private function getTemplate(int $form_id, array $general_settings): array
    {
        $template_id = $general_settings[FIELD_PREFIX . 'html_template'] ?? '';
        if (!$template_id) {
            throw new Exception(FormMessages::getFormTemplateNotConfiguredError());
        }

        $template = $this->template_loader->getTemplateById($template_id);
        if (!$template) {
            throw new Exception(FormMessages::getTemplateNotFoundError());
        }

        return $template;
    }

    /**
     * Process and validate form data
     *
     * @param array $template
     * @return array
     * @throws Exception
     */
    private function processFormData(array $template): array
    {
        $fields_config = $template['fields'] ?? [];

        if (empty($fields_config)) {
            throw new Exception(FormMessages::getNoFieldsConfiguredError());
        }

        // Extract only expected fields from POST data
        $raw_data = [];
        foreach ($fields_config as $field) {
            $field_name = $field['name'];
            if (isset($_POST[$field_name])) {
                $raw_data[$field_name] = $_POST[$field_name];
            }
        }

        // Sanitize data based on field types
        $form_data = $this->email_service->sanitizeFormData($fields_config, $raw_data);

        // Validate required fields
        $this->email_service->validateRequiredFields($fields_config, $form_data);

        // Additional validation for email fields
        $this->validateEmailFields($fields_config, $form_data);

        return $form_data;
    }

    /**
     * Validate email fields
     *
     * @param array $fields_config
     * @param array $form_data
     * @return void
     * @throws Exception
     */
    private function validateEmailFields(array $fields_config, array $form_data): void
    {
        foreach ($fields_config as $field_config) {
            $field_name = $field_config['name'];
            if ($field_config['type'] === 'email' && !empty($form_data[$field_name])) {
                if (!is_email($form_data[$field_name])) {
                    $field_label = $field_config['description'] ?? $field_name;
                    throw new Exception(FormMessages::getInvalidEmailMessage($field_label));
                }
            }
        }
    }

    /**
     * Send form data to CleverReach if enabled
     *
     * @param int $form_id
     * @param array $form_data
     * @param array $template
     * @return array Status array with 'success' (bool), 'error' (string|null), 'message' (string)
     */
    private function sendToCleverReach(int $form_id, array $form_data, array $template): array
    {
        // Note: Handler type already checked in handleSubmission() before calling this method
        try {
            $cr_service = CleverReachSubmissionService::getInstance();
            $result = $cr_service->submitFormToCleverReach($form_id, $form_data, $template);

            if ($result['success']) {
                // Apply filter to allow additional custom processing
                do_action('lexo-forms/cr/after-submission', $form_id, $form_data, $template);

                return [
                    'success' => true,
                    'error' => null,
                    'message' => 'Successfully submitted to CleverReach',
                    'skipped' => false,
                    'already_exists' => false
                ];
            } else {
                // Check if recipient already exists
                if ($result['already_exists']) {
                    return [
                        'success' => false,
                        'error' => FormMessages::getAlreadySubscribedMessage(),
                        'message' => 'Recipient already activated',
                        'skipped' => false,
                        'already_exists' => true
                    ];
                }

                // Other errors
                $error_message = $result['error'] ?? 'CleverReach submission failed';
                Logger::formError($error_message, $form_id);

                return [
                    'success' => false,
                    'error' => $error_message,
                    'message' => $error_message,
                    'skipped' => false,
                    'already_exists' => false
                ];
            }

        } catch (Exception $e) {
            $error_message = 'CleverReach submission exception: ' . $e->getMessage();
            Logger::formError($error_message, $form_id);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => $error_message,
                'skipped' => false,
                'already_exists' => false
            ];
        }
    }

    /**
     * Send confirmation email to visitor if enabled
     *
     * @param int $form_id
     * @param array $form_data
     * @param array $template
     * @param array $email_settings Email settings group
     * @return void
     */
    private function sendConfirmationEmail(int $form_id, array $form_data, array $template, array $email_settings): void
    {
        // Check if confirmation email is enabled
        $enable_confirmation = $email_settings[FIELD_PREFIX . 'enable_additional_email'] ?? false;
        if (!$enable_confirmation) {
            return;
        }

        try {
            // Get visitor's email from form data
            $visitor_email = $this->getVisitorEmail($form_data, $template);
            if (empty($visitor_email)) {
                Logger::emailError('Cannot send confirmation email - no visitor email found', $form_id);
                return;
            }

            // Get confirmation email settings with proper fallback hierarchy
            $subject = $this->getEmailSubject($form_id, $email_settings);
            $sender_email = $this->getEmailSender($form_id, $email_settings);
            $sender_name = $this->getEmailSenderName($form_id, $email_settings);
            $email_body = $this->getEmailBody($form_id, $email_settings);
            $attachment = $email_settings[FIELD_PREFIX . 'additional_email_document'] ?? null;

            // Replace placeholders in subject and body
            $subject = $this->replacePlaceholders($subject, $form_data);
            $email_body = $this->replacePlaceholders($email_body, $form_data);

            // Prepare attachment if exists
            $attachments = [];
            if (!empty($attachment) && is_array($attachment) && !empty($attachment['url'])) {
                $file_path = get_attached_file($attachment['ID']);
                if ($file_path && file_exists($file_path)) {
                    $attachments[] = $file_path;
                }
            }

            // Send confirmation email using email service
            $result = $this->email_service->sendConfirmationEmail(
                $visitor_email,
                $subject,
                $email_body,
                $sender_email,
                $sender_name,
                $attachments
            );

            if (!$result) {
                Logger::emailError('Failed to send confirmation email to ' . $visitor_email, $form_id);
            }

        } catch (Exception $e) {
            Logger::emailError('Confirmation email exception: ' . $e->getMessage(), $form_id);
        }
    }

    /**
     * Get email subject with proper fallback hierarchy
     * 1. Confirmation email subject
     * 2. Form email subject
     * 3. Filter fallback
     *
     * @param int $form_id
     * @param array $email_settings Email settings group
     * @return string
     */
    private function getEmailSubject(int $form_id, array $email_settings): string
    {
        // Priority 1: Confirmation email subject
        $subject = $email_settings[FIELD_PREFIX . 'additional_email_subject'] ?? '';
        if (!empty($subject)) {
            return $subject;
        }

        // Priority 2: Form email subject
        $subject = $email_settings[FIELD_PREFIX . 'email_subject'] ?? '';
        if (!empty($subject)) {
            return $subject;
        }

        // Priority 3: Filter fallback
        return apply_filters('lexo-forms/cr/email/confirmation/subject', FormMessages::getConfirmationEmailSubject(), $form_id);
    }

    /**
     * Get email sender with proper fallback hierarchy
     * 1. Confirmation email sender
     * 2. Form sender email
     * 3. Filter fallback
     *
     * @param int $form_id
     * @param array $email_settings Email settings group
     * @return string
     */
    private function getEmailSender(int $form_id, array $email_settings): string
    {
        // Priority 1: Confirmation email sender
        $sender_email = $email_settings[FIELD_PREFIX . 'additional_sender_email'] ?? '';
        if (!empty($sender_email)) {
            return $sender_email;
        }

        // Priority 2: Form sender email
        $sender_email = $email_settings[FIELD_PREFIX . 'sender_email'] ?? '';
        if (!empty($sender_email)) {
            return $sender_email;
        }

        // Priority 3: Filter fallback
        return apply_filters('lexo-forms/cr/email/confirmation/sender', EMAIL_FROM_EMAIL, $form_id);
    }

    /**
     * Get email sender name with proper fallback hierarchy
     * 1. Confirmation email sender name
     * 2. Form sender name
     * 3. Filter fallback
     *
     * @param int $form_id
     * @param array $email_settings Email settings group
     * @return string
     */
    private function getEmailSenderName(int $form_id, array $email_settings): string
    {
        // Priority 1: Confirmation email sender name
        $sender_name = $email_settings[FIELD_PREFIX . 'additional_sender_name'] ?? '';
        if (!empty($sender_name)) {
            return $sender_name;
        }

        // Priority 2: Form sender name
        $sender_name = $email_settings[FIELD_PREFIX . 'sender_name'] ?? '';
        if (!empty($sender_name)) {
            return $sender_name;
        }

        // Priority 3: Filter fallback
        return apply_filters('lexo-forms/cr/email/confirmation/sender-name', EMAIL_FROM_NAME, $form_id);
    }

    /**
     * Get email body with proper fallback hierarchy
     * 1. Confirmation email body
     * 2. Form email body (if applicable)
     * 3. Filter fallback
     *
     * @param int $form_id
     * @param array $email_settings Email settings group
     * @return string
     */
    private function getEmailBody(int $form_id, array $email_settings): string
    {
        // Priority 1: Confirmation email body
        $email_body = $email_settings[FIELD_PREFIX . 'additional_email_body'] ?? '';
        if (!empty($email_body)) {
            return $email_body;
        }

        // Priority 2: Form email body (if form has email body field)
        $email_body = $email_settings[FIELD_PREFIX . 'email_body'] ?? '';
        if (!empty($email_body)) {
            return $email_body;
        }

        // Priority 3: Filter fallback
        $default_body = '';
        return apply_filters('lexo-forms/cr/email/confirmation/body', $default_body, $form_id);
    }

    /**
     * Get visitor's email from form data
     *
     * @param array $form_data
     * @param array $template
     * @return string
     */
    private function getVisitorEmail(array $form_data, array $template): string
    {
        // Look for email field in form data
        $email_fields = ['email', 'e-mail', 'emailaddress', 'email_address'];

        foreach ($email_fields as $field_name) {
            if (!empty($form_data[$field_name]) && is_email($form_data[$field_name])) {
                return $form_data[$field_name];
            }
        }

        // Check template fields for email type
        foreach ($template['fields'] ?? [] as $field_config) {
            if ($field_config['type'] === 'email' && !empty($form_data[$field_config['name']])) {
                return $form_data[$field_config['name']];
            }
        }

        return '';
    }

    /**
     * Replace placeholders in text with form data
     *
     * @param string $text
     * @param array $form_data
     * @return string
     */
    private function replacePlaceholders(string $text, array $form_data): string
    {
        foreach ($form_data as $field_name => $value) {
            $text = str_replace('{' . $field_name . '}', $value, $text);
        }

        return $text;
    }
}
