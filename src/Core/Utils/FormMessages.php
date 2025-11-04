<?php

namespace LEXO\LF\Core\Utils;
class FormMessages
{
    /**
     * Front-end messages in multiple languages
     * These are shown to visitors and must not use __() to avoid locale switching
     */
    protected static $frontend_messages = [
        'success' => [
            'de' => 'Ihre Nachricht wurde erfolgreich gesendet. Vielen Dank!',
            'fr' => 'Votre message a été envoyé avec succès. Merci!',
            'it' => 'Il tuo messaggio è stato inviato con successo. Grazie!',
            'en' => 'Your message has been sent successfully. Thank you!'
        ],
        'email_fail' => [
            'de' => 'E-Mail konnte nicht gesendet werden',
            'fr' => 'Échec de l\'envoi de l\'e-mail',
            'it' => 'Impossibile inviare l\'e-mail',
            'en' => 'Failed to send email'
        ],
        'captcha_fail' => [
            'de' => 'Captcha-Validierung fehlgeschlagen. Bitte versuchen Sie es erneut.',
            'fr' => 'La validation du captcha a échoué. Veuillez réessayer.',
            'it' => 'Convalida captcha non riuscita. Per favore riprova.',
            'en' => 'Captcha validation failed. Please try again.'
        ],
        'confirmation_email_subject' => [
            'de' => 'Vielen Dank für Ihre Nachricht',
            'fr' => 'Merci pour votre message',
            'it' => 'Grazie per il tuo messaggio',
            'en' => 'Thank you for your message'
        ],
        'cleverreach_error' => [
            'de' => 'Bei der Übermittlung Ihrer Informationen ist ein Problem aufgetreten. Unser Team wurde benachrichtigt und wird sich in Kürze bei Ihnen melden.',
            'fr' => 'Nous avons rencontré un problème lors de la soumission de vos informations. Notre équipe a été informée et vous contactera sous peu.',
            'it' => 'Si è verificato un problema durante l\'invio delle tue informazioni. Il nostro team è stato informato e ti contatterà a breve.',
            'en' => 'We encountered an issue submitting your information. Our team has been notified and will contact you shortly.'
        ],
        'already_subscribed' => [
            'de' => 'Diese E-Mail-Adresse ist bereits angemeldet.',
            'fr' => 'Cette adresse e-mail est déjà inscrite.',
            'it' => 'Questo indirizzo e-mail è già registrato.',
            'en' => 'This email address is already subscribed.'
        ],
    ];

    /**
     * Admin messages in multiple languages
     * These are shown in admin emails and must use site language
     */
    protected static $admin_messages = [
        // Partial failure messages
        'partial_failure_heading' => [
            'de' => 'Teilweiser Fehler bei der Formularübermittlung',
            'fr' => 'Échec partiel de la soumission du formulaire',
            'it' => 'Errore parziale nell\'invio del modulo',
            'en' => 'Partial Form Submission Failure'
        ],
        'one_system_failed' => [
            'de' => 'Ein System ist fehlgeschlagen:',
            'fr' => 'Un système a échoué:',
            'it' => 'Un sistema è fallito:',
            'en' => 'One system failed:'
        ],
        'cr_failed_but_email_ok' => [
            'de' => 'CleverReach-Integration fehlgeschlagen, aber E-Mail wurde erfolgreich gesendet.',
            'fr' => 'L\'intégration CleverReach a échoué, mais l\'e-mail a été envoyé avec succès.',
            'it' => 'L\'integrazione CleverReach è fallita, ma l\'e-mail è stata inviata con successo.',
            'en' => 'CleverReach integration failed, but email was sent successfully.'
        ],
        'email_failed_but_cr_ok' => [
            'de' => 'E-Mail-Benachrichtigung fehlgeschlagen, aber CleverReach-Integration erfolgreich.',
            'fr' => 'La notification par e-mail a échoué, mais l\'intégration CleverReach a réussi.',
            'it' => 'La notifica e-mail è fallita, ma l\'integrazione CleverReach è riuscita.',
            'en' => 'Email notification failed, but CleverReach integration succeeded.'
        ],
        'cr_failed_footer' => [
            'de' => 'Die Formularübermittlung war aus Sicht des Benutzers erfolgreich (E-Mail wurde gesendet), aber die CleverReach-Integration ist fehlgeschlagen. Möglicherweise müssen Sie diesen Kontakt manuell zu CleverReach hinzufügen.',
            'fr' => 'La soumission du formulaire a réussi du point de vue de l\'utilisateur (l\'e-mail a été envoyé), mais l\'intégration CleverReach a échoué. Vous devrez peut-être ajouter ce contact manuellement à CleverReach.',
            'it' => 'L\'invio del modulo è riuscito dal punto di vista dell\'utente (l\'e-mail è stata inviata), ma l\'integrazione CleverReach è fallita. Potrebbe essere necessario aggiungere manualmente questo contatto a CleverReach.',
            'en' => 'The form submission was successful from the user\'s perspective (email was sent), but the CleverReach integration failed. You may need to manually add this contact to CleverReach.'
        ],
        'email_failed_footer' => [
            'de' => 'Die Formularübermittlung war aus Sicht des Benutzers erfolgreich (zu CleverReach hinzugefügt), aber die E-Mail-Benachrichtigung ist fehlgeschlagen. Möglicherweise haben Sie die Standard-Benachrichtigungs-E-Mail nicht erhalten.',
            'fr' => 'La soumission du formulaire a réussi du point de vue de l\'utilisateur (ajouté à CleverReach), mais la notification par e-mail a échoué. Vous n\'avez peut-être pas reçu l\'e-mail de notification standard.',
            'it' => 'L\'invio del modulo è riuscito dal punto di vista dell\'utente (aggiunto a CleverReach), ma la notifica e-mail è fallita. Potresti non aver ricevuto l\'e-mail di notifica standard.',
            'en' => 'The form submission was successful from the user\'s perspective (added to CleverReach), but the email notification failed. You may not have received the standard notification email.'
        ],

        // Complete failure messages
        'failure_heading' => [
            'de' => 'CleverReach-Übermittlung fehlgeschlagen',
            'fr' => 'Échec de la soumission CleverReach',
            'it' => 'Invio CleverReach fallito',
            'en' => 'CleverReach Submission Failed'
        ],
        'failure_footer' => [
            'de' => 'Diese E-Mail wurde gesendet, weil die CleverReach-Integration fehlgeschlagen ist. Bitte überprüfen Sie den Fehler und versuchen Sie gegebenenfalls, die Daten manuell erneut zu übermitteln.',
            'fr' => 'Cet e-mail a été envoyé car l\'intégration CleverReach a échoué. Veuillez vérifier l\'erreur et essayer de soumettre à nouveau les données manuellement si nécessaire.',
            'it' => 'Questa e-mail è stata inviata perché l\'integrazione CleverReach è fallita. Si prega di verificare l\'errore e provare a inviare nuovamente i dati manualmente se necessario.',
            'en' => 'This email was sent because CleverReach integration failed. Please review the error and try to resubmit the data manually if needed.'
        ],

        // Common labels
        'error_label' => [
            'de' => 'Fehler:',
            'fr' => 'Erreur:',
            'it' => 'Errore:',
            'en' => 'Error:'
        ],
        'form_information_heading' => [
            'de' => 'CleverReach-Formularinformationen',
            'fr' => 'Informations sur le formulaire CleverReach',
            'it' => 'Informazioni sul modulo CleverReach',
            'en' => 'CleverReach Form Information'
        ],
        'submitted_data_heading' => [
            'de' => 'Übermittelte Daten',
            'fr' => 'Données soumises',
            'it' => 'Dati inviati',
            'en' => 'Submitted Data'
        ],
        'form_label' => [
            'de' => 'Formular:',
            'fr' => 'Formulaire:',
            'it' => 'Modulo:',
            'en' => 'Form:'
        ],
        'form_id_label' => [
            'de' => 'Formular-ID:',
            'fr' => 'ID du formulaire:',
            'it' => 'ID modulo:',
            'en' => 'Form ID:'
        ],
        'date_label' => [
            'de' => 'Datum:',
            'fr' => 'Date:',
            'it' => 'Data:',
            'en' => 'Date:'
        ],
        'field_header' => [
            'de' => 'Feld',
            'fr' => 'Champ',
            'it' => 'Campo',
            'en' => 'Field'
        ],
        'value_header' => [
            'de' => 'Wert',
            'fr' => 'Valeur',
            'it' => 'Valore',
            'en' => 'Value'
        ],
        'already_subscribed_admin' => [
            'de' => 'Diese E-Mail-Adresse ist bereits angemeldet.',
            'fr' => 'Cette adresse e-mail est déjà inscrite.',
            'it' => 'Questo indirizzo e-mail è già registrato.',
            'en' => 'This email address is already subscribed.'
        ],

        // Subject lines
        'partial_failure_subject' => [
            'de' => '[%s] Teilweiser Fehler bei der Formularübermittlung - %s (%s fehlgeschlagen)',
            'fr' => '[%s] Échec partiel de la soumission du formulaire - %s (%s échoué)',
            'it' => '[%s] Errore parziale nell\'invio del modulo - %s (%s fallito)',
            'en' => '[%s] Partial Form Submission Failure - %s (%s Failed)'
        ],
        'failure_subject' => [
            'de' => '[%s] CleverReach-Übermittlung fehlgeschlagen - %s',
            'fr' => '[%s] Échec de la soumission CleverReach - %s',
            'it' => '[%s] Invio CleverReach fallito - %s',
            'en' => '[%s] CleverReach Submission Failed - %s'
        ],
        'cleverreach_label' => [
            'de' => 'CleverReach',
            'fr' => 'CleverReach',
            'it' => 'CleverReach',
            'en' => 'CleverReach'
        ],
        'email_label' => [
            'de' => 'E-Mail',
            'fr' => 'E-mail',
            'it' => 'E-mail',
            'en' => 'Email'
        ],
        'form_submitted_from' => [
            'de' => 'Formular gesendet von: %s',
            'fr' => 'Formulaire soumis depuis: %s',
            'it' => 'Modulo inviato da: %s',
            'en' => 'Form submitted from: %s'
        ],
    ];

    // ========================================================================
    // HELPER METHODS
    // ========================================================================

    /**
     * Get front-end message in session language (for visitors)
     *
     * @param string $key Message key from $frontend_messages array
     * @return string Translated message
     */
    protected static function getFrontendMessage(string $key): string
    {
        $language = FormHelpers::getLanguage();

        if (!isset(self::$frontend_messages[$key])) {
            return '';
        }

        $messages = self::$frontend_messages[$key];

        // Try session language, fallback to 'de', then first available
        return $messages[$language] ?? $messages['de'] ?? reset($messages);
    }

    /**
     * Get admin message in site language (for admin emails)
     *
     * @param string $key Message key from $admin_messages array
     * @return string Translated message
     */
    protected static function getAdminMessage(string $key): string
    {
        $language = self::getSiteLanguage();

        if (!isset(self::$admin_messages[$key])) {
            return '';
        }

        $messages = self::$admin_messages[$key];

        // Try site language, fallback to 'de', then first available
        return $messages[$language] ?? $messages['de'] ?? reset($messages);
    }

    // ========================================================================
    // GENERAL FORM MESSAGES (Non-CleverReach)
    // ========================================================================

    /**
     * Get default success message (for front-end display)
     *
     * @return string
     */
    public static function getSuccessMessage(): string
    {
        $message = self::getFrontendMessage('success');
        return apply_filters('lexo-forms/forms/messages/success', $message);
    }

    /**
     * Get email sending failure message (for front-end display)
     *
     * @return string
     */
    public static function getEmailFailMessage(): string
    {
        $message = self::getFrontendMessage('email_fail');
        return apply_filters('lexo-forms/forms/messages/email-fail', $message);
    }

    /**
     * Get captcha validation failure message (for front-end display)
     *
     * @return string
     */
    public static function getCaptchaFailMessage(): string
    {
        $message = self::getFrontendMessage('captcha_fail');
        return apply_filters('lexo-forms/forms/messages/captcha-fail', $message);
    }

    /**
     * Get invalid email format message (for front-end display)
     *
     * @param string $field_label Field label or name
     * @return string
     */
    public static function getInvalidEmailMessage(string $field_label): string
    {
        // This message needs dynamic content, so use __() with site locale (shown to user on error)
        $message = sprintf(__('Invalid email format in field: %s', 'lexoforms'), $field_label);
        return apply_filters('lexo-forms/forms/messages/invalid-email', $message, $field_label);
    }

    /**
     * Get default confirmation email subject (for visitor confirmation emails)
     *
     * @return string
     */
    public static function getConfirmationEmailSubject(): string
    {
        $message = self::getFrontendMessage('confirmation_email_subject');
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
        $template = self::getAdminMessage('form_submitted_from');
        $message = sprintf($template, $page);
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
     * Get CleverReach submission error message (for front-end display)
     *
     * @return string
     */
    public static function getCleverReachErrorMessage(): string
    {
        $message = self::getFrontendMessage('cleverreach_error');
        return apply_filters('lexo-forms/cr/messages/error', $message);
    }

    /**
     * Get already subscribed message
     *
     * @param bool $for_admin If true, uses site locale (for admin emails), otherwise uses session language (for front-end)
     * @return string
     */
    public static function getAlreadySubscribedMessage(bool $for_admin = false): string
    {
        if ($for_admin) {
            // Admin email - use hardcoded messages with site language
            $message = self::getAdminMessage('already_subscribed_admin');
        } else {
            // Front-end - use hardcoded messages with session language
            $message = self::getFrontendMessage('already_subscribed');
        }

        return apply_filters('lexo-forms/cr/messages/already-subscribed', $message, $for_admin);
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
        $template = self::getAdminMessage('failure_subject');
        $message = sprintf($template, $site_name, $form_title);
        return apply_filters('lexo-forms/cr/email/failure/subject', $message, $site_name, $form_title);
    }

    /**
     * Get heading for failure notification email
     *
     * @return string
     */
    public static function getFailureNotificationHeading(): string
    {
        $message = self::getAdminMessage('failure_heading');
        return apply_filters('lexo-forms/cr/email/failure/heading', $message);
    }

    /**
     * Get error label for failure notification email
     *
     * @return string
     */
    public static function getFailureNotificationErrorLabel(): string
    {
        $message = self::getAdminMessage('error_label');
        return apply_filters('lexo-forms/cr/email/failure/error-label', $message);
    }

    /**
     * Get footer message for failure notification email
     *
     * @return string
     */
    public static function getFailureNotificationFooter(): string
    {
        $message = self::getAdminMessage('failure_footer');
        return apply_filters('lexo-forms/cr/email/failure/footer', $message);
    }

    /**
     * Get subject for partial failure notification email
     *
     * @param string $site_name Site name
     * @param string $form_title Form title
     * @param string $failed_system Either 'cleverreach' or 'email'
     * @return string
     */
    public static function getPartialFailureNotificationSubject(string $site_name, string $form_title, string $failed_system): string
    {
        $failed_label = self::getFailedSystemLabel($failed_system);
        $template = self::getAdminMessage('partial_failure_subject');
        $message = sprintf($template, $site_name, $form_title, $failed_label);
        return apply_filters('lexo-forms/cr/email/partial-failure/subject', $message, $site_name, $form_title, $failed_system);
    }

    /**
     * Get translated label for failed system (for use in subjects and messages)
     *
     * @param string $failed_system Either 'cleverreach' or 'email'
     * @return string Translated label
     */
    public static function getFailedSystemLabel(string $failed_system): string
    {
        $key = $failed_system === 'cleverreach' ? 'cleverreach_label' : 'email_label';
        $label = self::getAdminMessage($key);
        return apply_filters('lexo-forms/cr/email/failed-system-label', $label, $failed_system);
    }

    /**
     * Get heading for partial failure notification email
     *
     * @return string
     */
    public static function getPartialFailureNotificationHeading(): string
    {
        $message = self::getAdminMessage('partial_failure_heading');
        return apply_filters('lexo-forms/cr/email/partial-failure/heading', $message);
    }

    /**
     * Get "one system failed" label
     *
     * @return string
     */
    public static function getPartialFailureOneSystemFailedLabel(): string
    {
        $message = self::getAdminMessage('one_system_failed');
        return apply_filters('lexo-forms/cr/email/partial-failure/one-system-failed-label', $message);
    }

    /**
     * Get CleverReach failed but email succeeded message
     *
     * @return string
     */
    public static function getPartialFailureCleverReachFailedMessage(): string
    {
        $message = self::getAdminMessage('cr_failed_but_email_ok');
        return apply_filters('lexo-forms/cr/email/partial-failure/cr-failed-message', $message);
    }

    /**
     * Get email failed but CleverReach succeeded message
     *
     * @return string
     */
    public static function getPartialFailureEmailFailedMessage(): string
    {
        $message = self::getAdminMessage('email_failed_but_cr_ok');
        return apply_filters('lexo-forms/cr/email/partial-failure/email-failed-message', $message);
    }

    /**
     * Get CleverReach failed footer message
     *
     * @return string
     */
    public static function getPartialFailureCleverReachFailedFooter(): string
    {
        $message = self::getAdminMessage('cr_failed_footer');
        return apply_filters('lexo-forms/cr/email/partial-failure/cr-failed-footer', $message);
    }

    /**
     * Get email failed footer message
     *
     * @return string
     */
    public static function getPartialFailureEmailFailedFooter(): string
    {
        $message = self::getAdminMessage('email_failed_footer');
        return apply_filters('lexo-forms/cr/email/partial-failure/email-failed-footer', $message);
    }

    /**
     * Get "CleverReach Form Information" heading
     *
     * @return string
     */
    public static function getFormInformationHeading(): string
    {
        $message = self::getAdminMessage('form_information_heading');
        return apply_filters('lexo-forms/cr/email/form-information-heading', $message);
    }

    /**
     * Get "Submitted Data" heading
     *
     * @return string
     */
    public static function getSubmittedDataHeading(): string
    {
        $message = self::getAdminMessage('submitted_data_heading');
        return apply_filters('lexo-forms/cr/email/submitted-data-heading', $message);
    }

    /**
     * Get "Form:" label
     *
     * @return string
     */
    public static function getFormLabel(): string
    {
        $message = self::getAdminMessage('form_label');
        return apply_filters('lexo-forms/cr/email/form-label', $message);
    }

    /**
     * Get "Form ID:" label
     *
     * @return string
     */
    public static function getFormIdLabel(): string
    {
        $message = self::getAdminMessage('form_id_label');
        return apply_filters('lexo-forms/cr/email/form-id-label', $message);
    }

    /**
     * Get "Date:" label
     *
     * @return string
     */
    public static function getDateLabel(): string
    {
        $message = self::getAdminMessage('date_label');
        return apply_filters('lexo-forms/cr/email/date-label', $message);
    }

    /**
     * Get "Field" table header
     *
     * @return string
     */
    public static function getFieldTableHeader(): string
    {
        $message = self::getAdminMessage('field_header');
        return apply_filters('lexo-forms/cr/email/field-header', $message);
    }

    /**
     * Get "Value" table header
     *
     * @return string
     */
    public static function getValueTableHeader(): string
    {
        $message = self::getAdminMessage('value_header');
        return apply_filters('lexo-forms/cr/email/value-header', $message);
    }

    /**
     * Helper for admin messages - just calls __() without any locale switching.
     * WordPress is already on site locale, no need to switch.
     * For critical messages, use $admin_messages array instead.
     *
     * @param callable():string $callback Translation callback that returns translated string
     * @return string Translated message
     */
    protected static function translateWithSiteLocale(callable $callback): string
    {
        // WordPress is already on site locale - just execute callback
        return $callback();
    }

    /**
     * Get user language code from user locale.
     *
     * Uses the current WordPress user's locale preference if available.
     * Falls back to site locale when user locale is not set.
     *
     * @return string Language code (e.g., 'de', 'en', 'fr', 'it') - defaults to 'de'
     */
    public static function getUserLanguage(): string
    {
        $locale = function_exists('get_user_locale') ? get_user_locale() : null;

        if (empty($locale)) {
            return self::getSiteLanguage();
        }

        return self::normalizeLocaleToLanguage($locale);
    }

    /**
     * Get site language code from site locale.
     *
     * Converts WordPress locale (e.g., 'de_CH', 'en_US') to a simple language code
     * (e.g., 'de', 'en') for use in email labels and admin notifications.
     *
     * @return string Language code (e.g., 'de', 'en', 'fr', 'it') - defaults to 'de'
     */
    public static function getSiteLanguage(): string
    {
        $locale = function_exists('get_locale') ? get_locale() : null;

        if (empty($locale)) {
            return 'de';
        }

        return self::normalizeLocaleToLanguage($locale);
    }

    /**
     * Normalize a WordPress locale string to a base language code.
     *
     * @param string $locale Locale string such as 'de_CH' or 'en_US'.
     * @return string Lowercase language code.
     */
    protected static function normalizeLocaleToLanguage(string $locale): string
    {
        $parts = explode('_', $locale);
        return strtolower($parts[0]);
    }
}
