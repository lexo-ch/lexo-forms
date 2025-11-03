<?php

namespace LEXO\LF\Core\Utils;

/**
 * Form Messages
 *
 * Centralized location for all default form messages.
 * Messages can be overridden per-form via ACF fields or via WordPress filters.
 *
 * Available Filters (General Form Messages):
 * - lexo-forms/forms/messages/success
 * - lexo-forms/forms/messages/fail
 * - lexo-forms/forms/messages/email-fail
 * - lexo-forms/forms/messages/captcha-fail
 * - lexo-forms/forms/messages/invalid-email (receives $field_label as second parameter)
 * - lexo-forms/forms/messages/confirmation-email-subject
 * - lexo-forms/forms/messages/validation-fail
 * - lexo-forms/forms/errors/form-id-required
 * - lexo-forms/forms/errors/form-not-found
 * - lexo-forms/forms/errors/template-not-configured
 * - lexo-forms/forms/errors/template-not-found
 * - lexo-forms/forms/errors/no-fields-configured
 * - lexo-forms/forms/errors/required-fields-missing (receives $field_list as second parameter)
 *
 * Available Filters (CleverReach Integration):
 * - lexo-forms/cr/messages/error
 * - lexo-forms/cr/messages/already-subscribed
 * - lexo-forms/cr/errors/form-creation-failed
 * - lexo-forms/cr/errors/group-id-failed
 *
 * Example Usage:
 * ```php
 * add_filter('lexo-forms/forms/messages/success', function($message) {
 *     return 'Custom success message!';
 * });
 * ```
 *
 * @package LEXO\LF\Core\Utils
 * @since 1.0.0
 */
class FormMessages
{
    // ========================================================================
    // GENERAL FORM MESSAGES (Non-CleverReach)
    // ========================================================================

    /**
     * Get default success message
     *
     * @return string
     */
    public static function getSuccessMessage(): string
    {
        return apply_filters(
            'lexo-forms/forms/messages/success',
            __('Your message has been sent successfully. Thank you!', 'lexoforms')
        );
    }

    /**
     * Get default fail message
     *
     * @return string
     */
    public static function getFailMessage(): string
    {
        return apply_filters(
            'lexo-forms/forms/messages/fail',
            __('Sorry, there was an error sending your message. Please try again.', 'lexoforms')
        );
    }

    /**
     * Get email sending failure message
     *
     * @return string
     */
    public static function getEmailFailMessage(): string
    {
        return apply_filters(
            'lexo-forms/forms/messages/email-fail',
            __('Failed to send email', 'lexoforms')
        );
    }

    /**
     * Get captcha validation failure message
     *
     * @return string
     */
    public static function getCaptchaFailMessage(): string
    {
        return apply_filters(
            'lexo-forms/forms/messages/captcha-fail',
            __('Captcha validation failed. Please try again.', 'lexoforms')
        );
    }

    /**
     * Get invalid email format message
     *
     * @param string $field_label Field label or name
     * @return string
     */
    public static function getInvalidEmailMessage(string $field_label): string
    {
        return apply_filters(
            'lexo-forms/forms/messages/invalid-email',
            sprintf(__('Invalid email format in field: %s', 'lexoforms'), $field_label),
            $field_label
        );
    }

    /**
     * Get default confirmation email subject
     *
     * @return string
     */
    public static function getConfirmationEmailSubject(): string
    {
        return apply_filters(
            'lexo-forms/forms/messages/confirmation-email-subject',
            __('Thank you for your message', 'lexoforms')
        );
    }

    /**
     * Get validation failure message
     *
     * @return string
     */
    public static function getValidationFailMessage(): string
    {
        return apply_filters(
            'lexo-forms/forms/messages/validation-fail',
            __('Form submission failed due to validation. Please try again.', 'lexoforms')
        );
    }

    /**
     * Get form ID required error message
     *
     * @return string
     */
    public static function getFormIdRequiredError(): string
    {
        return apply_filters(
            'lexo-forms/forms/errors/form-id-required',
            __('Error: Form ID is required.', 'lexoforms')
        );
    }

    /**
     * Get form not found error message
     *
     * @return string
     */
    public static function getFormNotFoundError(): string
    {
        return apply_filters(
            'lexo-forms/forms/errors/form-not-found',
            __('Error: Form not found.', 'lexoforms')
        );
    }

    /**
     * Get form template not configured error message
     *
     * @return string
     */
    public static function getFormTemplateNotConfiguredError(): string
    {
        return apply_filters(
            'lexo-forms/forms/errors/template-not-configured',
            __('Error: Form template not configured.', 'lexoforms')
        );
    }

    /**
     * Get template not found error message
     *
     * @return string
     */
    public static function getTemplateNotFoundError(): string
    {
        return apply_filters(
            'lexo-forms/forms/errors/template-not-found',
            __('Error: Template not found.', 'lexoforms')
        );
    }

    /**
     * Get no fields configured error message
     *
     * @return string
     */
    public static function getNoFieldsConfiguredError(): string
    {
        return apply_filters(
            'lexo-forms/forms/errors/no-fields-configured',
            __('No fields configured for this form', 'lexoforms')
        );
    }

    /**
     * Get required fields missing error message
     *
     * @param string $field_list Comma-separated list of missing field names
     * @return string
     */
    public static function getRequiredFieldsMissingError(string $field_list): string
    {
        return apply_filters(
            'lexo-forms/forms/errors/required-fields-missing',
            sprintf(__('Required fields are missing: %s', 'lexoforms'), $field_list),
            $field_list
        );
    }

    // ========================================================================
    // CLEVERREACH INTEGRATION MESSAGES
    // ========================================================================

    /**
     * Get CleverReach submission error message
     *
     * @return string
     */
    public static function getCleverReachErrorMessage(): string
    {
        return apply_filters(
            'lexo-forms/cr/messages/error',
            __('We encountered an issue submitting your information. Our team has been notified and will contact you shortly.', 'lexoforms')
        );
    }

    /**
     * Get already subscribed message
     *
     * @return string
     */
    public static function getAlreadySubscribedMessage(): string
    {
        return apply_filters(
            'lexo-forms/cr/messages/already-subscribed',
            __('This email address is already subscribed.', 'lexoforms')
        );
    }

    /**
     * Get CleverReach form creation failed error message
     *
     * @return string
     */
    public static function getCRFormCreationFailedError(): string
    {
        return apply_filters(
            'lexo-forms/cr/errors/form-creation-failed',
            __('Failed to determine or create form', 'lexoforms')
        );
    }

    /**
     * Get CleverReach group ID retrieval failed error message
     *
     * @return string
     */
    public static function getCRGroupIdFailedError(): string
    {
        return apply_filters(
            'lexo-forms/cr/errors/group-id-failed',
            __('Failed to get group ID from form', 'lexoforms')
        );
    }

    // ========================================================================
    // HELPER METHODS
    // ========================================================================

    /**
     * Get all default messages as array (for reference or export)
     *
     * @return array
     */
    public static function getAllMessages(): array
    {
        return [
            // General Form Messages
            'general' => [
                'success' => self::getSuccessMessage(),
                'fail' => self::getFailMessage(),
                'email_fail' => self::getEmailFailMessage(),
                'captcha_fail' => self::getCaptchaFailMessage(),
                'confirmation_email_subject' => self::getConfirmationEmailSubject(),
                'validation_fail' => self::getValidationFailMessage(),
                'form_id_required' => self::getFormIdRequiredError(),
                'form_not_found' => self::getFormNotFoundError(),
                'form_template_not_configured' => self::getFormTemplateNotConfiguredError(),
                'template_not_found' => self::getTemplateNotFoundError(),
                'no_fields_configured' => self::getNoFieldsConfiguredError(),
                'required_fields_missing' => self::getRequiredFieldsMissingError('example_field'),
            ],

            // CleverReach Integration Messages
            'cleverreach' => [
                'cleverreach_error' => self::getCleverReachErrorMessage(),
                'already_subscribed' => self::getAlreadySubscribedMessage(),
                'cr_form_creation_failed' => self::getCRFormCreationFailedError(),
                'cr_group_id_failed' => self::getCRGroupIdFailedError(),
            ],
        ];
    }
}
