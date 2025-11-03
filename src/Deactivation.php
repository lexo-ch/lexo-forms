<?php

namespace LEXO\LF;

use const LEXO\LF\{
    CACHE_KEY
};

class Deactivation
{
    public static function run()
    {
        delete_transient(CACHE_KEY);
        self::removeCapabilities();
    }

    /**
     * Remove custom capabilities from roles and specific users
     *
     * @return void
     */
    private static function removeCapabilities(): void
    {
        // Default roles that should have the capability removed
        $roles = ['administrator'];

        /**
         * Filter roles that should have manage_cleverreach_api capability removed
         *
         * @param array $roles Array of role slugs
         */
        $roles = apply_filters('lexo-forms/cr/access/deactivation/roles', $roles);

        // Remove capability from all specified roles
        foreach ($roles as $role_slug) {
            $role = get_role($role_slug);

            if ($role) {
                $role->remove_cap('manage_cleverreach_api');
            }
        }

        // Remove capability from specific users by username
        $usernames = [];

        /**
         * Filter usernames that should have manage_cleverreach_api capability removed
         *
         * @param array $usernames Array of usernames
         */
        $usernames = apply_filters('lexo-forms/cr/access/deactivation/usernames', $usernames);

        foreach ($usernames as $username) {
            $user = get_user_by('login', $username);

            if ($user) {
                $user->remove_cap('manage_cleverreach_api');
            }
        }
    }
}
