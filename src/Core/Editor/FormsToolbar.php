<?php

namespace LEXO\LF\Core\Editor;

use LEXO\LF\Core\Abstracts\Singleton;
use LEXO\LF\Core\PostTypes\FormsPostType;
use LEXO\LF\Core\Loader\Loader;
use LEXO\LF\Core\Utils\FormMessages;

use const LEXO\LF\{
    FIELD_PREFIX,
    DOMAIN
};

/**
 * Forms Toolbar for WYSIWYG Editor
 *
 * Adds Forms toolbar button to WYSIWYG editor and handles shortcode functionality.
 */
class FormsToolbar extends Singleton
{
    protected static $instance = null;

    /**
     * Register hooks
     *
     * @return void
     */
    public function register(): void
    {

        add_action('init', [$this, 'registerShortcode']);
        add_action('admin_head', [$this, 'addTinyMCEPlugin']);
        add_filter('mce_buttons', [$this, 'addTinyMCEButton']);
        add_filter('mce_external_plugins', [$this, 'addTinyMCEScript']);
        add_filter('acf/fields/wysiwyg/toolbars', [$this, 'addFormsButtonToACFToolbars'], 20);
    }

    /**
     * Register the forms shortcode
     *
     * @return void
     */
    public function registerShortcode(): void
    {
        add_shortcode('lexo_form', [$this, 'renderFormShortcode']);
    }

    /**
     * Render the form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function renderFormShortcode($atts): string
    {
        $atts = shortcode_atts([
            'id' => '',
        ], $atts, 'lexo_form');

        if (empty($atts['id'])) {
            return '<p style="color: red;">' . FormMessages::getFormIdRequiredError() . '</p>';
        }

        $form_id = intval($atts['id']);
        $form = get_post($form_id);

        if (!$form || $form->post_type !== FormsPostType::getPostType()) {
            return '<p style="color: red;">' . FormMessages::getFormNotFoundError() . '</p>';
        }

        // Get template ID from general_settings group
        $general_settings = get_field(FIELD_PREFIX . 'general_settings', $form_id) ?: [];
        $template_id = $general_settings[FIELD_PREFIX . 'html_template'] ?? '';
        if (!$template_id) {
            return '<p style="color: red;">' . FormMessages::getFormTemplateNotConfiguredError() . '</p>';
        }

        // Load template using TemplateLoader
        $template_loader = \LEXO\LF\Core\Templates\TemplateLoader::getInstance();
        $template = $template_loader->getTemplateById($template_id, $form_id);

        if (!$template) {
            return '<p style="color: red;">' . FormMessages::getTemplateNotFoundError() . '</p>';
        }

        // Replace {{FORM_ID}} placeholder with actual form ID
        $html = str_replace('{{FORM_ID}}', $form_id, $template['html']);

        return $html;
    }

    /**
     * Add TinyMCE plugin for forms
     *
     * @return void
     */
    public function addTinyMCEPlugin(): void
    {
        global $typenow;

        // Only add on post/page edit screens
        if (!in_array($typenow, ['post', 'page'])) {
            return;
        }

        // Get all forms for JavaScript
        $forms = get_posts([
            'post_type' => FormsPostType::getPostType(),
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        $forms_data = [];
        foreach ($forms as $form) {
            $forms_data[] = [
                'id' => $form->ID,
                'title' => $form->post_title
            ];
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Add forms data for TinyMCE
            window.lexoFormsData = {
                forms: <?php echo json_encode($forms_data); ?>,
                labels: {
                    title: '<?php echo esc_js(__('Forms', 'lexoforms')); ?>',
                    selectForm: '<?php echo esc_js(__('Select a form:', 'lexoforms')); ?>',
                    selectFormLabel: '<?php echo esc_js(__('Select a form:', 'lexoforms')); ?>',
                    selectFormPlaceholder: '<?php echo esc_js(__('— Select a form —', 'lexoforms')); ?>',
                    insert: '<?php echo esc_js(__('Insert', 'lexoforms')); ?>',
                    cancel: '<?php echo esc_js(__('Cancel', 'lexoforms')); ?>',
                    noForms: '<?php echo esc_js(__('No forms found.', 'lexoforms')); ?>',
                    selectFormRequired: '<?php echo esc_js(__('Please select a form first.', 'lexoforms')); ?>'
                }
            };
        });
        </script>
        <?php
    }

    /**
     * Add TinyMCE button
     *
     * @param array $buttons
     * @return array
     */
    public function addTinyMCEButton($buttons): array
    {
        array_push($buttons, 'lexo_forms_button');
        return $buttons;
    }

    /**
     * Add TinyMCE script
     *
     * @param array $plugins
     * @return array
     */
    public function addTinyMCEScript($plugins): array
    {
        $loader = Loader::getInstance();
        $script_url = $loader->getUri(DOMAIN, 'js/admin-lf.js');
        $plugins['lexo_forms'] = $script_url;
        return $plugins;
    }


    /**
     * Add Forms button to ACF WYSIWYG toolbars
     *
     * @param array $toolbars
     * @return array
     */
    public function addFormsButtonToACFToolbars($toolbars): array
    {

        // Default toolbars where forms button should appear
        $default_toolbars = ['Full', 'Full FC'];

        // Additional toolbars can be added via filter
        $additional_toolbars = apply_filters('lexo-forms/forms/toolbar/additional', []);

        // Merge default and additional toolbars
        $target_toolbars = array_merge($default_toolbars, $additional_toolbars);


        foreach ($target_toolbars as $toolbar_name) {
            if (isset($toolbars[$toolbar_name]) && isset($toolbars[$toolbar_name][1])) {
                $toolbars[$toolbar_name][1][] = 'lexo_forms_button';
            }
        }

        return $toolbars;
    }
}
