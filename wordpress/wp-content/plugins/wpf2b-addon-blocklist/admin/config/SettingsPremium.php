<?php declare(strict_types=1);
/**
 * Admin
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   1.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist\config;

use          org\lecklider\charles\wordpress\wp_fail2ban\Config;
use          org\lecklider\charles\wordpress\wp_fail2ban\premium\{
    TabBase,
    TabLoggingBase,
    WPf2b
};

use function org\lecklider\charles\wordpress\wp_fail2ban\premium\input_to_settings;

defined('ABSPATH') or exit;

/**
 *
 *
 */
class TabSettingsPremium extends TabSettingsFree
{
    use TabBase;
    use TabLoggingBase;

    /**
     * Construct
     *
     * @since  1.0.0
     */
    public function __construct()
    {
        add_filter(WP_FAIL2BAN_NS.'\premium\TabLogging::sanitize.post', [$this, 'logging_sanitize'], 10, 2);

        parent::__construct();
    }

    /**
     * Ignore IPs field
     *
     * @since  1.0.0
     *
     * @return void
     */
    public function ignore_ips(): void
    {
        if (WPf2b::is_plan_or_trial('bronze') && WPf2b::can_use_premium_code()) {
            printf(
                '<fieldset><textarea class="code" cols="20" rows="10" name="%s" id="%s" %s>%s</textarea></fieldset>',
                $this->field_name('WP_FAIL2BAN_ADDON_BLOCKLIST_IGNORE_IPS'),
                $this->field_id('WP_FAIL2BAN_ADDON_BLOCKLIST_IGNORE_IPS'),
                disabled(Config::ndef('WP_FAIL2BAN_ADDON_BLOCKLIST_IGNORE_IPS'), false, false),
                esc_html($this->ignore_ips_value())
            );
            $this->description('WP_FAIL2BAN_ADDON_BLOCKLIST_IGNORE_IPS');

        } else {
            parent::ignore_ips();
        }
    }

    /**
     * Save settings
     *
     * @since  1.0.0
     *
     * @param  array|null   $input
     *
     * @return array
     */
    public function sanitize(array $input = null): array
    {
        if (WPf2b::is_plan_or_trial('bronze')) {
            if (WPf2b::can_use_premium_code()) {
                $settings = Config::settings();

                input_to_settings('WP_FAIL2BAN_ADDON_BLOCKLIST_IGNORE_IPS', $input, $settings);

                input_to_settings('WP_FAIL2BAN_ADDON_BLOCKLIST_CUSTOM_JAIL', $input, $settings);

                return $settings;
            }
        }

        return [];
    }

    /**
     * Save Logging settings
     *
     * @since  1.0.0
     *
     * @param  array        $settings
     * @param  array|null   $input
     *
     * @return array
     */
    public function logging_sanitize(array $settings, array $input = null): array
    {
        input_to_settings('WP_FAIL2BAN_ADDON_BLOCKLIST_LOG', $input, $settings);

        return $settings;
    }
}

