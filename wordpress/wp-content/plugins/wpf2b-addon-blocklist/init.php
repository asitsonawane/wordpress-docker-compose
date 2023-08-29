<?php declare(strict_types=1);
/**
 * Init
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   1.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist;

use          com\wp_fail2ban\lib\Activation\VersionCheck;
use          org\lecklider\charles\wordpress\wp_fail2ban\premium\WPf2b;

defined('ABSPATH') or exit;

/**
 * Hook: plugins_loaded
 *
 * Run earlier than the standard hook.
 *
 * @since  1.0.0
 *
 * @return void
 */
function plugins_loaded__early(): void
{
    add_filter(WP_FAIL2BAN_NS.'\Config::load.config', __NAMESPACE__.'\loader');
}
add_action('plugins_loaded', __NAMESPACE__.'\plugins_loaded__early', 7);

/**
 * Hook: plugins_loaded
 *
 * @since  1.0.0
 *
 * @return void
 */
function plugins_loaded(): void
{
    /**
     * Only hook events if Premium isn't
     */
    if (!class_exists('\\'.WP_FAIL2BAN_NS.'\premium\WPf2b') || !WPf2b::can_use_premium_code()) {
        add_action(WP_FAIL2BAN_NS.'\core\authenticate', __NAMESPACE__.'\core\authenticate', 10, 3);
        // Ignore soft fails for now
        //add_action(WP_FAIL2BAN_NS.'\core\wp_login_failed', __NAMESPACE__.'\core\wp_login_failed');

        /**
         * Comments
         */
        add_action(WP_FAIL2BAN_NS.'\feature\notify_post_author', __NAMESPACE__.'\feature\notify_post_author');
        add_action(WP_FAIL2BAN_NS.'\feature\comment_id_not_found', __NAMESPACE__.'\feature\comment_id_not_found');
        add_action(WP_FAIL2BAN_NS.'\feature\comment_closed', __NAMESPACE__.'\feature\comment_closed');
        add_action(WP_FAIL2BAN_NS.'\feature\comment_on_trash', __NAMESPACE__.'\feature\comment_on_trash');
        add_action(WP_FAIL2BAN_NS.'\feature\comment_on_draft', __NAMESPACE__.'\feature\comment_on_draft');
        add_action(WP_FAIL2BAN_NS.'\feature\comment_on_password_protected', __NAMESPACE__.'\feature\comment_on_password_protected');

        /**
         * Spam
         */
        add_action(WP_FAIL2BAN_NS.'\feature\log_spam_comment', __NAMESPACE__.'\feature\log_spam_comment');

        /**
         * Users
         */
        add_action(WP_FAIL2BAN_NS.'\feature\block_users.block_username_login', __NAMESPACE__.'\feature\block_users', 10, 3);

        /**
         * User enumeration
         */
        add_action(WP_FAIL2BAN_NS.'\feature\_log_bail_user_enum', __NAMESPACE__.'\feature\_log_bail_user_enum');
    }
}

/**
 * Hook: init
 *
 * @since  1.0.0
 *
 * @return void
 */
function init(): void
{
    /**
     * Only hook events if Premium isn't
     */
    if (!class_exists('\\'.WP_FAIL2BAN_NS.'\premium\WPf2b') || !WPf2b::can_use_premium_code()) {
        add_action(WP_FAIL2BAN_NS.'\feature\xmlrpc_login_error', __NAMESPACE__.'\feature\xmlrpc_login_error', 10, 2);
        add_filter(WP_FAIL2BAN_NS.'\feature\xmlrpc_pingback_error', __NAMESPACE__.'\feature\xmlrpc_pingback_error', 5);
    }

    add_action('rest_api_init', __NAMESPACE__.'\rest_api_init');
    add_action('wp_fail2ban_register', __NAMESPACE__.'\wp_fail2ban_register_plugin');
}

/**
 * Hook: Config::load.config
 *
 * Add our config settings.
 *
 * @since  1.0.0
 *
 * @param  array    $config
 *
 * @return array
 */
function loader(array $config): array
{
    return array_merge(
        $config,
        [
            'WP_FAIL2BAN_ADDON_BLOCKLIST_IGNORE_IPS' => [
                'validate'  => WP_FAIL2BAN_NS.'\Config::validate_ips',
                'unset'     => false,
                'field'     => [
                    'addon',
                    'blocklist',
                    'ignore-ips'
                ]
            ],
            'WP_FAIL2BAN_ADDON_BLOCKLIST_LOG' => [
                'validate'  => 'intval',
                'unset'     => false,
                'default'   => LOG_USER,
                'field'     => [
                    'addon',
                    'blocklist',
                    'facility'
                ]
            ],
            'WP_FAIL2BAN_ADDON_BLOCKLIST_CUSTOM_JAIL' => [
                'validate'  => 'boolval',
                'unset'     => true,
                'default'   => false,
                'field'     => [
                    'addon',
                    'blocklist',
                    'shared-jail'
                ]
            ]
        ]
    );
}

