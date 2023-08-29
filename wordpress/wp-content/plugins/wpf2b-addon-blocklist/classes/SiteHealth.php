<?php declare(strict_types=1);
/**
 * WP fail2ban Blocklist Site Health
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   2.1
 * @php     7.4
 */
namespace    com\wp_fail2ban\addons\Blocklist;

use          org\lecklider\charles\wordpress\wp_fail2ban\Config;

use function com\wp_fail2ban\addons\Blocklist\Freemius\wpf2b_addon_blocklist_fs;
use function org\lecklider\charles\wordpress\wp_fail2ban\get_fail2ban_path;

defined('ABSPATH') or exit;

class SiteHealth
{
    const PREFIX = '[WP fail2ban Blocklist] ';

    protected static $instance = null;

    /**
     * Return an instance of the SiteHealth class, or create one if none exist yet.
     *
     * @since  2.1.0
     *
     * @return SiteHealth
     */
    public static function get_instance(): SiteHealth
    {
        if (null === self::$instance) {
            self::$instance = new SiteHealth();
        }
        return self::$instance;
    }

    /**
     * @see \WP_Site_Health::get_tests()
     *
     * @since  2.1.0
     *
     * @return array    The list of tests to run.
     */
    public static function get_tests(array $tests): array
    {
        $instance = self::get_instance();

        $tests['direct']['wp_fail2ban_blocklist_secret_key'] = [
            'label' => 'WP fail2ban Blocklist secret key',
            'test' => [$instance, 'get_test_blocklist_secret_key']
        ];
        if (!defined('WP_FAIL2BAN_SITE_HEALTH_SKIP_FILTERS')) {
            $tests['direct']['wp_fail2ban_blocklist_maxretry'] = [
                'label' => 'WP fail2ban Blocklist maxretry',
                'test' => [$instance, 'get_test_blocklist_maxretry']
            ];
        }

        return $tests;
    }

    /**
     * Is the wordpress-hard maxretry = 1? Or,
     * Is the wpf2b-blocklist-hard maxretry = 1?
     *
     * Bail out if we can't find fail2ban - WPf2b5 will handle that
     *
     * @since  2.1.0
     *
     * @return array    The test result.
     */
    public function get_test_blocklist_maxretry(): array
    {
        $results = [];

        /**
         * WPf2b5 simplifies this
         */
        if (function_exists(WP_FAIL2BAN_NS.'\get_fail2ban_path')) {
            $jail_d = get_fail2ban_path('jail.d');

        } elseif (defined('WP_FAIL2BAN_INSTALL_PATH')) {
            $filter = trailingslashit(WP_FAIL2BAN_INSTALL_PATH).'jail.d';
            if (is_dir($filter)) {
                $jail_d = $filter;
            }

        } else {
            $jails_d = [
                '/etc/fail2ban/jail.d',
                '/usr/local/etc/fail2ban/jail.d'
            ];

            $jail_d = null;
            foreach ($jails_d as $jail) {
                if (is_dir($jail)) {
                    $jail_d = $jail;
                    break;
                }
            }
        }

        if ($jail_d) {
            if (!empty($jails = glob("{$jail_d}/*.conf"))) {
                $results = [
                    'label'     => __('fail2ban appears to be configured to work with Blocklist', 'wpf2b-addon-blocklist'),
                    'status'    => 'critical',
                    'badge'     => [
                        'label' => __('Security'),
                        'color' => 'blue'
                    ],
                    'description'   => '',
                    'actions'       => '',
                    'test'          => 'wp_fail2ban_blocklist_maxretry'
                ];

                $merged_ini = [];
                foreach ($jails as $jail) {
                    if (is_array($ini = parse_ini_file($jail, true))) {
                        $merged_ini = array_merge($merged_ini, $ini);
                    }
                }

                if (Config::get('WP_FAIL2BAN_ADDON_BLOCKLIST_CUSTOM_JAIL')) {
                    if (is_null($maxretry = $this->ini_section_maxretry($merged_ini, 'wpf2b-blocklist-hard', $matching_section))) {
                        $results['label']       = __('No custom jail found', 'wpf2b-addon-blocklist');
                        $results['description'] = sprintf(
                            '<p>%s</p>',
                            __('WP fail2ban Blocklist is configured to use a custom jail, but no matching jail was found.', 'wpf2b-addon-blocklist')
                        );
                        $results['actions']     = sprintf(
                            '<p><a href="%s" target="_blank">%s</a> <span class="dashicons dashicons-external"></span></p>',
                            sprintf(
                                'https://docs.wp-fail2ban.com/projects/wp-fail2ban-blocklist/en/%s/configuration/fail2ban.html#custom-jail',
                                WP_FAIL2BAN_ADDON_BLOCKLIST_VER_SHORT
                            ),
                            __('Read more about Custom Jails', 'wpf2b-addon-blocklist')
                        );

                    } elseif ($maxretry > 1) {
                        $results['label']       = __('maxretry must be set to 1', 'wpf2b-addon-blocklist');
                        $results['description'] = sprintf(
                            '<p>%s</p><p>%s</p><p>%s</p>',
                            sprintf(
                                /* translators: 1: maxretry, 2: fail2ban, 3: fail2ban */
                                'The %1$s setting in a %2$s jail controls how many times an IP address can trigger events before being blocked. Any changes to the %3$s settings must be made by your server administrator.',
                                '<code>maxretry</code>',
                                '<code>fail2ban</code>',
                                '<code>fail2ban</code>'
                            ),
                            sprintf(
                                /* translators: 1: maxretry, 2: the digit 1 */
                                __('The Blocklist Network Service (BNS) provides IP addresses that should be banned <em>immediately</em>. To do that, %1$s must be set to %2$s.', 'wpf2b-addon-blocklist'),
                                '<code>maxretry</code>',
                                '<code>1</code>'
                            ),
                            sprintf(
                                /* translators: 1: section name, 2: maxretry, 3: number of retries */
                                __('You have a custom jail configured &mdash; %1$s &mdash; but %2$s is currently set to %3$s', 'wpf2b-addon-blocklist'),
                                "<code>{$matching_section}</code>",
                                '<code>maxretry</code>',
                                "<code>{$maxretry}</code>"
                            )
                        );

                    } else { // woo!
                        $results['status']      = 'good';
                        $results['description'] = sprintf(
                            '<p>%s</p>',
                            sprintf(
                                /* translators: %s: maxretry = 1 */
                                __('You are using a custom jail with %s.', 'wpf2b-addon-blocklist'),
                                '<code>maxretry = 1</code>'
                            )
                        );
                    }

                } elseif (is_null($maxretry = $this->ini_section_maxretry($merged_ini, 'wordpress-hard', $matching_section)) &&
                          is_null($maxretry = $this->ini_section_maxretry($merged_ini, 'wordpress-hard-custom', $matching_section)))
                {
                    // No hard filter and no custom jail? Eh?
                    // Leave this to core

                    return [];

                } elseif ($maxretry > 1) {
                    $results['label']       = __('maxretry must be set to 1', 'wpf2b-addon-blocklist');
                    $results['description'] = sprintf(
                        '<p>%s</p><p>%s</p><p>%s</p><p>%s</p>',
                        sprintf(
                            /* translators: 1: maxretry, 2: fail2ban, 3: fail2ban */
                            'The %1$s setting in a %2$s jail controls how many times an IP address can trigger events before being blocked. Any changes to the %3$s settings must be made by your server administrator.',
                            '<code>maxretry</code>',
                            '<code>fail2ban</code>',
                            '<code>fail2ban</code>'
                        ),
                        sprintf(
                            /* translators: 1: maxretry, 2: the digit 1 */
                            __('The Blocklist Network Service (BNS) provides IP addresses that should be banned <em>immediately</em>. To do that, %1$s must be set to %2$s.', 'wpf2b-addon-blocklist'),
                            '<code>maxretry</code>',
                            '<code>1</code>'
                        ),
                        sprintf(
                            /* translators: 1: section name, 2: maxretry, 3: number of retries */
                            __('In the %1$s jail %2$s is currently set to %3$s', 'wpf2b-addon-blocklist'),
                            "<code>{$matching_section}</code>",
                            '<code>maxretry</code>',
                            "<code>{$maxretry}</code>"
                        ),
                        sprintf(
                            /* translators: %s: maxretry = 1 */
                            __('If setting %s is not suitable for your requirements you will need to use a custom jail for the Blocklist.', 'wpf2b-addon-blocklist'),
                            '<code>maxretry = 1</code>'
                        )
                    );
                    $results['actions']     = sprintf(
                        '<p><a href="%s" target="_blank">%s</a> <span class="dashicons dashicons-external"></span></p>',
                        sprintf(
                            'https://docs.wp-fail2ban.com/projects/wp-fail2ban-blocklist/en/%s/configuration/fail2ban.html#custom-jail',
                            WP_FAIL2BAN_ADDON_BLOCKLIST_VER_SHORT
                        ),
                        __('Read more about Custom Jails', 'wpf2b-addon-blocklist')
                    );

                } else { // woo!
                    $results['status']      = 'good';
                    $results['description'] = sprintf(
                        '<p>%s</p>',
                        sprintf(
                            /* translators: %s: maxretry = 1 */
                            __('You are using the standard hard jail with %s.', 'wpf2b-addon-blocklist'),
                            '<code>maxretry = 1</code>'
                        )
                    );
                }

                $results['label'] = self::PREFIX.$results['label'];
            }
        }

        return $results;
    }

    /**
     * Get the maxretry value from the jail with the matching filter name
     *
     * Jail (section) names can be anything, but the filter name must match the filter filename.
     *
     * @since  2.1.0
     *
     * @param  array    $ini                Parsed ini file(s)
     * @param  string   $filter             Filter name to search for
     * @param  string   &$matching_section  The name of the matching Jail (section)
     *
     * @return int|null maxretry value, null if no matching filter
     */
    protected function ini_section_maxretry(array $ini, string $filter, string &$matching_section = null): ?int
    {
        foreach ($ini as $jail => $entries) {
            $maxretry = null;
            $foundFilter = false;

            foreach ($entries as $entry => $value) {
                switch ($entry) {
                    case 'filter':
                        if ($filter == $value) {
                            $foundFilter = true;
                            $matching_section = $value;
                            if ($maxretry) {
                                return $maxretry;
                            }
                        } else {
                            $foundFilter = false;
                            continue 2;
                        }
                        break;
                    case 'maxretry':
                        $maxretry = intval($value);
                        if ($foundFilter) {
                            return intval($value);
                        }
                        break;
                }
            }
        }
        return null;
    }

    /**
     * Is there a secret key?
     *
     * If the user hasn't opted into Freemius there won't be one,
     * in all other cases it's a problem that'll need support to resolve.
     *
     * @since  2.1.0
     *
     * @return array    The test result.
     */
    public function get_test_blocklist_secret_key(): array
    {
        $results = [
            'label'     => '',
            'status'    => '',
            'badge'     => [
                'label' => __('Security'),
                'color' => 'blue'
            ],
            'description'   => sprintf('<p>%s</p>', __('The Secret Key is used to authenticate with the Blocklist Network Service (BNS).', 'wpf2b-addon-blocklist')),
            'actions'       => '',
            'test'          => 'wp_fail2ban_blocklist_secret_key'
        ];

        try {
            RestRoute::get_secret_keys();
            $results['label'] = __('There seems to be a valid Secret Key', 'wpf2b-addon-blocklist');
            $results['status'] = 'good';

        } catch (\RuntimeException $e) {
            $results['label'] = __('No valid Secret Key could be found', 'wpf2b-addon-blocklist');
            $results['status'] = 'critical';

            if (null === ($fs = wpf2b_addon_blocklist_fs())) {
                // how did we get here?!

            } elseif ($fs->can_use_premium_code()) {
                // email support
                $results['description'] .= sprintf(
                    '<p>%s</p>',
                    sprintf(
                        __('Please %s to get help with resolving this.', 'wpf2b-addon-blocklist'),
                        sprintf(
                            '<a href="%s&summary=%s">%s</a>',
                            $fs->contact_url('technical_support', 'Failed Site Health check.'),
                            urlencode('No Secret Key found'),
                            __('contact support', 'wpf2b-addon-blocklist')
                        )
                    )
                );

            } elseif ($fs->is_registered() && $fs->is_tracking_allowed()) {
                // forum support
                $results['description'] .= sprintf(
                    '<p>%s</p>',
                    sprintf(
                        __('Please join the %s to get help with resolving this.', 'wpf2b-addon-blocklist'),
                        sprintf(
                            '<a href="https://forums.invis.net/c/wp-fail2ban-blocklist/support/37" target="_blank">%s</a> <span class="dashicons dashicons-external"></span>',
                            __('Forum support', 'wpf2b-addon-blocklist')
                        )
                    )
                );

            } else {
                // expected - not opted in
                $results['description'] .= sprintf('<p>%s</p>', __('You have not connected to Freemius. Blocklist will not work without connecting.'));
                $results['actions']      = sprintf('<p><a href="%s">%s</a></p>', network_admin_url('admin.php?page=wp-fail2ban_addon_blocklist'), __('Connect to Freemius', 'wpf2b-addon-blocklist'));
            }
        }

        $results['label'] = self::PREFIX.$results['label'];

        return $results;
    }
}

