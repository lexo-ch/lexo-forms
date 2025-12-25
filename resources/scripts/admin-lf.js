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
            savedTemplateId: lexoformIntegration.saved_template_id || '',

            init: function() {
                this.bindTemplateChange();
                this.bindSaveInterception();
                this.updateVisitorEmailVariantsField();
            },

            bindTemplateChange: function() {
                const self = this;
                $(document).on('change.lexoformTemplate', '[data-name="lexoform_html_template"] input[type="radio"]', function() {
                    self.updateVisitorEmailVariantsField();
                });
            },

            bindSaveInterception: function() {
                const self = this;

                // Intercept Save Form button
                $('#lexoforms-save-btn').off('click.templateCheck').on('click.templateCheck', function(e) {
                    if (self.shouldShowWarning()) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        self.showWarningModal();
                        return false;
                    }
                    $('#publish').click();
                });

                // Intercept native Publish/Update button
                $('#publish').off('click.templateCheck').on('click.templateCheck', function(e) {
                    if (self.shouldShowWarning()) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        self.showWarningModal();
                        return false;
                    }
                });
            },

            shouldShowWarning: function() {
                // Skip warning if user already confirmed via modal
                if (this.bypassWarning) {
                    return false;
                }
                return this.getAddedCRFields().length > 0;
            },

            isCRIntegrationEnabled: function() {
                const handlerType = $('[data-name="lexoform_handler_type"] input[type="radio"]:checked').val();
                return handlerType === 'cr_only' || handlerType === 'email_and_cr';
            },

            getAddedCRFields: function() {
                if (!this.savedTemplateId) return [];

                const currentTemplateId = $('[data-name="lexoform_html_template"] input[type="radio"]:checked').val();
                if (currentTemplateId === this.savedTemplateId) return [];
                if (!this.isCRIntegrationEnabled()) return [];

                return lexoformIntegration.templates_new_cr_fields?.[currentTemplateId] || [];
            },

            showWarningModal: function() {
                const self = this;
                const $modal = $('#lexoforms-template-change-modal');
                const $list = $modal.find('#lexoforms-new-fields-list');

                // Populate fields list
                $list.empty();
                this.getAddedCRFields().forEach(function(field) {
                    $list.append($('<li>').text(field));
                });

                $modal.addClass('active');

                // Bind modal events (one-time)
                $modal.off('click.templateModal')
                    .on('click.templateModal', '#lexoforms-template-cancel', function() {
                        $modal.removeClass('active');
                    })
                    .on('click.templateModal', '#lexoforms-template-confirm', function() {
                        $modal.removeClass('active');
                        // Update savedTemplateId to current template so modal won't show again
                        // after page reloads (CR fields will be added during save)
                        const currentTemplateId = $('[data-name="lexoform_html_template"] input[type="radio"]:checked').val();
                        self.savedTemplateId = currentTemplateId;
                        // Also clear the new fields for this template since they'll be added
                        if (lexoformIntegration.templates_new_cr_fields) {
                            lexoformIntegration.templates_new_cr_fields[currentTemplateId] = [];
                        }
                        self.bypassWarning = true;
                        $('#publish').click();
                    })
                    .on('click.templateModal', function(e) {
                        if (e.target === $modal[0]) $modal.removeClass('active');
                    });

                $(document).off('keydown.templateModal').on('keydown.templateModal', function(e) {
                    if (e.key === 'Escape' && $modal.hasClass('active')) {
                        $modal.removeClass('active');
                    }
                });
            },

            updateVisitorEmailVariantsField: function() {
                // Get selected template - ACF nested fields use just the field name, not full path
                const selectedTemplate = $('[data-name="lexoform_html_template"] input[type="radio"]:checked').val();
                
                if (!selectedTemplate || !lexoformIntegration.templates_with_variants) {
                    return;
                }

                // Check if this template has visitor_email_variants
                const hasVariants = lexoformIntegration.templates_with_variants[selectedTemplate] || false;
                
                // Get current state of the switch
                const $switch = $('[data-name="lexoform_has_visitor_email_variants"] .acf-switch');
                const isCurrentlyOn = $switch.hasClass('-on');
                
                // Only click if state needs to change
                if (hasVariants && !isCurrentlyOn) {
                    // Need to turn ON
                    $switch.click();
                } else if (!hasVariants && isCurrentlyOn) {
                    // Need to turn OFF
                    $switch.click();
                }
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
    // ============================================================
    // MODULE 4: Forms CPT Actions (Delete modal, Save button)
    // ============================================================
    const LexoFormsActions = {
        deleteUrl: '',
        $modal: null,

        init: function() {
            this.$modal = $('#lexoforms-delete-modal');

            // Always init save button (works on new posts too)
            this.initSaveButton();

            // Only bind delete modal events if modal exists
            if (this.$modal.length) {
                this.bindEvents();
            }
        },

        bindEvents: function() {
            const self = this;

            // Handle delete link clicks
            $(document).on('click', '.lexoforms-delete-link, .row-actions .delete a, #delete-action a', function(e) {
                const $link = $(this);
                let postId = $link.data('post-id');

                // For edit screen delete button
                if (!postId && $link.closest('#delete-action').length) {
                    const match = $link.attr('href').match(/post=(\d+)/);
                    if (match) postId = match[1];
                }

                if (!postId) return true;

                e.preventDefault();
                self.deleteUrl = $link.attr('href');
                self.showModal(postId);
            });

            // Close on overlay click
            this.$modal.on('click', function(e) {
                if (e.target === this) {
                    self.hideModal();
                }
            });

            // Close on Escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.$modal.hasClass('active')) {
                    self.hideModal();
                }
            });
        },

        initSaveButton: function() {
            // Only bind if LexoFormIntegration is NOT active (it handles save button with template check)
            if (typeof lexoformIntegration !== 'undefined') {
                return;
            }

            const $saveBtn = $('#lexoforms-save-btn');
            if ($saveBtn.length) {
                $saveBtn.on('click', function(e) {
                    e.preventDefault();
                    const $publishBtn = $('#publish');
                    if ($publishBtn.length) {
                        $publishBtn.click();
                    }
                });
            }
        },

        showModal: function(postId) {
            const self = this;
            const $modalInner = this.$modal.find('.lexoforms-modal');

            this.$modal.addClass('active');
            $modalInner.html('<div class="loading"><span class="spinner is-active" style="float: none;"></span> ' + (lexoformsAdmin?.i18n?.loading || 'Loading...') + '</div>');

            // Check if lexoformsAdmin is defined (localized data)
            if (typeof lexoformsAdmin === 'undefined') {
                $modalInner.html('<p>Error: Configuration not found.</p>');
                return;
            }

            const ajaxEndpoint = lexoformsAdmin.ajaxUrl || ajaxurl;

            $.post(ajaxEndpoint, {
                action: 'lexoforms_get_usage',
                post_id: postId,
                nonce: lexoformsAdmin.deleteNonce
            }, function(response) {
                if (response.success && response.data.html) {
                    $modalInner.html(response.data.html);
                    self.rebindModalButtons();
                } else {
                    $modalInner.html('<p>Error loading data.</p>');
                }
            }).fail(function() {
                $modalInner.html('<p>Error loading data.</p>');
            });
        },

        rebindModalButtons: function() {
            const self = this;

            this.$modal.find('#lexoforms-cancel-delete').on('click', function() {
                self.hideModal();
            });

            this.$modal.find('#lexoforms-confirm-delete').on('click', function() {
                if (self.deleteUrl) {
                    window.location.href = self.deleteUrl;
                }
            });
        },

        hideModal: function() {
            this.$modal.removeClass('active');
            this.deleteUrl = '';
        }
    };

    $(function() {
        LexoFormsActions.init();
    });

    // ============================================================
    // MODULE 5: Shortcode Copy (Forms list table)
    // ============================================================
    $(document).on('click', '.lexoforms-shortcode-copy', function() {
        const $code = $(this);
        const shortcode = $code.data('shortcode');
        const originalText = $code.text();
        const copiedText = (typeof lexoformsAdmin !== 'undefined' && lexoformsAdmin.i18n?.copied) 
            ? lexoformsAdmin.i18n.copied 
            : '✅ Copied!';

        navigator.clipboard.writeText(shortcode).then(function() {
            $code.text(copiedText).addClass('copied');

            setTimeout(function() {
                $code.text(originalText).removeClass('copied');
            }, 1500);
        });
    });

    // ============================================================
    // MODULE 6: Form Preview Lightbox
    // ============================================================
    const LexoFormsLightbox = {
        $overlay: null,

        init: function() {
            this.$overlay = $('#lexoforms-lightbox-overlay');

            if (!this.$overlay.length) {
                return;
            }

            this.bindEvents();
        },

        bindEvents: function() {
            const self = this;

            // Open lightbox on preview click (for list table and template selector)
            // Exclude --no-zoom modifier (plugin templates)
            $(document).on('click', '.lexoforms-preview-wrap:not(.lexoforms-preview-wrap--no-zoom), .lexoforms-template-zoom', function(e) {
                e.preventDefault();
                e.stopPropagation();
                // Find image in parent container (template choice, preview wrap, or ACF label)
                const $container = $(this).closest('.lexoforms-template-choice, .lexoforms-preview-wrap, label');
                const $img = $container.find('img').first();
                const src = $img.attr('src');
                const alt = $img.attr('alt');
                if (src) {
                    self.open(src, alt);
                }
            });

            // Close on overlay click
            this.$overlay.on('click', function(e) {
                if (e.target === this || $(e.target).hasClass('lexoforms-lightbox-close')) {
                    self.close();
                }
            });

            // Close on Escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.$overlay.hasClass('active')) {
                    self.close();
                }
            });
        },

        open: function(src, alt) {
            this.$overlay.find('.lexoforms-lightbox-image').attr('src', src).attr('alt', alt || '');
            this.$overlay.addClass('active');
            $('body').addClass('lexoforms-lightbox-open');
        },

        close: function() {
            this.$overlay.removeClass('active');
            $('body').removeClass('lexoforms-lightbox-open');
        }
    };

    $(function() {
        LexoFormsLightbox.init();
    });

})(window, jQuery, window.tinymce || null);
