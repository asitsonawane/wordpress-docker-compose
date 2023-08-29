<?php declare(strict_types=1);
/**
 * IP Decoder
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   2.2.1
 */
namespace    com\wp_fail2ban\addons\Blocklist;

use          org\lecklider\charles\wordpress\wp_fail2ban\Syslog;

class Debugger
{
    protected static ?bool $is_debug = null;

    protected static function set_debug(?bool $debug): ?bool
    {
        self::$is_debug = $debug;

        return $debug;
    }

    /**
     * Are we debugging?
     *
     * For remote debugging of users' sites.
     *
     * @since  2.2.1
     *
     * @param  bool|null    $test   Override for unit testing.
     *
     * @return bool
     */
    public static function is_debug(): bool
    {
        return (is_null(self::$is_debug))
            ? (defined('WP_FAIL2BAN_ADDON_BLOCKLIST_DEBUG') && true === WP_FAIL2BAN_ADDON_BLOCKLIST_DEBUG)
            : self::$is_debug;
    }

    public static function message(string $msg, ...$args): bool
    {
        if (self::is_debug()) {
            $fmt = sprintf('(%s) %s', WP_FAIL2BAN_ADDON_BLOCKLIST_MESSAGE_SLUG, $msg);

            return Syslog::single(LOG_DEBUG, vsprintf($fmt, $args));
        }

        return false;
    }
}

