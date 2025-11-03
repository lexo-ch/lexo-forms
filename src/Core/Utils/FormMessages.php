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
 * - lexo-forms/forms/messages/email-fail
 * - lexo-forms/forms/messages/captcha-fail
 * - lexo-forms/forms/messages/invalid-email (receives $field_label as second parameter)
 * - lexo-forms/forms/messages/confirmation-email-subject
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
        $message = self::translateWithFormLocale(function () {
            return __('Your message has been sent successfully. Thank you!', 'lexoforms');
        });

        return apply_filters('lexo-forms/forms/messages/success', $message);
    }

    /**
     * Get email sending failure message
     *
     * @return string
     */
    public static function getEmailFailMessage(): string
    {
        $message = self::translateWithFormLocale(function () {
            return __('Failed to send email', 'lexoforms');
        });

        return apply_filters('lexo-forms/forms/messages/email-fail', $message);
    }

    /**
     * Get captcha validation failure message
     *
     * @return string
     */
    public static function getCaptchaFailMessage(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('Captcha validation failed. Please try again.', 'lexoforms');
        });

        return apply_filters('lexo-forms/forms/messages/captcha-fail', $message);
    }

    /**
     * Get invalid email format message
     *
     * @param string $field_label Field label or name
     * @return string
     */
    public static function getInvalidEmailMessage(string $field_label): string
    {
        $message = self::translateWithSiteLocale(function () use ($field_label) {
            return sprintf(__('Invalid email format in field: %s', 'lexoforms'), $field_label);
        });

        return apply_filters('lexo-forms/forms/messages/invalid-email', $message, $field_label);
    }

    /**
     * Get default confirmation email subject
     *
     * @return string
     */
    public static function getConfirmationEmailSubject(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('Thank you for your message', 'lexoforms');
        });

        return apply_filters('lexo-forms/forms/messages/confirmation-email-subject', $message);
    }

    /**
     * Get form ID required error message
     *
     * @return string
     */
    public static function getFormIdRequiredError(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('Error: Form ID is required.', 'lexoforms');
        });

        return apply_filters('lexo-forms/forms/errors/form-id-required', $message);
    }

    /**
     * Get form not found error message
     *
     * @return string
     */
    public static function getFormNotFoundError(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('Error: Form not found.', 'lexoforms');
        });

        return apply_filters('lexo-forms/forms/errors/form-not-found', $message);
    }

    /**
     * Get form template not configured error message
     *
     * @return string
     */
    public static function getFormTemplateNotConfiguredError(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('Error: Form template not configured.', 'lexoforms');
        });

        return apply_filters('lexo-forms/forms/errors/template-not-configured', $message);
    }

    /**
     * Get template not found error message
     *
     * @return string
     */
    public static function getTemplateNotFoundError(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('Error: Template not found.', 'lexoforms');
        });

        return apply_filters('lexo-forms/forms/errors/template-not-found', $message);
    }

    /**
     * Get no fields configured error message
     *
     * @return string
     */
    public static function getNoFieldsConfiguredError(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('No fields configured for this form', 'lexoforms');
        });

        return apply_filters('lexo-forms/forms/errors/no-fields-configured', $message);
    }

    /**
     * Get required fields missing error message
     *
     * @param string $field_list Comma-separated list of missing field names
     * @return string
     */
    public static function getRequiredFieldsMissingError(string $field_list): string
    {
        $message = self::translateWithSiteLocale(function () use ($field_list) {
            return sprintf(__('Required fields are missing: %s', 'lexoforms'), $field_list);
        });

        return apply_filters('lexo-forms/forms/errors/required-fields-missing', $message, $field_list);
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
        $message = self::translateWithSiteLocale(function () {
            return __('We encountered an issue submitting your information. Our team has been notified and will contact you shortly.', 'lexoforms');
        });

        return apply_filters('lexo-forms/cr/messages/error', $message);
    }

    /**
     * Get already subscribed message
     *
     * @return string
     */
    public static function getAlreadySubscribedMessage(): string
    {
        $message = self::translateWithFormLocale(function () {
            return __('This email address is already subscribed.', 'lexoforms');
        });

        return apply_filters('lexo-forms/cr/messages/already-subscribed', $message);
    }

    /**
     * Get CleverReach form creation failed error message
     *
     * @return string
     */
    public static function getCRFormCreationFailedError(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('Failed to determine or create form', 'lexoforms');
        });

        return apply_filters('lexo-forms/cr/errors/form-creation-failed', $message);
    }

    /**
     * Get CleverReach group ID retrieval failed error message
     *
     * @return string
     */
    public static function getCRGroupIdFailedError(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('Failed to get group ID from form', 'lexoforms');
        });

        return apply_filters('lexo-forms/cr/errors/group-id-failed', $message);
    }

    /**
     * Translate using form/session locale (front-end messages).
     *
     * @param callable():string $callback
     * @return string
     */
    protected static function translateWithFormLocale(callable $callback): string
    {
        return self::translateWithLocale($callback, self::mapLanguageToLocale(FormHelpers::getLanguage()));
    }

    /**
     * Translate using site locale (admin/system messages).
     *
     * @param callable():string $callback
     * @return string
     */
    protected static function translateWithSiteLocale(callable $callback): string
    {
        return self::translateWithLocale($callback, self::getSiteLocale());
    }

    /**
     * Map session language codes to WordPress locale codes.
     *
     * @param string $language
     * @return string|null
     */
    protected static function mapLanguageToLocale(string $language): ?string
    {
        $map = [
            'de' => 'de_DE',
            'en' => 'en_US',
            'usca' => 'en_US',
            'fr' => 'fr_FR',
            'it' => 'it_IT',
        ];

        $key = strtolower($language);

        return $map[$key] ?? null;
    }

    /**
     * Resolve site locale, preferring explicit site language setting.
     *
     * @return string|null
     */
    protected static function getSiteLocale(): ?string
    {
        $site_locale = get_option('WPLANG');

        if (!empty($site_locale)) {
            return $site_locale;
        }

        return function_exists('get_locale') ? get_locale() : null;
    }

    /**
     * Run translation callback within a specific locale context.
     *
     * @param callable():string $callback
     * @param string|null $locale
     * @return string
     */
    protected static function translateWithLocale(callable $callback, ?string $locale): string
    {
        $switched = false;

        if ($locale && function_exists('switch_to_locale')) {
            $switched = switch_to_locale($locale);
        }

        $result = $callback();

        if ($switched) {
            restore_previous_locale();
        }

        return $result;
    }

    /**
     * Get all default messages as array (for reference or export).
     *
     * @return array
     */
    public static function getAllMessages(): array
    {
        return [
            'general' => [
                'success' => self::getSuccessMessage(),
                'email_fail' => self::getEmailFailMessage(),
                'captcha_fail' => self::getCaptchaFailMessage(),
                'invalid_email' => self::getInvalidEmailMessage('example'),
                'confirmation_email_subject' => self::getConfirmationEmailSubject(),
                'form_id_required' => self::getFormIdRequiredError(),
                'form_not_found' => self::getFormNotFoundError(),
                'form_template_not_configured' => self::getFormTemplateNotConfiguredError(),
                'template_not_found' => self::getTemplateNotFoundError(),
                'no_fields_configured' => self::getNoFieldsConfiguredError(),
                'required_fields_missing' => self::getRequiredFieldsMissingError('example_field'),
            ],
            'cleverreach' => [
                'cleverreach_error' => self::getCleverReachErrorMessage(),
                'already_subscribed' => self::getAlreadySubscribedMessage(),
                'cr_form_creation_failed' => self::getCRFormCreationFailedError(),
                'cr_group_id_failed' => self::getCRGroupIdFailedError(),
            ],
        ];
    }
}
