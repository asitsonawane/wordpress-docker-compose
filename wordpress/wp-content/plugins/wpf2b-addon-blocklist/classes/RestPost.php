<?php declare(strict_types=1);
/**
 * REST POST
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   2.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist;

use          org\lecklider\charles\wordpress\wp_fail2ban\Config;
use          org\lecklider\charles\wordpress\wp_fail2ban\Syslog;

use function org\lecklider\charles\wordpress\wp_fail2ban\premium\wp_fail2ban_event;

defined('ABSPATH') or exit;

/**
 * Handle REST POST requests.
 *
 * Abuse of a Trait to work around the lack of partial classes in PHP.
 *
 * @since  2.0.0
 */
trait RestPost
{
    /**
     * Process POST request
     *
     * @since  1.0.0
     *
     * @param  \WP_REST_Request     $request
     * @return bool
     */
    protected static function handlePost(\WP_REST_Request $request): bool
    {
        $n4 = $n6 = 0;

        if (is_string($hard = $request->get_param('hard4')) ||
            is_string($hard = $request->get_param('hard')))
        {
            $IPs = Decoder::IPv4($hard);
            $n4 = count($IPs);
            self::processDecodedIPs($IPs);
        }

        if (is_string($hard = $request->get_param('hard6'))) {
            $IPs = Decoder::IPv6($hard);
            $n6 = count($IPs);
            self::processDecodedIPs($IPs);
        }

        return self::update_stats('POST', 0, $n4, $n6);
    }

    /**
     * Process decoded IPv4/IPv6 IPs
     *
     * @since  2.0.0
     *
     * @param  array    $IPs
     *
     * @return void
     */
    protected static function processDecodedIPs(array $IPs): int
    {
        $i = 0;

        if (!empty($IPs)) {
            global $wp_fail2ban;

            $args       = $wp_fail2ban['plugins'][WP_FAIL2BAN_ADDON_BLOCKLIST_MESSAGE_SLUG]['messages']['wpbl_block_hard'];
            $customJail = Config::get('WP_FAIL2BAN_ADDON_BLOCKLIST_CUSTOM_JAIL');
            $facility   = self::getPostFacility($customJail, $args);
            $msg        = self::getPostMessage($customJail, $args);

            $log = Syslog::open($facility);
            if ($is_premium = (self::can_use_premium_code())) {
                $event = [
                    'event'     => $args['event_id'],
                    'plugin'    => $wp_fail2ban['plugins'][WP_FAIL2BAN_ADDON_BLOCKLIST_MESSAGE_SLUG]['id']
                ];
            }

            foreach ($IPs as $IP) {
                if ($log) {
                    Syslog::write($args['priority'], $msg, $IP);
                }

                if ($is_premium) {
                    $event['remote_addr'] = $IP;
                    wp_fail2ban_event($event);
                }

                $i++;
            }
            if ($log) {
                Syslog::close();
            }
        }

        return $i;
    }

    /**
     * Helper: get Facility for POST handling
     *
     * @since  1.0.1
     *
     * @param  bool     $customJail
     * @param  array    $args
     *
     * @return string
     */
    protected static function getPostFacility(bool $customJail, array $args): string
    {
        if ($customJail) {
            $cls = strtoupper($args['class']);
            $facility = "WP_FAIL2BAN_PLUGIN_{$cls}_LOG";
        } else {
            $facility = 'WP_FAIL2BAN_AUTH_LOG';
        }

        return $facility;
    }

    /**
     * Helper: get message format for POST handling
     *
     * @since  1.0.1
     *
     * @param  bool     $customJail
     * @param  array    $args
     *
     * @return string
     */
    protected static function getPostMessage(bool $customJail, array $args): string
    {
        $msg  = ($customJail)
            ? '('.WP_FAIL2BAN_ADDON_BLOCKLIST_MESSAGE_SLUG.') '
            : '';
        $msg .= $args['message'];

        return $msg;
    }
}

