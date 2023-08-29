<?php declare(strict_types=1);
/**
 * Admin
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   1.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist\config;

use          org\lecklider\charles\wordpress\wp_fail2ban\premium\WPf2b;

use function org\lecklider\charles\wordpress\wp_fail2ban\render_tabs;

defined('ABSPATH') or exit;

/**
 * Init tabs
 *
 * @since  1.0.0
 *
 * @param  bool $init
 *
 * @return bool
 */
function init_tabs(bool $init): bool
{
    require_once __DIR__.'/config/SettingsFree.php';

    if (class_exists(WP_FAIL2BAN_NS.'\premium\WPf2b') && WPf2b::can_use_premium_code()) {
        require_once __DIR__.'/config/SettingsPremium.php';
        new TabSettingsPremium();
    } else {
        new TabSettingsFree();
    }

    return $init;
}
add_filter('wp_fail2ban_init_tabs', __NAMESPACE__.'\init_tabs');

/**
 * Hook: security tabs - for ClassicPress
 *
 * @since  1.0.0
 *
 * @param  array    $tabs   Array of tab slugs
 *
 * @return array    Modified array of tab slugs
 */
function security(array $tabs): array
{
    $tabs[] = 'addon-blocklist-settings';

    return $tabs;
}
add_filter(WP_FAIL2BAN_NS.'\security.tabs', __NAMESPACE__.'\security', 100);

/**
 * Hook: settings tabs - for WordPress
 *
 * @since  1.0.0
 *
 * @param  array    $tabs   Array of tab slugs
 *
 * @return array    Possibly modified array of tab slugs
 */
function settings(array $tabs): array
{
    if (!function_exists('\add_security_page')) {
        $tabs[] = 'addon-blocklist-settings';
    }

    return $tabs;
}
add_filter(WP_FAIL2BAN_NS.'\settings.tabs', __NAMESPACE__.'\settings', 100);

