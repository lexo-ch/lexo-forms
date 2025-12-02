<?php

namespace LEXO\LF\Core\Plugin;

use LEXO\LF\Core\Abstracts\Singleton;
use LEXO\LF\Core\Utils\Logger;

use const LEXO\LF\{
    OAUTH_AUTHORIZE_URL,
    OAUTH_TOKEN_URL
};

/**
 * CleverReach Authentication & Token Management
 *
 * Handles OAuth2 flow and token lifecycle management.
 * Follows LEXO standard - separates auth logic from view/admin logic.
 */
class CleverReachAuth extends Singleton
{
    protected static $instance = null;

    protected string $menuSlug = 'cleverreach-settings';
    protected string $redirectUri;

    /**
     * Initialize authentication system
     */
    public function __construct()
    {
        $this->redirectUri = get_option('cleverreach_redirect_uri', $this->getDefaultRedirectUri());
        $this->registerHooks();
    }

    /**
     * Register WordPress hooks
     *
     * @return void
     */
    private function registerHooks(): void
    {
        add_action('admin_init', [$this, 'handleOAuthCallback']);
        add_action('init', [$this, 'handlePublicOAuthCallback']);
        add_action('init', [$this, 'ensureValidToken']);
        add_action('cleverreach_token_refresh', [$this, 'scheduleTokenRefresh']);

        // Schedule weekly token refresh cron job
        if (!wp_next_scheduled('cleverreach_token_refresh')) {
            wp_schedule_event(time(), 'weekly', 'cleverreach_token_refresh');
        }
    }

    /**
     * Handle OAuth callback in admin context
     *
     * @return void
     */
    public function handleOAuthCallback(): void
    {
        if (isset($_GET['page']) && $_GET['page'] === $this->menuSlug && isset($_GET['action']) && $_GET['action'] === 'oauth_callback') {
            if (isset($_GET['code'])) {
                $this->exchangeCodeForToken(sanitize_text_field($_GET['code']));
            } elseif (isset($_GET['error'])) {
                $error = sanitize_text_field($_GET['error_description'] ?? $_GET['error']);

                // Set error transient (LEXO standard)
                set_transient(
                    \LEXO\LF\DOMAIN . '_auth_error_notice',
                    $error,
                    HOUR_IN_SECONDS
                );

                wp_redirect(admin_url('admin.php?page=' . $this->menuSlug));
                exit;
            }
        }
    }

    /**
     * Handle OAuth callback in public context
     *
     * @return void
     */
    public function handlePublicOAuthCallback(): void
    {
        if (isset($_GET['cr_oauth']) && isset($_GET['code'])) {
            $expectedState = get_option('cleverreach_oauth_state');
            $receivedState = $_GET['state'] ?? '';

            if (!$expectedState || !hash_equals($expectedState, $receivedState)) {
                wp_die(__('Invalid OAuth state. Please try connecting again.', 'lexoforms'));
            }

            delete_option('cleverreach_oauth_state');

            $this->exchangeCodeForToken(sanitize_text_field($_GET['code']));
        }
    }

    /**
     * Exchange authorization code for access token
     *
     * @param string $code Authorization code
     * @return void
     */
    private function exchangeCodeForToken(string $code): void
    {
        $clientId = get_option('cleverreach_client_id');
        $clientSecret = get_option('cleverreach_client_secret');

        if (!$clientId || !$clientSecret) {
            // Set error transient (LEXO standard)
            set_transient(
                \LEXO\LF\DOMAIN . '_auth_error_notice',
                __('Client ID or Client Secret are not configured', 'lexoforms'),
                HOUR_IN_SECONDS
            );

            wp_redirect(admin_url('admin.php?page=' . $this->menuSlug));
            exit;
        }

        $tokenUrl = OAUTH_TOKEN_URL;

        $postData = [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code
        ];

        $response = wp_remote_post($tokenUrl, [
            'body' => $postData,
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            // Set error transient (LEXO standard)
            set_transient(
                \LEXO\LF\DOMAIN . '_auth_error_notice',
                __('Error communicating with CleverReach', 'lexoforms'),
                HOUR_IN_SECONDS
            );

            wp_redirect(admin_url('admin.php?page=' . $this->menuSlug));
            exit;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['access_token'])) {
            update_option('cleverreach_access_token', sanitize_text_field($data['access_token']));
            update_option('cleverreach_refresh_token', sanitize_text_field($data['refresh_token'] ?? ''));
            update_option('cleverreach_token_expires', time() + intval($data['expires_in'] ?? HOUR_IN_SECONDS));

            // Set success transient (LEXO standard)
            set_transient(
                \LEXO\LF\DOMAIN . '_auth_success_notice',
                __('Successfully connected to CleverReach!', 'lexoforms'),
                HOUR_IN_SECONDS
            );

            wp_redirect(admin_url('admin.php?page=' . $this->menuSlug));
            exit;
        } else {
            $error = $data['error_description'] ?? __('Unknown error while obtaining token', 'lexoforms');

            // Set error transient (LEXO standard)
            set_transient(
                \LEXO\LF\DOMAIN . '_auth_error_notice',
                $error,
                HOUR_IN_SECONDS
            );

            wp_redirect(admin_url('admin.php?page=' . $this->menuSlug));
            exit;
        }
    }

    /**
     * Get OAuth authorization URL
     *
     * @return string
     */
    public function getAuthUrl(): string
    {
        $clientId = get_option('cleverreach_client_id');

        $state = bin2hex(random_bytes(16));
        update_option('cleverreach_oauth_state', $state);

        $params = [
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
        ];

        return OAUTH_AUTHORIZE_URL . '?' . http_build_query($params);
    }

    /**
     * Check if CleverReach is connected and token is valid
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        $token = get_option('cleverreach_access_token');
        $expires = get_option('cleverreach_token_expires', 0);
        $currentTime = time();
        $remainingTime = $expires - $currentTime;

        // If token expires in less than 7 days, refresh it proactively
        if (!empty($token) && $remainingTime > 0 && $remainingTime < (7 * 24 * 3600)) {
            if ($this->refreshToken()) {
                $expires = get_option('cleverreach_token_expires', 0);
                $remainingTime = $expires - $currentTime;
            }
        }

        $isValid = !empty($token) && time() < $expires;

        return $isValid;
    }

    /**
     * Ensure token is valid, refresh if needed
     *
     * @return void
     */
    public function ensureValidToken(): void
    {
        $token = get_option('cleverreach_access_token');
        $expires = get_option('cleverreach_token_expires', 0);

        // Refresh token 24 hours before expiry
        if (!empty($token) && time() >= ($expires - 86400)) {
            $this->refreshToken();
        }
    }

    /**
     * Scheduled token refresh (cron job)
     *
     * @return void
     */
    public function scheduleTokenRefresh(): void
    {
        $this->refreshToken();
    }

    /**
     * Get valid access token (refresh if needed)
     *
     * @return string|null
     */
    public function getValidToken(): ?string
    {
        $token = get_option('cleverreach_access_token');
        $expires = get_option('cleverreach_token_expires', 0);

        // If token is expired or missing, try to refresh
        if (empty($token) || time() >= $expires) {
            if ($this->refreshToken()) {
                return get_option('cleverreach_access_token');
            }
            return null;
        }

        // If token expires within 1 hour, refresh proactively
        if (time() >= ($expires - 3600)) {
            $this->refreshToken();
            return get_option('cleverreach_access_token');
        }

        return $token;
    }

    /**
     * Refresh access token using refresh token
     *
     * @return bool
     */
    private function refreshToken(): bool
    {
        $refreshToken = get_option('cleverreach_refresh_token');
        $clientId = get_option('cleverreach_client_id');
        $clientSecret = get_option('cleverreach_client_secret');

        if (!$refreshToken || !$clientId || !$clientSecret) {
            return false;
        }

        $tokenUrl = OAUTH_TOKEN_URL;

        $postData = [
            'grant_type' => 'refresh_token',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken
        ];

        $response = wp_remote_post($tokenUrl, [
            'body' => $postData,
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ]);

        if (is_wp_error($response)) {
            Logger::authError('Token refresh error: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $httpCode = wp_remote_retrieve_response_code($response);

        if ($httpCode === 200 && isset($data['access_token'])) {
            update_option('cleverreach_access_token', sanitize_text_field($data['access_token']));

            if (isset($data['refresh_token'])) {
                update_option('cleverreach_refresh_token', sanitize_text_field($data['refresh_token']));
            }

            $expiresIn = intval($data['expires_in'] ?? 31536000);
            update_option('cleverreach_token_expires', time() + $expiresIn);

            return true;
        } else {
            $error = $data['error_description'] ?? $data['error'] ?? 'Unknown refresh error';
            Logger::authError('Token refresh failed: ' . $error);
            return false;
        }
    }

    /**
     * Get default redirect URI
     *
     * @return string
     */
    public function getDefaultRedirectUri(): string
    {
        return admin_url('admin.php?page=' . $this->menuSlug . '&action=oauth_callback');
    }
}
