<?php declare(strict_types=1);
/**
 * Activation Exceptions
 *
 * @package wp-fail2ban-lib-activation
 * @since   1.0.0
 */
namespace    com\wp_fail2ban\lib\Activation;

defined('ABSPATH') or exit; // @codeCoverageIgnore

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Parent plugin not loaded.
 *
 * @since 1.0.0
 */
class ActivationNoParentException extends \Exception
{

}

/**
 * Parent plugin too old.
 *
 * @since 1.0.0
 */
class ActivationTooOldException extends \Exception
{

}

