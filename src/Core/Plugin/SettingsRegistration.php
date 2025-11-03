<?php

namespace LEXO\LF\Core\Plugin;

/**
 * Settings Registration
 *
 * Registers WordPress settings with sanitization callbacks.
 * Follows LEXO standard pattern.
 */
class SettingsRegistration
{
    public function __construct()
    {
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Register plugin settings
     *
     * @return void
     */
    public function registerSettings(): void
    {
        register_setting(\LEXO\LF\FIELD_NAME, 'cleverreach_client_id', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);

        register_setting(\LEXO\LF\FIELD_NAME, 'cleverreach_client_secret', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);

        register_setting(\LEXO\LF\FIELD_NAME, 'cleverreach_redirect_uri', [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ]);

        register_setting(\LEXO\LF\FIELD_NAME, 'cleverreach_fallback_admin_email', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_email',
            'default' => ''
        ]);
    }
}
