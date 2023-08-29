<?php declare(strict_types=1);
/**
 * Admin
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   1.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist\config;

use          org\lecklider\charles\wordpress\wp_fail2ban\Config;
use          org\lecklider\charles\wordpress\wp_fail2ban\TabLoggingBase;

defined('ABSPATH') or exit;

/**
 *
 *
 */
class TabSettingsFree extends TabLoggingBase
{
    /**
     * Settings page slug
     *
     * @since 1.0.0
     */
    const SETTINGS_PAGE = 'wp-fail2ban-addon-blocklist-settings';

    /**
     * {@inheritDoc}
     */
    const HELP_LINK_DOCS = 'https://life-with.wp-fail2ban.com/add-ons/blocklist/configuration/advanced/';
    /**
     * {@inheritDoc}
     */
    const HELP_LINK_REFERENCE = 'https://docs.wp-fail2ban.com/projects/wp-fail2ban-blocklist/en/'.WP_FAIL2BAN_ADDON_BLOCKLIST_VER_SHORT;
    /**
     * {@inheritDoc}
     */
    const HELP_LINK_SUPPORT = 'https://forums.invis.net/c/wp-fail2ban-blocklist/support/';

    /**
     * Link to documentation for WPf2b < 5.0
     *
     * @since  2.0.1    Override base
     *
     * @param  string   $define
     *
     * @return string
     */
    protected function doc_link(string $define): string
    {
        if (version_compare('WP_FAIL2BAN_VER_SHORT', '5.0', '>=')) {
            return parent::doc_link($define);

        } else {
            $link = <<< HTML
<a href="%s/defines/constants/%s.html"
   style="text-decoration: none;"
   target="_blank"
   title="%s">%s<span class="dashicons dashicons-external"
                      style="vertical-align: text-bottom"></span></a>
HTML;

            return sprintf($link, self::HELP_LINK_REFERENCE, $define, __('Documentation', 'wp-fail2ban'), $define);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        // phpcs:disable Generic.Functions.FunctionCallArgumentSpacing
        $this->__['logging-log-blocklist']     = __('Blocklist', 'wpf2b-addon-blocklist');

        $this->__['ips']            = __('IPs',         'wpf2b-addon-blocklist');
        $this->__['ignore-ips']     = __('Ignore IPs',  'wpf2b-addon-blocklist');
        $this->__['advanced']       = __('Advanced',    'wpf2b-addon-blocklist');
        $this->__['custom-jail']    = __('Custom Jail', 'wpf2b-addon-blocklist');
        // phpcs:enable

        parent::__construct('addon-blocklist-settings', '++ Blocklist');
    }

    /**
     * Hook: admin_init
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function admin_init(): void
    {
        // phpcs:disable Generic.Functions.FunctionCallArgumentSpacing
        add_settings_field('logging-log-blocklist',                     $this->__['logging-log-blocklist'],    [$this, 'logging_log_blocklist'],  'wp-fail2ban-logging', 'wp-fail2ban-logging');

        add_settings_section('addon-blocklist-settings-ips',            $this->__['ips'],         [$this, 'section_ips'], self::SETTINGS_PAGE);
        add_settings_field('blocklist-settings-ips-ignore',             $this->__['ignore-ips'],  [$this, 'ignore_ips'],  self::SETTINGS_PAGE, 'addon-blocklist-settings-ips');

        add_settings_section('addon-blocklist-settings-advanced',       $this->__['advanced'],    [$this, 'section_advanced'], self::SETTINGS_PAGE);
        add_settings_field('blocklist-settings-advanced-custom-jail',   $this->__['custom-jail'], [$this, 'custom_jail'],  self::SETTINGS_PAGE, 'addon-blocklist-settings-advanced');
        // phpcs:enable

        add_filter(WP_FAIL2BAN_NS.'\Config::getDesc', [$this, 'getDesc'], 10, 2);
    }

    /**
     * {@inheritDoc}
     *
     * @since  1.0.0
     *
     * @return void
     */
    public function current_screen(): void
    {
        $this->add_help_tab('ips', [
            $this->help_entry('ignore-ips', [
                __('A list of full IPv4 addresses in CIDR notation (/32 is assumed if missing), and/or IPv6 addresses (/128 is assumed if missing).', 'wpf2b-addon-blocklist'),
                __('Lines starting with "#" are comments.', 'wpf2b-addon-blocklist'),
                __('<strong>NB:</strong> Abbreviated CIDR like <code>127/8</code> is not supported. IPv6 addresses require <i>WP fail2ban</i> 5.0 or later.', 'wpf2b-addon-blocklist')
            ])
        ]);
        $this->add_help_tab('advanced', [
            $this->help_entry('custom-jail', [
                __('By default the Blocklist add-on uses the existing <tt>hard</tt> and <tt>soft</tt> jails. To use a custom jail enable this option.', 'wpf2b-addon-blocklist'),
                $this->see_also(['WP_FAIL2BAN_ADDON_BLOCKLIST_CUSTOM_JAIL'])
            ])
        ]);

        parent::current_screen();
    }

    /**
     * Section: IPs
     *
     * @since  1.0.0
     *
     * @return void
     */
    public function section_ips(): void
    {
        // noop
    }

    /**
     * Ignore IPs.
     *
     * @since  1.0.0
     *
     * @return void
     */
    public function ignore_ips(): void
    {
        printf(
            '<fieldset><textarea class="code" cols="20" rows="10" disabled="disabled">%s</textarea></fieldset>',
            esc_html($this->ignore_ips_value())
        );
        $this->description('WP_FAIL2BAN_ADDON_BLOCKLIST_IGNORE_IPS');
    }

    /**
     * Section: Advanced
     *
     * @since  1.0.0
     *
     * @return void
     */
    public function section_advanced(): void
    {
        // noop
    }

    /**
     * Use custom jail.
     *
     * @since  1.0.0
     *
     * @return void
     */
    public function custom_jail(): void
    {
        $this->checkbox('WP_FAIL2BAN_ADDON_BLOCKLIST_CUSTOM_JAIL');
    }

    /**
     * Helper - multi-line string from ignore IPs list.
     *
     * @since  1.0.0
     *
     * @return string
     */
    protected function ignore_ips_value(): string
    {
        $ignore = Config::get('WP_FAIL2BAN_ADDON_BLOCKLIST_IGNORE_IPS');
        return (is_array($ignore))
            ? join("\n", $ignore)
            : join("\n", array_map('trim', explode(',', $ignore)));
    }

    /**
     * WP_FAIL2BAN_ADDON_BLOCKLIST_LOG
     *
     * @since  1.0.0
     *
     * @return void
     */
    public function logging_log_blocklist(): void
    {
        printf(
            '<a name="%s"></a><label>%s: %s</label><p class="description">%s</p>',
            $this->field_id('WP_FAIL2BAN_ADDON_BLOCKLIST_LOG'),
            __('Use facility', 'wp-fail2ban'),
            $this->getLogFacilities('WP_FAIL2BAN_ADDON_BLOCKLIST_LOG', true),
            Config::desc('WP_FAIL2BAN_ADDON_BLOCKLIST_LOG')
        );
    }

    /**
     * Filter: provide form element descriptions
     *
     * @since  1.0.0
     *
     * @param  null     $null
     * @param  string   $define
     *
     * @return string|null
     */
    public function getDesc($null, $define): ?string
    {
        switch ($define) {
            case 'WP_FAIL2BAN_ADDON_BLOCKLIST_IGNORE_IPS':
                return __('A list of IP addresses in CIDR notation.', 'wpf2b-addon-blocklist');
            case 'WP_FAIL2BAN_ADDON_BLOCKLIST_CUSTOM_JAIL':
                return __('Use a custom <tt>fail2ban</tt> jail.', 'wpf2b-addon-blocklist');
            default:
                return $null;
        }
    }
}

