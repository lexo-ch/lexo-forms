<?php

namespace LEXO\LF\Core\Services;

use LEXO\LF\Core\Abstracts\Singleton;
use LEXO\LF\Core\Plugin\CleverReachAPI;
use LEXO\LF\Core\Plugin\CleverReachAuth;
use LEXO\LF\Core\Utils\Logger;

use const LEXO\LF\{
    CACHE_KEY_GROUPS_LIST,
    CACHE_KEY_GROUP_PREFIX,
    CACHE_EXPIRY_LONG
};

/**
 * Groups Service
 *
 * Handles CleverReach Groups CRUD operations with caching.
 * Follows LEXO standard - business logic separation.
 */
class GroupsService extends Singleton
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
     * Get all groups (with cache)
     *
     * @return array|null
     */
    public function getGroups(): ?array
    {
        // Check cache first
        $cached = get_transient(CACHE_KEY_GROUPS_LIST);
        if ($cached !== false) {
            return $cached;
        }

        try {
            $api = $this->getAPI();
            if (!$api) {
                return null;
            }

            $response = $api->getGroups();

            if ($response['success'] && isset($response['data'])) {
                $groups = $response['data'];

                // Cache the result
                set_transient(CACHE_KEY_GROUPS_LIST, $groups, CACHE_EXPIRY_LONG);

                return $groups;
            }

            return null;
        } catch (\Exception $e) {
            Logger::apiError('GroupsService::getGroups error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get single group by ID (with cache)
     *
     * @param int $groupId
     * @return array|null
     */
    public function getGroup(int $groupId): ?array
    {
        // Check cache first
        $cacheKey = CACHE_KEY_GROUP_PREFIX . $groupId;
        $cached = get_transient($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        try {
            $api = $this->getAPI();
            if (!$api) {
                return null;
            }
            $response = $api->getGroup($groupId);

            if ($response['success'] && isset($response['data'])) {
                $group = $response['data'];

                // Cache the result
                set_transient($cacheKey, $group, CACHE_EXPIRY_LONG);

                return $group;
            }

            return null;
        } catch (\Exception $e) {
            Logger::apiError('GroupsService::getGroup error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create new group
     *
     * @param array $groupData
     * @return array|null
     */
    public function createGroup(array $groupData): ?array
    {
        try {
            $api = $this->getAPI();
            if (!$api) {
                return null;
            }
            $response = $api->createGroup($groupData);

            if ($response['success'] && isset($response['data'])) {
                // Clear groups list cache
                $this->clearCache();

                return $response['data'];
            }

            return null;
        } catch (\Exception $e) {
            Logger::apiError('GroupsService::createGroup error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update existing group
     *
     * @param int $groupId
     * @param array $groupData
     * @return array|null
     */
    public function updateGroup(int $groupId, array $groupData): ?array
    {
        try {
            $api = $this->getAPI();
            if (!$api) {
                return null;
            }
            $response = $api->updateGroup($groupId, $groupData);

            if ($response['success'] && isset($response['data'])) {
                // Clear both list and specific group cache
                $this->clearCache();
                $this->clearGroupCache($groupId);

                return $response['data'];
            }

            return null;
        } catch (\Exception $e) {
            Logger::apiError('GroupsService::updateGroup error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete group
     *
     * @param int $groupId
     * @return bool
     */
    public function deleteGroup(int $groupId): bool
    {
        try {
            $auth = CleverReachAuth::getInstance();
            $token = $auth->getValidToken();

            if (!$token) {
                return false;
            }

            $api = new CleverReachAPI($token);
            $response = $api->deleteGroup($groupId);

            if ($response['success']) {
                // Clear both list and specific group cache
                $this->clearCache();
                $this->clearGroupCache($groupId);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Logger::apiError('GroupsService::deleteGroup error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get group attributes (fields)
     *
     * @param int $groupId Group ID
     * @return array|null Array of attributes or null on failure
     */
    public function getGroupAttributes(int $groupId): ?array
    {
        try {
            $api = $this->getAPI();
            if (!$api) {
                return null;
            }

            // Use the dedicated getGroupAttributes method
            $response = $api->getGroupAttributes($groupId);

            if ($response['success'] && isset($response['data'])) {
                return $response['data'];
            }

            return null;
        } catch (\Exception $e) {
            Logger::apiError('GroupsService::getGroupAttributes error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create group attribute (field)
     *
     * @param int $groupId Group ID
     * @param array $fieldData Field data (name, type, description, etc.)
     * @return array|null Created attribute or null on failure
     */
    public function createAttribute(int $groupId, array $fieldData): ?array
    {
        try {
            $api = $this->getAPI();
            if (!$api) {
                return null;
            }

            // Use the dedicated createGroupAttribute method
            $response = $api->createGroupAttribute($groupId, $fieldData);

            if ($response['success'] && isset($response['data'])) {
                return $response['data'];
            }

            return null;
        } catch (\Exception $e) {
            Logger::apiError('GroupsService::createAttribute error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Clear groups list cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        delete_transient(CACHE_KEY_GROUPS_LIST);
    }

    /**
     * Clear specific group cache
     *
     * @param int $groupId
     * @return void
     */
    public function clearGroupCache(int $groupId): void
    {
        delete_transient(CACHE_KEY_GROUP_PREFIX . $groupId);
    }

    /**
     * Clear all groups cache (list + all individual groups)
     *
     * @return void
     */
    public function clearAllCache(): void
    {
        global $wpdb;

        // Clear list cache
        $this->clearCache();

        // Clear all individual group caches
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . CACHE_KEY_GROUP_PREFIX . '%'
            )
        );
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_' . CACHE_KEY_GROUP_PREFIX . '%'
            )
        );
    }
}
