<?php

namespace LEXO\LF\Core\Editor;

use LEXO\LF\Core\Abstracts\Singleton;

use const LEXO\LF\FIELD_PREFIX;

/**
 * Additional Email Toolbar for WYSIWYG Editor
 *
 * Provides a custom toolbar configuration specifically for the additional email body field.
 * This toolbar is optimized for email content editing with text formatting, links,
 * and basic styling suitable for confirmation email messages.
 */
class AdditionalEmailToolbar extends Singleton
{
    protected static $instance = null;

    /**
     * Unique toolbar name to avoid conflicts
     * Note: ACF toolbar names cannot contain spaces
     */
    const TOOLBAR_NAME = 'LexoFormsAdditionalEmail';

    /**
     * Register hooks
     *
     * @return void
     */
    public function register(): void
    {
        add_filter('acf/fields/wysiwyg/toolbars', [$this, 'addAdditionalEmailToolbar'], 10);
    }

    /**
     * Add Additional Email toolbar to ACF WYSIWYG toolbars
     *
     * @param array $toolbars
     * @return array
     */
    public function addAdditionalEmailToolbar($toolbars): array
    {
        // Define the default additional email toolbar configuration
        // All buttons in a single row for compact display
        $default_toolbar = [
            1 => [
                'formatselect',
                'bold',
                'italic',
                'underline',
                'bullist',
                'numlist',
                'link',
                'unlink',
                'alignleft',
                'aligncenter',
                'alignright',
                'removeformat',
                'pastetext',
                'undo',
                'redo',
            ]
        ];

        /**
         * Filter to customize LexoForms additional email toolbar buttons
         *
         * Allows developers to modify the toolbar configuration for additional email body field.
         *
         * @param array $toolbar The toolbar configuration with row 1 and row 2 buttons
         * @return array Modified toolbar configuration
         *
         * @example
         * add_filter('lexo-forms/forms/toolbar/email/buttons', function($toolbar) {
         *     // Add strikethrough to row 1
         *     $toolbar[1][] = 'strikethrough';
         *
         *     // Remove forecolor from row 1
         *     $toolbar[1] = array_diff($toolbar[1], ['forecolor']);
         *
         *     // Add backcolor to row 2
         *     $toolbar[2][] = 'backcolor';
         *
         *     return $toolbar;
         * });
         */
        $toolbar = apply_filters('lexo-forms/forms/toolbar/email/buttons', $default_toolbar);

        // Add the toolbar to available toolbars with unique name
        $toolbars[self::TOOLBAR_NAME] = $toolbar;

        return $toolbars;
    }

    /**
     * Get the toolbar name for use in ACF field configuration
     *
     * @return string
     */
    public static function getToolbarName(): string
    {
        return self::TOOLBAR_NAME;
    }
}
