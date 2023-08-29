<?php declare(strict_types=1);
/**
 * REST GET Free
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   2.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist;

defined('ABSPATH') or exit;

/**
 * Handle REST Get for Free version.
 *
 * Abuse of a Trait to work around the lack of partial classes in PHP.
 *
 * @since  2.0.0
 */
trait RestGetFree
{
    /**
     * Get SQL for reading events (Free version)
     *
     * @since  2.0.0    Add $meta_key
     * @since  1.0.0
     *
     * @param  string   $meta_key           meta_key
     * @param  bool     $forceMultisite     Force multisite
     * @return string
     */
    protected static function getReadEventsSQL(string $meta_key, bool $forceMultisite = false): string
    {
        global $wpdb;

        if (is_multisite() || $forceMultisite) {
            $sql = <<< SQL
SELECT meta_value AS value
  FROM `{$wpdb->sitemeta}`
 WHERE meta_key = '%s'
   AND site_id = %d;
SQL;
            return sprintf($sql, $meta_key, get_current_network_id());
        } else {
            $sql = <<< SQL
SELECT option_value AS value
  FROM `{$wpdb->options}`
 WHERE option_name = '%s';
SQL;
            return sprintf($sql, $meta_key);
        }
    }

    /**
     * xxx
     *
     * @since  2.0.0
     *
     * @param  \WP_REST_Request $request
     * @param  string           $key
     *
     * @return string
     */
    protected static function handleGetFreeIPvX(\WP_REST_Request $request, string $key): string
    {
        global $wpdb;

        $sql = self::getReadEventsSQL($key);

        /**
         * Bail if the query fails
         */
        if (null === ($row = $wpdb->get_row($sql))) {
            add_site_option($key, '');
            return '';

        } else {
            $results = $row->value;

            /**
             * Exit if we're being called from blocklist development
             */
            if ($request->get_param('devel')) {
                // noop

            } else {
                update_site_option($key, '');
            }

            return $results;
        }
    }

    /**
     * Handle GET requests for WPf2b Free
     *
     * @since  2.0.0    Add $inet6
     * @since  1.0.0
     *
     * @param  \WP_REST_Request     $request
     * @param  int                  &$last_id
     * @param  string               &$inet4
     * @param  string               &$inet6
     * @param  int                  &$cur_time
     * @return bool
     *
     * @codeCoverageIgnore
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected static function handleGetFree(\WP_REST_Request $request, int &$last_id, string &$inet4, string&$inet6, int &$cur_time): bool
    {
        global $wpdb;

        if (false === $wpdb->query('START TRANSACTION;')) {
            error_log(WP_FAIL2BAN_ADDON_BLOCKLIST_MESSAGE_SLUG.': Failed to start transaction');
            return false;
        }

        $inet4 = self::handleGetFreeIPvX($request, WP_FAIL2BAN_ADDON_BLOCKLIST_EVENTS_IP4);
        $inet6 = self::handleGetFreeIPvX($request, WP_FAIL2BAN_ADDON_BLOCKLIST_EVENTS_IP6);

        return (false !== $wpdb->query('COMMIT;'));
    }
}

