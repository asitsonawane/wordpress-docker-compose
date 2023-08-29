<?php declare(strict_types=1);
/**
 * REST Permission
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   2.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist;

use          org\lecklider\charles\wordpress\wp_fail2ban\Syslog;

use function org\lecklider\charles\wordpress\wp_fail2ban\array_value;

defined('ABSPATH') or exit;

/**
 * Handle REST Authentication.
 *
 * Abuse of a Trait to work around the lack of partial classes in PHP.
 *
 * @since  2.0.0
 */
trait RestPermission
{
    /**
     * @var stirng Last error
     * @since 2.0.0
     */
    public static $last_error_message = null;

    /**
     * Get the nonce
     *
     * Should be in the Date header, but also check query string because LiteSpeed doesn't pass the Date header.
     *
     * Could have used X-WP-Nonce but a) this is already stupid, and b) can't trust it wouldn't be fiddled with by something else
     *
     * @since  2.1.0
     *
     * @return int|null
     */
    protected static function get_nonce(\WP_REST_Request $request): ?int
    {
        $nonce = null;

        if (null === ($date = $request->get_header('date'))) {
            // LiteSpeed or nginx?
            if (null === ($nonce = array_value('nonce', $_GET))) {
                self::$last_error_message = '[C] No Date header, no nonce';

            } else {
                $nonce = intval($nonce);
            }

        } elseif (false === ($nonce = strtotime($date))) {
            self::$last_error_message = "[D] Invalid Date header: $date";
            $nonce = null;
        }

        return $nonce;
    }

    /**
     * Endpoint permission callback
     *
     * @since  1.0.0
     *
     * @param  \WP_REST_Request     $request
     * @return bool
     */
    public static function permission_callback(\WP_REST_Request $request): bool
    {
        self::$last_error_message = null;

        if (null === ($authentication = $request->get_header('authentication'))) {
            self::$last_error_message = '[A] No Authentication header';

        } elseif (null === ($digest = self::extract_digest($authentication))) {
            self::$last_error_message = '[B] Invalid Authentication header';

        } elseif (null === ($nonce = self::get_nonce($request))) {
            // last_error_message already set

        } elseif ($nonce > $_SERVER['REQUEST_TIME']+300) {
            self::$last_error_message = "[E] Date must be within 5 minutes of current server time; too new ($nonce)";

        } elseif ($nonce < $_SERVER['REQUEST_TIME']-300) {
            self::$last_error_message = "[F] Date must be within 5 minutes of current server time; too old ($nonce)";

        } else {
            try {
                switch ($request->get_method()) {
                    case 'GET':
                        return self::check_read_hmac($digest, $nonce); // @codeCoverageIgnore
                    case 'POST':
                        return self::check_write_hmac($digest, $nonce); // @codeCoverageIgnore
                    default:
                        self::$last_error_message = '[G] Invalid Method';
                        break;
                }
            } catch (\Exception $e) {
                self::$last_error_message = $e->getMessage(); // H
            }
        }
        Syslog::single(LOG_WARNING, sprintf('(%s) %s', WP_FAIL2BAN_ADDON_BLOCKLIST_MESSAGE_SLUG, self::$last_error_message));

        return false;
    }

    /**
     * Get the Freemius secret key
     *
     * @since  2.2.1    Refactor
     * @since  2.2.0    Add WP_FAIL2BAN_ADDON_BLOCKLIST_DEBUG_TRY_ALL_KEYS
     * @since  2.0.1    Return array of keys; rename
     * @since  1.1.2    Check known slugs first; make public
     * @since  1.1.1    Handle differing slugs
     * @since  1.0.0
     *
     * @throws \RuntimeException
     *
     * @return array
     */
    public static function get_secret_keys(): array
    {
        $keys  = [];
        $opt   = \FS_Options::instance(WP_FS__ACCOUNTS_OPTION_NAME);
        $sites = $opt->get_option('sites');
        $slug  = plugin_basename(WP_FAIL2BAN_ADDON_BLOCKLIST_FILE);

        foreach ($sites as $key => $site) {
            Debugger::message('%s => "%s"', $key, $site->secret_key);
            switch ($key) {
                case $slug:
                case 'wpf2b-addon-blocklist': // Free slug?
                case 'wp-fail2ban-addon-blocklist': // Premium slug?
                    $keys[$key] = $site->secret_key;
                    break;
                default:
                    if (defined('WP_FAIL2BAN_ADDON_BLOCKLIST_DEBUG_TRY_ALL_KEYS') &&
                        true === WP_FAIL2BAN_ADDON_BLOCKLIST_DEBUG_TRY_ALL_KEYS)
                    {
                        $keys[$key] = $site->secret_key;
                    }
                    break;
            }
        }

        if (empty($keys)) {
            throw new \RuntimeException('Cannot find secret key');
        }

        return $keys;
    }

    /**
     * Extract the digest.
     *
     * @since  1.0.0
     *
     * @param  string   $authentication
     * @return string|null
     */
    protected static function extract_digest(string $authentication): ?string
    {
        $parts = explode(' ', trim($authentication));

        return (2 === count($parts) && 'WPf2b' === $parts[0])
            ? urldecode($parts[1])
            : null;
    }

    /**
     * Strip the query string.
     *
     * @since  2.2.1    Trim trailing slash
     * @since  2.0.0    Use parse_url()
     * @since  1.0.0
     *
     * @param  string   $url
     * @return string
     */
    protected static function extract_url(string $url): string
    {
        if (is_null($url = parse_url($url, PHP_URL_PATH))) {
            $url = ''; // In theory this can't happen but let's handle it just in case
        }
        Debugger::message('Raw URL is "%s"', $url);

        $url = rtrim($url, '/');

        return $url;
    }

    /**
     * @since  2.2.0    Add WP_FAIL2BAN_ADDON_BLOCKLIST_DEBUG
     * @since  2.0.1    Refactor for multiple keys
     * @since  1.0.0
     *
     * @param  string   $digest
     * @param  int      $nonce
     *
     * @return bool
     *
     * @codeCoverageIgnore
     */
    protected static function check_read_hmac(string $digest, int $nonce): bool
    {
        $sks  = self::get_secret_keys();
        $url  = self::extract_url($_SERVER['REQUEST_URI']);
        Debugger::message('URL is "%s"', $url);

        foreach ($sks as $slug => $sk) {
            $hmac = self::hmac($sk, 'GET', $url, $nonce);
            if ($digest === $hmac) {
                Debugger::message('Using secret key "%s"', $slug);
                return true;

            } else {
                Debugger::message('Not using secret key "%s" (%s != %s)', $slug, $digest, $hmac);
            }
        }

        Debugger::message('No matching secret key');

        return false;
    }

    /**
     * @since  2.0.1    Refactor for multiple keys
     * @since  1.0.0
     *
     * @param  string   $digest
     * @param  int      $nonce
     *
     * @return bool
     *
     * @codeCoverageIgnore
     */
    protected static function check_write_hmac(string $digest, int $nonce): bool
    {
        if (!is_null($body = file_get_contents('php://input'))) {
            $sks  = self::get_secret_keys();
            $url  = self::extract_url($_SERVER['REQUEST_URI']);

            foreach ($sks as $sk) {
                $hmac = self::hmac($sk, 'POST', $url, $nonce, $body);
                if ($digest === $hmac) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @since  1.0.0
     *
     * @param  string       $secret
     * @param  string       $method
     * @param  string       $url
     * @param  int|null     $nonce
     * @param  string       $message
     * @return string
     */
    protected static function hmac(string $secret, string $method, string $url, int $nonce = 0, string $message = ''): string
    {
        $data = join(':', [$method, $url, $nonce, $message]);
        Debugger::message('hmac data "%s"', $data);
        $data = hash_hmac('sha256', $data, $secret, true);
        $data = base64_encode($data);

        return urldecode($data);
    }
}

