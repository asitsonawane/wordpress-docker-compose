<?php declare(strict_types=1);
/**
 * Event class
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   1.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist;

use          org\lecklider\charles\wordpress\wp_fail2ban\IP;
use          org\lecklider\charles\wordpress\wp_fail2ban\IpRangeList;

use function org\lecklider\charles\wordpress\wp_fail2ban\remote_addr as remote_addr_legacy;
use function org\lecklider\charles\wordpress\wp_fail2ban\core\remote_addr;

defined('ABSPATH') or exit;

/**
 * Event
 *
 * @since 1.0.0
 */
abstract class Event
{
    const MAX_EVENTS = 16384;

    /**
     * Save Event
     *
     * @since  2.0.0    Refactor for IPv6
     * @since  1.0.0
     *
     * @param  int      $id     Event ID.
     * @return bool
     */
    public static function save(int $id): bool
    {
        $t = time();

        try {
            if (function_exists(WP_FAIL2BAN_NS.'\core\remote_addr')) {
                $ip = remote_addr();
                if (null === ($enc = Encoder::encode46($t, $ip, $id))) {
                    echo __LINE__."\n";
                    return false;

                } else {
                    return self::write_db($enc, $ip->getVersion());
                }

            } elseif (null === ($enc = Encoder::encode4($t, remote_addr_legacy(), $id))) {
                return false;

            } else {
                return self::write_db($enc, 4);
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * SQL to save event
     *
     * @since  2.0.0    Add $version
     * @since  0.9.4
     *
     * @param  string   $event
     * @param  int      $version
     * @param  bool     $testing
     * @return string
     */
    protected static function write_sql(string $event, int $version, bool $testing = false): string
    {
        global $wpdb;

        switch ($version) {
            case 4:
                $key = WP_FAIL2BAN_ADDON_BLOCKLIST_EVENTS_IP4;
                break;
            case 6:
                $key = WP_FAIL2BAN_ADDON_BLOCKLIST_EVENTS_IP6;
                break;
            default:
                throw new \RuntimeException('Invalid version.');
        }

        if (is_multisite() || $testing) {
            $sql = <<< SQL
UPDATE `{$wpdb->sitemeta}`
   SET meta_value = CONCAT(meta_value, '%s')
 WHERE meta_key = '%s'
   AND site_id = %d;
SQL;
            return sprintf($sql, $event, $key, get_current_network_id());
        } else {
            $sql = <<< SQL
UPDATE `{$wpdb->options}`
   SET option_value = CONCAT(option_value, '%s')
 WHERE option_name = '%s';
SQL;
            return sprintf($sql, $event, $key);
        }
    }

    /**
     * SQL to trim events
     *
     * In case something goes wrong with the backend, this ensures we don't fill the row.
     *
     * @since  2.0.0    Add $version, exception
     * @since  1.0.0
     *
     * @throw  \RuntimeException
     *
     * @param  int      $version
     * @param  bool     $testing
     * @return string
     */
    protected static function trim_sql(int $version, bool $testing = false): string
    {
        global $wpdb;

        switch ($version) {
            case 4:
                $key = WP_FAIL2BAN_ADDON_BLOCKLIST_EVENTS_IP4;
                $len = 16;
                break;
            case 6:
                $key = WP_FAIL2BAN_ADDON_BLOCKLIST_EVENTS_IP6;
                $len = 24;
                break;
            default:
                throw new \RuntimeException('Invalid version.');
        }
        $maxLen = self::MAX_EVENTS * $len;

        if (is_multisite() || $testing) {
            $sql = <<< SQL
UPDATE `{$wpdb->sitemeta}`
   SET meta_value = SUBSTRING(meta_value FROM %d FOR %d)
 WHERE meta_key = '%s'
   AND LENGTH(meta_value) >= %d
   AND site_id = %d;
SQL;
            return sprintf($sql, $len, $maxLen - $len, $key, $maxLen, get_current_network_id());
        } else {
            $sql = <<< SQL
UPDATE `{$wpdb->options}`
   SET option_value = SUBSTRING(option_value FROM %d FOR %d)
 WHERE option_name = '%s'
   AND LENGTH(option_value) >= %d;
SQL;
            return sprintf($sql, $len, $maxLen - $len, $key, $maxLen);
        }
    }

    /**
     * Write event to the database
     *
     * @since  2.0.0    Add $version
     * @since  1.0.0
     *
     * @param  string   $event  Encoded event
     * @return bool
     */
    protected static function write_db(string $event, int $version): bool
    {
        global $wpdb;

        $write_sql = self::write_sql($event, $version);
        $trim_sql = self::trim_sql($version);

        if (false === $wpdb->query('START TRANSACTION;')) {
            // TODO: major problem!
            error_log('Cannot start transaction'); // @codeCoverageIgnore

        } elseif (1 == $wpdb->query($write_sql)) { // Must be ==
            $wpdb->query($trim_sql); // usually does nothing

            return (1 == $wpdb->query('COMMIT;')); // Must be ==

        } else {
            $wpdb->query('ROLLBACK;');
        }

        return false;
    }
}

