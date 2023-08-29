<?php declare(strict_types=1);
/**
 * Admin - widgets
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   1.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist;

use          org\lecklider\charles\wordpress\wp_fail2ban\premium\WPf2b;

defined('ABSPATH') or exit;

/**
 * Helper: Get row data.
 *
 * @since  1.1.2    Improve i18n
 * @since  1.0.0
 *
 * @param  array    $last
 * @param  array    $total
 *
 * @return array
 */
function get_list_item_data(array $last, array $total): array
{
    return [
        'last' => [
            'dt'    => (is_null($last['time']))
                            ? '<em>('.__('Never', 'wpf2b-addon-blocklist').')</em>'
                            : date('Y-m-d H:i:s', $last['time']),
            'entries'   => intval($last['entries'] ?? 0)
        ],
        'total' => [
            'entries'   => intval($total['entries'] ?? 0),
            'requests'  => intval($total['requests'] ?? 0)
        ]
    ];
}

/**
 * Helper: List item
 *
 * @since  1.1.2    Add hints; improve i18n
 * @since  1.0.0
 *
 * @param  string   $direction
 * @param  string   $singular
 * @param  string   $plural
 * @param  array    $last
 * @param  array    $total
 *
 * @return string
 */
function get_list_item(string $direction, string $singular, string $plural, array $last, array $total, $pending = null): string
{
    $args = get_list_item_data($last, $total);
    $what = _n($singular, $plural, $args['last']['entries'], 'wpf2b-addon-blocklist');
    if (!is_null($pending)) {
        $pending = '; <span class="queued">'.$pending.'</span> '.__('queued', 'wpf2b-addon-blocklist');
    }
    $total              = __('total', 'wpf2b-addon-blocklist');
    $last_polled        = __('At last polling', 'wpf2b-addon-blocklist');
    $last_polled_time   = __('Last polled', 'wpf2b-addon-blocklist');
    $lifetime_total     = __('Lifetime total', 'wpf2b-addon-blocklist');

    return <<< HTML
    <li class="${direction}load wp-clearfix">
      <div class="info">
        <div class="dashicons"><span class="dashicons-${direction}load"></span></div>
        <div>
          <span class="last" title="${last_polled}"><span class="num last-entries">{$args['last']['entries']}</span> $what</span><br>
          <span class="total"><span title="${lifetime_total}"><span class="num total-entries">{$args['total']['entries']}</span> ${total}</span>${pending}</span>
        </div>
      </div>
      <div class="date-time">
        <span class="dt last-dt" title="${last_polled_time}">{$args['last']['dt']}</span><br>
        <span class="total" title="${lifetime_total}"><span class="num total-requests">{$args['total']['requests']}</span> ${total}</span>
      </div>
    </li>

HTML;
}

/**
 * Helper: queued stats
 *
 * @since  2.1.0    Work properly with IPv4/IPv6 length
 * @since  2.0.0
 *
 * @param  array    $opt
 *
 * @return string
 */
function dashboard_widget_queued(array $opt): string
{
    if (class_exists(WP_FAIL2BAN_NS.'\premium\WPf2b') && WPf2b::can_use_premium_code()) {
        global $wpdb;

        $addrField = (class_exists(WP_FAIL2BAN_NS.'\IP'))
            ? 'ipv6'
            : 'addr';
        $events = join(',', RestRoute::$events);

        // IPv6 is always 16 bytes, but for whatever reason IPv4 varies.
        // Oh how I wish WordPress used a real database....
        $sql = <<< SQL
SELECT COUNT({$addrField})
  FROM {$wpdb->base_prefix}fail2ban_log
 WHERE 16 > LENGTH({$addrField})
   AND event IN ($events)
   AND ID > %d;
SQL;
        $inet4 = $wpdb->get_var(sprintf($sql, $opt['last']['GET']['id'] ?? PHP_INT_MAX));
        $sql = <<< SQL
SELECT COUNT({$addrField})
  FROM {$wpdb->base_prefix}fail2ban_log
 WHERE 16 = LENGTH({$addrField})
   AND event IN ($events)
   AND ID > %d;
SQL;
        $inet6 = $wpdb->get_var(sprintf($sql, $opt['last']['GET']['id'] ?? PHP_INT_MAX));
    } else {
        $inet4 = strlen(get_site_option(WP_FAIL2BAN_ADDON_BLOCKLIST_EVENTS_IP4, '')) / 16;
        $inet6 = strlen(get_site_option(WP_FAIL2BAN_ADDON_BLOCKLIST_EVENTS_IP6, '')) / 24;
    }

    return sprintf('%d <small>(v4)</small>, %d <small>(v6)</small>', $inet4, $inet6);
}

/**
 * Widget: Blocklist Summary
 *
 * @since  1.0.0
 *
 * @return void
 */
function dashboard_widget_summary(): void
{
    $fs = Freemius\wpf2b_addon_blocklist_fs();
    if ($fs && $fs->is_registered() && $fs->is_tracking_allowed()) {
        $opt = get_site_option(WP_FAIL2BAN_ADDON_BLOCKLIST_STATS, WP_FAIL2BAN_ADDON_BLOCKLIST_STATS_DEFAULT);
        $pending = dashboard_widget_queued($opt);

        $html  = "<ul>\n";
        $html .= get_list_item('up', 'event', 'events', $opt['last']['GET'] ?? [], $opt['total']['GET'] ?? [], $pending);
        $html .= get_list_item('down', 'IP', 'IPs', $opt['last']['POST'] ?? [], $opt['total']['POST'] ?? []);
        $html .= "</ul>\n";

    } else {
        $html  = '<p class="error">'.__('Must opt in to Freemius.', 'wpf2b-addon-blocklist')."</p>\n";
    }

    echo $html;
}

/**
 * Hook: heartbeat_received
 *
 * @since  1.0.0
 *
 * @param  array    $response
 * @param  array    $data
 *
 * @return array
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function heartbeat_received(array $response, array $data, string $screen_id): array
{
    if ('wp_fail2ban_addon_blocklist_summary' == ($data['wp_fail2ban_addon_blocklist'] ?? null)) {
        $opt = get_site_option(WP_FAIL2BAN_ADDON_BLOCKLIST_STATS, WP_FAIL2BAN_ADDON_BLOCKLIST_STATS_DEFAULT);
        $queued = $pending = dashboard_widget_queued($opt);
        $response['wp-fail2ban-addon-blocklist-summary'] = [
            'upload' => array_merge(get_list_item_data($opt['last']['GET'] ?? [], $opt['total']['GET'] ?? []), ['queued' => $queued]),
            'download' => get_list_item_data($opt['last']['POST'] ?? [], $opt['total']['POST'] ?? [])
        ];
    }
    return $response;
}
add_filter('heartbeat_received', __NAMESPACE__.'\heartbeat_received', 10, 3);

/**
 * wp_dashboard_setup action hook
 *
 * @since  1.0.0
 *
 * @see https://codex.wordpress.org/Function_Reference/wp_add_dashboard_widget
 *
 * @return void
 */
function wp_dashboard_setup(): void
{
    if ((!is_multisite() && current_user_can('manage_options')) ||
        (is_network_admin() && current_user_can('manage_network_options')))
    {
        wp_add_dashboard_widget(
            'wp_fail2ban_addon_blocklist_summary',
            __('<span>[<i>WPf2b</i>] Blocklist Summary</span>', 'wp-fail2ban-addon-blocklist'),
            __NAMESPACE__.'\dashboard_widget_summary'
        );
        wp_enqueue_style('wp-fail2ban-addon-blocklist', plugins_url('css/widgets.css', __FILE__));
        wp_enqueue_script('wp-fail2ban-addon-blocklist', plugins_url('js/widgets.js', __FILE__), ['jquery']);
    }
}
add_action('wp_dashboard_setup', __NAMESPACE__.'\wp_dashboard_setup');
add_action('wp_network_dashboard_setup', __NAMESPACE__.'\wp_dashboard_setup');

