<?php declare(strict_types=1);
/**
 * Admin
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   1.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist;

use          org\lecklider\charles\wordpress\wp_fail2ban\TabBase;
use          org\lecklider\charles\wordpress\wp_fail2ban\premium\WPf2b;

use function com\wp_fail2ban\addons\Blocklist\Freemius\wpf2b_addon_blocklist_fs;
use function org\lecklider\charles\wordpress\wp_fail2ban\add_wpf2b_addon_page;
use function org\lecklider\charles\wordpress\wp_fail2ban\wf_fs;

defined('ABSPATH') or exit;

require_once __DIR__.'/config.php';
require_once __DIR__.'/widgets.php';

/**
 * Override Freemius connect message
 *
 * @since   1.0.0
 *
 * @param   string  $msg
 * @param   string  $first_name
 * @param   string  $plugin_name
 * @param   string  $user_login
 * @param   string  $link
 * @param   string  $freemius_link
 * @param   bool    $is_gdpr_required
 * @return  string
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function connect_message(
    string $msg,
    string $first_name,
    string $plugin_name,
    string $user_login,
    string $link,
    string $freemius_link,
    bool $is_gdpr_required
): string {
    return ('wp-fail2ban_addon_blocklist' == get_query_var('page'))
        ? <<< HTML
<p><b>WP fail2ban Blocklist</b> <em>requires</em> a connection to Freemius.</p>
<style>
  p + p { margin-top: 0.5em !important; }
  ul { list-style: disc; padding-left: 1em; }
  li { font-size: 1.2em; margin-left: 1em; }
</style>
<hr>
<p>To work, the Blocklist Network Service <b>must</b> know:</p>
<ul>
  <li>which sites are running the blocklist add-on,</li>
  <li>which version is in use,</li>
  <li>and a shared secret for secure communication.</li>
</ul>
<p>Freemius already provides all these, and <i>WP fail2ban</i> already uses Freemius; why reinvent the wheel?</p>
<p>Therefore, unlike the core <i>WP fail2ban</i> plugin, you <b>must</b> opt into Freemius for the blocklist to work.</p>
HTML
    : $msg;
}

/**
 * Standard helper
 *
 * @since  1.1.2
 *
 * @return string
 */
function get_extra_spash_info(): string
{
    $html = '<p>';
    if (current_user_can('manage_options')) {
        $sk = '';
        try {
            RestRoute::get_secret_keys();
            $html .= sprintf(
                '<span class="ok">%s</span>',
                __('Secret Key OK.', 'wpf2b-addon-blocklist')
            );

        } catch (\RuntimeException $e) {
            if (null === ($fs = wpf2b_addon_blocklist_fs())) {
                // how did we get here?!

            } elseif ($fs->can_use_premium_code()) {
                // email support
                $html .= sprintf(
                    '<span class="error"><b>%s</b></span>',
                    __('No Secret Key.', 'wpf2b-addon-blocklist')
                );
                $html .= '&nbsp;';
                $html .= sprintf(
                    '<a href="%s&summary=%s">%s</a>.',
                    $fs->contact_url('technical_support', 'Error message in Summary box.'),
                    urlencode('No Secret Key found'),
                    __('Contact Support', 'wpf2b-addon-blocklist')
                );

            } elseif ($fs->is_registered() && $fs->is_tracking_allowed()) {
                // forum support
                $html .= sprintf(
                    '<span class="error"><b>%s</b></span>',
                    __('No Secret Key.', 'wpf2b-addon-blocklist')
                );
                $html .= '&nbsp;';
                $html .= sprintf(
                    '<a href="https://forums.invis.net/c/wp-fail2ban-blocklist/support/37" target="_blank">%s</a> <span class="dashicons dashicons-external"></span>',
                    __('Contact Support', 'wpf2b-addon-blocklist')
                );

            } else {
                $html .= __('Must opt in.', 'wpf2b-addon-blocklist');
            }
        }
    } else {
        $html .= 'Active.';
    }
    $html .= '</p>';

    return $html;
}

/**
 * Submenu callback
 *
 * @since   1.0.0
 *
 * @return  array|string
 */
function get_blocklist_callback()
{
    global $wf_fs, $wpf2b_addon_blocklist_fs;

    if ($wf_fs) { // Not sure how, but just in case
        if ($wpf2b_addon_blocklist_fs->can_use_premium_code() ||
            ($wpf2b_addon_blocklist_fs->is_registered() && $wpf2b_addon_blocklist_fs->is_tracking_allowed()))
        {
            require_once __DIR__.'/splash.php';
            return __NAMESPACE__.'\splash';

        } else {
            $wf_fs->add_filter('plugin_icon', function () {
                return dirname(__DIR__).'/assets/icon.png';
            });
            $wf_fs->add_filter('connect_message', __NAMESPACE__.'\connect_message', 10, 7);

            return [$wf_fs, '_connect_page_render'];
        }
    } else {
        require_once __DIR__.'/splash.php';
        return __NAMESPACE__.'\splash';
    }
}

/**
 * Helper: Blocklist menu
 *
 * @since   0.9.6
 *
 * @return  void
 */
function admin_menu(): void
{
    if (!is_multisite() || is_network_admin()) {
        if ($hook = add_wpf2b_addon_page('Blocklist', null, 'wp-fail2ban_addon_blocklist', get_blocklist_callback())) {
            add_action("load-$hook", function () {
                wp_enqueue_style('wpf2b-admin', plugins_url('admin/css/admin.css', WP_FAIL2BAN_FILE));
            });
        }
    }
}
add_action('admin_menu', __NAMESPACE__.'\admin_menu', PHP_INT_MAX);
add_action('network_admin_menu', __NAMESPACE__.'\admin_menu', PHP_INT_MAX);

