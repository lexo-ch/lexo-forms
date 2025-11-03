<?php

namespace LEXO\LF\Core\Services;

use LEXO\LF\Core\Abstracts\Singleton;
use LEXO\LF\Core\Plugin\CleverReachAPI;
use LEXO\LF\Core\Plugin\CleverReachAuth;
use LEXO\LF\Core\Utils\Logger;

use const LEXO\LF\{
    CACHE_KEY_FORMS_LIST,
    CACHE_KEY_FORM_PREFIX,
    CACHE_EXPIRY_LONG
};

/**
 * Forms Service
 *
 * Handles CleverReach Forms retrieval with caching.
 * Follows LEXO standard - business logic separation.
 * Note: Forms are read-only, created/edited via CleverReach admin panel.
 */
class FormsService extends Singleton
{
    protected static $instance = null;

    /**
     * Cached API instance to avoid creating multiple instances
     * @var CleverReachAPI|null
     */
    private ?CleverReachAPI $api_instance = null;

    /**
     * Get API instance (reuses same instance for performance)
     *
     * @return CleverReachAPI|null
     */
    private function getAPI(): ?CleverReachAPI
    {
        $auth = CleverReachAuth::getInstance();
        $token = $auth->getValidToken();

        if (!$token) {
            return null;
        }

        // Reuse existing instance or create new one
        if ($this->api_instance === null) {
            $this->api_instance = new CleverReachAPI($token);
        } else {
            // Update token in case it was refreshed
            $this->api_instance->setToken($token);
        }

        return $this->api_instance;
    }

    /**
     * Get all forms (with cache)
     *
     * @return array|null
     */
    public function getForms(): ?array
    {
        // Check cache first
        $cached = get_transient(CACHE_KEY_FORMS_LIST);
        if ($cached !== false) {
            return $cached;
        }

        try {
            $api = $this->getAPI();
            if (!$api) {
                return null;
            }

            $response = $api->getForms();

            if ($response['success'] && isset($response['data'])) {
                $forms = $response['data'];

                // Cache the result
                set_transient(CACHE_KEY_FORMS_LIST, $forms, CACHE_EXPIRY_LONG);

                return $forms;
            }

            return null;
        } catch (\Exception $e) {
            Logger::apiError('FormsService::getForms error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get single form by ID (with cache)
     *
     * @param int $formId
     * @return array|null
     */
    public function getForm(int $formId): ?array
    {
        // Check cache first
        $cacheKey = CACHE_KEY_FORM_PREFIX . $formId;
        $cached = get_transient($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        try {
            $api = $this->getAPI();
            if (!$api) {
                return null;
            }
            $response = $api->getForm($formId);

            if ($response['success'] && isset($response['data'])) {
                $form = $response['data'];

                // Cache the result
                set_transient($cacheKey, $form, CACHE_EXPIRY_LONG);

                return $form;
            }

            return null;
        } catch (\Exception $e) {
            Logger::apiError('FormsService::getForm error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create new CleverReach form
     *
     * @param int $groupId Group ID to connect the form to
     * @param string $name Form name
     * @param string $type Form type (DOI, NOI, etc.)
     * @return array|null Created form data or null on failure
     */
    public function createForm(int $groupId, string $name, string $type = 'default'): ?array
    {
        try {
            $api = $this->getAPI();
            if (!$api) {
                return null;
            }

            // Use the dedicated createFormFromTemplate method
            $response = $api->createFormFromTemplate($groupId, $name, $type);

            if ($response['success'] && isset($response['data'])) {
                // Clear cache to refresh forms list
                $this->clearCache();

                return $response['data'];
            }

            return null;
        } catch (\Exception $e) {
            Logger::apiError('FormsService::createForm error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Clear forms list cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        delete_transient(CACHE_KEY_FORMS_LIST);
    }

    /**
     * Clear specific form cache
     *
     * @param int $formId
     * @return void
     */
    public function clearFormCache(int $formId): void
    {
        delete_transient(CACHE_KEY_FORM_PREFIX . $formId);
    }

    /**
     * Clear all forms cache (list + all individual forms)
     *
     * @return void
     */
    public function clearAllCache(): void
    {
        global $wpdb;

        // Clear list cache
        $this->clearCache();

        // Clear all individual form caches
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . CACHE_KEY_FORM_PREFIX . '%'
            )
        );
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_' . CACHE_KEY_FORM_PREFIX . '%'
            )
        );
    }
}
