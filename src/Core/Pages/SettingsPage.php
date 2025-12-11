<?php

namespace LEXO\LF\Core\Pages;

use LEXO\LF\Core\Plugin\CleverReachAuth;

/**
 * Settings Page View
 *
 * Renders the CleverReach settings admin page.
 * Follows LEXO standard - only view rendering, no business logic.
 */
class SettingsPage
{
    /**
     * Render settings page content
     *
     * @return void
     */
    public function getSettingsPageContent(): void
    {
        $auth = CleverReachAuth::getInstance();
        $isConnected = $auth->isConnected();
        $clientId = get_option('cleverreach_client_id', '');
        $clientSecret = get_option('cleverreach_client_secret', '');
        $redirectUri = get_option('cleverreach_redirect_uri', $auth->getDefaultRedirectUri());

        ?>
        <div class="wrap">
            <h1><?php echo __('CleverReach API Settings', 'lexoforms'); ?></h1>

            <div class="card">
                <h2><?php echo __('Setup Instructions', 'lexoforms'); ?></h2>

                <h3><?php echo __('Step 1: Create a CleverReach OAuth App', 'lexoforms'); ?></h3>
                <ol>
                    <li><?php echo __('Log in to your', 'lexoforms'); ?> <a href="https://eu1.cleverreach.com/admin/login.php" target="_blank">CleverReach</a> <?php echo __('account', 'lexoforms'); ?></li>
                    <li><?php echo __('Navigate to', 'lexoforms'); ?> <strong><?php echo __('My Account', 'lexoforms'); ?></strong> → <strong><?php echo __('Extras', 'lexoforms'); ?></strong> → <strong><a href="https://eu1.cleverreach.com/admin/account_rest.php" target="_blank"><?php echo __('REST API', 'lexoforms'); ?></a></strong></li>
                    <li><?php echo __('Click on', 'lexoforms'); ?> <strong><?php echo __('Create new OAuth App', 'lexoforms'); ?></strong></li>
                    <li><?php echo __('Fill in the required fields:', 'lexoforms'); ?>
                        <ul style="margin-top: 8px; margin-left: 20px;">
                            <li><strong><?php echo __('App Name', 'lexoforms'); ?>:</strong> <?php echo __('Choose a descriptive name (e.g., "LEXO Forms - yoursite.com")', 'lexoforms'); ?></li>
                            <li><strong><?php echo __('Description', 'lexoforms'); ?>:</strong> <?php echo __('Optional description of your integration', 'lexoforms'); ?></li>
                        </ul>
                    </li>
                    <li><?php echo __('Click', 'lexoforms'); ?> <strong><?php echo __('Save', 'lexoforms'); ?></strong> <?php echo __('to create the app', 'lexoforms'); ?></li>
                </ol>

                <h3><?php echo __('Step 2: Configure the OAuth App', 'lexoforms'); ?></h3>
                <ol>
                    <li><?php echo __('After creating the app, click on it to open the settings', 'lexoforms'); ?></li>
                    <li><?php echo __('In the', 'lexoforms'); ?> <strong><?php echo __('Redirect URI', 'lexoforms'); ?></strong> <?php echo __('field, add the following URL:', 'lexoforms'); ?>
                        <br><code style="display: inline-block; margin-top: 8px; padding: 8px 12px; background: #f0f0f1; border-radius: 4px;"><?php echo esc_html($auth->getDefaultRedirectUri()); ?></code>
                    </li>
                    <li><?php echo __('Copy the', 'lexoforms'); ?> <strong><?php echo __('Client ID', 'lexoforms'); ?></strong> <?php echo __('and', 'lexoforms'); ?> <strong><?php echo __('Client Secret', 'lexoforms'); ?></strong> <?php echo __('from the app details', 'lexoforms'); ?></li>
                </ol>

                <h3><?php echo __('Step 3: Connect WordPress to CleverReach', 'lexoforms'); ?></h3>
                <ol>
                    <li><?php echo __('Paste the Client ID and Client Secret into the fields below', 'lexoforms'); ?></li>
                    <li><?php echo __('Click', 'lexoforms'); ?> <strong><?php echo __('Save Settings', 'lexoforms'); ?></strong></li>
                    <li><?php echo __('Click', 'lexoforms'); ?> <strong><?php echo __('Connect to CleverReach', 'lexoforms'); ?></strong> <?php echo __('and authorize the connection', 'lexoforms'); ?></li>
                </ol>

                <p style="margin-top: 15px;">
                    <em><?php echo __('For more details, see the', 'lexoforms'); ?> <a href="https://rest.cleverreach.com/howto/" target="_blank"><?php echo __('CleverReach REST API documentation', 'lexoforms'); ?></a>.</em>
                </p>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h2><?php echo __('API Configurations', 'lexoforms'); ?></h2>

                <?php if (!$isConnected) { ?>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <input type="hidden" name="action" value="cr_save_settings">
                        <?php wp_nonce_field(\LEXO\LF\FIELD_NAME); ?>

                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php echo __('Client ID', 'lexoforms'); ?></th>
                                <td>
                                    <input type="text" name="cleverreach_client_id"
                                           value="<?php echo esc_attr($clientId); ?>"
                                           class="regular-text" required />
                                    <p class="description"><?php echo __('Enter your CleverReach Client ID', 'lexoforms'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php echo __('Client Secret', 'lexoforms'); ?></th>
                                <td>
                                    <input type="password" name="cleverreach_client_secret"
                                           value="<?php echo esc_attr($clientSecret); ?>"
                                           class="regular-text" required />
                                    <p class="description"><?php echo __('Enter your CleverReach Client Secret', 'lexoforms'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php echo __('Redirect URI', 'lexoforms'); ?></th>
                                <td>
                                    <input type="text" id="cleverreach_redirect_uri"
                                           name="cleverreach_redirect_uri"
                                           value="<?php echo esc_attr($redirectUri); ?>"
                                           class="regular-text" />
                                    <button type="button" id="auto-generate-redirect" class="button" style="margin-left: 10px;">
                                        <?php echo __('Auto Generate', 'lexoforms'); ?>
                                    </button>
                                    <p class="description"><?php echo __('Copy this URL into your CleverReach App settings. Use "Auto Generate" for default setup or enter custom URL.', 'lexoforms'); ?></p>
                                    <input type="hidden" id="default_redirect_uri" value="<?php echo esc_attr($auth->getDefaultRedirectUri()); ?>" />
                                </td>
                            </tr>
                        </table>

                        <?php submit_button(__('Save Settings', 'lexoforms')); ?>
                    </form>

                    <?php if ($clientId && $clientSecret) { ?>
                        <hr>
                        <h3><?php echo __('Connect to CleverReach', 'lexoforms'); ?></h3>
                        <p><?php echo __('After saving the settings, click the button below to connect to CleverReach:', 'lexoforms'); ?></p>
                        <a href="<?php echo esc_url($auth->getAuthUrl()); ?>" target="_blank" class="button button-primary">
                            <?php echo __('Connect to CleverReach', 'lexoforms'); ?>
                        </a>
                    <?php } ?>

                <?php } else { ?>
                    <div style="padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 20px;">
                        <h3 style="margin-top: 0; color: #155724;">✓ <?php echo __('Successfully connected to CleverReach', 'lexoforms'); ?></h3>
                        <p style="margin-bottom: 0; color: #155724;"><?php echo __('API token is active and ready to use.', 'lexoforms'); ?></p>
                    </div>

                    <p>
                        <button type="button" id="test-connection" class="button"><?php echo __('Test Connection', 'lexoforms'); ?></button>
                        <button type="button" id="disconnect-cleverreach" class="button button-secondary" style="margin-left: 10px;">
                            <?php echo __('Disconnect', 'lexoforms'); ?>
                        </button>
                    </p>

                    <div id="connection-result" style="margin-top: 15px;"></div>
                <?php } ?>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h2><?php echo __('Email Backup Settings', 'lexoforms'); ?></h2>
                <p class="description"><?php echo __('Configure fallback behavior when CleverReach integration fails', 'lexoforms'); ?></p>

                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="cr_save_fallback_email">
                    <?php wp_nonce_field(\LEXO\LF\FIELD_NAME); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php echo __('Fallback Admin Email', 'lexoforms'); ?></th>
                            <td>
                                <input type="email" name="<?php echo esc_attr(\LEXO\LF\FIELD_PREFIX . 'fallback_admin_email'); ?>"
                                       value="<?php echo esc_attr(get_option(\LEXO\LF\FIELD_PREFIX . 'fallback_admin_email', '')); ?>"
                                       class="regular-text" />
                                <p class="description">
                                    <?php echo __('When CleverReach submission fails, form data will be sent to this email address. If empty, it will use the site admin email:', 'lexoforms'); ?>
                                    <strong><?php echo esc_html(get_option('admin_email')); ?></strong>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(__('Save Fallback Email', 'lexoforms')); ?>
                </form>
            </div>
        </div>
        <?php
    }
}