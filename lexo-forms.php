<?php

/**
 * Plugin Name:       LEXO Forms
 * Plugin URI:        https://github.com/lexo-ch/lexo-forms/
 * Description:       LEXO forms plugin with CleverReach integration.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4.1
 * Requires Plugins:  advanced-custom-fields-pro, lexo-captcha
 * Author:            LEXO GmbH
 * Author URI:        https://www.lexo.ch
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       lexoforms
 * Domain Path:       /languages
 * Update URI:        lexo-forms
 */

namespace LEXO\LF;

use Exception;
use LEXO\LF\Activation;
use LEXO\LF\Deactivation;
use LEXO\LF\Uninstalling;
use LEXO\LF\Core\Bootloader;

// Prevent direct access
!defined('WPINC')
    && die;

// Define Main plugin file
!defined('LEXO\LF\FILE')
    && define('LEXO\LF\FILE', __FILE__);

// Define plugin name
!defined('LEXO\LF\PLUGIN_NAME')
    && define('LEXO\LF\PLUGIN_NAME', get_file_data(FILE, [
        'Plugin Name' => 'Plugin Name'
    ])['Plugin Name']);

// Define plugin slug
!defined('LEXO\LF\PLUGIN_SLUG')
    && define('LEXO\LF\PLUGIN_SLUG', get_file_data(FILE, [
        'Update URI' => 'Update URI'
    ])['Update URI']);

// Define Basename
!defined('LEXO\LF\BASENAME')
    && define('LEXO\LF\BASENAME', plugin_basename(FILE));

// Define internal path
!defined('LEXO\LF\PATH')
    && define('LEXO\LF\PATH', plugin_dir_path(FILE));

// Define assets path
!defined('LEXO\LF\ASSETS')
    && define('LEXO\LF\ASSETS', trailingslashit(PATH) . 'assets');

// Define internal url
!defined('LEXO\LF\URL')
    && define('LEXO\LF\URL', plugin_dir_url(FILE));

// Define internal version
!defined('LEXO\LF\VERSION')
    && define('LEXO\LF\VERSION', get_file_data(FILE, [
        'Version' => 'Version'
    ])['Version']);

// Define min PHP version
!defined('LEXO\LF\MIN_PHP_VERSION')
    && define('LEXO\LF\MIN_PHP_VERSION', get_file_data(FILE, [
        'Requires PHP' => 'Requires PHP'
    ])['Requires PHP']);

// Define min WP version
!defined('LEXO\LF\MIN_WP_VERSION')
    && define('LEXO\LF\MIN_WP_VERSION', get_file_data(FILE, [
        'Requires at least' => 'Requires at least'
    ])['Requires at least']);

// Define Text domain
!defined('LEXO\LF\DOMAIN')
    && define('LEXO\LF\DOMAIN', get_file_data(FILE, [
        'Text Domain' => 'Text Domain'
    ])['Text Domain']);

// Define locales folder (with all translations)
!defined('LEXO\LF\LOCALES')
    && define('LEXO\LF\LOCALES', 'languages');

!defined('LEXO\LF\FIELD_NAME')
    && define('LEXO\LF\FIELD_NAME', 'clever_reach_setting');

!defined('LEXO\LF\CACHE_KEY')
    && define('LEXO\LF\CACHE_KEY', DOMAIN . '_cache_key_update');

!defined('LEXO\LF\UPDATE_PATH')
    && define('LEXO\LF\UPDATE_PATH', 'https://wprepo.lexo.ch/public/lexo-forms/info.json');

// Define cache keys - following LEXO-Captcha pattern
!defined('LEXO\LF\CACHE_KEY_FORMS_LIST')
    && define('LEXO\LF\CACHE_KEY_FORMS_LIST', DOMAIN . '_cpt_lexoforms_list');

!defined('LEXO\LF\CACHE_KEY_FORM_PREFIX')
    && define('LEXO\LF\CACHE_KEY_FORM_PREFIX', DOMAIN . '_cpt_lexoform_');

!defined('LEXO\LF\CACHE_KEY_GROUPS_LIST')
    && define('LEXO\LF\CACHE_KEY_GROUPS_LIST', DOMAIN . '_cpt_lexogroups_list');

!defined('LEXO\LF\CACHE_KEY_GROUP_PREFIX')
    && define('LEXO\LF\CACHE_KEY_GROUP_PREFIX', DOMAIN . '_cpt_lexogroup_');

// Define cache expiry times
!defined('LEXO\LF\CACHE_EXPIRY_LONG')
    && define('LEXO\LF\CACHE_EXPIRY_LONG', 30 * DAY_IN_SECONDS); // 30 days

!defined('LEXO\LF\CACHE_EXPIRY_SHORT')
    && define('LEXO\LF\CACHE_EXPIRY_SHORT', 24 * HOUR_IN_SECONDS); // 24 hours

// Define API related constants
!defined('LEXO\LF\API_BASE_URL')
    && define('LEXO\LF\API_BASE_URL', 'https://rest.cleverreach.com/v3');

!defined('LEXO\LF\API_TIMEOUT')
    && define('LEXO\LF\API_TIMEOUT', 30);

// Define email related constants
!defined('LEXO\LF\EMAIL_FROM_NAME')
    && define('LEXO\LF\EMAIL_FROM_NAME', get_bloginfo('name'));

!defined('LEXO\LF\EMAIL_FROM_EMAIL')
    && define('LEXO\LF\EMAIL_FROM_EMAIL', get_option('admin_email'));

// Define ACF field prefix - centralized field naming
!defined('LEXO\LF\FIELD_PREFIX')
    && define('LEXO\LF\FIELD_PREFIX', 'lexoform_');

// Define OAuth related constants
!defined('LEXO\LF\OAUTH_AUTHORIZE_URL')
    && define('LEXO\LF\OAUTH_AUTHORIZE_URL', 'https://rest.cleverreach.com/oauth/authorize.php');

!defined('LEXO\LF\OAUTH_TOKEN_URL')
    && define('LEXO\LF\OAUTH_TOKEN_URL', 'https://rest.cleverreach.com/oauth/token.php');


if (!file_exists($composer = PATH . '/vendor/autoload.php')) {
    wp_die('Error locating autoloader in LEXO Forms.
        Please run a following command:<pre>composer install</pre>', 'lexoforms');
}

require $composer;

register_activation_hook(FILE, function () {
    (new Activation())->run();
});

register_deactivation_hook(FILE, function () {
    (new Deactivation())->run();
});

if (!function_exists('lf_uninstall')) {
    function lf_uninstall()
    {
        (new Uninstalling())->run();
    }
}
register_uninstall_hook(FILE, __NAMESPACE__ . '\lf_uninstall');

try {
    Bootloader::run();
} catch (Exception $e) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');

    deactivate_plugins(FILE);

    wp_die($e->getMessage());
}
