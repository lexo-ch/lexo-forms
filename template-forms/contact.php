<?php

use LEXO\LF\Core\Utils\FormHelpers;

// Form name (displayed in ACF select dropdown) - supports multilingual
$name = [
    'de' => 'Kontaktformular',
    'en' => 'Contact Form',
    'fr' => 'Formulaire de contact',
    'it' => 'Modulo di contatto'
];

// CleverReach Group attributes (fields that must exist in CR group)
$fields = [
    [
        'name' => 'firstname',
        'type' => 'text',
        'html_type' => 'input',
        'input_type' => 'text',
        'label' => [
            'de' => 'Name',
            'en' => 'Name',
            'fr' => 'Prénom',
            'it' => 'Nome'
        ],
        'placeholder' => [
            'de' => 'Name',
            'en' => 'Name',
            'fr' => 'Prénom',
            'it' => 'Nome'
        ],
        'required' => true,
        'email_label' => [
            'de' => 'Name',
            'en' => 'Name',
            'fr' => 'Prénom',
            'it' => 'Nome'
        ],
        'cr_description' => 'Name',
        'global' => false,
        'send_to_cr' => true
    ],
    [
        'name' => 'lastname',
        'type' => 'text',
        'html_type' => 'input',
        'input_type' => 'text',
        'label' => [
            'de' => 'Nachname',
            'en' => 'Surname',
            'fr' => 'Nom de famille',
            'it' => 'Cognome'
        ],
        'placeholder' => [
            'de' => 'Nachname',
            'en' => 'Surname',
            'fr' => 'Nom de famille',
            'it' => 'Cognome'
        ],
        'required' => true,
        'email_label' => [
            'de' => 'Nachname',
            'en' => 'Surname',
            'fr' => 'Nom de famille',
            'it' => 'Cognome'
        ],
        'cr_description' => 'Nachname',
        'global' => false,
        'send_to_cr' => true
    ],
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
    ],
    [
        'name' => 'phone',
        'type' => 'text',
        'html_type' => 'input',
        'input_type' => 'tel',
        'label' => [
            'de' => 'Telefon',
            'en' => 'Phone',
            'fr' => 'Téléphone',
            'it' => 'Telefono'
        ],
        'placeholder' => [
            'de' => 'Telefon',
            'en' => 'Phone',
            'fr' => 'Téléphone',
            'it' => 'Telefono'
        ],
        'required' => false,
        'email_label' => [
            'de' => 'Telefon',
            'en' => 'Phone',
            'fr' => 'Téléphone',
            'it' => 'Telefono'
        ],
        'cr_description' => 'Telefon',
        'global' => false,
        'send_to_cr' => true
    ],
    [
        'name' => 'message',
        'type' => 'text',
        'html_type' => 'textarea',
        'label' => [
            'de' => 'Ihre Nachricht',
            'en' => 'Your Message',
            'fr' => 'Votre message',
            'it' => 'Il tuo messaggio'
        ],
        'placeholder' => [
            'de' => 'Ihre Nachricht',
            'en' => 'Your Message',
            'fr' => 'Votre message',
            'it' => 'Il tuo messaggio'
        ],
        'required' => true,
        'email_label' => [
            'de' => 'Nachricht',
            'en' => 'Message',
            'fr' => 'Message',
            'it' => 'Messaggio'
        ],
        'cr_description' => 'Message',
        'global' => false,
        'send_to_cr' => false
    ],
];

// Submit button text
$submit_button = [
    'de' => 'Senden',
    'en' => 'Send',
    'fr' => 'Envoyer',
    'it' => 'Invia'
];

// Grid columns configuration (filterable)
$grid_columns = apply_filters('lexo-forms/forms/grid/columns', 2, 'contact');
$grid_classes = apply_filters(
    'lexo-forms/forms/grid/classes',
    "form-fields-wrapper grid-view grid-view-{$grid_columns}-columns",
    'contact',
    $grid_columns
);

// Privacy policy link (filterable from theme)
$privacy_url = apply_filters('lexo-forms/forms/contact/privacy_url', '');
$privacy_text = apply_filters('lexo-forms/forms/contact/privacy_text', [
    'de' => 'Datenschutz',
    'en' => 'Privacy Policy',
    'fr' => 'Politique de confidentialité',
    'it' => 'Informativa sulla privacy'
]);
$privacy_message = apply_filters('lexo-forms/forms/contact/privacy_message', [
    'de' => ' ist uns wichtig.',
    'en' => ' is important to us.',
    'fr' => ' est importante pour nous.',
    'it' => ' è importante per noi.'
]);

// Form HTML
ob_start();
?>
    <div class="contact-form-wrapper">
        <form class="contact-form" data-action="lexo-form" data-form-type="contact">
            <input type="hidden" name="form_id" value="{{FORM_ID}}" class="send_field" readonly>
            <div class="<?php echo esc_attr($grid_classes); ?>">
                <?php foreach ($fields as $field) { ?>
                    <?php FormHelpers::renderField($field); ?>
                <?php } ?>
            </div>
            <div class="form-submit">
                <?php if (!empty($privacy_url) && filter_var($privacy_url, FILTER_VALIDATE_URL)) : ?>
                    <div class="privacy-notice">
                        <a href="<?php echo esc_url($privacy_url); ?>" target="_blank" rel="noopener noreferrer">
                            <?php echo esc_html(FormHelpers::getTranslatedText($privacy_text)); ?>
                        </a><?php echo esc_html(FormHelpers::getTranslatedText($privacy_message)); ?>
                    </div>
                <?php endif; ?>
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
