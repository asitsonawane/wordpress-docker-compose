<?php declare(strict_types=1);
/**
 * IP Encoder
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   2.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist;

use          org\lecklider\charles\wordpress\wp_fail2ban\IpRangeList;

abstract class Encoder extends Coder
{
    /**
     * Backward-compatible Encode event for storage
     *
     * @since  2.0.0    Rename
     * @since  0.9.4    Skip 0.0.0.0
     * @since  1.0.0
     *
     * @throw  \InvalidArgumentException
     *
     * @param  int          $time   UNIX time
     * @param  long|string  $ip     IPv4 address
     * @param  int          $id     Event ID
     * @return string|null
     */
    public static function encode4(int $time, $ip, int $id): ?string
    {
        if (is_int($ip)) {
            $lIP = $ip;
        } elseif (is_string($ip)) {
            $lIP = ip2long($ip);
        } else {
            throw new \InvalidArgumentException((string)$ip);
        }

        if (false === $lIP) {
            return null;
        } else {
            return base64_encode(pack('NNN', $time, $lIP, $id));
        }
    }

    /**
     * Encode event for storage
     *
     * @since  2.0.0
     *
     * @param  int                                                      $time   UNIX time
     * @param  \org\lecklider\charles\wordpress\wp_fail2ban\IP|string   $ip     IP address
     * @param  int                                                      $id     Event ID
     * @return string|null
     */
    public static function encode46(int $time, $ip, int $id): ?string
    {
        static $irl = null;

        if (is_a($ip, WP_FAIL2BAN_NS.'\IP')) {
            $inet = inet_pton((string)$ip); // @codeCoverageIgnore
        } elseif (is_string($ip)) {
            $inet = inet_pton($ip);
        } elseif (is_int($ip)) {
            $inet = inet_pton(long2ip($ip));
        } else {
            throw new \InvalidArgumentException((string)$ip);
        }

        if (false === $inet) {
            return null;
        }

        if (null === $irl) {
            $irl = new IpRangeList(array_merge(self::IPV4_RANGES, self::IPV6_RANGES));
        }

        if ($irl->containsBinaryIP($inet)) {
            return null;

        } else {
            return base64_encode(pack('Na*N', $time, $inet, $id));
        }
    }
}

