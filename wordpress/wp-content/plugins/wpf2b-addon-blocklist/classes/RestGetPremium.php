<?php declare(strict_types=1);
/**
 * REST GET Premium
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   2.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist;

defined('ABSPATH') or exit;

/**
 * Handle REST GET for Premium version.
 *
 * Abuse of a Trait to work around the lack of partial classes in PHP.
 *
 * @since  2.0.0
 */
trait RestGetPremium
{
    /**
     * @var int[] Interesting events
     */
    public static $events = [
        WPF2B_EVENT_AUTH_FAIL,
        WPF2B_EVENT_AUTH_BLOCK_USER_ENUM,
        WPF2B_EVENT_AUTH_BLOCK_USERNAME_LOGIN,
        WPF2B_EVENT_AUTH_EMPTY_USER,
        WPF2B_EVENT_COMMENT_SPAM,
        WPF2B_EVENT_REST_AUTH_FAIL,
        WPF2B_EVENT_XMLRPC_BLOCKED,
        WPF2B_EVENT_XMLRPC_MULTI_AUTH_FAIL,
        WPF2B_EVENT_XMLRPC_PINGBACK,
        WPF2B_EVENT_XMLRPC_PINGBACK_ERROR,
        WPF2B_EVENT_XMLRPC_AUTH_FAIL
    ];

    /**
     * Handle GET requests for WPf2b Premium
     *
     * @since  2.0.0    Add inet6
     * @since  1.0.0
     *
     * @param  \WP_REST_Request     $request
     * @param  int                  &$last_id
     * @param  string               &$inet4
     * @param  string               &$inet6
     * @param  int                  &$cur_time
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected static function handleGetPremium(\WP_REST_Request $request, int &$last_id, string &$inet4, string &$inet6, int &$cur_time): void
    {
        global $wpdb;

        if (is_array($rows = $wpdb->get_results(self::handleGetPremium_SQL($last_id)))) {
            if (count($rows)) {
                $inet4 = self::encodeRows(self::filterRows4($rows));
                $inet6 = self::encodeRows(self::filterRows6($rows));
            }
            if ($wpdb->query("SELECT MAX(ID), UNIX_TIMESTAMP() FROM `{$wpdb->base_prefix}fail2ban_log`;")) {
                $last_id  = $wpdb->get_var(null, 0);
                $cur_time = $wpdb->get_var(null, 1);
            } else {
                error_log($wpdb->last_error);
            }
        }
    }

    /**
     *
     *
     * @since  2.0.0    Add inet6
     * @since  1.0.0
     *
     * @param  int      $last_id
     *
     * @return string
     */
    protected static function handleGetPremium_SQL(int $last_id): string
    {
        global $wpdb;

        $events = join(',', self::$events);
        $sql  = 'SELECT ID, UNIX_TIMESTAMP(dt) AS ut, event, INET6_NTOA(';
        $sql .= (function_exists(WP_FAIL2BAN_NS.'\core\remote_addr'))
            ? 'ipv6'
            : 'addr';
        $sql .= ") AS addr FROM `{$wpdb->base_prefix}fail2ban_log` WHERE event IN ($events)";
        $sql .= (0 == $last_id)
            ? " ORDER BY ID DESC"
            : " AND ID > $last_id ORDER BY ID ASC";
        $sql .= ' LIMIT 16384;';

        return $sql;
    }

    /**
     * Encode IPv4 events to wire format
     *
     * @since  2.0.0
     *
     * @param  array    $rows
     * @return array
     */
    protected static function filterRows4(array $rows): array
    {
        return array_filter($rows, function ($row) {
            return (!is_null($row->addr) && false !== strpos($row->addr, '.'));
        });
    }

    /**
     * Encode IPv6 events to wire format
     *
     * @since  2.0.0
     *
     * @param  array    $rows
     * @return array
     */
    protected static function filterRows6(array $rows): array
    {
        return array_filter($rows, function ($row) {
            return (!is_null($row->addr) && false !== strpos($row->addr, ':'));
        });
    }

    /**
     * Encode events to wire format
     *
     * @since  2.0.0    Handle both IPv4 and IPv6
     * @since  1.0.0
     *
     * @param  array    $rows
     * @return string
     */
    protected static function encodeRows(array $rows): string
    {
        return array_reduce($rows, function ($carry, $row) {
            return $carry.Encoder::encode46(intval($row->ut), $row->addr, intval($row->event));
        }, '');
    }
}

