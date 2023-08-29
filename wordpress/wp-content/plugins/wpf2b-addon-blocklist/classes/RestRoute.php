<?php declare(strict_types=1);
/**
 * Functions
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   1.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist;

use          org\lecklider\charles\wordpress\wp_fail2ban\Config;
use          org\lecklider\charles\wordpress\wp_fail2ban\Syslog;
use          org\lecklider\charles\wordpress\wp_fail2ban\premium\WPf2b;

defined('ABSPATH') or exit;

/**
 *
 *
 */
abstract class RestRoute
{
    use RestPermission;
    use RestGetFree;
    use RestGetPremium;
    use RestPost;

    /**
     * @since  1.0.0
     *
     * @return bool
     */
    public static function rest_api_init(): bool
    {
        return register_rest_route('wp-fail2ban/v1', '/blocklist', [
            'methods'               => ['GET', 'POST'],
            'callback'              => __NAMESPACE__.'\RestRoute::route_callback',
            'permission_callback'   => __NAMESPACE__.'\RestRoute::permission_callback'
        ]);
    }

    /**
     * For subsites
     *
     * @since  1.0.0
     *
     * @return bool
     */
    public static function rest_api_init_teapot(): bool
    {
        return register_rest_route('wp-fail2ban/v1', '/blocklist', [
            'methods'   => ['GET'],
            'callback'  => __NAMESPACE__.'\RestRoute::route_callback_teapot',
            'permission_callback'   => '\__return_true'
        ]);
    }

    /**
     * /blocklist
     *
     * @since  1.0.0
     *
     * @return array
     *
     * @codeCoverageIgnore
     */
    public static function route_callback(\WP_REST_Request $request)
    {
        switch ($request->get_method()) {
            case 'GET':
                return self::handleGet($request);
            case 'POST':
                return self::handlePost($request);
            default:
                return false;
        }
    }

    /**
     * /blocklist
     *
     * Used for subsites
     *
     * @since  1.0.0
     *
     * @return void
     *
     * @codeCoverageIgnore
     */
    public static function route_callback_teapot(\WP_REST_Request $request): void
    {
        wp_die('Unavailable.', 'WP fail2ban Blocklist', 418);
    }

    /**
     * Helper: check if WPf2b is Premium version
     *
     * @since  1.0.0
     *
     * @return bool
     */
    protected static function can_use_premium_code(): bool
    {
        return (defined('WP_FAIL2BAN_NS') &&
                class_exists(WP_FAIL2BAN_NS.'\premium\WPf2b', false) &&
                WPf2b::can_use_premium_code());
    }

    /**
     * Update request stats
     *
     * @since  2.0.0    Add $id
     * @since  1.0.0
     *
     * @param  string   $method     GET|POST
     * @param  int      $id
     * @param  int      $n
     * @return bool
     */
    protected static function update_stats(string $method, int $id = 0, int $n4 = 0, int $n6 = 0): bool
    {
        $stats = get_site_option(WP_FAIL2BAN_ADDON_BLOCKLIST_STATS, WP_FAIL2BAN_ADDON_BLOCKLIST_STATS_DEFAULT);
        $stats['last'][$method] = [
            'id'        => $id,
            'time'      => time(),
            'entries'   => $n4 + $n6
        ];
        $requests   = $stats['total'][$method]['requests'] ?? 0;
        $entries    = $stats['total'][$method]['entries'] ?? 0;
        $stats['total'][$method] = [
            'requests'  => $requests + 1,
            'entries'   => $entries + $n4 + $n6
        ];
        return update_site_option(WP_FAIL2BAN_ADDON_BLOCKLIST_STATS, $stats);
    }

    /**
     * Process GET request
     *
     * @since  1.0.0
     *
     * @param  \WP_REST_Request     $request
     * @return array
     */
    protected static function handleGet(\WP_REST_Request $request): array
    {
        $inet4 = '';
        $inet6 = '';
        $last_id = intval($request->get_param('last_id'));
        $cur_time = 0;

        if (self::can_use_premium_code()) {
            self::handleGetPremium($request, $last_id, $inet4, $inet6, $cur_time);

        } else {
            self::handleGetFree($request, $last_id, $inet4, $inet6, $cur_time);
        }

        self::update_stats('GET', intval($last_id), intval(strlen($inet4 ?? '')/16), intval(strlen($inet6 ?? '')/24));

        $rv = [
            'last_id' => intval($last_id),
            'events' => [
                'ip4' => $inet4 ?? '',
                'ip6' => $inet6 ?? ''
            ],
            'time' => intval($cur_time) ?? time()
        ];

        return $rv;
    }
}

