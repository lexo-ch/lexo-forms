/**
 * LEXO Forms - Admin JavaScript
 *
 * Consolidated admin scripts for LEXO Forms plugin
 * - CleverReach API integration (Settings page)
 * - Form Integration (Forms edit page)
 * - TinyMCE plugin (Post/Page editor)
 */

(function(window, $, tinymce) {
    'use strict';

    if (window.lexoFormsAdminInitialized) {
        return;
    }
    window.lexoFormsAdminInitialized = true;

    // ============================================================
    // MODULE 1: CleverReach API (Settings page)
    // ============================================================
    if (typeof cleverreach_ajax !== 'undefined') {
        const CleverReachAPI = {
            init: function() {
                this.bindEvents();
            },

            bindEvents: function() {
                $(document).on('click', '#test-connection', this.testConnection);
                $(document).on('click', '#disconnect-cleverreach', this.disconnectCleverReach);
                $(document).on('click', '#auto-generate-redirect', this.autoGenerateRedirect);
            },

            testConnection: function() {
                const button = $(this);
                const resultDiv = $('#connection-result');

                button.prop('disabled', true).text('Testing...');
                resultDiv.html('');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_cleverreach_connection',
                        nonce: cleverreach_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            resultDiv.html('<div style="padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; color: #155724;">✓ Connection successful!</div>');
                        } else {
                            resultDiv.html('<div style="padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">✗ Error: ' + response.data + '</div>');
                        }
                    },
                    error: function() {
                        resultDiv.html('<div style="padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">✗ An error occurred during testing</div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Test Connection');
                    }
                });
            },

            disconnectCleverReach: function() {
                if (!confirm('Are you sure you want to disconnect from CleverReach?')) {
                    return;
                }

                const button = $(this);
                button.prop('disabled', true).text('Disconnecting...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'disconnect_cleverreach',
                        nonce: cleverreach_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                            button.prop('disabled', false).text('Disconnect');
                        }
                    },
                    error: function() {
                        alert('An error occurred');
                        button.prop('disabled', false).text('Disconnect');
                    }
                });
            },

            autoGenerateRedirect: function() {
                const defaultUri = $('#default_redirect_uri').val();

                $('#cleverreach_redirect_uri').val(defaultUri);

                const button = $(this);
                const originalText = button.text();
                button.text('Generated!').prop('disabled', true);

                setTimeout(function() {
                    button.text(originalText).prop('disabled', false);
                }, 1500);
            }
        };

        $(function() {
            CleverReachAPI.init();
        });
    }

    // ============================================================
    // MODULE 2: Form Integration (Forms edit page)
    // ============================================================
    if (typeof lexoformIntegration !== 'undefined') {
        const LexoFormIntegration = {
            init: function() {
                // Field visibility is handled by ACF conditional logic.
            },

            checkDuplicates: function() {
                const formAction = $('[data-name="lexoform_cr_integration_lexoform_form_action"] input[type="radio"]:checked').val();

                if (formAction === 'create_new') {
                    const newFormName = $('[data-name="lexoform_cr_integration_lexoform_new_form_name"] input').val();
                    if (newFormName && lexoformIntegration.existing_form_names.includes(newFormName)) {
                        if (confirm(lexoformIntegration.i18n.duplicate_form_warning)) {
                            this.switchToExistingForm(newFormName);
                            return false;
                        }
                    }

                    const groupAction = $('[data-name="lexoform_cr_integration_lexoform_group_action"] input[type="radio"]:checked').val();
                    if (groupAction === 'create_new_group') {
                        const newGroupName = $('[data-name="lexoform_cr_integration_lexoform_new_group_name"] input').val();
                        if (newGroupName && lexoformIntegration.existing_group_names.includes(newGroupName)) {
                            if (confirm(lexoformIntegration.i18n.duplicate_group_warning)) {
                                this.switchToExistingGroup(newGroupName);
                                return false;
                            }
                        }
                    }
                }

                return true;
            },

            switchToExistingForm: function(formName) {
                const formId = lexoformIntegration.forms_by_name[formName];
                if (!formId) {
                    return;
                }

                $('[data-name="lexoform_cr_integration_lexoform_form_action"] input[value="use_existing"]').prop('checked', true).trigger('change');
                $('[data-name="lexoform_cr_integration_lexoform_existing_form"] select').val(formId).trigger('change');
                $('[data-name="lexoform_cr_integration_lexoform_new_form_name"] input').val('');
            },

            switchToExistingGroup: function(groupName) {
                const groupId = lexoformIntegration.groups_by_name[groupName];
                if (!groupId) {
                    return;
                }

                $('[data-name="lexoform_cr_integration_lexoform_group_action"] input[value="use_existing_group"]').prop('checked', true).trigger('change');
                $('[data-name="lexoform_cr_integration_lexoform_existing_group"] select').val(groupId).trigger('change');
                $('[data-name="lexoform_cr_integration_lexoform_new_group_name"] input').val('');
            }
        };

        $(function() {
            LexoFormIntegration.init();

            $('#post').on('submit', function(event) {
                if (!LexoFormIntegration.checkDuplicates()) {
                    event.preventDefault();
                    return false;
                }

                return true;
            });
        });

        window.copyShortcode = function() {
            const shortcodeInput = $('#lexoform-shortcode-container input[type="text"]');
            if (!shortcodeInput.length) {
                return;
            }

            shortcodeInput.select();
            document.execCommand('copy');

            const button = $('#lexoform-shortcode-container button');
            const originalText = button.text();
            button.text('✅ Copied!');

            setTimeout(function() {
                button.text(originalText);
            }, 2000);
        };
    }

    // ============================================================
    // MODULE 3: TinyMCE plugin (Post/Page editor)
    // ============================================================
    if (tinymce && tinymce.PluginManager) {
        tinymce.PluginManager.add('lexo_forms', function(editor) {
            if (!window.lexoFormsData || !window.lexoFormsData.labels) {
                return;
            }

            editor.addButton('lexo_forms_button', {
                title: window.lexoFormsData.labels.title,
                text: window.lexoFormsData.labels.title,
                icon: false,
                onclick: function() {
                    openFormsDialog();
                }
            });

            function openFormsDialog() {
                var labels = window.lexoFormsData.labels;
                var labelText = labels.selectFormLabel || labels.selectForm;
                var placeholderText = labels.selectFormPlaceholder || labels.selectForm;

                var dialogContent = '<div id="lexo-forms-dialog">' +
                    '<label for="lexo-forms-select" style="display: block; margin-bottom: 6px; font-weight: 600;">' + labelText + '</label>' +
                    '<select id="lexo-forms-select" style="width: 100%; border: 1px solid #ccd0d4; max-width:none; box-sizing: border-box; min-height: 34px;">' +
                    '<option value="">' + placeholderText + '</option>' +
                    '</select>' +
                    '</div>';

                editor.windowManager.open({
                    title: window.lexoFormsData.labels.title,
                    body: [
                        {
                            type: 'container',
                            html: dialogContent
                        }
                    ],
                    buttons: [
                        {
                            text: window.lexoFormsData.labels.cancel,
                            onclick: function() {
                                editor.windowManager.close();
                            }
                        },
                        {
                            text: window.lexoFormsData.labels.insert,
                            classes: 'widget btn primary',
                            onclick: function() {
                                insertSelectedForm();
                            }
                        }
                    ],
                    onopen: function() {
                        loadFormsList();
                    },
                    width: 500,
                    height: 300
                });
            }

            function loadFormsList() {
                var selectElement = document.getElementById('lexo-forms-select');

                if (!selectElement) {
                    return;
                }

                if (window.lexoFormsData.forms) {
                    populateFormsList(window.lexoFormsData.forms);
                } else {
                    showError(window.lexoFormsData.labels.noForms);
                }
            }

            function populateFormsList(forms) {
                var selectElement = document.getElementById('lexo-forms-select');

                if (!selectElement) {
                    return;
                }

                while (selectElement.children.length > 1) {
                    selectElement.removeChild(selectElement.lastChild);
                }

                if (!forms.length) {
                    var option = document.createElement('option');
                    option.value = '';
                    option.textContent = window.lexoFormsData.labels.noForms;
                    option.disabled = true;
                    selectElement.appendChild(option);
                } else {
                    forms.forEach(function(form) {
                        var option = document.createElement('option');
                        option.value = form.id;
                        option.textContent = form.title;
                        selectElement.appendChild(option);
                    });
                }

                selectElement.style.display = 'block';
            }

            function insertSelectedForm() {
                var selectElement = document.getElementById('lexo-forms-select');

                if (!selectElement || !selectElement.value) {
                    var warning = window.lexoFormsData.labels.selectFormRequired || window.lexoFormsData.labels.selectForm;
                    alert(warning);
                    return;
                }

                var formId = selectElement.value;
                var shortcode = '[lexo_form id="' + formId + '"]';

                editor.insertContent(shortcode);
                editor.windowManager.close();
            }

            function showError(message) {
                var selectElement = document.getElementById('lexo-forms-select');
                if (!selectElement) {
                    return;
                }

                selectElement.innerHTML = '<option value="" disabled>' + message + '</option>';
                selectElement.style.display = 'block';
            }
        });
    }
})(window, jQuery, window.tinymce || null);
