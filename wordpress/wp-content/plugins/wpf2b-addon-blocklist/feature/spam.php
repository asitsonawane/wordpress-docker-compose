<?php declare(strict_types=1);
/**
 * Spam comments
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   1.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist\feature;

use          com\wp_fail2ban\addons\Blocklist\Event;

defined('ABSPATH') or exit;

/**
 * Log events: WPF2B_EVENT_COMMENT_SPAM
 *
 * @since   1.0.0
 *
 * @param   int     $comment_id
 * @param   string  $comment_status
 * @return  bool
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function log_spam_comment(int $comment_id, string $comment_status): bool
{
    return Event::save(WPF2B_EVENT_COMMENT_SPAM);
}

