<?php

namespace LEXO\LF;

class Activation
{
    public function run()
    {
        $this->addCapabilities();
    }

    /**
     * Add custom capabilities to roles and specific users
     *
     * @return void
     */
    private function addCapabilities(): void
    {
        // Default roles that should get the capability
        $roles = ['administrator'];

        /**
         * Filter roles that should receive manage_cleverreach_api capability
         *
         * @param array $roles Array of role slugs
         */
        $roles = apply_filters('lexo-forms/cr/access/activation/roles', $roles);

        // Add capability to all specified roles
        foreach ($roles as $role_slug) {
            $role = get_role($role_slug);

            if ($role) {
                $role->add_cap('manage_cleverreach_api');
            }
        }

        // Add capability to specific users by username
        $usernames = [];

        /**
         * Filter usernames that should receive manage_cleverreach_api capability
         *
         * @param array $usernames Array of usernames
         */
        $usernames = apply_filters('lexo-forms/cr/access/activation/usernames', $usernames);

        foreach ($usernames as $username) {
            $user = get_user_by('login', $username);

            if ($user) {
                $user->add_cap('manage_cleverreach_api');
            }
        }
    }
}
