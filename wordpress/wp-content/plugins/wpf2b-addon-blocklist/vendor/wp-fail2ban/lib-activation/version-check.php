<?php declare(strict_types=1);
/**
 * Version Check
 *
 * @package wp-fail2ban-lib-activation
 * @since   1.0.0
 */
namespace com\wp_fail2ban\lib\Activation;

require_once __DIR__.'/version-check-exceptions.php';

/**
 * A wrapper for static functions - simplifies `use`.
 *
 * @since  1.0.0
 */
abstract class VersionCheck
{
    /**
     * Check core plugin is new enough
     *
     * @since  1.0.0
     *
     * @throws ActivationTooOldException
     * @throws ActivationNoParentException
     *
     * @param  array    $vers   Array of minimum versions; sorted, asc.
     * @param  string   $title  Plugin title.
     *
     * @return bool
     */
    public static function check_raw(array $vers, string $title, string $test_ver = null): bool
    {
        if (defined('WP_FAIL2BAN_VER') || !is_null($test_ver)) {
            if (empty($vers)) {
                throw new \UnderflowException();
            }
            $wpf2b_ver = $test_ver ?? WP_FAIL2BAN_VER;

            try {
                $verA = array_shift($vers);

                if (version_compare($wpf2b_ver, $verA, '<')) {
                    throw new \Exception($verA);
                }

                while ($verB = array_shift($vers)) {
                    if (version_compare($wpf2b_ver, $verA, '>') &&
                        version_compare($wpf2b_ver, $verB, '<'))
                    {
                        throw new \Exception($verB);
                    }
                    $verA = $verB;
                }

            } catch (\Exception $e) {
                throw new ActivationTooOldException(
                    sprintf(
                        '<p><u>WP fail2ban %s</u> requires <u>WP fail2ban</u> version <b>%s</b> or later. The version installed is %s.</p>',
                        $title,
                        $e->getMessage(),
                        $wpf2b_ver
                    )
                );
            }
        } else {
            throw new ActivationNoParentException("<p><u>WP fail2ban {$title}</u> requires <u>WP fail2ban</u> to be enabled.</p>");
        }

        return true;
    }

    /**
     * Handle exceptions.
     *
     * Deactivate the add-on, die with an error message.
     *
     * @since  1.0.0
     *
     * @param  \Exception   $e
     * @param  string       $file
     *
     * @return bool     Always `false`; for testing.
     */
    public static function exception_handler(\Exception $e, string $file): bool
    {
        deactivate_plugins(plugin_basename($file));
        $msg = sprintf(
            '%s<p>Click <a href="%s">here</a> to return to the plugins page.</p>',
            $e->getMessage(),
            network_admin_url('plugins.php')
        );
        wp_die($msg);

        return false;
    }

    /**
     * Simple helper.
     *
     * Do the checks and catch the result. For simple add-ons.
     *
     * @since  1.0.0
     *
     * @param  array    $vers   Array of minimum versions; sorted, asc.
     * @param  string   $title  Plugin title.
     * @param  string   $file   Main plugin file.
     *
     * @codeCoverageIgnore
     */
    public static function check(array $vers, string $title, string $file)
    {
        try {
            self::check_raw($vers, $title);

        } catch (\Exception $e) {
            self::exception_handler($e, $file);
        }
    }
}

