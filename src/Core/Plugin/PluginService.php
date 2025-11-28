<?php

namespace LEXO\LF\Core\Plugin;

use LEXO\LF\Core\Abstracts\Singleton;
use LEXO\LF\Core\Traits\Helpers;
use LEXO\LF\Core\Loader\Loader;
use LEXO\LF\Core\Updater\PluginUpdater;
use LEXO\LF\Core\Pages\SettingsPage;
use LEXO\LF\Core\Plugin\CleverReachAuth;
use LEXO\LF\Core\Plugin\CleverReachAPI;
use LEXO\LF\Core\Utils\Logger;

use const LEXO\LF\{
    ASSETS,
    PLUGIN_NAME,
    PLUGIN_SLUG,
    VERSION,
    MIN_PHP_VERSION,
    MIN_WP_VERSION,
    FIELD_NAME,
    FIELD_PREFIX,
    DOMAIN,
    BASENAME,
    CACHE_KEY,
    UPDATE_PATH,
};

class PluginService extends Singleton
{
    use Helpers;

    private static string $namespace    = 'custom-plugin-namespace';
    protected static $instance          = null;

    private const CHECK_UPDATE              = 'check-update-' . PLUGIN_SLUG;
    private const MANAGE_PLUGIN_CAP         = 'manage_options';
    private const SETTINGS_PARENT_SLUG      = 'options-general.php';
    private const SETTINGS_PAGE_SLUG        = 'settings-' . PLUGIN_SLUG;
    private const ACF_DIR                   = ASSETS . '/acf';

    private $settingsPage;
    private ?CleverReachAPI $api_instance = null;

    /**
     * Get API instance (reuses same instance for performance)
     *
     * @param string $token
     * @return CleverReachAPI
     */
    private function getAPI(string $token): CleverReachAPI
    {
        // Reuse existing instance or create new one
        if ($this->api_instance === null) {
            $this->api_instance = new CleverReachAPI($token);
        } else {
            // Update token in case it's different
            $this->api_instance->setToken($token);
        }

        return $this->api_instance;
    }

    public function setNamespace(string $namespace)
    {
        self::$namespace = $namespace;
    }

    public function registerNamespace()
    {
        $config = require_once trailingslashit(ASSETS) . 'config/config.php';

        $loader = Loader::getInstance();

        $loader->registerNamespace(self::$namespace, $config);

        // Initialize Pages
        $this->settingsPage = new SettingsPage();

        add_action('admin_post_' . self::CHECK_UPDATE, [$this, 'checkForUpdateManually']);
        add_action('save_post', [$this, 'validateFormOnSave'], 10, 3);
        add_action('admin_notices', [$this, 'displayNotices']);

    }
    
    public function addAdminLocalizedScripts()
    {
        $vars = [
            'plugin_name'       => PLUGIN_NAME,
            'plugin_slug'       => PLUGIN_SLUG,
            'plugin_version'    => VERSION,
            'min_php_version'   => MIN_PHP_VERSION,
            'min_wp_version'    => MIN_WP_VERSION,
            'text_domain'       => DOMAIN
        ];

        $vars = apply_filters(self::$namespace . '/admin_localized_script', $vars);

        wp_localize_script($this->getAdminScriptHandle(), DOMAIN . 'AdminLocalized', $vars);
    }
    
    public function addAdminCleverReachLocalization(): void
    {
        global $pagenow;

        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';

        if ($pagenow === 'admin.php' && $page === 'cleverreach-settings') {
            wp_localize_script(
                $this->getAdminScriptHandle(),
                'cleverreach_ajax',
                [
                    'nonce' => wp_create_nonce('cleverreach_nonce'),
                    'ajax_url' => admin_url('admin-ajax.php')
                ]
            );
        }
    }

    public static function getSettingsPageParentSlug()
    {
        $slug = self::SETTINGS_PARENT_SLUG;

        $slug = apply_filters(self::$namespace . '/options-page/parent-slug', $slug);

        return $slug;
    }

    public static function getManagePluginCap()
    {
        $capability = self::MANAGE_PLUGIN_CAP;

        $capability = apply_filters(self::$namespace . '/options-page/capability', $capability);

        return $capability;
    }

    /**
     * Add CleverReach settings page to admin menu
     *
     * @return void
     */
    public function addSettingsPage(): void
    {
        // Add main menu page
        add_menu_page(
            __('CleverReach Settings', 'lexoforms'),
            __('LEXO Forms', 'lexoforms'),
            'edit_posts',                      // Editor and above can see main menu
            'cleverreach-settings',
            [$this->settingsPage, 'getSettingsPageContent'],
            'dashicons-email-alt2',
            65
        );

        // Rename first submenu item to "Settings"
        // WordPress automatically creates it with same slug as parent
        // Only administrators can access settings
        add_submenu_page(
            'cleverreach-settings',
            __('CleverReach Settings', 'lexoforms'),
            __('Settings', 'lexoforms'),
            self::getManagePluginCap(),               // Only Administrator can see Settings
            'cleverreach-settings',
            [$this->settingsPage, 'getSettingsPageContent']
        );

        // Note: Forms CPT submenu is added in FormsPostType::addSubmenuPage()
    }

    /**
     * Save CleverReach settings
     *
     * @return void
     */
    public function saveSettings(): void
    {
        if (!current_user_can(self::getManagePluginCap())) {
            wp_die(__('You do not have permission for this action', 'lexoforms'));
        }

        check_admin_referer(FIELD_NAME);

        // Save credentials
        if (isset($_POST['cleverreach_client_id'])) {
            update_option('cleverreach_client_id', sanitize_text_field($_POST['cleverreach_client_id']));
        }

        if (isset($_POST['cleverreach_client_secret'])) {
            update_option('cleverreach_client_secret', sanitize_text_field($_POST['cleverreach_client_secret']));
        }

        if (isset($_POST['cleverreach_redirect_uri'])) {
            update_option('cleverreach_redirect_uri', sanitize_text_field($_POST['cleverreach_redirect_uri']));
        }

        // Success notice (LEXO standard)
        set_transient(
            DOMAIN . '_settings_saved_notice',
            sprintf(
                __('The settings for the %s have been successfully saved.', 'lexoforms'),
                PLUGIN_NAME
            ),
            HOUR_IN_SECONDS
        );

        wp_safe_redirect(admin_url('admin.php?page=cleverreach-settings'));
        exit;
    }

    /**
     * Save fallback admin email setting
     *
     * @return void
     */
    public function saveFallbackEmail(): void
    {
        if (!current_user_can(self::getManagePluginCap())) {
            wp_die(__('You do not have permission for this action', 'lexoforms'));
        }

        check_admin_referer(FIELD_NAME);

        // Save fallback email
        $fallback_email_key = FIELD_PREFIX . 'fallback_admin_email';
        if (isset($_POST[$fallback_email_key])) {
            update_option($fallback_email_key, sanitize_email($_POST[$fallback_email_key]));
        }

        // Success notice
        set_transient(
            DOMAIN . '_settings_saved_notice',
            __('Fallback email settings have been successfully saved.', 'lexoforms'),
            HOUR_IN_SECONDS
        );

        wp_safe_redirect(admin_url('admin.php?page=cleverreach-settings'));
        exit;
    }

    /**
     * Display all admin notices
     *
     * @return void
     */
    public function displayNotices(): void
    {
        $this->settingsSavedNotice();
        $this->authSuccessNotice();
        $this->authErrorNotice();
        $this->crConnectionNotice();
        $this->noUpdatesNotice();
        $this->updateSuccessNotice();
        $this->showValidationNotices();

        // Display CleverReach integration notices
        $cr_integration = \LEXO\LF\Core\Admin\CleverReachIntegration::getInstance();
        $cr_integration->displaySuccessNotice();
        $cr_integration->displayErrorNotice();
    }

    /**
     * Display settings saved notice
     *
     * @return void
     */
    public function settingsSavedNotice(): void
    {
        $message = get_transient(DOMAIN . '_settings_saved_notice');
        delete_transient(DOMAIN . '_settings_saved_notice');

        if (!$message) {
            return;
        }

        wp_admin_notice($message, [
            'type' => 'success',
            'dismissible' => true
        ]);
    }

    /**
     * Display auth success notice
     *
     * @return void
     */
    public function authSuccessNotice(): void
    {
        $message = get_transient(DOMAIN . '_auth_success_notice');
        delete_transient(DOMAIN . '_auth_success_notice');

        if (!$message) {
            return;
        }

        wp_admin_notice($message, [
            'type' => 'success',
            'dismissible' => true
        ]);
    }

    /**
     * Display auth error notice
     *
     * @return void
     */
    public function authErrorNotice(): void
    {
        $message = get_transient(DOMAIN . '_auth_error_notice');
        delete_transient(DOMAIN . '_auth_error_notice');

        if (!$message) {
            return;
        }

        wp_admin_notice($message, [
            'type' => 'error',
            'dismissible' => true
        ]);
    }

    /**
     * Display CleverReach connection status notice on Forms edit screen
     *
     * @return void
     */
    public function crConnectionNotice(): void
    {
        global $post, $pagenow;

        // Only on edit screen for cpt-lexoforms
        if ($pagenow !== 'post.php' || !$post || $post->post_type !== 'cpt-lexoforms') {
            return;
        }

        // Skip for new posts (auto-draft)
        if ($post->post_status === 'auto-draft') {
            return;
        }

        // Check if CR integration is enabled (from general_settings group)
        $general_settings = get_field(FIELD_PREFIX . 'general_settings', $post->ID) ?: [];
        $handler_type = $general_settings[FIELD_PREFIX . 'handler_type'] ?? '';

        if ($handler_type !== 'email_and_cr' && $handler_type !== 'cr_only') {
            return;
        }

        // Check CR status (from cr_integration group)
        $cr_integration = get_field(FIELD_PREFIX . 'cr_integration', $post->ID) ?: [];
        $cr_status = $cr_integration[FIELD_PREFIX . 'cr_status'] ?? null;

        if ($cr_status !== 'OK') {
            $message = sprintf(
                '<strong>%s</strong><br>%s',
                __('CleverReach Connection Required!', 'lexoforms'),
                __('This form is not connected to CleverReach. Please click the "Connect to CleverReach" button below and complete the setup before using this form.', 'lexoforms')
            );

            wp_admin_notice($message, [
                'type' => 'error',
                'dismissible' => false
            ]);
        }
    }

    private function getAdminScriptHandle(): string
    {
        return trailingslashit(self::$namespace) . 'admin-lf.js';
    }

    /**
     * AJAX handler - Test CleverReach connection
     *
     * @return void
     */
    public function handleTestConnection(): void
    {
        check_ajax_referer('cleverreach_nonce', 'nonce');

        if (!current_user_can(self::getManagePluginCap())) {
            wp_die(__('You do not have permission for this action', 'lexoforms'));
        }

        $auth = CleverReachAuth::getInstance();

        try {
            $token = $auth->getValidToken();

            if (!$token) {
                wp_send_json_error(__('Token could not be obtained or refreshed', 'lexoforms'));
                return;
            }

            $expires = get_option('cleverreach_token_expires', 0);
            $tokenInfo = [
                'expires_at' => date('Y-m-d H:i:s', $expires),
                'remaining_days' => floor(($expires - time()) / 86400),
                'is_expired' => time() >= $expires
            ];

            $api = $this->getAPI($token);

            if ($api->testConnection()) {
                wp_send_json_success([
                    'message' => __('Connection is successful!', 'lexoforms'),
                    'token_info' => $tokenInfo
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Connection failed - check token', 'lexoforms'),
                    'token_info' => $tokenInfo
                ]);
            }
        } catch (\Exception $e) {
            Logger::error('Test connection error: ' . $e->getMessage(), Logger::CATEGORY_API);
            wp_send_json_error(__('Error: ', 'lexoforms') . $e->getMessage());
        }
    }

    /**
     * AJAX handler - Disconnect from CleverReach
     *
     * @return void
     */
    public function handleDisconnect(): void
    {
        check_ajax_referer('cleverreach_nonce', 'nonce');

        if (!current_user_can(self::getManagePluginCap())) {
            wp_die(__('You do not have permission for this action', 'lexoforms'));
        }

        delete_option('cleverreach_access_token');
        delete_option('cleverreach_refresh_token');
        delete_option('cleverreach_token_expires');

        wp_send_json_success(__('Connection has been terminated', 'lexoforms'));
    }

    public function addPluginLinks()
    {
        add_filter(
            'plugin_action_links_' . BASENAME,
            [$this, 'setPluginLinks']
        );
    }

    public function setPluginLinks($links)
    {
        $update_check_url = self::getManualUpdateCheckLink();
        $update_check_link = "<a href='{$update_check_url}'>" . __('Update check', 'lexoforms') . '</a>';

        array_push(
            $links,
            $update_check_link
        );

        return $links;
    }

    public function updater()
    {
        return (new PluginUpdater())
            ->setBasename(BASENAME)
            ->setSlug(PLUGIN_SLUG)
            ->setVersion(VERSION)
            ->setRemotePath(UPDATE_PATH)
            ->setCacheKey(CACHE_KEY)
            ->setCacheExpiration(HOUR_IN_SECONDS)
            ->setCache(true);
    }

    public function checkForUpdateManually()
    {
        if (!wp_verify_nonce($_REQUEST['nonce'], self::CHECK_UPDATE)) {
            wp_die(__('Security check failed.', 'lexoforms'));
        }

        $plugin_settings = PluginService::getInstance();

        if (!$plugin_settings->updater()->hasNewUpdate()) {
            set_transient(
                DOMAIN . '_no_updates_notice',
                sprintf(
                    __('Plugin %s is up to date.', 'lexoforms'),
                    PLUGIN_NAME
                ),
                HOUR_IN_SECONDS
            );
        } else {
            delete_transient(CACHE_KEY);
        }

        wp_safe_redirect(admin_url('plugins.php'));

        exit;
    }

    public function noUpdatesNotice()
    {
        $message = get_transient(DOMAIN . '_no_updates_notice');
        delete_transient(DOMAIN . '_no_updates_notice');

        if (!$message) {
            return false;
        }

        wp_admin_notice($message, [
            'type' => 'success',
            'dismissible' => true
        ]);
    }

    public function updateSuccessNotice()
    {
        $message = get_transient(DOMAIN . '_update_success_notice');
        delete_transient(DOMAIN . '_update_success_notice');

        if (!$message) {
            return false;
        }

        wp_admin_notice($message, [
            'type' => 'success',
            'dismissible' => true
        ]);
    }

    public static function getManualUpdateCheckLink(): string
    {
        return esc_url(
            add_query_arg(
                [
                    'action' => self::CHECK_UPDATE,
                    'nonce' => wp_create_nonce(self::CHECK_UPDATE)
                ],
                admin_url('admin-post.php')
            )
        );
    }

    /**
     * Validate form on save - check CR connection and group existence
     *
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @param bool $update Whether this is an update
     * @return void
     */
    public function validateFormOnSave($post_id, $post, $update): void
    {
        // Only for cpt-lexoforms
        if ($post->post_type !== 'cpt-lexoforms') {
            return;
        }

        // Skip autosave and revisions
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        // Skip for auto-draft
        if ($post->post_status === 'auto-draft') {
            return;
        }

        // Check if CR integration is enabled (from general_settings group)
        $general_settings = get_field(FIELD_PREFIX . 'general_settings', $post_id) ?: [];
        $handler_type = $general_settings[FIELD_PREFIX . 'handler_type'] ?? '';
        if ($handler_type !== 'email_and_cr' && $handler_type !== 'cr_only') {
            return;
        }

        // Skip validation if integration is handling it
        // CleverReachIntegration handles more thorough validation via acf/save_post hook
        if (class_exists('LEXO\LF\Core\Admin\CleverReachIntegration')) {
            return;
        }

        $errors = [];

        // Check CR connection
        $auth = CleverReachAuth::getInstance();
        $access_token = $auth->getValidToken();
        if (empty($access_token)) {
            $errors[] = __('CleverReach API connection is not established. Please connect to CleverReach first.', 'lexoforms');
        }

        // Check if group exists (from cr_integration group)
        $cr_integration = get_field(FIELD_PREFIX . 'cr_integration', $post_id) ?: [];
        $group_id = $cr_integration[FIELD_PREFIX . 'group_id'] ?? null;

        if ($group_id) {
            // Direct API call to check if group exists and get proper HTTP status
            $auth = CleverReachAuth::getInstance();
            $token = $auth->getValidToken();

            if ($token) {
                try {
                    $api = $this->getAPI($token);
                    $response = $api->getGroup($group_id);

                    if (!$response['success'] || $response['http_code'] !== 200) {
                        $errors[] = sprintf(__('Selected CleverReach group (ID: %d) does not exist. Please select a valid group.', 'lexoforms'), $group_id);
                    }
                } catch (\Exception $e) {
                    Logger::error('Error validating CleverReach group: ' . $e->getMessage(), Logger::CATEGORY_API);
                    // Check if it's a 404 error (group doesn't exist)
                    if (strpos($e->getMessage(), '404') !== false || strpos($e->getMessage(), 'not found') !== false || strpos($e->getMessage(), 'invalid group') !== false) {
                        $errors[] = sprintf(__('Selected CleverReach group (ID: %d) does not exist or was deleted. Please select a valid group.', 'lexoforms'), $group_id);
                    } else {
                        $errors[] = sprintf(__('Error checking CleverReach group (ID: %d): %s', 'lexoforms'), $group_id, $e->getMessage());
                    }
                }
            }
        } else {
            $errors[] = __('No CleverReach group selected. Please select a group for form submissions.', 'lexoforms');
        }


        // If there are errors, store them as transient to show on redirect
        if (!empty($errors)) {
            set_transient(FIELD_PREFIX . 'validation_errors_' . $post_id, $errors, 30);

            // Update CR status with error (sub-field in cr_integration group)
            $cr_integration = get_field(FIELD_PREFIX . 'cr_integration', $post_id) ?: [];
            $cr_integration[FIELD_PREFIX . 'cr_status'] = 'ERROR: ' . implode(' ', $errors);
            update_field(FIELD_PREFIX . 'cr_integration', $cr_integration, $post_id);
        } else {
            // Clear any previous errors
            delete_transient(FIELD_PREFIX . 'validation_errors_' . $post_id);

            // Update CR status to OK if all validations pass (sub-field in cr_integration group)
            $cr_integration = get_field(FIELD_PREFIX . 'cr_integration', $post_id) ?: [];
            $cr_integration[FIELD_PREFIX . 'cr_status'] = 'OK';
            update_field(FIELD_PREFIX . 'cr_integration', $cr_integration, $post_id);
        }
    }


    /**
     * Show validation notices for form save errors
     *
     * @return void
     */
    public function showValidationNotices(): void
    {
        global $post, $pagenow;

        // Only on edit screen for cpt-lexoforms
        if ($pagenow !== 'post.php' || !$post || $post->post_type !== 'cpt-lexoforms') {
            return;
        }

        // Check for validation errors
        $errors = get_transient(FIELD_PREFIX . 'validation_errors_' . $post->ID);
        if (!$errors || !is_array($errors)) {
            return;
        }

        // Delete the transient so it only shows once
        delete_transient(FIELD_PREFIX . 'validation_errors_' . $post->ID);

        // Display errors
        foreach ($errors as $error) {
            $message = sprintf(
                '<strong>%s:</strong> %s',
                __('CleverReach Form Error', 'lexoforms'),
                esc_html($error)
            );

            wp_admin_notice($message, [
                'type' => 'error',
                'dismissible' => true
            ]);
        }
    }

    /**
     * Get all PHP files from a directory
     *
     * @param string $directory Directory path
     * @return array List of PHP files
     */
    private static function getFilesFromDirectory(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];
        $dir = opendir($directory);

        if ($dir) {
            while (($file = readdir($dir)) !== false) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $files[] = $file;
                }
            }
            closedir($dir);
        }

        return $files;
    }

    /**
     * Import ACF field groups from PHP files
     *
     * Following LEXO standard - imports all PHP files from assets/acf/ directory
     * Each file should return an array compatible with acf_add_local_field_group()
     *
     * @return void
     */
    public function importFields(): void
    {
        if (function_exists('acf_add_local_field_group')) {
            $assets = self::getFilesFromDirectory(self::ACF_DIR);

            if (is_array($assets) && !empty($assets)) {
                foreach ($assets as $file) {
                    $field_group = require_once trailingslashit(self::ACF_DIR) . $file;

                    if (is_array($field_group) && !empty($field_group)) {
                        acf_add_local_field_group($field_group);
                    }
                }
            }
        }
    }
}
