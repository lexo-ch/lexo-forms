# Form Messages Filters

All form messages in the plugin can be customized via WordPress filters. This allows you to override default messages without modifying the plugin code.

## Location

All messages are centralized in: `src/Core/Utils/FormMessages.php`

## Available Filters

### General Form Messages

#### Success/Failure Messages

```php
// Override success message
add_filter('lexo_cr_message_success', function($message) {
    return 'Your form has been submitted successfully!';
});

// Override general fail message
add_filter('lexo_cr_message_fail', function($message) {
    return 'Oops! Something went wrong. Please try again.';
});

// Override email sending failure message
add_filter('lexo_cr_message_email_fail', function($message) {
    return 'Failed to send notification email';
});
```

#### Validation Messages

```php
// Override captcha validation message
add_filter('lexo_cr_message_captcha_fail', function($message) {
    return 'Please complete the captcha verification.';
});

// Override invalid email message
add_filter('lexo_cr_message_invalid_email', function($message, $field_label) {
    return "The email format is invalid in field: {$field_label}";
}, 10, 2);

// Override validation failure message
add_filter('lexo_cr_message_validation_fail', function($message) {
    return 'Please check your form and try again.';
});

// Override required fields missing message
add_filter('lexo_cr_message_required_fields_missing', function($message, $field_list) {
    return "Please fill in the following fields: {$field_list}";
}, 10, 2);
```

#### Email Settings

```php
// Override confirmation email subject
add_filter('lexo_cr_message_confirmation_email_subject', function($message) {
    return 'Thank you for contacting us!';
});
```

#### Error Messages

```php
// Override form ID required error
add_filter('lexo_cr_message_form_id_required', function($message) {
    return 'Form ID is missing';
});

// Override form not found error
add_filter('lexo_cr_message_form_not_found', function($message) {
    return 'The requested form does not exist';
});

// Override form template not configured error
add_filter('lexo_cr_message_form_template_not_configured', function($message) {
    return 'Form template is not configured';
});

// Override template not found error
add_filter('lexo_cr_message_template_not_found', function($message) {
    return 'The form template could not be found';
});

// Override no fields configured error
add_filter('lexo_cr_message_no_fields_configured', function($message) {
    return 'This form has no fields configured';
});
```

### CleverReach Integration Messages

```php
// Override CleverReach submission error
add_filter('lexo_cr_message_cleverreach_error', function($message) {
    return 'Unable to submit to CleverReach. Our team has been notified.';
});

// Override already subscribed message
add_filter('lexo_cr_message_already_subscribed', function($message) {
    return 'You are already subscribed to our newsletter.';
});

// Override form creation failed error
add_filter('lexo_cr_message_cr_form_creation_failed', function($message) {
    return 'Failed to create CleverReach form';
});

// Override group ID retrieval failed error
add_filter('lexo_cr_message_cr_group_id_failed', function($message) {
    return 'Failed to retrieve CleverReach group ID';
});
```

## Usage Examples

### Example 1: Multilingual Support

```php
// Add multilingual support based on current locale
add_filter('lexo_cr_message_success', function($message) {
    $locale = get_locale();

    switch ($locale) {
        case 'de_DE':
            return 'Ihre Nachricht wurde erfolgreich gesendet!';
        case 'fr_FR':
            return 'Votre message a été envoyé avec succès!';
        default:
            return $message; // Return default English message
    }
});
```

### Example 2: Contextual Messages

```php
// Different success messages based on page
add_filter('lexo_cr_message_success', function($message) {
    if (is_page('contact')) {
        return 'Thank you for contacting us! We will respond within 24 hours.';
    } elseif (is_page('quote')) {
        return 'Your quote request has been submitted! We will get back to you soon.';
    }

    return $message;
});
```

### Example 3: Custom HTML in Messages

```php
// Add custom HTML to success message
add_filter('lexo_cr_message_success', function($message) {
    return '<strong>Success!</strong> Your message has been sent. <a href="/faq">View our FAQ</a>';
});
```

## Notes

- All filters receive the default message as the first parameter
- Some filters receive additional parameters (marked in the documentation)
- Filters with parameters must specify the correct number of accepted arguments (e.g., `10, 2` for 2 parameters)
- Messages support HTML if displayed in appropriate context
- Changes via filters apply globally unless you add conditional logic

## Priority

All filters use the default WordPress filter priority (10). You can specify a different priority:

```php
add_filter('lexo_cr_message_success', 'your_callback', 20); // Higher priority
add_filter('lexo_cr_message_fail', 'your_callback', 5);    // Lower priority
```
