<?php declare(strict_types=1);
/**
 * Functions
 *
 * @package wp-fail2ban-addon-blocklist
 * @since 1.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist;

use          com\wp_fail2ban\lib\Activation\VersionCheck;

use function org\lecklider\charles\wordpress\wp_fail2ban\remote_addr;
use function org\lecklider\charles\wordpress\wp_fail2ban\wf_fs;

defined('ABSPATH') or exit;

/**
 * @todo Autoloader
 */
require_once __DIR__.'/core.php';
require_once __DIR__.'/classes/Coder.php';
require_once __DIR__.'/classes/Debugger.php';
require_once __DIR__.'/classes/Decoder.php';
require_once __DIR__.'/classes/Encoder.php';
require_once __DIR__.'/classes/Event.php';
require_once __DIR__.'/classes/RestGetFree.php';
require_once __DIR__.'/classes/RestGetPremium.php';
require_once __DIR__.'/classes/RestPermission.php';
require_once __DIR__.'/classes/RestPost.php';
require_once __DIR__.'/classes/RestRoute.php';
require_once __DIR__.'/classes/SiteHealth.php';
require_once __DIR__.'/feature/comments.php';
require_once __DIR__.'/feature/spam.php';
require_once __DIR__.'/feature/user.php';
require_once __DIR__.'/feature/user-enum.php';
require_once __DIR__.'/feature/xmlrpc.php';

if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
    /**
     * Activation hook
     *
     * @since 1.0.0
     *
     * @return void
     */
    \register_activation_hook(WP_FAIL2BAN_ADDON_BLOCKLIST_FILE, function () {
        require_once __DIR__.'/vendor/wp-fail2ban/lib-activation/version-check.php';

        VersionCheck::check([
            '4.4.0'
        ], 'Blocklist', WP_FAIL2BAN_ADDON_BLOCKLIST_FILE);

        add_site_option(WP_FAIL2BAN_ADDON_BLOCKLIST_EVENTS_IP4, '');
        add_site_option(WP_FAIL2BAN_ADDON_BLOCKLIST_EVENTS_IP6, '');
        add_site_option(WP_FAIL2BAN_ADDON_BLOCKLIST_STATS, WP_FAIL2BAN_ADDON_BLOCKLIST_STATS_DEFAULT);
    });
}

/**
 * Hook: rest_api_init
 *
 * @since 1.0.0
 *
 * @return void
 */
function rest_api_init(): void
{
    RestRoute::rest_api_init();
}

/**
 * Hook: rest_api_init
 *
 * For subsites
 *
 * @since 1.0.0
 *
 * @return void
 */
function rest_api_init_teapot(): void
{
    RestRoute::rest_api_init_teapot();
}

/**
 * @since 0.9.3
 *
 * @return void
 *
 * @wp-f2b-hard \(WPf2b\+\+/blocklist\) Immediately block connections
 * @wp-f2b-soft \(WPf2b\+\+/blocklist\) Consider blocking connections
 */
function wp_fail2ban_register_plugin(): void
{
    try {
        do_action('wp_fail2ban_register_plugin', WP_FAIL2BAN_ADDON_BLOCKLIST_MESSAGE_SLUG, '<b>WPf2b++</b> | Blocklist');
        do_action('wp_fail2ban_register_message', WP_FAIL2BAN_ADDON_BLOCKLIST_MESSAGE_SLUG, [
            'slug'          => 'wpbl_block_hard',
            'fail'          => 'hard',
            'priority'      => LOG_NOTICE,
            'event_class'   => 'block',
            'event_desc'    => __('Immediate', 'wpf2b-addon-blocklist'),
            'event_id'      => 0x0004,
            'message'       => 'Immediately block connections',
            'vars'          => []
        ]);
        do_action('wp_fail2ban_register_message', WP_FAIL2BAN_ADDON_BLOCKLIST_MESSAGE_SLUG, [
            'slug'          => 'wpbl_block_soft',
            'fail'          => 'soft',
            'priority'      => LOG_NOTICE,
            'event_class'   => 'block',
            'event_desc'    => __('Recommended', 'wpf2b-addon-blocklist'),
            'event_id'      => 0x0008,
            'message'       => 'Consider blocking connections',
            'vars'          => []
        ]);
    } catch (\RuntimeException $e) {
        // @todo decide what to do
        error_log($e->getMessage());
    }
}

