<?php

namespace LEXO\LF\Core;

use LEXO\LF\Core\Abstracts\Singleton;
use LEXO\LF\Core\Plugin\PluginService;
use LEXO\LF\Core\Plugin\CleverReachAuth;
use LEXO\LF\Core\Plugin\SettingsRegistration;
use LEXO\LF\Core\PostTypes\FormsPostType;
use LEXO\LF\Core\Editor\FormsToolbar;
use LEXO\LF\Core\Editor\AdditionalEmailToolbar;
use LEXO\LF\Core\Services\FormSubmissionHandler;
use LEXO\LF\Core\Admin\CleverReachIntegration;

use const LEXO\LF\{
    DOMAIN,
    PATH,
    LOCALES
};

class Bootloader extends Singleton
{
    protected static $instance = null;

    public static function run(): void
    {
        // Initialize CleverReach Auth (registers its own hooks)
        CleverReachAuth::getInstance();

        // Initialize Settings Registration
        $settingsRegistration = new SettingsRegistration();

        FormsPostType::getInstance()->register();
        FormsToolbar::getInstance()->register();
        AdditionalEmailToolbar::getInstance()->register();
        FormSubmissionHandler::getInstance()->register();
        CleverReachIntegration::getInstance()->register();

        add_action('init', [self::class, 'onInit'], 10);
        add_action('admin_init', [self::class, 'onAdminInit']);
        add_action('admin_menu', [self::class, 'onAdminMenu'], 10);
        add_action(DOMAIN . '/localize/admin-lf.js', [self::class, 'onAdminLfJsLoad']);
        add_action('after_setup_theme', [self::class, 'onAfterSetupTheme']);

        // Register AJAX handlers - Settings
        add_action('wp_ajax_test_cleverreach_connection', [PluginService::getInstance(), 'handleTestConnection']);
        add_action('wp_ajax_disconnect_cleverreach', [PluginService::getInstance(), 'handleDisconnect']);

        // Register admin_post handler for settings save
        add_action('admin_post_cr_save_settings', [PluginService::getInstance(), 'saveSettings']);
        add_action('admin_post_cr_save_fallback_email', [PluginService::getInstance(), 'saveFallbackEmail']);
    }

    public static function onInit(): void
    {
        do_action(DOMAIN . '/init');

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $plugin_settings = PluginService::getInstance();
        $plugin_settings->setNamespace(DOMAIN);
        $plugin_settings->registerNamespace();
        $plugin_settings->importFields(); // Register ACF fields (LEXO standard)
        $plugin_settings->addPluginLinks();
    }

    public static function onAdminInit(): void
    {
        // Admin init tasks if needed in future
    }

    public static function onAdminMenu(): void
    {
        PluginService::getInstance()->addSettingsPage();
    }

    public static function onAdminLfJsLoad(): void
    {
        $pluginService = PluginService::getInstance();

        $pluginService->addAdminLocalizedScripts();
        $pluginService->addAdminCleverReachLocalization();

        CleverReachIntegration::getInstance()->addLexoformIntegrationLocalization();
    }

    public static function onAfterSetupTheme(): void
    {
        self::loadPluginTextdomain();
        // PluginService::getInstance()->updater()->run();
    }

    public static function loadPluginTextdomain(): void
    {
        load_plugin_textdomain(DOMAIN, false, trailingslashit(trailingslashit(basename(PATH)) . LOCALES));
    }
}
