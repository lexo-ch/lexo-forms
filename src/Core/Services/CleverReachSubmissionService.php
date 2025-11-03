<?php

namespace LEXO\LF\Core\Services;

use Exception;
use LEXO\LF\Core\Abstracts\Singleton;
use LEXO\LF\Core\Plugin\CleverReachAPI;
use LEXO\LF\Core\Plugin\CleverReachAuth;
use LEXO\LF\Core\Utils\Logger;

use const LEXO\LF\FIELD_PREFIX;

/**
 * CleverReach Submission Service
 *
 * Handles form submissions to CleverReach including field creation and recipient management.
 * Follows LEXO standard - business logic separation.
 */
class CleverReachSubmissionService extends Singleton
{
    protected static $instance = null;

    private ?CleverReachAPI $api = null;
    private CleverReachAuth $auth;

    /**
     * Initialize the service
     */
    public function __construct()
    {
        $this->auth = CleverReachAuth::getInstance();
    }

    /**
     * Get API instance (reuses same instance for performance)
     *
     * @return CleverReachAPI|null
     */
    private function getAPI(): ?CleverReachAPI
    {
        $token = $this->auth->getValidToken();
        if (!$token) {
            return null;
        }

        // Reuse existing instance or create new one
        if ($this->api === null) {
            $this->api = new CleverReachAPI($token);
        } else {
            // Update token in case it was refreshed
            $this->api->setToken($token);
        }

        return $this->api;
    }

    /**
     * Get singleton instance
     *
     * @return static
     */
    public static function getInstance(): self
    {
        return (static::$instance === null)
            ? static::$instance = new static()
            : static::$instance;
    }

    /**
     * Submit form data to CleverReach
     *
     * @param int $form_id WordPress form post ID
     * @param array $form_data Submitted form data
     * @param array $template Form template with fields definition
     * @return array Status array with 'success' (bool), 'already_exists' (bool), 'error' (string|null)
     */
    public function submitFormToCleverReach(int $form_id, array $form_data, array $template): array
    {
        try {
            // Get CleverReach integration settings from meta fields
            $general_settings = get_field(FIELD_PREFIX . 'general_settings', $form_id) ?: [];
            $handler_type = $general_settings[FIELD_PREFIX . 'handler_type'] ?? '';

            // Get cr_form_id and cr_status from cr_integration group
            $cr_integration = get_field(FIELD_PREFIX . 'cr_integration', $form_id) ?: [];
            $cr_form_id = $cr_integration[FIELD_PREFIX . 'form_id'] ?? null;
            $cr_status = $cr_integration[FIELD_PREFIX . 'cr_status'] ?? null;

            // Check if CleverReach integration is enabled
            if ($handler_type !== 'email_and_cr' && $handler_type !== 'cr_only') {
                Logger::formError('CleverReach integration not enabled', $form_id);
                return ['success' => false, 'already_exists' => false, 'error' => 'CleverReach integration not enabled'];
            }

            // Check if integration is properly configured
            if ($cr_status !== 'OK') {
                Logger::formError('CleverReach integration not properly configured. Status: ' . $cr_status, $form_id);
                return ['success' => false, 'already_exists' => false, 'error' => 'CleverReach integration not properly configured'];
            }

            // Get CleverReach form ID
            if (!$cr_form_id) {
                Logger::formError('No CleverReach form ID configured', $form_id);
                return ['success' => false, 'already_exists' => false, 'error' => 'No CleverReach form ID configured'];
            }

            // Initialize API
            $this->api = $this->getAPI();
            if (!$this->api) {
                Logger::authError('No valid CleverReach token available');
                return ['success' => false, 'already_exists' => false, 'error' => 'No valid CleverReach token available'];
            }

            // Get CleverReach form details to find group ID
            $cr_form = $this->api->getForm($cr_form_id);
            if (!$cr_form['success'] || !isset($cr_form['data']['customer_tables_id'])) {
                Logger::apiError('Failed to get CleverReach form details for form ID: ' . $cr_form_id);
                return ['success' => false, 'already_exists' => false, 'error' => 'Failed to get CleverReach form details'];
            }

            $group_id = $cr_form['data']['customer_tables_id'];

            $group_details = $this->api->getGroup($group_id);

            if (!$group_details['success'] || $group_details['http_code'] !== 200) {
                Logger::apiError('Failed to get group details for group ID: ' . $group_id);
                return ['success' => false, 'already_exists' => false, 'error' => 'Failed to get group details'];
            }

            // Ensure required fields exist in CleverReach group
            $this->ensureRequiredFields($group_id, $template['fields']);

            // Prepare recipient data (only fields marked for CleverReach)
            $recipient_data = $this->prepareRecipientData($form_data, $template['fields']);

            if (empty($recipient_data['email'])) {
                Logger::formError('No email address provided in form data');
                return ['success' => false, 'already_exists' => false, 'error' => 'No email address provided'];
            }

            // Check recipient status and handle accordingly
            $email = $recipient_data['email'];
            $recipient_status = $this->getRecipientStatus($group_id, $email);
            $send_double_opt_in = in_array($recipient_status, ['inactive', 'not_found'], true);

            switch ($recipient_status) {
                case 'activated':
                    // Recipient already exists and is active
                    Logger::warning('Recipient already activated: ' . $email, Logger::CATEGORY_API);
                    return ['success' => false, 'already_exists' => true, 'error' => 'Recipient already activated'];

                case 'inactive':
                    $result = $this->updateExistingRecipient($group_id, $email, $recipient_data);
                    break;

                case 'not_found':
                default:
                    $result = $this->addRecipientToGroup($group_id, $recipient_data);
                    break;
            }

            if ($result) {
                if ($send_double_opt_in) {
                    $double_opt_in_result = $this->api->sendDoubleOptInEmail($group_id, $email, $cr_form_id);

                    if (
                        !$double_opt_in_result['success']
                        || $double_opt_in_result['http_code'] < 200
                        || $double_opt_in_result['http_code'] >= 300
                    ) {
                        Logger::apiError('Failed to send double opt-in email for: ' . $email);
                        return ['success' => false, 'already_exists' => false, 'error' => 'Failed to send double opt-in email'];
                    }
                }

                return ['success' => true, 'already_exists' => false, 'error' => null];
            } else {
                return ['success' => false, 'already_exists' => false, 'error' => 'Failed to add/update recipient'];
            }

        } catch (Exception $e) {
            Logger::error('CleverReach submission error: ' . $e->getMessage(), Logger::CATEGORY_API);
            return ['success' => false, 'already_exists' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Ensure required fields exist in CleverReach group
     *
     * @param int $group_id CleverReach group ID
     * @param array $fields Template fields definition
     * @return void
     */
    private function ensureRequiredFields(int $group_id, array $fields): void
    {
        // CleverReach reserved attribute names that should not be created
        $reservedNames = ['email', 'activated', 'registered', 'deactivated', 'bounced', 'source'];

        foreach ($fields as $field) {
            if ($field['send_to_cr']) {
                $fieldName = $field['name'] ?? '';

                // Skip reserved attribute names
                if (in_array(strtolower($fieldName), $reservedNames)) {
                    continue;
                }

                try {
                    // Use cr_description if available, fallback to field name
                    $description = $field['cr_description'] ?? $fieldName;

                    $this->api->ensureAttribute(
                        $fieldName,
                        $field['type'],
                        $description,
                        $field['global'],
                        $field['global'] ? null : $group_id
                    );
                } catch (Exception $e) {
                    Logger::apiError('Failed to ensure attribute ' . $fieldName . ': ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Prepare recipient data for CleverReach submission
     *
     * @param array $form_data Submitted form data
     * @param array $fields Template fields definition
     * @return array Prepared recipient data
     */
    private function prepareRecipientData(array $form_data, array $fields): array
    {
        $email = '';
        $global_attributes = [];
        $group_attributes = [];

        foreach ($fields as $field) {
            if ($field['send_to_cr'] && isset($form_data[$field['name']])) {
                $field_name = $field['name'];
                $value = $form_data[$field_name];

                if (!empty($value)) {
                    if ($field_name === 'email') {
                        $email = $value;
                    } else {
                        // Separate global and group attributes
                        if ($field['global']) {
                            $global_attributes[$field_name] = $value;
                        } else {
                            $group_attributes[$field_name] = $value;
                        }
                    }
                }
            }
        }

        // CleverReach expects all attributes in one 'attributes' object
        // but we need to handle them differently during creation
        $all_attributes = array_merge($global_attributes, $group_attributes);

        return [
            'email' => $email,
            'attributes' => $all_attributes,
            'global_attributes' => $global_attributes,
            'group_attributes' => $group_attributes,
            'registered' => time()
        ];
    }

    /**
     * Get recipient status in CleverReach group
     *
     * @param int $group_id CleverReach group ID
     * @param string $email Recipient email
     * @return string Status: 'activated', 'inactive', or 'not_found'
     */
    private function getRecipientStatus(int $group_id, string $email): string
    {
        try {
            $recipient = $this->api->getRecipient($group_id, $email);

            if (!$recipient['success'] || $recipient['http_code'] !== 200) {
                return 'not_found';
            }

            $isActivated = ($recipient['data']['activated'] ?? 0) > 0;
            return $isActivated ? 'activated' : 'inactive';

        } catch (Exception $e) {
            return 'not_found';
        }
    }

    /**
     * Add recipient to CleverReach group
     *
     * @param int $group_id CleverReach group ID
     * @param array $recipient_data Recipient data
     * @return bool Success status
     */
    private function addRecipientToGroup(int $group_id, array $recipient_data): bool
    {
        try {
            // Create recipient data with global_attributes separated
            $addData = [
                'email' => $recipient_data['email'],
                'attributes' => $recipient_data['group_attributes'], // Only group attributes here
                'global_attributes' => $recipient_data['global_attributes'], // Global attributes separately
                'registered' => $recipient_data['registered']
            ];

            $result = $this->api->addRecipient($group_id, $addData);

            if ($result['success'] && $result['http_code'] >= 200 && $result['http_code'] < 300) {
                return true;
            } else {
                return false;
            }

        } catch (Exception $e) {
            Logger::error('Error adding recipient to group: ' . $e->getMessage(), Logger::CATEGORY_API);
            return false;
        }
    }

    /**
     * Update existing recipient in CleverReach group
     *
     * @param int $group_id CleverReach group ID
     * @param string $email Recipient email
     * @param array $recipient_data Recipient data
     * @return bool Success status
     */
    private function updateExistingRecipient(int $group_id, string $email, array $recipient_data): bool
    {
        try {
            // Update with separated attributes structure
            $updateData = [
                'email' => $email,
                'attributes' => $recipient_data['group_attributes'],
                'global_attributes' => $recipient_data['global_attributes']
            ];

            $result = $this->api->updateRecipient($group_id, $email, $updateData);

            if ($result['success'] && $result['http_code'] >= 200 && $result['http_code'] < 300) {
                return true;
            } else {
                return false;
            }

        } catch (Exception $e) {
            Logger::apiError('Error updating existing recipient: ' . $e->getMessage());
            return false;
        }
    }
}
