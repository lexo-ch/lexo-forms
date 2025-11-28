# LEXO Forms

WordPress plugin for advanced form management with CleverReach integration.

## Versioning

Release tags are created with Semantic versioning in mind. Commit messages were following convention of [Conventional Commits](https://www.conventionalcommits.org/).

## Compatibility
- WordPress version `>=6.4`. Tested and works fine up to `6.8.3`.
- PHP version `>=7.4.1`. Tested and works fine up to `8.4.4`.

## Requirements

- **Advanced Custom Fields PRO** (`advanced-custom-fields-pro`)
- **LEXO Captcha** (`lexo-captcha`)

## Installation

1. Download the [latest release](https://github.com/lexo-ch/lexo-forms/releases/latest).
2. Under Assets, click on the link named `Version x.y.z`. It's a compiled build.
3. Extract zip file and copy the folder into your `wp-content/plugins` folder and activate LEXO Forms in plugins admin page. Alternatively, you can use downloaded zip file to install it directly from your plugin admin page.

## Usage

### Creating a Form

1. Go to **Forms** > **Add New** in WordPress admin.
2. Configure the form settings:
   - **General Settings:** Select template, success/fail messages
   - **Email Settings:** Configure recipients, subject, sender
   - **CleverReach Integration:** Select group, form type, double opt-in settings
3. Publish the form.

### Inserting Forms

#### Shortcode

```php
[lexo_form id="123"]
```

#### TinyMCE Button

Use the "Forms" button in the WordPress editor toolbar to visually select and insert a form.

#### PHP

```php
echo do_shortcode('[lexo_form id="123"]');
```

### Form Templates

Forms are built using PHP templates located in `template-forms/`.
Custom templates can also be registered by creating a `template-forms/` directory in your active theme and placing template files inside it. The plugin automatically loads templates from both locations.

#### Contact Form Template

```php
<?php
// template-forms/contact.php
$name = __('Contact Form', 'lf');
$fields = [
    [
        'name' => 'firstname',
        'type' => 'text',
        'label' => ['de' => 'Name', 'en' => 'Name'],
        'required' => true,
        'send_to_cr' => true
    ],
    // ... more fields
];
```

#### Available Template Fields

- `name` — Internal field name
- `type` — Field type (`text`, `email`, `textarea`, `file`, `tel`, `checkbox`)
- `html_type` — HTML element (`input`, `textarea`, `select`)
- `label` — Multi-language labels (array with language codes)
- `placeholder` — Multi-language placeholders
- `required` — Required field flag (boolean)
- `email_label` — Label for email notifications
- `cr_description` — CleverReach field description
- `global` — CleverReach global attribute flag
- `send_to_cr` — Send to CleverReach flag

#### Form Handler Types

- **Email Only** — Send only email notifications.
- **CleverReach Only** — Send only to CleverReach.
- **Email & CleverReach** — Send to both (recommended).

### Email Configuration

#### Admin Notification Emails

Configure in the form's **Email Settings** section:

- **Recipients** — Multiple email addresses (repeater field)
- **Subject** — Email subject line
- **Sender Email** — From email address
- **Sender Name** — From name

#### Confirmation Emails

Enable in **Email Settings** > **Enable Additional Email**:

- **Subject** — Confirmation email subject
- **Body** — HTML email body with placeholder support
- **Sender Email** — From email address
- **Sender Name** — From name
- **Attachment** — PDF or other file attachment

## Filters

### CleverReach Email Filters

#### `lexo-forms/cr/email/config`

Filter email configuration for form submissions.

```php
apply_filters('lexo-forms/cr/email/config', array $config, int $form_id, array $form_data);
```

**Parameters**

- `$config` (array) — Email configuration
  - `recipients` (array) — Email recipients
  - `subject` (string) — Email subject
  - `from_email` (string) — Sender email
  - `from_name` (string) — Sender name
  - `reply_to_email` (string) — Reply-to email
  - `reply_to_name` (string) — Reply-to name
- `$form_id` (int) — Form post ID
- `$form_data` (array) — Submitted form data

**Example**

```php
add_filter('lexo-forms/cr/email/config', function($config, $form_id, $form_data) {
    if ($form_id === 123) {
        $config['recipients'] = ['admin@example.com'];
        $config['subject'] = 'New Contact Form Submission';
    }
    return $config;
}, 10, 3);
```

---

#### `lexo-forms/cr/email/confirmation/subject`

Filter confirmation email subject.

```php
apply_filters('lexo-forms/cr/email/confirmation/subject', string $subject, int $form_id);
```

**Default:** Value from `FormMessages::getConfirmationEmailSubject()`

**Example**

```php
add_filter('lexo-forms/cr/email/confirmation/subject', function($subject, $form_id) {
    return 'Thank you for contacting us!';
}, 10, 2);
```

---

#### `lexo-forms/cr/email/confirmation/sender`

Filter confirmation email sender address.

```php
apply_filters('lexo-forms/cr/email/confirmation/sender', string $sender_email, int $form_id);
```

**Default:** `EMAIL_FROM_EMAIL` constant

---

#### `lexo-forms/cr/email/confirmation/sender-name`

Filter confirmation email sender name.

```php
apply_filters('lexo-forms/cr/email/confirmation/sender-name', string $sender_name, int $form_id);
```

**Default:** `EMAIL_FROM_NAME` constant

---

#### `lexo-forms/cr/email/confirmation/body`

Filter confirmation email body content.

```php
apply_filters('lexo-forms/cr/email/confirmation/body', string $body, int $form_id);
```

**Default:** Empty string

---

#### `lexo-forms/cr/email/admin-notification/label-language`

Filter language for field labels in admin notification emails.

```php
apply_filters('lexo-forms/cr/email/admin-notification/label-language', string $language);
```

**Default:** `'de'`

**Example**

```php
add_filter('lexo-forms/cr/email/admin-notification/label-language', function($language) {
    return 'en'; // Use English labels
});
```

---

#### `lexo-forms/cr/email/admin-notification/field-label`

Filter individual field label in admin notification emails.

```php
apply_filters('lexo-forms/cr/email/admin-notification/field-label', string $label, string $field_name, array $field_config);
```

**Parameters**

- `$label` (string) — Field label
- `$field_name` (string) — Field name
- `$field_config` (array) — Complete field configuration

**Example**

```php
add_filter('lexo-forms/cr/email/admin-notification/field-label', function($label, $field_name, $field_config) {
    if ($field_name === 'email') {
        return 'Customer Email';
    }
    return $label;
}, 10, 3);
```

---

### Forms Filters

#### `lexo-forms/forms/grid/columns`

Filter number of grid columns for form layout.

```php
apply_filters('lexo-forms/forms/grid/columns', int $columns, string $form_type);
```

**Parameters**

- `$columns` (int) — Number of columns (default: 2 for contact, 1 for newsletter)
- `$form_type` (string) — Form type identifier (`contact`, `newsletter`)

**Example**

```php
add_filter('lexo-forms/forms/grid/columns', function($columns, $form_type) {
    if ($form_type === 'contact') {
        return 3; // Use 3 columns for contact forms
    }
    return $columns;
}, 10, 2);
```

---

#### `lexo-forms/forms/grid/classes`

Filter CSS classes for form grid wrapper.

```php
apply_filters('lexo-forms/forms/grid/classes', string $classes, string $form_type, int $grid_columns);
```

**Parameters**

- `$classes` (string) — Default CSS classes
- `$form_type` (string) — Form type identifier
- `$grid_columns` (int) — Number of columns

**Example**

```php
add_filter('lexo-forms/forms/grid/classes', function($classes, $form_type, $grid_columns) {
    return $classes . ' custom-form-styling';
}, 10, 3);
```

---

#### `lexo-forms/forms/toolbar/additional`

Filter additional ACF WYSIWYG toolbars where forms button appears.

```php
apply_filters('lexo-forms/forms/toolbar/additional', array $toolbars);
```

**Default:** `[]` (only "Full" and "Full FC" by default)

**Example**

```php
add_filter('lexo-forms/forms/toolbar/additional', function($toolbars) {
    $toolbars[] = 'Basic';
    $toolbars[] = 'Custom';
    return $toolbars;
});
```

---

#### `lexo-forms/forms/toolbar/email/buttons`

Filter buttons in additional email WYSIWYG toolbar.

```php
apply_filters('lexo-forms/forms/toolbar/email/buttons', array $toolbar);
```

**Parameters**

- `$toolbar` (array) — Toolbar configuration with rows

**Default Buttons (Row 1)**

- `formatselect`, `bold`, `italic`, `underline`, `bullist`, `numlist`, `link`, `unlink`, `alignleft`, `aligncenter`, `alignright`, `removeformat`, `pastetext`, `undo`, `redo`

**Example**

```php
add_filter('lexo-forms/forms/toolbar/email/buttons', function($toolbar) {
    // Add strikethrough to row 1
    $toolbar[1][] = 'strikethrough';

    // Add color buttons to row 2
    $toolbar[2] = ['forecolor', 'backcolor'];

    return $toolbar;
});
```

---

#### `lexo-forms/forms/email/label-language`

Filter language for email labels in form submission emails.

```php
apply_filters('lexo-forms/forms/email/label-language', string $language);
```

**Default:** `'de'`

**Example**

```php
add_filter('lexo-forms/forms/email/label-language', function($language) {
    return 'en';
});
```

---

### Forms Messages & Errors Filters

#### `lexo-forms/forms/messages/success`

Filter the default success message shown after successful form submission.

```php
apply_filters('lexo-forms/forms/messages/success', string $message);
```

**Default:** `'Your message has been sent successfully. Thank you!'`

**Example**

```php
add_filter('lexo-forms/forms/messages/success', function($message) {
    return 'Thank you! We will get back to you soon.';
});
```

---

#### `lexo-forms/forms/messages/email-fail`

Filter the message shown when email sending fails.

```php
apply_filters('lexo-forms/forms/messages/email-fail', string $message);
```

**Default:** `'Failed to send email'`

---

#### `lexo-forms/forms/messages/captcha-fail`

Filter the message shown when captcha validation fails.

```php
apply_filters('lexo-forms/forms/messages/captcha-fail', string $message);
```

**Default:** `'Captcha validation failed. Please try again.'`

---

#### `lexo-forms/forms/messages/invalid-email`

Filter the message shown when email field contains invalid format.

```php
apply_filters('lexo-forms/forms/messages/invalid-email', string $message, string $field_label);
```

**Parameters**

- `$message` (string) — Error message
- `$field_label` (string) — Label of the email field

**Default:** `'Invalid email format in field: {field_label}'`

---

#### `lexo-forms/forms/messages/confirmation-email-subject`

Filter the default subject for confirmation emails sent to visitors.

```php
apply_filters('lexo-forms/forms/messages/confirmation-email-subject', string $subject);
```

**Default:** `'Thank you for your message'`

---

#### `lexo-forms/forms/errors/form-id-required`

Filter the error message when form ID is missing.

```php
apply_filters('lexo-forms/forms/errors/form-id-required', string $message);
```

**Default:** `'Error: Form ID is required.'`

---

#### `lexo-forms/forms/errors/form-not-found`

Filter the error message when form is not found.

```php
apply_filters('lexo-forms/forms/errors/form-not-found', string $message);
```

**Default:** `'Error: Form not found.'`

---

#### `lexo-forms/forms/errors/template-not-configured`

Filter the error message when form template is not configured.

```php
apply_filters('lexo-forms/forms/errors/template-not-configured', string $message);
```

**Default:** `'Error: Form template not configured.'`

---

#### `lexo-forms/forms/errors/template-not-found`

Filter the error message when template file is not found.

```php
apply_filters('lexo-forms/forms/errors/template-not-found', string $message);
```

**Default:** `'Error: Template not found.'`

---

#### `lexo-forms/forms/errors/no-fields-configured`

Filter the error message when no fields are configured in template.

```php
apply_filters('lexo-forms/forms/errors/no-fields-configured', string $message);
```

**Default:** `'No fields configured for this form'`

---

#### `lexo-forms/forms/errors/required-fields-missing`

Filter the error message when required fields are missing.

```php
apply_filters('lexo-forms/forms/errors/required-fields-missing', string $message, string $field_list);
```

**Parameters**

- `$message` (string) — Error message
- `$field_list` (string) — Comma-separated list of missing field names

**Default:** `'Required fields are missing: {field_list}'`

---

### CleverReach Messages & Errors Filters

#### `lexo-forms/cr/messages/error`

Filter the message shown when CleverReach submission fails.

```php
apply_filters('lexo-forms/cr/messages/error', string $message);
```

**Default:** `'We encountered an issue submitting your information. Our team has been notified and will contact you shortly.'`

**Example**

```php
add_filter('lexo-forms/cr/messages/error', function($message) {
    return 'Submission failed. Please try again later.';
});
```

---

#### `lexo-forms/cr/messages/already-subscribed`

Filter the message shown when email is already subscribed.

```php
apply_filters('lexo-forms/cr/messages/already-subscribed', string $message);
```

**Default:** `'This email address is already subscribed.'`

---

#### `lexo-forms/cr/errors/form-creation-failed`

Filter the error message when CleverReach form creation fails.

```php
apply_filters('lexo-forms/cr/errors/form-creation-failed', string $message);
```

**Default:** `'Failed to determine or create form'`

---

#### `lexo-forms/cr/errors/group-id-failed`

Filter the error message when retrieving CleverReach group ID fails.

```php
apply_filters('lexo-forms/cr/errors/group-id-failed', string $message);
```

**Default:** `'Failed to get group ID from form'`

---

### Core Filters

#### `lexoforms/load_styles`

Filter whether to load plugin styles.

```php
apply_filters('lexoforms/load_styles', bool $load);
```

**Default:** `true`

**Example**

```php
add_filter('lexoforms/load_styles', function($load) {
    // Don't load on specific pages
    if (is_page('custom-page')) {
        return false;
    }
    return $load;
});
```

---

#### `lexoforms/enqueue/{basename}`

Filter whether to enqueue specific stylesheet.

```php
apply_filters('lexoforms/enqueue/{basename}', bool $enqueue);
```

**Example**

```php
// Don't load admin-lf.css on dashboard
add_filter('lexoforms/enqueue/admin-lf.css', function($enqueue) {
    if (is_admin() && get_current_screen()->id === 'dashboard') {
        return false;
    }
    return $enqueue;
});
```

---

#### `lexoforms/load_scripts`

Filter whether to load plugin scripts.

```php
apply_filters('lexoforms/load_scripts', bool $load);
```

**Default:** `true`

---

#### `lexoforms/enqueue/{basename}`

Filter whether to enqueue specific script.

```php
apply_filters('lexoforms/enqueue/{basename}', bool $enqueue);
```

**Example**

```php
// Don't load admin-lf.js on specific pages
add_filter('lexoforms/enqueue/admin-lf.js', function($enqueue) {
    if (is_admin() && get_current_screen()->id === 'plugins') {
        return false;
    }
    return $enqueue;
});
```

---

#### `lexoforms/load_editor_styles`

Filter whether to load editor styles.

```php
apply_filters('lexoforms/load_editor_styles', bool $load);
```

**Default:** `true`

---

#### `lexoforms/add_editor_style/{basename}`

Filter whether to add specific editor style.

```php
apply_filters('lexoforms/add_editor_style/{basename}', bool $add);
```

**Default:** `true`

---

#### `lexo-forms/plugin_sections`

Filter plugin update information sections.

```php
apply_filters('lexo-forms/plugin_sections', array $sections);
```

**Parameters**

- `$sections` (array) — Array with `description` and `changelog` keys

---

#### `lexoforms/admin_localized_script`

Filter admin localized script variables.

```php
apply_filters('lexoforms/admin_localized_script', array $vars);
```

**Parameters**

- `$vars` (array) — Array of variables passed to `wp_localize_script`
  - `ajaxurl` (string) — WordPress AJAX URL
  - `nonce` (string) — Nonce for AJAX requests
  - `plugin_name` (string) — Plugin name
  - `plugin_slug` (string) — Plugin slug
  - `plugin_version` (string) — Plugin version
  - `min_php_version` (string) — Minimum PHP version
  - `min_wp_version` (string) — Minimum WordPress version
  - `text_domain` (string) — Text domain

**Example**

```php
add_filter('lexoforms/admin_localized_script', function($vars) {
    $vars['custom_data'] = 'My custom value';
    return $vars;
});
```

---

#### `lexoforms/options-page/parent-slug`

Filter the parent slug for the settings page in admin menu.

```php
apply_filters('lexoforms/options-page/parent-slug', string $slug);
```

**Default:** `'options-general.php'`

**Example**

```php
add_filter('lexoforms/options-page/parent-slug', function($slug) {
    return 'admin.php'; // Move to main admin menu
});
```

---

#### `lexoforms/options-page/capability`

Filter the capability required to access plugin settings.

```php
apply_filters('lexoforms/options-page/capability', string $capability);
```

**Default:** `'manage_options'`

**Example**

```php
add_filter('lexoforms/options-page/capability', function($capability) {
    return 'edit_posts'; // Allow editors and above
});
```

---

## Actions

### `lexoforms/init`

Fires during plugin initialization.

```php
do_action('lexoforms/init');
```

**Usage**

```php
add_action('lexoforms/init', function() {
    // Custom initialization code
});
```

---

### `lexoforms/localize/{script-basename}`

Fires when localizing scripts for specific script file.

```php
do_action('lexoforms/localize/{script-basename}');
```

**Example**

```php
add_action('lexoforms/localize/admin-lf.js', function() {
    wp_localize_script('lexoforms/admin-lf.js', 'customData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('custom_nonce'),
    ]);
});
```

---

### `lexo-forms/cr/after-submission`

Fires after successful CleverReach form submission.

```php
do_action('lexo-forms/cr/after-submission', int $form_id, array $form_data, array $template);
```

**Parameters**

- `$form_id` (int) — Form post ID
- `$form_data` (array) — Submitted and sanitized form data
- `$template` (array) — Form template configuration

**Example**

```php
add_action('lexo-forms/cr/after-submission', function($form_id, $form_data, $template) {
    // Log submission to custom analytics
    error_log('Form submitted: ' . $form_id);

    // Send to additional service
    send_to_crm($form_data);
}, 10, 3);
```

---

### `lexo-forms/core/log`

Fires when plugin logs a message.

```php
do_action('lexo-forms/core/log', string $level, string $category, string $message, array $context);
```

**Parameters**

- `$level` (string) — Log level (`debug`, `info`, `warning`, `error`)
- `$category` (string) — Log category
- `$message` (string) — Log message
- `$context` (array) — Additional context data

**Example**

```php
add_action('lexo-forms/core/log', function($level, $category, $message, $context) {
    if ($level === 'error') {
        // Send error notification
        mail('admin@example.com', 'Plugin Error', $message);
    }
}, 10, 4);
```

---

## Changelog

For detailed changelog, see [Releases](https://github.com/lexo-ch/lexo-forms/releases).

---

## Author

- **LEXO GmbH**
  - Website: [https://www.lexo.ch](https://www.lexo.ch)
  - GitHub: [https://github.com/lexo-ch](https://github.com/lexo-ch)
