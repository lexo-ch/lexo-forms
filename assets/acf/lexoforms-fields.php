<?php

// Use centralized field prefix from constants
use const LEXO\LF\{
    FIELD_PREFIX
};

// Location rules
$location = [
    [
        [
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'cpt-lexoforms',
        ],
    ],
];

// Fields array
$fields = [];

/**
 * ============================================================================
 * SECTION 1: General Settings
 * ============================================================================
 */

// Tab: General
$fields[] = [
    'key' => FIELD_PREFIX . 'tab_general',
    'label' => __('General', 'lexoforms'),
    'name' => '',
    'type' => 'tab',
    'placement' => 'left',
    'endpoint' => 0,
];

// GENERAL SETTINGS GROUP - All general fields grouped together (1 DB query)
$fields[] = [
    'key' => 'field_lexoform_general_settings_group',
    'label' => __('General Configuration', 'lexoforms'),
    'name' => FIELD_PREFIX . 'general_settings',
    'type' => 'group',
    'instructions' => '',
    'required' => 0,
    'layout' => 'block',
    'sub_fields' => [
        // HTML Form Template (Button Group with images)
        [
            'key' => FIELD_PREFIX . 'html_template',
            'label' => __('HTML Form Template', 'lexoforms'),
            'name' => FIELD_PREFIX . 'html_template',
            'type' => 'button_group',
            'instructions' => __('Select HTML form template.', 'lexoforms'),
            'required' => 1,
            'choices' => [], // Will be populated dynamically via acf/load_field hook
            'default_value' => '',
            'return_format' => 'value',
            'layout' => 'horizontal',
            'wrapper' => [
                'class' => 'lexoforms-template-selector',
            ],
        ],
        // Hidden field to track CR connection status
        [
            'key' => FIELD_PREFIX . 'cr_connection_available',
            'label' => '',
            'name' => FIELD_PREFIX . 'cr_connection_available',
            'type' => 'true_false',
            'wrapper' => [
                'class' => 'hidden',
            ],
            'default_value' => 0,
            'ui' => 0,
        ],
        // Email & Integration Method (Button Group)
        [
            'key' => FIELD_PREFIX . 'handler_type',
            'label' => __('Email & Integration Method', 'lexoforms'),
            'name' => FIELD_PREFIX . 'handler_type',
            'type' => 'button_group',
            'instructions' => __('Choose how to handle form submissions - email notifications and/or CleverReach sync. (If not connected to the CleverReach API, only email notifications will be available)', 'lexoforms'),
            'required' => 1,
            'choices' => [], // Will be populated dynamically via acf/load_field hook
            'default_value' => 'email_only',
            'return_format' => 'value',
            'layout' => 'horizontal',
        ],
        // Success Message
        [
            'key' => 'field_success_message',
            'label' => __('Success Message', 'lexoforms'),
            'name' => FIELD_PREFIX . 'success_message',
            'type' => 'textarea',
            'instructions' => __('Message displayed when form is submitted successfully. If empty, default message will be used.', 'lexoforms'),
            'required' => 0,
            'placeholder' => '',
            'rows' => 2,
        ],
        // Error Message
        [
            'key' => 'field_fail_message',
            'label' => __('Error Message', 'lexoforms'),
            'name' => FIELD_PREFIX . 'fail_message',
            'type' => 'textarea',
            'instructions' => __('Message displayed when form submission fails. If empty, default error message will be used.', 'lexoforms'),
            'required' => 0,
            'placeholder' => '',
            'rows' => 2,
        ],
    ],
];

/**
 * ============================================================================
 * SECTION 2: Email Settings
 * ============================================================================
 */

// Tab: Email Settings
$fields[] = [
    'key' => FIELD_PREFIX . 'tab_email',
    'label' => __('Email Settings', 'lexoforms'),
    'name' => '',
    'type' => 'tab',
    'placement' => 'left',
    'endpoint' => 0,
    'conditional_logic' => [
        [
            [
                'field' => FIELD_PREFIX . 'handler_type',
                'operator' => '==',
                'value' => 'email_only',
            ],
        ],
        [
            [
                'field' => FIELD_PREFIX . 'handler_type',
                'operator' => '==',
                'value' => 'email_and_cr',
            ],
        ],
    ],
];

// EMAIL SETTINGS GROUP - All email fields grouped together (1 DB query)
$fields[] = [
    'key' => 'field_lexoform_email_settings_group',
    'label' => __('Email Configuration', 'lexoforms'),
    'name' => FIELD_PREFIX . 'email_settings',
    'type' => 'group',
    'instructions' => '',
    'required' => 0,
    'layout' => 'block',
    'conditional_logic' => [
        [
            [
                'field' => FIELD_PREFIX . 'handler_type',
                'operator' => '==',
                'value' => 'email_only',
            ],
        ],
        [
            [
                'field' => FIELD_PREFIX . 'handler_type',
                'operator' => '==',
                'value' => 'email_and_cr',
            ],
        ],
    ],
    'sub_fields' => [
        // Recipients Override (Repeater)
        [
            'key' => 'field_recipients',
            'label' => __('Email Recipients', 'lexoforms'),
            'name' => FIELD_PREFIX . 'recipients',
            'type' => 'repeater',
            'instructions' => __('Add email addresses to receive form submissions. If empty, theme defaults will be used.', 'lexoforms'),
            'required' => 0,
            'collapsed' => '',
            'min' => 0,
            'max' => 0,
            'layout' => 'table',
            'button_label' => __('Add Recipient', 'lexoforms'),
            'sub_fields' => [
                [
                    'key' => 'field_recipient_email',
                    'label' => __('Email', 'lexoforms'),
                    'name' => FIELD_PREFIX . 'email',
                    'type' => 'email',
                    'instructions' => '',
                    'required' => 1,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'default_value' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                ],
            ],
        ],
        // Email Subject
        [
            'key' => 'field_email_subject',
            'label' => __('Email Subject', 'lexoforms'),
            'name' => FIELD_PREFIX . 'email_subject',
            'type' => 'text',
            'instructions' => __('Subject line for notification emails. If empty, default subject will be used.', 'lexoforms'),
            'required' => 0,
            'placeholder' => '',
        ],
        // Email Sender Email
        [
            'key' => 'field_sender_email',
            'label' => __('Sender Email', 'lexoforms'),
            'name' => FIELD_PREFIX . 'sender_email',
            'type' => 'email',
            'instructions' => __('Email address to send from. If empty, email from theme settings will be used.', 'lexoforms'),
            'required' => 0,
            'placeholder' => '',
        ],
        // Email Sender Name
        [
            'key' => 'field_sender_name',
            'label' => __('Sender Name', 'lexoforms'),
            'name' => FIELD_PREFIX . 'sender_name',
            'type' => 'text',
            'instructions' => __('Name to send from. If empty, site name will be used.', 'lexoforms'),
            'required' => 0,
            'placeholder' => '',
        ],
        // Additional Email Toggle
        [
            'key' => 'field_enable_additional_email',
            'label' => __('Send Email to Visitor', 'lexoforms'),
            'name' => FIELD_PREFIX . 'enable_additional_email',
            'type' => 'true_false',
            'instructions' => __('Send an email to the person who submitted the form.', 'lexoforms'),
            'required' => 0,
            'message' => '',
            'default_value' => 0,
            'ui' => 1,
            'ui_on_text' => __('Enabled', 'lexoforms'),
            'ui_off_text' => __('Disabled', 'lexoforms'),
        ],
        // Additional Email Subject
        [
            'key' => 'field_additional_email_subject',
            'label' => __('Visitor Email Subject', 'lexoforms'),
            'name' => FIELD_PREFIX . 'additional_email_subject',
            'type' => 'text',
            'instructions' => __('Subject line for the email sent to the visitor.', 'lexoforms'),
            'required' => 0,
            'placeholder' => '',
            'conditional_logic' => [
                [
                    [
                        'field' => 'field_enable_additional_email',
                        'operator' => '==',
                        'value' => '1',
                    ],
                    [
                        'field' => 'field_has_visitor_email_variants',
                        'operator' => '!=',
                        'value' => '1',
                    ],
                ],
            ],
        ],
        // Additional Email Sender Email
        [
            'key' => 'field_additional_sender_email',
            'label' => __('Visitor Email Sender', 'lexoforms'),
            'name' => FIELD_PREFIX . 'additional_sender_email',
            'type' => 'email',
            'instructions' => __('Email address to send visitor email from. If empty, form sender email will be used.', 'lexoforms'),
            'required' => 0,
            'placeholder' => '',
            'conditional_logic' => [
                [
                    [
                        'field' => 'field_enable_additional_email',
                        'operator' => '==',
                        'value' => '1',
                    ],
                ],
            ],
        ],
        // Additional Email Sender Name
        [
            'key' => 'field_additional_sender_name',
            'label' => __('Visitor Email Sender Name', 'lexoforms'),
            'name' => FIELD_PREFIX . 'additional_sender_name',
            'type' => 'text',
            'instructions' => __('Name to send visitor email from. If empty, form sender name will be used.', 'lexoforms'),
            'required' => 0,
            'placeholder' => '',
            'conditional_logic' => [
                [
                    [
                        'field' => 'field_enable_additional_email',
                        'operator' => '==',
                        'value' => '1',
                    ],
                ],
            ],
        ],
        // Additional Email Body
        [
            'key' => 'field_additional_email_body',
            'label' => __('Visitor Email Content', 'lexoforms'),
            'name' => FIELD_PREFIX . 'additional_email_body',
            'type' => 'wysiwyg',
            'instructions' => __('Content of the email sent to the visitor.', 'lexoforms'),
            'required' => 0,
            'default_value' => '',
            'tabs' => 'all',
            'toolbar' => 'lexoformsadditionalemail',
            'media_upload' => 1,
            'delay' => 0,
            'conditional_logic' => [
                [
                    [
                        'field' => 'field_enable_additional_email',
                        'operator' => '==',
                        'value' => '1',
                    ],
                    [
                        'field' => 'field_has_visitor_email_variants',
                        'operator' => '!=',
                        'value' => '1',
                    ],
                ],
            ],
        ],
        // Additional Email Document Attachment
        [
            'key' => 'field_additional_email_document',
            'label' => __('Attachment', 'lexoforms'),
            'name' => FIELD_PREFIX . 'additional_email_document',
            'type' => 'file',
            'instructions' => __('Optional document to attach to the visitor email. Maximum file size: 20MB.', 'lexoforms'),
            'required' => 0,
            'return_format' => 'array',
            'library' => 'all',
            'mime_types' => '',
            'max_size' => '20',
            'conditional_logic' => [
                [
                    [
                        'field' => 'field_enable_additional_email',
                        'operator' => '==',
                        'value' => '1',
                    ],
                    [
                        'field' => 'field_has_visitor_email_variants',
                        'operator' => '!=',
                        'value' => '1',
                    ],
                ],
            ],
        ],
        // Visitor Email BCC Recipients (Repeater)
        [
            'key' => 'field_additional_bcc_recipients',
            'label' => __('Visitor Email BCC', 'lexoforms'),
            'name' => FIELD_PREFIX . 'additional_bcc_recipients',
            'type' => 'repeater',
            'instructions' => __('Add email addresses to receive blind carbon copy (BCC) of the visitor email.', 'lexoforms'),
            'required' => 0,
            'collapsed' => '',
            'min' => 0,
            'max' => 0,
            'layout' => 'table',
            'button_label' => __('Add BCC Recipient', 'lexoforms'),
            'conditional_logic' => [
                [
                    [
                        'field' => 'field_enable_additional_email',
                        'operator' => '==',
                        'value' => '1',
                    ],
                ],
            ],
            'sub_fields' => [
                [
                    'key' => 'field_additional_bcc_email',
                    'label' => __('Email', 'lexoforms'),
                    'name' => FIELD_PREFIX . 'bcc_email',
                    'type' => 'email',
                    'instructions' => '',
                    'required' => 1,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'default_value' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                ],
            ],
        ],
        // Hidden field to track if template has visitor email variants
        [
            'key' => 'field_has_visitor_email_variants',
            'label' => '',
            'name' => FIELD_PREFIX . 'has_visitor_email_variants',
            '_name' => FIELD_PREFIX . 'has_visitor_email_variants',
            'type' => 'true_false',
            'wrapper' => [
                'class' => 'acf-hidden',
                'style' => 'display:none !important;',
            ],
            'default_value' => 0,
            'ui' => 1,
        ],
        // VISITOR EMAIL VARIANTS GROUP - Dynamic fields based on template
        [
            'key' => 'field_lexoform_visitor_email_variants_group',
            'label' => __('Visitor Email Variants', 'lexoforms'),
            'name' => FIELD_PREFIX . 'visitor_email_variants',
            '_name' => FIELD_PREFIX . 'visitor_email_variants',
            'type' => 'group',
            'instructions' => __('Configure different email content for each option selected by the visitor.', 'lexoforms'),
            'required' => 0,
            'layout' => 'block',
            'conditional_logic' => [
                [
                    [
                        'field' => 'field_enable_additional_email',
                        'operator' => '==',
                        'value' => '1',
                    ],
                    [
                        'field' => 'field_has_visitor_email_variants',
                        'operator' => '==',
                        'value' => '1',
                    ],
                ],
            ],
            'sub_fields' => [], // Will be populated dynamically via acf/load_field hook
        ],
    ],
];

/**
 * ============================================================================
 * SECTION 4: CleverReach Integration
 * ============================================================================
 */

// Tab: CR Integration
$fields[] = [
    'key' => FIELD_PREFIX . 'tab_integration',
    'label' => __('CleverReach Integration', 'lexoforms'),
    'name' => '',
    'type' => 'tab',
    'placement' => 'left',
    'endpoint' => 0,
    'conditional_logic' => [
        [
            [
                'field' => FIELD_PREFIX . 'cr_connection_available',
                'operator' => '==',
                'value' => '1',
            ],
            [
                'field' => FIELD_PREFIX . 'handler_type',
                'operator' => '==',
                'value' => 'email_and_cr',
            ],
        ],
        [
            [
                'field' => FIELD_PREFIX . 'cr_connection_available',
                'operator' => '==',
                'value' => '1',
            ],
            [
                'field' => FIELD_PREFIX . 'handler_type',
                'operator' => '==',
                'value' => 'cr_only',
            ],
        ],
    ],
];

// CR INTEGRATION GROUP - All CR fields grouped together (1 DB query)
$fields[] = [
    'key' => 'field_lexoform_cr_integration_group',
    'label' => __('CleverReach Configuration', 'lexoforms'),
    'name' => FIELD_PREFIX . 'cr_integration',
    'type' => 'group',
    'instructions' => '',
    'required' => 0,
    'layout' => 'block',
    'conditional_logic' => [
        [
            [
                'field' => FIELD_PREFIX . 'cr_connection_available',
                'operator' => '==',
                'value' => '1',
            ],
        ],
    ],
    'sub_fields' => [
        // Auto-sync message
        [
            'key' => 'field_auto_sync_message',
            'label' => '',
            'name' => '',
            'type' => 'message',
            'message' => '
                <div style="margin-top: -10px; padding: 15px; background: #e7f3ff; border-left: 4px solid #2271b1;"><p style="margin: 0; font-size: 13px; color: #135e96;"><span class="dashicons dashicons-info" style="margin-top: 1px;"></span> ' . __('Connection to CleverReach will be established automatically when you save this form.', 'lexoforms') . '</p></div>
            ',
            'conditional_logic' => [
                [
                    [
                        'field' => 'field_cr_status',
                        'operator' => '!=',
                        'value' => 'OK',
                    ],
                ],
            ],
        ],
        // CR Form Action (radio)
        [
            'key' => 'field_form_action',
            'label' => __('CleverReach Form', 'lexoforms'),
            'name' => FIELD_PREFIX . 'form_action',
            'type' => 'radio',
            'instructions' => __('Choose whether to use an existing CleverReach form or create a new one.', 'lexoforms'),
            'required' => 1,
            'choices' => [
                'use_existing' => __('Use Existing CleverReach Form', 'lexoforms'),
                'create_new' => __('Create New CleverReach Form', 'lexoforms'),
            ],
            'default_value' => 'use_existing',
            'layout' => 'vertical',
        ],
        // Existing CR Form (select) - Conditional A
        [
            'key' => 'field_existing_form',
            'label' => __('Select Existing Form', 'lexoforms'),
            'name' => FIELD_PREFIX . 'existing_form',
            'type' => 'select',
            'instructions' => __('Select an existing CleverReach form. The group connected to this form will be used automatically.', 'lexoforms'),
            'required' => 1,
            'choices' => [], // Populated dynamically
            'default_value' => false,
            'allow_null' => 0,
            'multiple' => 0,
            'ui' => 1,
            'ajax' => 0,
            'return_format' => 'value',
            'conditional_logic' => [
                [
                    [
                        'field' => 'field_form_action',
                        'operator' => '==',
                        'value' => 'use_existing',
                    ],
                ],
            ],
        ],
        // New Form Name - Conditional B
        [
            'key' => 'field_new_form_name',
            'label' => __('New Form Name', 'lexoforms'),
            'name' => FIELD_PREFIX . 'new_form_name',
            'type' => 'text',
            'instructions' => __('Enter a name for the new CleverReach form.', 'lexoforms'),
            'required' => 1,
            'placeholder' => '',
            'conditional_logic' => [
                [
                    [
                        'field' => 'field_form_action',
                        'operator' => '==',
                        'value' => 'create_new',
                    ],
                ],
            ],
        ],
        // CR Group Action (radio) - Conditional B
        [
            'key' => 'field_group_action',
            'label' => __('CleverReach Group', 'lexoforms'),
            'name' => FIELD_PREFIX . 'group_action',
            'type' => 'radio',
            'instructions' => __('Choose whether to use an existing CleverReach group or create a new one.', 'lexoforms'),
            'required' => 1,
            'choices' => [
                'use_existing_group' => __('Use Existing CleverReach Group', 'lexoforms'),
                'create_new_group' => __('Create New CleverReach Group', 'lexoforms'),
            ],
            'default_value' => 'use_existing_group',
            'layout' => 'vertical',
            'conditional_logic' => [
                [
                    [
                        'field' => 'field_form_action',
                        'operator' => '==',
                        'value' => 'create_new',
                    ],
                ],
            ],
        ],
        // Existing CR Group (select) - Conditional B1
        [
            'key' => 'field_existing_group',
            'label' => __('Select Existing Group', 'lexoforms'),
            'name' => FIELD_PREFIX . 'existing_group',
            'type' => 'select',
            'instructions' => __('Select an existing CleverReach group.', 'lexoforms'),
            'required' => 1,
            'choices' => [], // Populated dynamically
            'default_value' => false,
            'allow_null' => 0,
            'multiple' => 0,
            'ui' => 1,
            'ajax' => 0,
            'return_format' => 'value',
            'conditional_logic' => [
                [
                    [
                        'field' => 'field_form_action',
                        'operator' => '==',
                        'value' => 'create_new',
                    ],
                    [
                        'field' => 'field_group_action',
                        'operator' => '==',
                        'value' => 'use_existing_group',
                    ],
                ],
            ],
        ],
        // New Group Name - Conditional B2
        [
            'key' => 'field_new_group_name',
            'label' => __('New Group Name', 'lexoforms'),
            'name' => FIELD_PREFIX . 'new_group_name',
            'type' => 'text',
            'instructions' => __('Enter a name for the new CleverReach group.', 'lexoforms'),
            'required' => 1,
            'placeholder' => '',
            'conditional_logic' => [
                [
                    [
                        'field' => 'field_form_action',
                        'operator' => '==',
                        'value' => 'create_new',
                    ],
                    [
                        'field' => 'field_group_action',
                        'operator' => '==',
                        'value' => 'create_new_group',
                    ],
                ],
            ],
        ],
        // CR Connection Info Display (Message field with links)
        [
            'key' => 'field_cr_connection_info',
            'label' => __('CleverReach Connection', 'lexoforms'),
            'name' => '',
            'type' => 'message',
            'message' => '', // Will be populated dynamically via acf/load_field hook
            'new_lines' => '',
            'esc_html' => 0,
        ],
        // Send Double Opt-in Link
        [
            'key' => 'field_send_double_opt_link',
            'label' => __('Send Double opt link', 'lexoforms'),
            'name' => FIELD_PREFIX . 'send_double_opt_link',
            'type' => 'true_false',
            'instructions' => __('Enable to send double opt-in confirmation email to new subscribers. Disable to skip the confirmation process.', 'lexoforms'),
            'required' => 0,
            'message' => '',
            'default_value' => 1,
            'ui' => 1,
            'ui_on_text' => __('Enabled', 'lexoforms'),
            'ui_off_text' => __('Disabled', 'lexoforms'),
        ],
        // CR Status (hidden - for data storage)
        [
            'key' => 'field_cr_status',
            'label' => '',
            'name' => FIELD_PREFIX . 'cr_status',
            'type' => 'text',
            'wrapper' => [
                'class' => 'hidden',
            ],
        ],
        // CR Form ID (hidden - for data storage)
        [
            'key' => 'field_form_id',
            'label' => '',
            'name' => FIELD_PREFIX . 'form_id',
            'type' => 'text',
            'wrapper' => [
                'class' => 'hidden',
            ],
        ],
        // CR Group ID (hidden - for data storage)
        [
            'key' => 'field_group_id',
            'label' => '',
            'name' => FIELD_PREFIX . 'group_id',
            'type' => 'text',
            'wrapper' => [
                'class' => 'hidden',
            ],
        ],
    ],
];

/**
 * ============================================================================
 * Return Field Group Definition
 * ============================================================================
 */

return [
    'key' => 'group_lexo_cr_lexoforms',
    'title' => __('Form Configuration', 'lexoforms'),
    'location' => $location,
    'menu_order' => 0,
    'position' => 'normal',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
    'hide_on_screen' => '',
    'active' => true,
    'description' => '',
    'show_in_rest' => 0,
    'fields' => $fields,
];
