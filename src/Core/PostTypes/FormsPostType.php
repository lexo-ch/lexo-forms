<?php

namespace LEXO\LF\Core\PostTypes;

use LEXO\LF\Core\Abstracts\Singleton;
use LEXO\LF\Core\Plugin\CleverReachAuth;
use LEXO\LF\Core\Utils\FormHelpers;
use LEXO\LF\Core\Utils\FormMessages;

use const LEXO\LF\FIELD_PREFIX;

/**
 * Forms Custom Post Type
 *
 * Registers CleverReach Forms CPT.
 * Follows LEXO standard - CPT registration.
 */
class FormsPostType extends Singleton
{
    protected static $instance = null;

    private const POST_TYPE = 'cpt-lexoforms';

    /**
     * Register the Custom Post Type
     *
     * @return void
     */
    public function register(): void
    {
        add_action('init', [$this, 'registerPostType']);
        add_action('admin_menu', [$this, 'addSubmenuPage'], 5); // Priority 5 to run before Settings
        add_filter('parent_file', [$this, 'fixActiveMenu'], 999);
        add_filter('submenu_file', [$this, 'fixActiveSubmenu'], 999);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'addAdminColumns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'renderAdminColumns'], 10, 2);
    }

    /**
     * Register post type
     *
     * @return void
     */
    public function registerPostType(): void
    {
        $labels = $this->getLabels();
        $args = $this->getArgs($labels);

        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * Get CPT labels
     *
     * @return array
     */
    private function getLabels(): array
    {
        return [
            'name' => __('Forms', 'lexoforms'),
            'singular_name' => __('Form', 'lexoforms'),
            'menu_name' => __('Forms', 'lexoforms'),
            'name_admin_bar' => __('Form', 'lexoforms'),
            'add_new' => __('Add New', 'lexoforms'),
            'add_new_item' => __('Add New Form', 'lexoforms'),
            'new_item' => __('New Form', 'lexoforms'),
            'edit_item' => __('Edit Form', 'lexoforms'),
            'view_item' => __('View Form', 'lexoforms'),
            'all_items' => __('Forms', 'lexoforms'),
            'search_items' => __('Search Forms', 'lexoforms'),
            'parent_item_colon' => __('Parent Forms:', 'lexoforms'),
            'not_found' => __('No forms found.', 'lexoforms'),
            'not_found_in_trash' => __('No forms found in Trash.', 'lexoforms'),
        ];
    }

    /**
     * Get CPT arguments
     *
     * @param array $labels
     * @return array
     */
    private function getArgs(array $labels): array
    {
        return [
            'labels' => $labels,
            'description' => __('CleverReach Forms management', 'lexoforms'),
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => false, // Don't show in menu - we'll add manually
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'capabilities' => [
                'edit_post' => 'edit_posts',
                'read_post' => 'edit_posts',
                'delete_post' => 'delete_posts',
                'edit_posts' => 'edit_posts',
                'edit_others_posts' => 'edit_others_posts',
                'publish_posts' => 'publish_posts',
                'read_private_posts' => 'edit_posts',
                'delete_posts' => 'delete_posts',
                'delete_private_posts' => 'delete_posts',
                'delete_published_posts' => 'delete_posts',
                'delete_others_posts' => 'delete_others_posts',
                'edit_private_posts' => 'edit_posts',
                'edit_published_posts' => 'edit_posts',
                'create_posts' => 'edit_posts',
            ],
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => null,
            'menu_icon' => 'dashicons-feedback',
            'supports' => ['title'],
            'show_in_rest' => false,
        ];
    }

    /**
     * Add Forms as submenu under CleverReach
     *
     * @return void
     */
    public function addSubmenuPage(): void
    {
        add_submenu_page(
            'cleverreach-settings',           // Parent slug
            __('Forms', 'lexoforms'),                // Page title
            __('Forms', 'lexoforms'),                // Menu title
            'edit_posts',                     // Editor and above can see Forms
            'edit.php?post_type=' . self::POST_TYPE  // Menu slug
        );
    }

    /**
     * Fix active parent menu for CPT pages
     *
     * @param string|null $parent_file
     * @return string|null
     */
    public function fixActiveMenu($parent_file)
    {
        global $current_screen;

        if (!$current_screen) {
            return $parent_file;
        }

        // If we're on any page related to cpt-lexoforms CPT
        if ($current_screen->post_type === self::POST_TYPE) {
            return 'cleverreach-settings';
        }

        return $parent_file;
    }

    /**
     * Fix active submenu for CPT pages
     *
     * @param string|null $submenu_file
     * @return string|null
     */
    public function fixActiveSubmenu($submenu_file)
    {
        global $current_screen;

        if (!$current_screen) {
            return $submenu_file;
        }

        // If we're on any page related to cpt-lexoforms CPT
        if ($current_screen->post_type === self::POST_TYPE) {
            return 'edit.php?post_type=' . self::POST_TYPE;
        }

        return $submenu_file;
    }

    /**
     * Add custom admin columns
     *
     * @param array $columns
     * @return array
     */
    public function addAdminColumns(array $columns): array
    {
        // Check if CR is connected
        $auth = CleverReachAuth::getInstance();
        $isConnected = $auth->isConnected();

        // Remove date column
        unset($columns['date']);

        // Add custom columns
        $columns['template'] = __('HTML Template', 'lexoforms');
        $columns['cr_status'] = __('CR Status', 'lexoforms');

        // Only show CR Form and CR Group columns if connected to CR API
        if ($isConnected) {
            $columns[FIELD_PREFIX . 'cr_id'] = __('CR Form', 'lexoforms');
            $columns[FIELD_PREFIX . 'group'] = __('CR Group', 'lexoforms');
        }

        $columns['shortcode'] = __('Shortcode', 'lexoforms');
        $columns['date'] = __('Date', 'lexoforms'); // Re-add date at the end

        return $columns;
    }

    /**
     * Render custom admin columns
     *
     * @param string $column
     * @param int $post_id
     * @return void
     */
    public function renderAdminColumns(string $column, int $post_id): void
    {
        // OPTIMIZATION: Load CR integration settings once (1 DB query instead of 5 per row)
        static $cr_cache = [];
        if (!isset($cr_cache[$post_id])) {
            $cr_cache[$post_id] = get_field(FIELD_PREFIX . 'cr_integration', $post_id) ?: [];
        }
        $cr_settings = $cr_cache[$post_id];

        // OPTIMIZATION: Load general settings once per row (if needed)
        static $general_cache = [];
        if (!isset($general_cache[$post_id]) && $column === 'template') {
            $general_cache[$post_id] = get_field(FIELD_PREFIX . 'general_settings', $post_id) ?: [];
        }

        switch ($column) {
            case 'template':
                $general_settings = $general_cache[$post_id] ?? [];
                $template_id = $general_settings[FIELD_PREFIX . 'html_template'] ?? '';

                if ($template_id) {
                    // Get template name using TemplateLoader
                    $template_loader = \LEXO\LF\Core\Templates\TemplateLoader::getInstance();
                    $templates = $template_loader->getAvailableTemplates();

                    if (isset($templates[$template_id])) {
                        $template = $templates[$template_id];
                        $template_name = $template['name'];

                        // If template name is multilingual array, use current user language
                        if (is_array($template_name)) {
                            $user_language = FormMessages::getUserLanguage();
                            $template_name = $template_name[$user_language] ?? $template_name['de'] ?? reset($template_name);
                        }

                        echo esc_html($template_name);
                    } else {
                        echo '<span style="color: #d63638;">' . __('Template not found', 'lexoforms') . '</span>';
                    }
                } else {
                    echo '—';
                }
                break;

            case 'cr_status':
                // Check if CR API is connected
                $auth = CleverReachAuth::getInstance();
                $isConnected = $auth->isConnected();

                if (!$isConnected) {
                    echo '<span style="color: #999;">' . __('No API Connection', 'lexoforms') . '</span>';
                    break;
                }

                // Load general settings if not already loaded
                if (!isset($general_cache[$post_id])) {
                    $general_cache[$post_id] = get_field(FIELD_PREFIX . 'general_settings', $post_id) ?: [];
                }
                $general_settings = $general_cache[$post_id];

                $handler_type = $general_settings[FIELD_PREFIX . 'handler_type'] ?? '';
                $cr_status = $cr_settings[FIELD_PREFIX . 'cr_status'] ?? '';
                $cr_enabled = ($handler_type === 'email_and_cr' || $handler_type === 'cr_only');

                if ($cr_enabled) {
                    if ($cr_status && $cr_status !== 'ERROR: Missing required fields') {
                        if (strpos($cr_status, 'ERROR:') === 0) {
                            echo '<span style="color: #d63638;">✗ ' . __('Error', 'lexoforms') . '</span>';
                        } else {
                            echo '<span style="color: #00a32a;">✓ ' . __('Connected', 'lexoforms') . '</span>';
                        }
                    } else {
                        echo '<span style="color: #dba617;">⚠ ' . __('Pending Setup', 'lexoforms') . '</span>';
                    }
                } else {
                    echo '<span style="color: #999;">' . __('Email Only', 'lexoforms') . '</span>';
                }
                break;

            case FIELD_PREFIX . 'cr_id':
                // Load general settings if not already loaded
                if (!isset($general_cache[$post_id])) {
                    $general_cache[$post_id] = get_field(FIELD_PREFIX . 'general_settings', $post_id) ?: [];
                }
                $general_settings = $general_cache[$post_id];
                $handler_type = $general_settings[FIELD_PREFIX . 'handler_type'] ?? '';
                $cr_enabled = ($handler_type === 'email_and_cr' || $handler_type === 'cr_only');

                if (!$cr_enabled) {
                    echo '<span style="color: #999;">—</span>';
                } else {
                    $form_id = $cr_settings[FIELD_PREFIX . 'form_id'] ?? '';
                    if ($form_id) {
                        echo '<code>#' . esc_html($form_id) . '</code>';
                    } else {
                        echo '—';
                    }
                }
                break;

            case FIELD_PREFIX . 'group':
                // Load general settings if not already loaded
                if (!isset($general_cache[$post_id])) {
                    $general_cache[$post_id] = get_field(FIELD_PREFIX . 'general_settings', $post_id) ?: [];
                }
                $general_settings = $general_cache[$post_id];
                $handler_type = $general_settings[FIELD_PREFIX . 'handler_type'] ?? '';
                $cr_enabled = ($handler_type === 'email_and_cr' || $handler_type === 'cr_only');

                if (!$cr_enabled) {
                    echo '<span style="color: #999;">—</span>';
                } else {
                    $group_id = $cr_settings[FIELD_PREFIX . 'group_id'] ?? '';
                    if ($group_id) {
                        echo '<code>#' . esc_html($group_id) . '</code>';
                    } else {
                        echo '—';
                    }
                }
                break;

            case 'shortcode':
                $shortcode = '[lexo_form id="' . $post_id . '"]';
                echo '<code style="background: #f0f0f1; padding: 2px 6px; border-radius: 3px; font-size: 12px; cursor: pointer;" onclick="navigator.clipboard.writeText(\'' . esc_js($shortcode) . '\'); this.style.background=\'#00a32a\'; this.style.color=\'white\'; setTimeout(() => { this.style.background=\'#f0f0f1\'; this.style.color=\'inherit\'; }, 1000);" title="' . esc_attr(__('Click to copy', 'lexoforms')) . '">' . esc_html($shortcode) . '</code>';
                break;
        }
    }

    /**
     * Get post type slug
     *
     * @return string
     */
    public static function getPostType(): string
    {
        return self::POST_TYPE;
    }
}
