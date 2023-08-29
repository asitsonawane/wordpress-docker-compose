<?php declare(strict_types=1);
/**
 * User enumeration
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   1.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist\feature;

use          com\wp_fail2ban\addons\Blocklist\Event;

defined('ABSPATH') or exit;

/**
 * Common enumeration handling
 *
 * @since   1.0.0
 *
 * @return  bool
 */
function _log_bail_user_enum(): bool
{
    return Event::save(WPF2B_EVENT_AUTH_BLOCK_USER_ENUM);
}

