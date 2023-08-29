<?php declare(strict_types=1);
/**
 * XML-RPC functionality
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   1.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist\feature;

use          com\wp_fail2ban\addons\Blocklist\Event;

defined('ABSPATH') or exit;

/**
 * Log event: WPF2B_EVENT_XMLRPC_MULTI_AUTH_FAIL
 *
 * @see \wp_xmlrpc_server::login()
 *
 * @since   1.0.0
 *
 * @param   \IXR_Error  $error
 * @param   \WP_Error   $user
 * @return  bool
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function xmlrpc_login_error($error, $user): bool
{
    return Event::save(WPF2B_EVENT_XMLRPC_MULTI_AUTH_FAIL);
}

/**
 * Log event: WPF2B_EVENT_XMLRPC_PINGBACK_ERROR
 *
 * @see \wp_xmlrpc_server::pingback_error()
 *
 * @since   1.0.0
 *
 * @param   \IXR_Error  $ixr_error
 * @return  bool
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function xmlrpc_pingback_error($ixr_error): bool
{
    return Event::save(WPF2B_EVENT_XMLRPC_PINGBACK_ERROR);
}

