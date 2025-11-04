<?php

namespace LEXO\LF\Core\Admin;

use LEXO\LF\Core\Abstracts\Singleton;
use LEXO\LF\Core\Handlers\CRSyncHandler;
use LEXO\LF\Core\Services\FormsService;
use LEXO\LF\Core\Services\GroupsService;
use LEXO\LF\Core\Templates\TemplateLoader;
use LEXO\LF\Core\Utils\Logger;
use LEXO\LF\Core\Utils\FormHelpers;
use LEXO\LF\Core\Utils\FormMessages;

use const LEXO\LF\{
    FIELD_PREFIX,
    DOMAIN
};

/**
 * CleverReach Integration
 *
 * Handles CleverReach integration setup and form synchronization.
 * Uses ACF fields for configuration and syncs on post save.
 */
class CleverReachIntegration extends Singleton
{
    protected static $instance = null;

    /**
     * Register hooks
     */
    public function register(): void
    {

        // Load field choices dynamically
        add_filter('acf/load_field/name=lexoform_html_template', [$this, 'loadTemplateChoices']);
        add_filter('acf/load_field/name=lexoform_existing_form', [$this, 'loadFormChoices']);
        add_filter('acf/load_field/name=lexoform_existing_group', [$this, 'loadGroupChoices']);

        // Handle form submission via ACF save
        add_action('acf/save_post', [$this, 'handleFormSave'], 20);

        // Clear cache when post is saved
        add_action('save_post', [$this, 'clearCacheOnSave'], 10, 3);

        // Add meta boxes
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);

        // Auto-clear cache when editing/creating forms
        add_action('load-post.php', [$this, 'autoRefreshCacheOnEdit']);
        add_action('load-post-new.php', [$this, 'autoRefreshCacheOnNew']);
    }

    /**
     * Load template choices for ACF field
     */
    public function loadTemplateChoices($field): array
    {
        $templateLoader = TemplateLoader::getInstance();
        $templates = $templateLoader->getAvailableTemplates() ?: [];

        $choices = [];
        $user_language = FormMessages::getUserLanguage();

        foreach ($templates as $template_id => $template) {
            $template_name = $template['name'];

            // If template name is multilingual array, use current user language
            if (is_array($template_name)) {
                $template_name = $template_name[$user_language] ?? $template_name['de'] ?? reset($template_name);
            }

            $choices[$template_id] = $template_name;
        }

        $field['choices'] = $choices;
        return $field;
    }

    /**
     * Load form choices for ACF field
     */
    public function loadFormChoices($field): array
    {
        $formsService = FormsService::getInstance();
        $cr_forms = $formsService->getForms() ?: [];

        $choices = [];
        foreach ($cr_forms as $form) {
            $choices[$form['id']] = $form['name'] . ' (ID: ' . $form['id'] . ')';
        }

        $field['choices'] = $choices;
        return $field;
    }

    /**
     * Load group choices for ACF field
     */
    public function loadGroupChoices($field): array
    {
        $groupsService = GroupsService::getInstance();
        $cr_groups = $groupsService->getGroups() ?: [];

        $choices = [];
        foreach ($cr_groups as $group) {
            $choices[$group['id']] = $group['name'] . ' (ID: ' . $group['id'] . ')';
        }

        $field['choices'] = $choices;
        return $field;
    }

    /**
     * Handle form save via ACF
     */
    public function handleFormSave($post_id): void
    {
        // Check if this is our custom post type
        if (get_post_type($post_id) !== 'cpt-lexoforms') {
            return;
        }

        // OPTIMIZATION: Load field groups (2 DB queries instead of 15+)
        $general_settings = get_field(FIELD_PREFIX . 'general_settings', $post_id) ?: [];
        $cr_settings = get_field(FIELD_PREFIX . 'cr_integration', $post_id) ?: [];

        // Check if CleverReach integration is enabled (from general_settings group)
        $handler_type = $general_settings[FIELD_PREFIX . 'handler_type'] ?? '';
        if ($handler_type !== 'email_and_cr' && $handler_type !== 'cr_only') {
            return; // CleverReach not enabled, skip
        }

        // Get ACF field values
        $form_action = $cr_settings[FIELD_PREFIX . 'form_action'] ?? '';
        $html_template = $general_settings[FIELD_PREFIX . 'html_template'] ?? '';

        if (!$form_action || !$html_template) {
            // Update cr_status (sub-field in cr_integration group)
            $cr_settings[FIELD_PREFIX . 'cr_status'] = 'ERROR: Missing required fields';
            update_field(FIELD_PREFIX . 'cr_integration', $cr_settings, $post_id);
            return;
        }

        try {
            // Prepare data for CRSyncHandler
            $sync_data = [
                'post_id' => $post_id,
                'form_action' => $form_action,
                'existing_form' => $cr_settings[FIELD_PREFIX . 'existing_form'] ?? '',
                'new_form_name' => $cr_settings[FIELD_PREFIX . 'new_form_name'] ?? '',
                'group_action' => $cr_settings[FIELD_PREFIX . 'group_action'] ?? '',
                'existing_group' => $cr_settings[FIELD_PREFIX . 'existing_group'] ?? '',
                'new_group_name' => $cr_settings[FIELD_PREFIX . 'new_group_name'] ?? '',
                'html_template' => $html_template
            ];

            // Use CRSyncHandler to perform the connection
            $sync_handler = CRSyncHandler::getInstance();
            $result = $sync_handler->performSyncFromData($sync_data);

            // If successful and we created new form/group, switch to "use existing" mode
            if ($result) {
                $this->handlePostSyncFieldUpdate($post_id);

                // Refresh cr_settings to get updated form_id after sync
                $cr_settings = get_field(FIELD_PREFIX . 'cr_integration', $post_id) ?: [];

                // Add success notice via transient
                $form_id = $cr_settings[FIELD_PREFIX . 'form_id'] ?? '';
                $message = '<strong>' . __('CleverReach Connection Successful!', 'lexoforms') . '</strong><br>' .
                           sprintf(__('Form has been connected to CleverReach (Form ID: %s).', 'lexoforms'), esc_html($form_id));

                set_transient(
                    DOMAIN . '_cr_success_notice',
                    $message,
                    HOUR_IN_SECONDS
                );
            }

        } catch (\Exception $e) {
            // Handle any exceptions
            // Update cr_status (sub-field in cr_integration group)
            $cr_settings = get_field(FIELD_PREFIX . 'cr_integration', $post_id) ?: [];
            $cr_settings[FIELD_PREFIX . 'cr_status'] = 'ERROR: ' . $e->getMessage();
            update_field(FIELD_PREFIX . 'cr_integration', $cr_settings, $post_id);

            // Add error notice via transient
            $message = '<strong>' . __('CleverReach Connection Error:', 'lexoforms') . '</strong><br>' .
                       esc_html($e->getMessage()) . '<br><em>' .
                       __('Please check your form configuration and try saving again.', 'lexoforms') . '</em>';

            set_transient(
                DOMAIN . '_cr_error_notice',
                $message,
                HOUR_IN_SECONDS
            );

            Logger::error('CleverReach connection exception for post ID ' . $post_id . ': ' . $e->getMessage(), Logger::CATEGORY_GENERAL);
        }
    }

    /**
     * Clear cache when post is saved
     *
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     * @param bool $update Whether this is an update or new post
     * @return void
     */
    public function clearCacheOnSave($post_id, $post, $update): void
    {
        // Skip autosaves and revisions
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        // Only clear cache for our custom post type
        if ($post->post_type !== 'cpt-lexoforms') {
            return;
        }

        // Skip auto-drafts (new posts that haven't been saved yet)
        if ($post->post_status === 'auto-draft') {
            return;
        }

        // Clear all cache when form is saved
        $this->clearAllCache();
    }

    /**
     * Display CleverReach success notice
     *
     * @return void
     */
    public function displaySuccessNotice(): void
    {
        $message = get_transient(DOMAIN . '_cr_success_notice');
        delete_transient(DOMAIN . '_cr_success_notice');

        if (!$message) {
            return;
        }

        wp_admin_notice($message, [
            'type' => 'success',
            'dismissible' => true
        ]);
    }

    /**
     * Display CleverReach error notice
     *
     * @return void
     */
    public function displayErrorNotice(): void
    {
        $message = get_transient(DOMAIN . '_cr_error_notice');
        delete_transient(DOMAIN . '_cr_error_notice');

        if (!$message) {
            return;
        }

        wp_admin_notice($message, [
            'type' => 'error',
            'dismissible' => true
        ]);
    }

    /**
     * Handle field updates after successful sync
     */
    private function handlePostSyncFieldUpdate($post_id): void
    {
        $cr_settings = get_field(FIELD_PREFIX . 'cr_integration', $post_id) ?: [];
        $form_action = $cr_settings[FIELD_PREFIX . 'form_action'] ?? '';
        $form_id = $cr_settings[FIELD_PREFIX . 'form_id'] ?? '';
        $group_id = $cr_settings[FIELD_PREFIX . 'group_id'] ?? '';

        // If we created a new form, switch to "use_existing" mode
        // All these fields are sub-fields in cr_integration group
        if ($form_action === 'create_new' && $form_id) {
            $cr_settings[FIELD_PREFIX . 'form_action'] = 'use_existing';
            $cr_settings[FIELD_PREFIX . 'existing_form'] = $form_id;
            $cr_settings[FIELD_PREFIX . 'new_form_name'] = '';

            // Also clear group creation fields since we're now using the form's group
            $cr_settings[FIELD_PREFIX . 'group_action'] = '';
            $cr_settings[FIELD_PREFIX . 'existing_group'] = '';
            $cr_settings[FIELD_PREFIX . 'new_group_name'] = '';

            // Update the entire cr_integration group
            update_field(FIELD_PREFIX . 'cr_integration', $cr_settings, $post_id);
        }
    }

    /**
     * Add meta boxes
     */
    public function addMetaBoxes(): void
    {

        add_meta_box(
            'lexoform-shortcode',
            __('Form Shortcode', 'lexoforms'),
            [$this, 'renderShortcodeMetaBox'],
            'cpt-lexoforms',
            'side',
            'default'
        );
    }


    /**
     * Render shortcode meta box
     */
    public function renderShortcodeMetaBox($post): void
    {
        $post_id = $post->ID;
        $shortcode = "[lexo_form id=\"{$post_id}\"]";
        $cr_settings = get_field(FIELD_PREFIX . 'cr_integration', $post_id) ?: [];
        $form_status = $cr_settings[FIELD_PREFIX . 'cr_status'] ?? '';
        $form_id = $cr_settings[FIELD_PREFIX . 'form_id'] ?? '';
        ?>
        <div id="lexoform-shortcode-container">
            <p><strong><?php echo __('Use this shortcode to display the form:', 'lexoforms'); ?></strong></p>

            <input type="text" value="<?php echo esc_attr($shortcode); ?>" readonly
                   style="width: 100%; font-family: monospace; background: #f9f9f9;"
                   onclick="this.select();" />

            <p style="margin-top: 10px;">
                <button type="button" onclick="copyShortcode()" class="button button-secondary" style="width: 100%;">
                    <?php echo __('Copy Shortcode', 'lexoforms'); ?>
                </button>
            </p>
        </div>
        <?php
    }

    /**
     * Auto-refresh cache when editing existing form
     */
    public function autoRefreshCacheOnEdit(): void
    {
        // Check if we have a post ID in the query string
        $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;

        if ($post_id && get_post_type($post_id) === 'cpt-lexoforms') {
            $this->clearAllCache();
        }
    }

    /**
     * Auto-refresh cache when creating new form
     */
    public function autoRefreshCacheOnNew(): void
    {
        $post_type = $_GET['post_type'] ?? '';

        if ($post_type === 'cpt-lexoforms') {
            $this->clearAllCache();
        }
    }

    /**
     * Clear all cache (forms and groups)
     */
    private function clearAllCache(): void
    {
        $formsService = FormsService::getInstance();
        $groupsService = GroupsService::getInstance();

        // Clear ALL caches (list + individual items)
        $formsService->clearAllCache();
        $groupsService->clearAllCache();
    }


    /**
     * Provide localized data for lexoform integration section inside admin-lf.js bundle.
     *
     * @return void
     */
    public function addLexoformIntegrationLocalization(): void
    {
        global $post, $pagenow;

        if ($pagenow !== 'post.php' && $pagenow !== 'post-new.php') {
            return;
        }

        if (!$post || $post->post_type !== 'cpt-lexoforms') {
            return;
        }

        $formsService = FormsService::getInstance();
        $groupsService = GroupsService::getInstance();
        $existing_forms = $formsService->getForms() ?: [];
        $existing_groups = $groupsService->getGroups() ?: [];

        $form_names = [];
        $forms_by_name = [];
        foreach ($existing_forms as $form) {
            $form_names[] = $form['name'];
            $forms_by_name[$form['name']] = $form['id'];
        }

        $group_names = [];
        $groups_by_name = [];
        foreach ($existing_groups as $group) {
            $group_names[] = $group['name'];
            $groups_by_name[$group['name']] = $group['id'];
        }

        wp_localize_script(
            DOMAIN . '/admin-lf.js',
            'lexoformIntegration',
            [
                'post_id' => $post->ID,
                'existing_form_names' => $form_names,
                'existing_group_names' => $group_names,
                'forms_by_name' => $forms_by_name,
                'groups_by_name' => $groups_by_name,
                'i18n' => [
                    'duplicate_form_warning' => __('A form with this name already exists. Do you want to use the existing form instead?', 'lexoforms'),
                    'duplicate_group_warning' => __('A group with this name already exists. Do you want to use the existing group instead?', 'lexoforms'),
                    'yes' => __('Yes, use existing', 'lexoforms'),
                    'no' => __('No, create new', 'lexoforms')
                ]
            ]
        );
    }

}
