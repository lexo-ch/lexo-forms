<?php

namespace LEXO\LF\Core\Abstracts;

use Exception;

use const LEXO\LF\{
    EMAIL_FROM_NAME,
    EMAIL_FROM_EMAIL,
    FIELD_PREFIX
};

use LEXO\LF\Core\Utils\Logger;
use LEXO\LF\Core\Utils\FormMessages;

/**
 * Abstract Email Handler
 *
 * Base class for handling email functionality with WordPress filters integration.
 * Allows themes to define email configuration through filters.
 */
abstract class EmailHandler
{
    /**
     * Get fallback admin email
     * Priority: lexoform_fallback_admin_email option -> admin_email
     *
     * @return string
     */
    private function getFallbackAdminEmail(): string
    {
        $fallback = get_option(FIELD_PREFIX . 'fallback_admin_email', '');
        if (!empty($fallback) && is_email($fallback)) {
            return $fallback;
        }
        return EMAIL_FROM_EMAIL;
    }

    /**
     * Get email configuration from form ACF fields and theme filters
     *
     * @param int $form_id
     * @param array $form_data
     * @param string $handler_type Handler type to determine if email_settings should be loaded
     * @return array
     */
    protected function getEmailConfig(int $form_id, array $form_data, string $handler_type = ''): array
    {
        // Start with system defaults
        $default_config = [
            'recipients' => [],
            'subject' => 'New Form Submission',
            'from_email' => '',
            'from_name' => EMAIL_FROM_NAME,
            'reply_to_email' => '',
            'reply_to_name' => ''
        ];

        // Allow theme to override defaults via filter
        $config = apply_filters('lexo-forms/cr/email/config', $default_config, $form_id, $form_data);

        // Skip email_settings for cr_only handler type
        if ($handler_type === 'cr_only') {
            // Validate and normalize recipients
            if (empty($config['recipients'])) {
                Logger::emailError('No email recipients configured', $form_id);
                $config['recipients'] = [$this->getFallbackAdminEmail()];
            }

            // Ensure recipients is array and filter out empty values
            if (!\is_array($config['recipients'])) {
                $config['recipients'] = [$config['recipients']];
            }

            $config['recipients'] = array_filter($config['recipients'], function($email) {
                return !empty($email) && is_email($email);
            });

            if (empty($config['recipients'])) {
                Logger::emailError('No valid email recipients after filtering', $form_id);
                $config['recipients'] = [$this->getFallbackAdminEmail()];
            }

            return $config;
        }

        // OPTIMIZATION: Get email settings group (1 DB query instead of 4)
        $email_settings = get_field(FIELD_PREFIX . 'email_settings', $form_id) ?: [];

        // Get form-specific email settings (ACF fields have priority over filter)
        $form_recipients = $email_settings[FIELD_PREFIX . 'recipients'] ?? null;
        $form_subject = $email_settings[FIELD_PREFIX . 'email_subject'] ?? null;
        $form_sender_email = $email_settings[FIELD_PREFIX . 'sender_email'] ?? null;
        $form_sender_name = $email_settings[FIELD_PREFIX . 'sender_name'] ?? null;

        // Process recipients (get from ACF repeater) - ACF takes priority
        if (!empty($form_recipients) && \is_array($form_recipients)) {
            $recipients = [];
            foreach ($form_recipients as $recipient) {
                if (!empty($recipient[FIELD_PREFIX . 'email']) && is_email($recipient[FIELD_PREFIX . 'email'])) {
                    $recipients[] = $recipient[FIELD_PREFIX . 'email'];
                }
            }
            if (!empty($recipients)) {
                $config['recipients'] = $recipients;
            }
        }

        // Override other fields if ACF values exist
        if (!empty($form_subject)) {
            $config['subject'] = $form_subject;
        }
        if (!empty($form_sender_email)) {
            $config['from_email'] = $form_sender_email;
        }
        if (!empty($form_sender_name)) {
            $config['from_name'] = $form_sender_name;
        }

        // Validate and normalize recipients
        if (empty($config['recipients'])) {
            Logger::emailError('No email recipients configured', $form_id);
            $config['recipients'] = [$this->getFallbackAdminEmail()];
        }

        // Ensure recipients is array and filter out empty values
        if (!is_array($config['recipients'])) {
            $config['recipients'] = [$config['recipients']];
        }

        $config['recipients'] = array_filter($config['recipients'], function($email) {
            return !empty($email) && is_email($email);
        });

        if (empty($config['recipients'])) {
            Logger::emailError('No valid email recipients after filtering', $form_id);
            $config['recipients'] = [$this->getFallbackAdminEmail()];
        }

        return $config;
    }

    /**
     * Get visitor email BCC recipients from ACF fields
     *
     * @param int $form_id
     * @return array
     */
    protected function getVisitorEmailBccRecipients(int $form_id): array
    {
        $email_settings = get_field(FIELD_PREFIX . 'email_settings', $form_id) ?: [];
        $bcc_recipients = [];

        $form_bcc = $email_settings[FIELD_PREFIX . 'additional_bcc_recipients'] ?? null;
        if (!empty($form_bcc) && \is_array($form_bcc)) {
            foreach ($form_bcc as $bcc_recipient) {
                $bcc_email_key = FIELD_PREFIX . 'bcc_email';
                if (!empty($bcc_recipient[$bcc_email_key]) && is_email($bcc_recipient[$bcc_email_key])) {
                    $bcc_recipients[] = $bcc_recipient[$bcc_email_key];
                }
            }
        }

        return $bcc_recipients;
    }

    /**
     * Set HTML content type for wp_mail
     *
     * @return string
     */
    public function setHtmlContentType(): string
    {
        return 'text/html';
    }

    /**
     * Configure PHPMailer for sending emails
     *
     * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer
     * @param array $config
     * @return void
     */
    protected function configurePHPMailer($phpmailer, array $config): void
    {
        try {
            $from_email = !empty($config['from_email']) ? $config['from_email'] : EMAIL_FROM_EMAIL;
            $from_name = !empty($config['from_name']) ? $config['from_name'] : EMAIL_FROM_NAME;

            // Set From address
            if (!empty($from_email)) {
                $phpmailer->setFrom($from_email, $from_name);
                // Ensure Return-Path/envelope sender follows the From address.
                $phpmailer->Sender = $from_email;
            } else {
                // If no from_email configured, at least set FromName (WordPress will use default email)
                $phpmailer->FromName = $from_name;
            }

            // Set Reply-To address
            if (!empty($config['reply_to_email'])) {
                $phpmailer->addReplyTo($config['reply_to_email'], $config['reply_to_name'] ?? '');
            }

            // Set BCC recipients
            if (!empty($config['bcc_recipients']) && \is_array($config['bcc_recipients'])) {
                foreach ($config['bcc_recipients'] as $bcc_email) {
                    if (!empty($bcc_email) && is_email($bcc_email)) {
                        $phpmailer->addBCC($bcc_email);
                    }
                }
            }

            // Set charset
            $phpmailer->CharSet = 'UTF-8';
        } catch (Exception $e) {
            Logger::emailError("Failed to configure PHPMailer: {$e->getMessage()}", 0);
        }
    }

    /**
     * Send email using wp_mail (for form submissions with ACF configuration)
     *
     * @param int $form_id
     * @param array $form_data
     * @param string $message
     * @return bool
     */
    protected function sendEmail(int $form_id, array $form_data, string $message): bool
    {
        try {
            $config = $this->getEmailConfig($form_id, $form_data);

            // Use sendSimpleEmail internally (no BCC for admin notifications)
            $result = $this->sendSimpleEmail(
                $config['recipients'],
                $config['subject'] ?: 'Form Submission',
                $message,
                $config['from_email'],
                $config['from_name'],
                [], // attachments
                $config['reply_to_email'] ?? '',
                $config['reply_to_name'] ?? ''
            );

            if (!$result) {
                Logger::emailError('Failed to send email', $form_id);
            }

            return $result;

        } catch (Exception $e) {
            Logger::emailError('Email sending exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send simple email with custom configuration
     *
     * @param string|array $recipients Email recipient(s)
     * @param string $subject Email subject
     * @param string $message Email body (HTML)
     * @param string $from_email Sender email address
     * @param string $from_name Sender name
     * @param array $attachments Optional file attachments
     * @param string $reply_to_email Optional Reply-To email
     * @param string $reply_to_name Optional Reply-To name
     * @param array $bcc_recipients Optional BCC recipients
     * @return bool
     */
    protected function sendSimpleEmail(
        $recipients,
        string $subject,
        string $message,
        string $from_email = '',
        string $from_name = '',
        array $attachments = [],
        string $reply_to_email = '',
        string $reply_to_name = '',
        array $bcc_recipients = []
    ): bool {
        try {
            // Ensure recipients is an array
            if (!is_array($recipients)) {
                $recipients = [$recipients];
            }

            // Validate recipients
            $recipients = array_filter($recipients, function($email) {
                return !empty($email) && is_email($email);
            });

            if (empty($recipients)) {
                Logger::emailError('No valid email recipients');
                return false;
            }

            // Build config for PHPMailer
            $config = [
                'from_email' => $from_email,
                'from_name' => $from_name,
                'reply_to_email' => $reply_to_email,
                'reply_to_name' => $reply_to_name,
                'bcc_recipients' => $bcc_recipients
            ];

            // Add HTML content type filter
            add_filter('wp_mail_content_type', [$this, 'setHtmlContentType']);

            // Configure PHPMailer
            add_action('phpmailer_init', function($phpmailer) use ($config) {
                $this->configurePHPMailer($phpmailer, $config);
            }, 99);

            // Send email
            $result = wp_mail($recipients, $subject, $message, [], $attachments);

            // Remove HTML content type filter
            remove_filter('wp_mail_content_type', [$this, 'setHtmlContentType']);

            if (!$result) {
                Logger::emailError('Failed to send simple email');
            }

            return $result;

        } catch (Exception $e) {
            Logger::emailError('Simple email sending exception: ' . $e->getMessage());
            return false;
        }
    }
}