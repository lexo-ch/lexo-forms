<?php

namespace LEXO\LF\Core\Utils;
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
     * Get "form submitted from" message for email body
     *
     * @param string $page Page URL/name where form was submitted
     * @return string
     */
    public static function getFormSubmittedFromMessage(string $page): string
    {
        $message = self::translateWithFormLocale(function () use ($page) {
            return sprintf(__('Form submitted from: %s', 'lexoforms'), $page);
        });

        return apply_filters('lexo-forms/forms/messages/form-submitted-from', $message, $page);
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

    // ========================================================================
    // FAILURE NOTIFICATION MESSAGES (Admin Emails)
    // ========================================================================

    /**
     * Get subject for complete failure notification email
     *
     * @param string $site_name Site name
     * @param string $form_title Form title
     * @return string
     */
    public static function getFailureNotificationSubject(string $site_name, string $form_title): string
    {
        $message = self::translateWithSiteLocale(function () use ($site_name, $form_title) {
            return sprintf(__('[%s] CleverReach Submission Failed - %s', 'lexoforms'), $site_name, $form_title);
        });

        return apply_filters('lexo-forms/cr/email/failure/subject', $message, $site_name, $form_title);
    }

    /**
     * Get heading for failure notification email
     *
     * @return string
     */
    public static function getFailureNotificationHeading(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('CleverReach Submission Failed', 'lexoforms');
        });

        return apply_filters('lexo-forms/cr/email/failure/heading', $message);
    }

    /**
     * Get error label for failure notification email
     *
     * @return string
     */
    public static function getFailureNotificationErrorLabel(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('Error:', 'lexoforms');
        });

        return apply_filters('lexo-forms/cr/email/failure/error-label', $message);
    }

    /**
     * Get footer message for failure notification email
     *
     * @return string
     */
    public static function getFailureNotificationFooter(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('This email was sent because CleverReach integration failed. Please review the error and try to resubmit the data manually if needed.', 'lexoforms');
        });

        return apply_filters('lexo-forms/cr/email/failure/footer', $message);
    }

    /**
     * Get subject for partial failure notification email
     *
     * @param string $site_name Site name
     * @param string $form_title Form title
     * @param string $failed_label Failed system label (CleverReach or Email)
     * @return string
     */
    public static function getPartialFailureNotificationSubject(string $site_name, string $form_title, string $failed_label): string
    {
        $message = self::translateWithSiteLocale(function () use ($site_name, $form_title, $failed_label) {
            return sprintf(__('[%s] Partial Form Submission Failure - %s (%s Failed)', 'lexoforms'), $site_name, $form_title, $failed_label);
        });

        return apply_filters('lexo-forms/cr/email/partial-failure/subject', $message, $site_name, $form_title, $failed_label);
    }

    /**
     * Get heading for partial failure notification email
     *
     * @return string
     */
    public static function getPartialFailureNotificationHeading(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('Partial Form Submission Failure', 'lexoforms');
        });

        return apply_filters('lexo-forms/cr/email/partial-failure/heading', $message);
    }

    /**
     * Get "one system failed" label
     *
     * @return string
     */
    public static function getPartialFailureOneSystemFailedLabel(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('One system failed:', 'lexoforms');
        });

        return apply_filters('lexo-forms/cr/email/partial-failure/one-system-failed-label', $message);
    }

    /**
     * Get CleverReach failed but email succeeded message
     *
     * @return string
     */
    public static function getPartialFailureCleverReachFailedMessage(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('CleverReach integration failed, but email was sent successfully.', 'lexoforms');
        });

        return apply_filters('lexo-forms/cr/email/partial-failure/cr-failed-message', $message);
    }

    /**
     * Get email failed but CleverReach succeeded message
     *
     * @return string
     */
    public static function getPartialFailureEmailFailedMessage(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('Email notification failed, but CleverReach integration succeeded.', 'lexoforms');
        });

        return apply_filters('lexo-forms/cr/email/partial-failure/email-failed-message', $message);
    }

    /**
     * Get CleverReach failed footer message
     *
     * @return string
     */
    public static function getPartialFailureCleverReachFailedFooter(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('The form submission was successful from the user\'s perspective (email was sent), but the CleverReach integration failed. You may need to manually add this contact to CleverReach.', 'lexoforms');
        });

        return apply_filters('lexo-forms/cr/email/partial-failure/cr-failed-footer', $message);
    }

    /**
     * Get email failed footer message
     *
     * @return string
     */
    public static function getPartialFailureEmailFailedFooter(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('The form submission was successful from the user\'s perspective (added to CleverReach), but the email notification failed. You may not have received the standard notification email.', 'lexoforms');
        });

        return apply_filters('lexo-forms/cr/email/partial-failure/email-failed-footer', $message);
    }

    /**
     * Get "Form Information" heading
     *
     * @return string
     */
    public static function getFormInformationHeading(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('Form Information', 'lexoforms');
        });

        return apply_filters('lexo-forms/cr/email/form-information-heading', $message);
    }

    /**
     * Get "Submitted Data" heading
     *
     * @return string
     */
    public static function getSubmittedDataHeading(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('Submitted Data', 'lexoforms');
        });

        return apply_filters('lexo-forms/cr/email/submitted-data-heading', $message);
    }

    /**
     * Get "Form:" label
     *
     * @return string
     */
    public static function getFormLabel(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('Form:', 'lexoforms');
        });

        return apply_filters('lexo-forms/cr/email/form-label', $message);
    }

    /**
     * Get "Form ID:" label
     *
     * @return string
     */
    public static function getFormIdLabel(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('Form ID:', 'lexoforms');
        });

        return apply_filters('lexo-forms/cr/email/form-id-label', $message);
    }

    /**
     * Get "Date:" label
     *
     * @return string
     */
    public static function getDateLabel(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('Date:', 'lexoforms');
        });

        return apply_filters('lexo-forms/cr/email/date-label', $message);
    }

    /**
     * Get "Field" table header
     *
     * @return string
     */
    public static function getFieldTableHeader(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('Field', 'lexoforms');
        });

        return apply_filters('lexo-forms/cr/email/field-header', $message);
    }

    /**
     * Get "Value" table header
     *
     * @return string
     */
    public static function getValueTableHeader(): string
    {
        $message = self::translateWithSiteLocale(function () {
            return __('Value', 'lexoforms');
        });

        return apply_filters('lexo-forms/cr/email/value-header', $message);
    }

    /**
     * Translate using form/session locale (front-end messages).
     *
     * Gets the language from the current form session, validates it,
     * and maps it to a WordPress locale for translation.
     *
     * Edge cases handled:
     * - Filter returns non-string value: falls back to session language
     * - Language code is empty: falls back to 'de'
     * - Language code not in map: returns null, uses current WP locale
     *
     * @param callable():string $callback Translation callback
     * @return string Translated message
     */
    protected static function translateWithFormLocale(callable $callback): string
    {
        $language = FormHelpers::getLanguage();

        // Validate that filter didn't return invalid value
        if (!is_string($language) || empty($language)) {
            $language = isset($_SESSION['jez']) && is_string($_SESSION['jez']) ? $_SESSION['jez'] : 'de';
        }

        $locale = self::mapLanguageToLocale($language);

        return self::translateWithLocale($callback, $locale);
    }

    /**
     * Translate using site locale (admin/system messages).
     *
     * Uses the WordPress site locale (from get_locale() or WPLANG option)
     * for translating admin notifications and system error messages.
     *
     * This ensures that system messages are displayed in the admin's language,
     * regardless of the front-end user's language preference.
     *
     * Edge cases handled:
     * - Site locale cannot be determined: uses current WordPress locale
     * - Locale switch fails: logs error and continues with current locale
     *
     * @param callable():string $callback Translation callback that returns translated string
     * @return string Translated message in site locale
     */
    protected static function translateWithSiteLocale(callable $callback): string
    {
        return self::translateWithLocale($callback, self::getSiteLocale());
    }

    /**
     * Map session language codes to WordPress locale codes.
     *
     * Supports common language codes and provides fallback logic for unmapped languages.
     * If an exact match is not found, attempts to find a partial match (e.g., "en" from "en_GB").
     *
     * @param string $language Language code (e.g., "de", "en", "sr", "en_GB")
     * @return string|null WordPress locale code or null if no match found
     */
    protected static function mapLanguageToLocale(string $language): ?string
    {
        $map = [
            // German
            'de' => 'de_DE',
            'de_de' => 'de_DE',
            'de_ch' => 'de_CH',
            'de_at' => 'de_AT',

            // English
            'en' => 'en_US',
            'en_us' => 'en_US',
            'en_gb' => 'en_GB',
            'en_ca' => 'en_CA',
            'en_au' => 'en_AU',
            'usca' => 'en_US', // Legacy support

            // French
            'fr' => 'fr_FR',
            'fr_fr' => 'fr_FR',
            'fr_ca' => 'fr_CA',
            'fr_be' => 'fr_BE',

            // Italian
            'it' => 'it_IT',
            'it_it' => 'it_IT',

            // Spanish
            'es' => 'es_ES',
            'es_es' => 'es_ES',
            'es_mx' => 'es_MX',
            'es_ar' => 'es_AR',

            // Serbian
            'sr' => 'sr_RS',
            'sr_rs' => 'sr_RS',

            // Croatian
            'hr' => 'hr',
            'hr_hr' => 'hr',

            // Other common languages
            'nl' => 'nl_NL',
            'pt' => 'pt_PT',
            'pt_br' => 'pt_BR',
            'pl' => 'pl_PL',
            'ru' => 'ru_RU',
            'ja' => 'ja',
            'zh' => 'zh_CN',
        ];

        $key = strtolower($language);

        // Exact match
        if (isset($map[$key])) {
            return $map[$key];
        }

        // Fallback: Try to extract base language code (e.g., "en" from "en-GB" or "en_GB")
        $base_lang = strtok($key, '_-');
        if ($base_lang && isset($map[$base_lang])) {
            return $map[$base_lang];
        }

        // If still no match, return null (will use current WordPress locale)
        return null;
    }

    /**
     * Resolve site locale for admin and system messages.
     *
     * Uses get_locale() as primary source (recommended for WordPress 4.0+),
     * with WPLANG as fallback for legacy installations.
     *
     * Edge cases handled:
     * - get_locale() doesn't exist: falls back to WPLANG option
     * - Both sources empty: returns null (uses current locale)
     * - get_locale() returns empty string: tries WPLANG
     *
     * @return string|null WordPress locale code (e.g., 'de_DE') or null
     */
    protected static function getSiteLocale(): ?string
    {
        // Primary: Use get_locale() which always returns the active locale
        if (function_exists('get_locale')) {
            $locale = get_locale();
            if (!empty($locale)) {
                return $locale;
            }
        }

        // Fallback: WPLANG option (legacy WordPress < 4.0)
        $site_locale = get_option('WPLANG');
        if (!empty($site_locale)) {
            return $site_locale;
        }

        return null;
    }

    /**
     * Run translation callback within a specific locale context.
     *
     * Temporarily switches WordPress to the specified locale for translation,
     * then restores the previous locale. Logs errors if locale switching fails.
     *
     * Edge cases handled:
     * - Locale is null: skips switching, uses current locale
     * - switch_to_locale() doesn't exist: skips switching (WP < 4.7)
     * - switch_to_locale() returns false: logs error, continues with current locale
     * - Callback throws exception: still restores locale before re-throwing
     *
     * @param callable():string $callback Translation callback that returns translated string
     * @param string|null $locale WordPress locale code (e.g., 'de_DE', 'en_US')
     * @return string Translated message
     */
    protected static function translateWithLocale(callable $callback, ?string $locale): string
    {
        $switched = false;

        if ($locale && function_exists('switch_to_locale')) {
            $switched = switch_to_locale($locale);

            // Log if locale switch failed (locale might not be installed)
            if (!$switched && WP_DEBUG) {
                error_log(sprintf(
                    '[LEXO Forms] Failed to switch to locale "%s". The locale may not be installed. Using current locale instead.',
                    $locale
                ));
            }
        }

        try {
            $result = $callback();
        } finally {
            // Always restore locale, even if callback throws exception
            if ($switched) {
                restore_previous_locale();
            }
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
