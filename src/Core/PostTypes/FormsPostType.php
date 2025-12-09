<?php

namespace LEXO\LF\Core\PostTypes;

use LEXO\LF\Core\Abstracts\Singleton;
use LEXO\LF\Core\Plugin\CleverReachAuth;
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

        // Hard delete (skip trash) and custom delete confirmation
        add_filter('pre_trash_post', [$this, 'forceHardDelete'], 10, 2);
        add_filter('post_row_actions', [$this, 'modifyRowActions'], 10, 2);
        add_filter('bulk_actions-edit-' . self::POST_TYPE, [$this, 'modifyBulkActions']);
        add_action('admin_footer-edit.php', [$this, 'addDeleteConfirmationModal']);
        add_action('admin_footer-post.php', [$this, 'addDeleteConfirmationModal']);
        add_action('admin_enqueue_scripts', [$this, 'localizeDeleteScript'], 100);
        add_action('wp_ajax_lexoforms_get_usage', [$this, 'ajaxGetFormUsage']);
        add_action('admin_action_lexoforms_clone', [$this, 'handleCloneForm']);

        // Clear usage cache on any post save (content might have changed)
        add_action('save_post', [$this, 'clearUsageCache'], 10, 2);

        // Hide Trash and Draft views, redirect if accessed directly
        add_filter('views_edit-' . self::POST_TYPE, [$this, 'removeUnusedViews']);
        add_action('load-edit.php', [$this, 'redirectFromUnusedViews']);

        // Custom publish metabox (hide native via CSS, add custom with Save/Clone/Delete)
        add_action('add_meta_boxes_' . self::POST_TYPE, [$this, 'setupCustomMetaboxes']);

        // Disable autosave for this CPT
        add_action('admin_enqueue_scripts', [$this, 'disableAutosave']);

        // Force publish status (no drafts)
        add_filter('wp_insert_post_data', [$this, 'forcePublishStatus'], 10, 2);

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
        $columns['used_on'] = __('Used on', 'lexoforms');
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
                        $cr_form_url = 'https://eu1.cleverreach.com/admin/forms_layout_create.php?id=' . esc_attr($form_id);
                        echo '<a href="' . esc_url($cr_form_url) . '" target="_blank" rel="noopener" title="' . esc_attr(__('Open in CleverReach', 'lexoforms')) . '"><code style="color: #2271b1; cursor: pointer;">#' . esc_html($form_id) . '</code></a>';
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
                        $cr_group_url = 'https://eu1.cleverreach.com/admin/customer_view.php?id=' . esc_attr($group_id);
                        echo '<a href="' . esc_url($cr_group_url) . '" target="_blank" rel="noopener" title="' . esc_attr(__('Open in CleverReach', 'lexoforms')) . '"><code style="color: #2271b1; cursor: pointer;">#' . esc_html($group_id) . '</code></a>';
                    } else {
                        echo '—';
                    }
                }
                break;

            case 'shortcode':
                $shortcode = '[lexo_form id="' . $post_id . '"]';
                echo '<code class="lexoforms-shortcode-copy" data-shortcode="' . esc_attr($shortcode) . '" title="' . esc_attr(__('Click to copy', 'lexoforms')) . '">' . esc_html($shortcode) . '</code>';
                break;

            case 'used_on':
                $locations = $this->findShortcodeLocations($post_id);
                if (!empty($locations)) {
                    $links = [];
                    foreach ($locations as $location) {
                        $links[] = '<a href="' . esc_url($location['edit_url']) . '" title="' . esc_attr($location['post_type_label']) . '">' . esc_html($location['title']) . '</a>';
                    }
                    echo implode(', ', $links);
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;
        }
    }

    /**
     * Search for shortcode in a value recursively
     *
     * @param mixed $value
     * @param string $shortcode
     * @return bool
     */
    private function searchShortcodeRecursive($value, string $shortcode): bool
    {
        if (is_string($value)) {
            if (strpos($value, '[' . $shortcode) !== false) {
                return true;
            }
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->searchShortcodeRecursive($item, $shortcode)) {
                    return true;
                }
            }
        }

        if (is_object($value)) {
            $value = (array) $value;
            foreach ($value as $item) {
                if ($this->searchShortcodeRecursive($item, $shortcode)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Find all locations where a form shortcode is used
     * Cache is populated only when visiting Forms list and cleared on any post save
     *
     * @param int $form_id
     * @return array
     */
    private function findShortcodeLocations(int $form_id): array
    {
        // Use transient cache (no expiration - cleared on save_post)
        $cache_key = "lexoforms_used_on_{$form_id}";
        $cached = get_transient($cache_key);

        // Return cached data if available
        if ($cached !== false) {
            return $cached;
        }

        $shortcode_name = "lexo_form id=\"{$form_id}\"";
        $locations = [];

        $post_types = get_post_types(['public' => true], 'names');
        $args = [
            'post_type' => $post_types,
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'private'],
            'fields' => 'ids',
        ];

        $post_ids = get_posts($args);

        foreach ($post_ids as $pid) {
            $post = get_post($pid);
            $found = false;

            // Check post content
            if (strpos($post->post_content, "[{$shortcode_name}") !== false) {
                $found = true;
            }

            // Check post meta (ACF fields, page builders, etc.)
            if (!$found) {
                $meta = get_post_meta($pid);
                foreach ($meta as $meta_values) { // phpcs:ignore -- $meta_key not needed
                    foreach ($meta_values as $meta_value) {
                        $value = maybe_unserialize($meta_value);
                        if ($this->searchShortcodeRecursive($value, $shortcode_name)) {
                            $found = true;
                            break 2;
                        }
                    }
                }
            }

            if ($found) {
                $locations[] = [
                    'post_id' => $pid,
                    'title' => get_the_title($pid) ?: __('(no title)', 'lexoforms'),
                    'post_type' => $post->post_type,
                    'post_type_label' => $this->getPostTypeLabel($post->post_type),
                    'edit_url' => admin_url("post.php?post={$pid}&action=edit"),
                ];
            }
        }

        // Cache without expiration (0 = no expiration)
        set_transient($cache_key, $locations, 0);

        return $locations;
    }

    /**
     * Get human-readable post type label
     *
     * @param string $post_type Post type slug
     * @return string
     */
    private function getPostTypeLabel(string $post_type): string
    {
        $post_type_obj = get_post_type_object($post_type);

        if ($post_type_obj) {
            return $post_type_obj->labels->singular_name;
        }

        return $post_type;
    }

    /**
     * Clear all form usage cache when any post is saved
     * This ensures the "Used on" column is always accurate
     *
     * @param int $post_id
     * @param \WP_Post $post
     * @return void
     */
    public function clearUsageCache(int $post_id, $post): void
    {
        // Skip autosaves and revisions
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        // Get all lexoforms posts and clear their usage cache
        $forms = get_posts([
            'post_type' => self::POST_TYPE,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'any',
        ]);

        foreach ($forms as $form_id) {
            delete_transient("lexoforms_used_on_{$form_id}");
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

    /**
     * Force hard delete (skip trash) for Forms
     *
     * @param bool|null $trash Whether to trash the post
     * @param \WP_Post $post Post object
     * @return bool|null
     */
    public function forceHardDelete($trash, $post)
    {
        if ($post->post_type === self::POST_TYPE) {
            // Clear the usage cache before deleting
            delete_transient("lexoforms_used_on_{$post->ID}");

            // Force permanent delete
            wp_delete_post($post->ID, true);

            // Redirect back to list
            wp_safe_redirect(admin_url('edit.php?post_type=' . self::POST_TYPE . '&deleted=1'));
            exit;
        }

        return $trash;
    }

    /**
     * Modify row actions in admin list
     *
     * @param array $actions Row actions
     * @param \WP_Post $post Post object
     * @return array
     */
    public function modifyRowActions(array $actions, $post): array
    {
        if ($post->post_type !== self::POST_TYPE) {
            return $actions;
        }

        // Remove trash and quick edit actions
        unset($actions['trash'], $actions['inline hide-if-no-js']);

        // Add clone action only for published forms
        if ($post->post_status === 'publish') {
            $clone_url = admin_url("admin.php?action=lexoforms_clone&post={$post->ID}&_wpnonce=" . wp_create_nonce('lexoforms_clone_' . $post->ID));
            $actions['clone'] = sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                esc_url($clone_url),
                esc_attr(sprintf(__('Clone "%s"', 'lexoforms'), get_the_title($post->ID))),
                __('Clone', 'lexoforms')
            );
        }

        // Add permanent delete with confirmation
        $actions['delete'] = sprintf(
            '<a href="%s" class="lexoforms-delete-link submitdelete" data-post-id="%d" aria-label="%s">%s</a>',
            get_delete_post_link($post->ID, '', true),
            $post->ID,
            esc_attr(sprintf(__('Delete "%s" permanently', 'lexoforms'), get_the_title($post->ID))),
            __('Delete', 'lexoforms')
        );

        return $actions;
    }

    /**
     * Modify bulk actions
     *
     * @param array $actions Bulk actions
     * @return array
     */
    public function modifyBulkActions(array $actions): array
    {
        // Remove "Move to Trash" and "Edit"
        unset($actions['trash'], $actions['edit']);

        // Add permanent delete
        $actions['delete'] = __('Delete Permanently', 'lexoforms');

        return $actions;
    }

    /**
     * Remove Trash and Draft views from post list
     *
     * @param array $views Available views
     * @return array
     */
    public function removeUnusedViews(array $views): array
    {
        unset($views['trash'], $views['draft']);
        return $views;
    }

    /**
     * Redirect from Trash or Draft view to main list
     *
     * @return void
     */
    public function redirectFromUnusedViews(): void
    {
        global $typenow;

        if ($typenow !== self::POST_TYPE) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $status = $_GET['post_status'] ?? '';
        if ($status === 'trash' || $status === 'draft') {
            wp_safe_redirect(admin_url('edit.php?post_type=' . self::POST_TYPE));
            exit;
        }
    }

    /**
     * Disable autosave for this CPT
     *
     * @return void
     */
    public function disableAutosave(): void
    {
        global $post_type;

        if ($post_type === self::POST_TYPE) {
            wp_dequeue_script('autosave');
        }
    }

    /**
     * Force publish status - prevent draft status for this CPT
     *
     * @param array $data Post data
     * @param array $postarr Post array with additional data
     * @return array
     */
    public function forcePublishStatus(array $data, array $postarr): array
    {
        // Only apply to our CPT
        if ($data['post_type'] !== self::POST_TYPE) {
            return $data;
        }

        // Skip auto-draft (new post creation)
        if ($data['post_status'] === 'auto-draft') {
            return $data;
        }

        // Force publish status instead of draft
        if ($data['post_status'] === 'draft') {
            $data['post_status'] = 'publish';
        }

        return $data;
    }

    /**
     * Setup custom metaboxes - remove native publish box and add custom one
     *
     * @return void
     */
    public function setupCustomMetaboxes(): void
    {
        // Don't remove submitdiv - we need it for native form submission
        // It's hidden via CSS but remains functional

        // Add custom Form Actions metabox
        add_meta_box(
            'lexoforms_actions',
            __('Form Actions', 'lexoforms'),
            [$this, 'renderActionsMetabox'],
            self::POST_TYPE,
            'side',
            'high'
        );
    }

    /**
     * Render custom Form Actions metabox
     *
     * @param \WP_Post $post
     * @return void
     */
    public function renderActionsMetabox($post): void
    {
        $is_new = $post->post_status === 'auto-draft';
        $delete_url = get_delete_post_link($post->ID, '', true);
        $clone_url = admin_url("admin.php?action=lexoforms_clone&post={$post->ID}&_wpnonce=" . wp_create_nonce('lexoforms_clone_' . $post->ID));
        ?>
        <div class="lexoforms-actions-metabox">
            <button type="button" id="lexoforms-save-btn" class="button button-primary button-large">
                <?php echo $is_new ? esc_html__('Save Form', 'lexoforms') : esc_html__('Update Form', 'lexoforms'); ?>
            </button>

            <?php if (!$is_new) { ?>
                <a href="<?php echo esc_url($clone_url); ?>" class="button button-secondary">
                    <?php esc_html_e('Clone Form', 'lexoforms'); ?>
                </a>

                <a href="<?php echo esc_url($delete_url); ?>" class="button button-secondary button-delete lexoforms-delete-link" data-post-id="<?php echo esc_attr($post->ID); ?>">
                    <?php esc_html_e('Delete Form', 'lexoforms'); ?>
                </a>
            <?php } ?>
        </div>
        <?php
    }

    /**
     * Handle form cloning
     *
     * @return void
     */
    public function handleCloneForm(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;

        if (!$post_id) {
            wp_die(__('No form to clone.', 'lexoforms'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'lexoforms_clone_' . $post_id)) {
            wp_die(__('Security check failed.', 'lexoforms'));
        }

        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied.', 'lexoforms'));
        }

        $original = get_post($post_id);

        if (!$original || $original->post_type !== self::POST_TYPE) {
            wp_die(__('Invalid form.', 'lexoforms'));
        }

        // Prevent cloning draft forms - only published forms can be cloned
        if ($original->post_status !== 'publish') {
            wp_die(__('Only published forms can be cloned. Please save this form first.', 'lexoforms'));
        }

        // Create new post as publish (clone is complete copy, ready to use)
        $new_post_id = wp_insert_post([
            'post_title' => sprintf(__('%s (Copy)', 'lexoforms'), $original->post_title),
            'post_status' => 'publish',
            'post_type' => self::POST_TYPE,
            'post_author' => get_current_user_id(),
        ]);

        if (is_wp_error($new_post_id)) {
            wp_die($new_post_id->get_error_message());
        }

        // Copy all post meta (including CR settings)
        $meta = get_post_meta($post_id);
        foreach ($meta as $key => $values) {
            foreach ($values as $value) {
                add_post_meta($new_post_id, $key, maybe_unserialize($value));
            }
        }

        // Keep CR settings - cloned form uses existing CR form connection
        $cr_integration = get_field(FIELD_PREFIX . 'cr_integration', $new_post_id) ?: [];
        if (!empty($cr_integration[FIELD_PREFIX . 'form_id'])) {
            $cr_integration[FIELD_PREFIX . 'form_action'] = 'use_existing';
            update_field(FIELD_PREFIX . 'cr_integration', $cr_integration, $new_post_id);
        }

        // Redirect to edit the new form
        wp_safe_redirect(admin_url("post.php?post={$new_post_id}&action=edit&cloned=1"));
        exit;
    }

    /**
     * AJAX handler to get form usage locations
     *
     * @return void
     */
    public function ajaxGetFormUsage(): void
    {
        check_ajax_referer('lexoforms_delete_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

        if (!$post_id || get_post_type($post_id) !== self::POST_TYPE) {
            wp_send_json_error(['message' => __('Invalid form ID', 'lexoforms')]);
        }

        if (!current_user_can('delete_post', $post_id)) {
            wp_send_json_error(['message' => __('Permission denied', 'lexoforms')]);
        }

        $locations = $this->findShortcodeLocations($post_id);
        $count = count($locations);

        wp_send_json_success([
            'count' => $count,
            'html' => $this->renderUsageModalContent($locations, $count),
        ]);
    }

    /**
     * Render the modal content HTML for form usage
     *
     * @param array $locations Array of locations where form is used
     * @param int $count Number of locations
     * @return string
     */
    private function renderUsageModalContent(array $locations, int $count): string
    {
        ob_start();
        ?>
        <h2><?php esc_html_e('Delete Form?', 'lexoforms'); ?></h2>

        <?php if ($count > 0) { ?>
            <p class="warning-text">
                <?php
                printf(
                    /* translators: %d: number of pages */
                    esc_html__('This form is used on %d page(s):', 'lexoforms'),
                    $count
                );
                ?>
            </p>
            <div class="usage-list">
                <ol>
                    <?php foreach ($locations as $location) { ?>
                        <li>
                            <a href="<?php echo esc_url($location['edit_url']); ?>" target="_blank">
                                <?php echo esc_html($location['title']); ?>
                            </a>
                            <span class="post-type-label">(<?php echo esc_html($location['post_type_label']); ?>)</span>
                        </li>
                    <?php } ?>
                </ol>
            </div>
        <?php } else { ?>
            <p class="warning-text"><?php esc_html_e('This form is not used anywhere.', 'lexoforms'); ?></p>
        <?php } ?>

        <p class="confirm-text"><?php esc_html_e('This action cannot be undone.', 'lexoforms'); ?></p>

        <div class="button-group">
            <button type="button" class="button button-secondary" id="lexoforms-cancel-delete">
                <?php esc_html_e('Cancel', 'lexoforms'); ?>
            </button>
            <button type="button" class="button button-delete" id="lexoforms-confirm-delete">
                <?php esc_html_e('Delete Permanently', 'lexoforms'); ?>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Localize script data for delete confirmation
     * Called on admin_enqueue_scripts to ensure data is available before script runs
     *
     * @return void
     */
    public function localizeDeleteScript(): void
    {
        // Check if we're on the Forms CPT screens
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== self::POST_TYPE) {
            return;
        }

        wp_localize_script(
            'lexoforms/admin-lf.js',
            'lexoformsAdmin',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'deleteNonce' => wp_create_nonce('lexoforms_delete_nonce'),
                'i18n' => [
                    'loading' => __('Checking usage...', 'lexoforms'),
                    'copied' => __('✅ Copied!', 'lexoforms'),
                ],
            ]
        );
    }

    /**
     * Add delete confirmation modal HTML
     *
     * @return void
     */
    public function addDeleteConfirmationModal(): void
    {
        global $current_screen;

        if (!$current_screen || $current_screen->post_type !== self::POST_TYPE) {
            return;
        }

        ?>
        <div class="lexoforms-modal-overlay" id="lexoforms-delete-modal">
            <div class="lexoforms-modal">
                <div class="loading">
                    <span class="spinner is-active" style="float: none;"></span>
                    <?php esc_html_e('Checking usage...', 'lexoforms'); ?>
                </div>
            </div>
        </div>
        <?php
    }
}
