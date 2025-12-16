<?php

use LEXO\LF\Core\Utils\FormHelpers;

// Form name (displayed in ACF select dropdown) - supports multilingual
$name = [
    'de' => 'Newsletter-Abonnement',
    'en' => 'Newsletter Subscription',
    'fr' => 'Abonnement à la newsletter',
    'it' => 'Iscrizione alla newsletter'
];

// CleverReach Group attributes (fields that must exist in CR group)
$fields = [
    [
        'name' => 'email',
        'type' => 'text',
        'html_type' => 'input',
        'input_type' => 'email',
        'label' => [
            'de' => 'E-Mail',
            'en' => 'E-mail',
            'fr' => 'E-mail',
            'it' => 'E-mail'
        ],
        'placeholder' => [
            'de' => 'E-Mail',
            'en' => 'E-mail',
            'fr' => 'E-mail',
            'it' => 'E-mail'
        ],
        'required' => true,
        'email_label' => [
            'de' => 'E-Mail',
            'en' => 'E-mail',
            'fr' => 'E-mail',
            'it' => 'E-mail'
        ],
        'cr_description' => 'E-Mail',
        'global' => false,
        'send_to_cr' => true
    ]
];

// Submit button text
$submit_button = [
    'de' => 'Abonnieren',
    'en' => 'Subscribe',
    'fr' => 'S\'abonner',
    'it' => 'Iscriviti'
];

// Grid columns configuration (filterable)
$grid_columns = apply_filters('lexo-forms/forms/grid/columns', 1, 'newsletter');
$grid_classes = apply_filters(
    'lexo-forms/forms/grid/classes',
    "form-fields-wrapper grid-view grid-view-{$grid_columns}-columns",
    'newsletter',
    $grid_columns
);

// Form HTML
ob_start();
?>
    <div class="newsletter-form-wrapper">
        <form class="newsletter-form" data-action="lexo-form" data-form-type="newsletter">
            <input type="hidden" name="form_id" value="{{FORM_ID}}" readonly>
            <div class="<?php echo esc_attr($grid_classes); ?>">
                <?php foreach ($fields as $field) { ?>
                    <?php FormHelpers::renderField($field); ?>
                <?php } ?>
            </div>
            <div class="form-submit">
                <button type="submit" class="btn btn-primary submitable">
                    <?php echo esc_html(FormHelpers::getTranslatedText($submit_button)); ?>
                </button>
            </div>
        </form>
    </div>
<?php
$html = ob_get_clean();

// Return template definition
return [
    'name' => $name,
    'fields' => $fields,
    'html' => $html,
];
