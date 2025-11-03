<?php

namespace LEXO\LF\Core\Handlers;

use LEXO\LF\Core\Abstracts\Singleton;
use LEXO\LF\Core\Services\FormsService;
use LEXO\LF\Core\Services\GroupsService;
use LEXO\LF\Core\Services\TemplateService;
use LEXO\LF\Core\Utils\Logger;
use LEXO\LF\Core\Utils\FormMessages;
use LEXO\LF\Core\Utils\CleverReachHelper;

use const LEXO\LF\FIELD_PREFIX;

/**
 * CleverReach Sync Handler
 *
 * Handles CleverReach form synchronization via ACF save hooks.
 * Creates groups, forms, and syncs field attributes.
 *
 * Called from CleverReachIntegration::handleFormSave() on post save.
 *
 * @package LEXO\LF
 */
class CRSyncHandler extends Singleton
{
    protected static $instance = null;

    /**
     * Perform CleverReach sync from provided data
     *
     * @param array $data Sync data containing all required parameters
     * @return bool Success status
     */
    public function performSyncFromData(array $data): bool
    {
        try {
            $post_id = $data['post_id'] ?? 0;

            if (!$post_id) {
                throw new \Exception(__('Missing post ID', 'lexoforms'));
            }

            // Step 1: Determine FORM_ID
            $form_id = $this->determineFormId(
                $data['form_action'] ?? '',
                intval($data['existing_form'] ?? 0),
                $data['new_form_name'] ?? '',
                $data['group_action'] ?? '',
                intval($data['existing_group'] ?? 0),
                $data['new_group_name'] ?? ''
            );

            if (!$form_id) {
                throw new \Exception(FormMessages::getCRFormCreationFailedError());
            }

            // Step 2: Extract GROUP_ID from the form
            $group_id = $this->getGroupIdFromForm($form_id);

            if (!$group_id) {
                throw new \Exception(FormMessages::getCRGroupIdFailedError());
            }

            // Step 3: Validate that the group actually exists
            $group_error = $this->validateGroupExists($group_id);
            if ($group_error) {
                throw new \Exception($group_error);
            }

            // Step 4: Sync Fields (this can throw exceptions if group is invalid)
            $synced_fields = $this->syncFields($group_id, $data['html_template'] ?? '');

            // Step 5: Update ACF fields (only if everything succeeded)
            // These fields are now sub-fields in cr_integration group
            $cr_integration = get_field(FIELD_PREFIX . 'cr_integration', $post_id) ?: [];
            $cr_integration[FIELD_PREFIX . 'form_id'] = $form_id;
            $cr_integration[FIELD_PREFIX . 'group_id'] = $group_id;
            $cr_integration[FIELD_PREFIX . 'cr_status'] = 'OK';
            update_field(FIELD_PREFIX . 'cr_integration', $cr_integration, $post_id);

            return true;

        } catch (\Exception $e) {
            Logger::syncError('CRSyncHandler error: ' . $e->getMessage());

            // Update status to ERROR (sub-field in cr_integration group)
            if (isset($data['post_id']) && $data['post_id']) {
                $cr_integration = get_field(FIELD_PREFIX . 'cr_integration', $data['post_id']) ?: [];
                $cr_integration[FIELD_PREFIX . 'cr_status'] = 'ERROR: ' . $e->getMessage();
                update_field(FIELD_PREFIX . 'cr_integration', $cr_integration, $data['post_id']);
            }

            return false;
        }
    }


    /**
     * Determine Form ID
     *
     * @param string $form_action
     * @param int $existing_form
     * @param string $new_form_name
     * @param string $group_action
     * @param int $existing_group
     * @param string $new_group_name
     * @return int|null Form ID or null on failure
     */
    private function determineFormId(string $form_action, int $existing_form, string $new_form_name, string $group_action, int $existing_group, string $new_group_name): ?int
    {
        // Use existing form
        if ($form_action === 'use_existing' && $existing_form) {
            return $existing_form;
        }

        // Create new form - need to determine group first
        if ($form_action === 'create_new' && $new_form_name) {
            $group_id = null;

            if ($group_action === 'use_existing_group' && $existing_group) {
                $group_id = $existing_group;
                $group_error = $this->validateGroupExists($group_id);
                if ($group_error) {
                    Logger::syncError('Selected existing group ' . $group_id . ' error: ' . $group_error);
                    throw new \Exception("Selected group error: " . $group_error);
                }
            } elseif ($group_action === 'create_new_group' && $new_group_name) {
                $groupsService = GroupsService::getInstance();
                // Description will be automatically added and sanitized in CleverReachAPI::createGroup
                $group = $groupsService->createGroup(['name' => $new_group_name]);

                if ($group && isset($group['id'])) {
                    $group_id = intval($group['id']);
                }
            }

            if (!$group_id) {
                return null;
            }

            $formsService = FormsService::getInstance();
            $form = $formsService->createForm($group_id, $new_form_name);

            if ($form && isset($form['id'])) {
                $form_id = intval($form['id']);
                return $form_id;
            }
        }

        return null;
    }

    /**
     * Get Group ID from CleverReach Form ID
     *
     * @param int $form_id CleverReach form ID
     * @return int|null Group ID or null on failure
     */
    private function getGroupIdFromForm(int $form_id): ?int
    {
        try {
            $formsService = FormsService::getInstance();
            $form = $formsService->getForm($form_id);

            if ($form && isset($form['customer_tables_id'])) {
                return intval($form['customer_tables_id']);
            }

            return null;
        } catch (\Exception $e) {
            Logger::syncError('Error getting group ID from form: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate that CleverReach group exists
     *
     * @param int $group_id CleverReach group ID
     * @return string|null Error message if group invalid, null if valid
     */
    private function validateGroupExists(int $group_id): ?string
    {
        try {
            $groupsService = GroupsService::getInstance();
            $group = $groupsService->getGroup($group_id);

            // Check if group exists and has valid data
            if (!$group || !is_array($group) || empty($group['id'])) {
                $error = "Group ID {$group_id} does not exist or was deleted from CleverReach";
                Logger::syncError($error);
                return $error;
            }

            // Additional check: try to get group attributes to verify accessibility
            $attributes = $groupsService->getGroupAttributes($group_id);
            if ($attributes === null) {
                $error = "Group ID {$group_id} exists but is not accessible (may be archived or deleted)";
                Logger::syncError($error);
                return $error;
            }

            return null; // Group is valid
        } catch (\Exception $e) {
            $error = "Error validating group ID {$group_id}: " . $e->getMessage();
            Logger::syncError($error);
            return $error;
        }
    }

    /**
     * Sync template fields with CR group attributes
     *
     * @param int $group_id Group ID
     * @param string $template_id Template ID (hash)
     * @return int Number of synced fields
     */
    private function syncFields(int $group_id, string $template_id): int
    {
        $templateService = TemplateService::getInstance();
        $template = $templateService->loadTemplate($template_id);

        if (!$template || !isset($template['fields'])) {
            return 0;
        }

        $template_fields = $template['fields'];

        // Filter only fields with send_to_cr = true
        $fields_to_sync = array_filter($template_fields, function($field) {
            return isset($field['send_to_cr']) && $field['send_to_cr'] === true;
        });

        if (empty($fields_to_sync)) {
            return 0;
        }

        // Get existing group attributes
        $groupsService = GroupsService::getInstance();
        $existing_attributes = $groupsService->getGroupAttributes($group_id);

        if (!is_array($existing_attributes)) {
            $existing_attributes = [];
        }

        // Index existing attributes by name for faster lookup
        $existing_by_name = [];
        foreach ($existing_attributes as $attr) {
            if (isset($attr['name'])) {
                $existing_by_name[$attr['name']] = $attr;
            }
        }

        $synced_count = 0;

        // CleverReach reserved attribute names that should not be created
        $reservedNames = ['email', 'activated', 'registered', 'deactivated', 'bounced', 'source'];

        // Sync each field
        foreach ($fields_to_sync as $field) {
            $field_name = $field['name'] ?? '';
            $field_type = $field['type'] ?? 'text';

            if (empty($field_name)) {
                continue;
            }

            // Skip reserved attribute names - they exist by default in CleverReach
            if (in_array(strtolower($field_name), $reservedNames)) {
                $synced_count++;
                continue;
            }

            // Check if field already exists (by name and type)
            if (isset($existing_by_name[$field_name])) {
                $existing_field = $existing_by_name[$field_name];

                // If type matches, assume it's the same field
                if (isset($existing_field['type']) && $existing_field['type'] === $field_type) {
                    $synced_count++;
                    continue; // Skip, field already exists
                }
            }

            // Create new attribute
            // CleverReach requires alphanumeric description, so sanitize it
            $description = $field['cr_description'] ?? $field_name;
            $sanitized_description = $this->sanitizeDescription($description);

            $attr_data = [
                'name' => $field_name,
                'type' => $field_type,
                'description' => $sanitized_description,
            ];

            if (isset($field['global'])) {
                $attr_data['global'] = $field['global'];
            }

            $result = $groupsService->createAttribute($group_id, $attr_data);

            if ($result) {
                $synced_count++;
            }
        }

        return $synced_count;
    }

    /**
     * Sanitize description to be alphanumeric only
     * CleverReach API requires alphanumeric descriptions
     *
     * @deprecated Use CleverReachHelper::sanitizeDescription() instead
     * @param string $description Original description
     * @return string Sanitized alphanumeric description
     */
    private function sanitizeDescription(string $description): string
    {
        return CleverReachHelper::sanitizeDescription($description);
    }
}
