<?php declare(strict_types=1);
/**
 * Core
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   1.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist\core;

use          com\wp_fail2ban\addons\Blocklist\Event;

defined('ABSPATH') or exit;

/**
 * Catch empty usernames
 *
 * @see \wp_authenticate()
 *
 * @since   1.0.0
 *
 * @param   mixed|null  $user
 * @param   string      $username
 * @param   string      $password
 * @return  bool
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function authenticate($user, string $username, string $password): bool
{
    return Event::save(WPF2B_EVENT_AUTH_EMPTY_USER);
}

/**
 * Hook: wp_login_failed
 *
 * @since   1.0.0
 * @param   string  $username
 * @return  bool
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function wp_login_failed(string $username): bool
{
    global $wp_xmlrpc_server;

    if (defined('REST_REQUEST')) { // C
        $event  = WPF2B_EVENT_REST_AUTH_FAIL;
    } elseif ($wp_xmlrpc_server) { // B
        $event  = WPF2B_EVENT_XMLRPC_AUTH_FAIL;
    } else { // A
        $event  = WPF2B_EVENT_AUTH_FAIL;
    }

    return Event::save($event);
}

