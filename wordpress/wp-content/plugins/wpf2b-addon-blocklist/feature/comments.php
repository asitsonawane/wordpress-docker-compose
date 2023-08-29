<?php declare(strict_types=1);
/**
 * Comment logging
 *
 * @package wp-fail2ban-addon-blocklist
 * @since   1.0.0
 */
namespace    com\wp_fail2ban\addons\Blocklist\feature;

use          com\wp_fail2ban\addons\Blocklist\Event;

defined('ABSPATH') or exit;

/**
 * Log event
 *
 * @since   1.0.0
 *
 * @param   bool    $maybe_notify
 * @param   int     $comment_ID
 * @return  bool
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function notify_post_author(bool $maybe_notify, int $comment_ID): bool
{
    return Event::save(WPF2B_EVENT_COMMENT);
}

/**
 * Log event: WPF2B_EVENT_COMMENT_NOT_FOUND
 *
 * @since   1.0.0
 *
 * @param   int     $comment_post_ID
 * @return  bool
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function comment_id_not_found(int $comment_post_ID): bool
{
    return Event::save(WPF2B_EVENT_COMMENT_NOT_FOUND);
}

/**
 * Log event: WPF2B_EVENT_COMMENT_CLOSED
 *
 * @since   1.0.0
 *
 * @param   int     $comment_post_ID
 * @return  bool
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function comment_closed(int $comment_post_ID): bool
{
    return Event::save(WPF2B_EVENT_COMMENT_CLOSED);
}

/**
 * Log event: WPF2B_EVENT_COMMENT_TRASH
 *
 * @since   1.0.0
 *
 * @param   int     $comment_post_ID
 * @return  bool
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function comment_on_trash(int $comment_post_ID): bool
{
    return Event::save(WPF2B_EVENT_COMMENT_TRASH);
}

/**
 * Log event: WPF2B_EVENT_COMMENT_DRAFT
 *
 * @since   1.0.0
 *
 * @param   int     $comment_post_ID
 * @return  bool
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function comment_on_draft(int $comment_post_ID): bool
{
    return Event::save(WPF2B_EVENT_COMMENT_DRAFT);
}

/**
 * Log event: WPF2B_EVENT_COMMENT_PASSWORD
 *
 * @since   1.0.0
 *
 * @param   int     $comment_post_ID
 * @return  bool
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function comment_on_password_protected(int $comment_post_ID): bool
{
    return Event::save(WPF2B_EVENT_COMMENT_PASSWORD);
}

