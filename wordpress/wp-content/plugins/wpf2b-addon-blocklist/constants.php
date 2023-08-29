<?php declare(strict_types=1);
/**
 * Constants
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   1.0.0
 */
namespace com\wp_fail2ban\addons\Blocklist;

// @codeCoverageIgnoreStart

if (!defined('WP_FAIL2BAN_ADDON_BLOCKLIST_VER')) {
    define('WP_FAIL2BAN_ADDON_BLOCKLIST_VER', '2.2.1');
}
if (!defined('WP_FAIL2BAN_ADDON_BLOCKLIST_VER_SHORT')) {
    define('WP_FAIL2BAN_ADDON_BLOCKLIST_VER_SHORT', '2');
}
if (!defined('WP_FAIL2BAN_ADDON_BLOCKLIST_I18N')) {
    define('WP_FAIL2BAN_ADDON_BLOCKLIST_I18N', 'wp-fail2ban-addon-blocklist');
}
if (!defined('WP_FAIL2BAN_ADDON_BLOCKLIST_DIR')) {
    define('WP_FAIL2BAN_ADDON_BLOCKLIST_DIR', __DIR__);
}
if (!defined('WP_FAIL2BAN_ADDON_BLOCKLIST_FILE')) {
    define('WP_FAIL2BAN_ADDON_BLOCKLIST_FILE', __DIR__.'/addon.php');
}
if (!defined('WP_FAIL2BAN_ADDON_BLOCKLIST_NS')) {
    define('WP_FAIL2BAN_ADDON_BLOCKLIST_NS', __NAMESPACE__);
}
if (!defined('WP_FAIL2BAN_ADDON_BLOCKLIST_EVENTS_IP4')) {
    define('WP_FAIL2BAN_ADDON_BLOCKLIST_EVENTS_IP4', 'wp-fail2ban-blocklist-events-ip4');
}
if (!defined('WP_FAIL2BAN_ADDON_BLOCKLIST_EVENTS_IP6')) {
    define('WP_FAIL2BAN_ADDON_BLOCKLIST_EVENTS_IP6', 'wp-fail2ban-blocklist-events-ip6');
}
if (!defined('WP_FAIL2BAN_ADDON_BLOCKLIST_STATS')) {
    define('WP_FAIL2BAN_ADDON_BLOCKLIST_STATS', 'wp-fail2ban-blocklist-stats');
}
if (!defined('WP_FAIL2BAN_ADDON_BLOCKLIST_STATS_DEFAULT')) {
    define('WP_FAIL2BAN_ADDON_BLOCKLIST_STATS_DEFAULT', [
        'last' => [
            'GET' => [
                'time' => null,
                'count' => null,
                'id' => PHP_INT_MAX
            ],
            'POST' => [
                'time' => null,
                'count' => null
            ]
        ]
    ]);
}
if (!defined('WP_FAIL2BAN_ADDON_BLOCKLIST_MESSAGE_SLUG')) {
    define('WP_FAIL2BAN_ADDON_BLOCKLIST_MESSAGE_SLUG', 'WPf2b++/blocklist');
}

