<?php declare(strict_types=1);
/**
 * IP Decoder
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   2.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist;

use function org\lecklider\charles\wordpress\wp_fail2ban\ip_in_range;

use          org\lecklider\charles\wordpress\wp_fail2ban\Config;
use          org\lecklider\charles\wordpress\wp_fail2ban\IpRangeList;

class Decoder extends Coder
{
    /**
     * Unpack IPs from binary network order
     *
     * @since  2.0.0    Rename
     * @since  1.0.0
     *
     * @param  string   $encodedIPs
     * @return array
     */
    public static function IPv4(string $encodedIPs): array
    {
        $IPs = [];

        if (strlen($encodedIPs)) {
            $rawIPs = base64_decode($encodedIPs);
            $netIPs = str_split($rawIPs, 4);
            $ignore = Config::get('WP_FAIL2BAN_ADDON_BLOCKLIST_IGNORE_IPS');
            if (empty($ignore)) {
                $ignore = []; // @codeCoverageIgnore
            } elseif (is_string($ignore)) {
                $ignore = explode(',', $ignore);
            }
            $ignIPs = array_merge(self::IPV4_RANGES, $ignore);

            if ($rawIPs != $netIPs[0]) {
                foreach ($netIPs as $netIP) {
                    $up = unpack('Nip', $netIP);
                    if (!ip_in_range($up['ip'], $ignIPs)) {
                        $IPs[] = long2ip($up['ip']);
                    }
                }
            }
        }

        return $IPs;
    }

    /**
     * Unpack IPs from binary network order
     *
     * @since  2.0.0
     *
     * @param  string   $encodedIPs
     * @return array
     */
    public static function IPv6(string $encodedIPs): array
    {
        $IPs = [];

        if (strlen($encodedIPs)) {
            $rawIPs = base64_decode($encodedIPs);
            $netIPs = str_split($rawIPs, 16);
            $ignore = Config::get('WP_FAIL2BAN_ADDON_BLOCKLIST_IGNORE_IPS');
            if (empty($ignore)) {
                $ignore = []; // @codeCoverageIgnore
            } elseif (is_string($ignore)) {
                $ignore = explode(',', $ignore);
            }
            $ignIPs = array_merge(self::IPV6_RANGES, $ignore);
            $irl = new IpRangeList($ignIPs);

            foreach ($netIPs as $netIP) {
                if (!$irl->containsBinaryIP($netIP)) {
                    $IPs[] = inet_ntop($netIP);
                }
            }
        }

        return $IPs;
    }
}

