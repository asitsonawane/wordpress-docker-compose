<?php declare(strict_types=1);
/**
 * Blocked user functionality
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
 * @since 1.0.0
 *
 * @param   mixed|null  $user
 * @param   string      $username
 * @param   string      $password
 * @return  bool
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function block_users($user, string $username, string $password): bool
{
    return Event::save(WPF2B_EVENT_AUTH_BLOCK_USERNAME_LOGIN);
}

