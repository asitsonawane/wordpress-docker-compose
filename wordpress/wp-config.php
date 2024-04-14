<?php
if (!function_exists('getenv_docker')) {
	// https://github.com/docker-library/wordpress/issues/588 (WP-CLI will load this file 2x)
	function getenv_docker($env, $default) {
		if ($fileEnv = getenv($env . '_FILE')) {
			return rtrim(file_get_contents($fileEnv), "\r\n");
		}
		else if (($val = getenv($env)) !== false) {
			return $val;
		}
		else {
			return $default;
		}
	}
}

// ** Database settings - You can get this info from your web host ** //
define( 'DB_NAME', 'wordpress');

define( 'DB_USER', 'wordpress');

define( 'DB_PASSWORD', 'wordpress456@');

define( 'DB_HOST', 'db:3306');

define( 'DB_CHARSET', 'utf8');

define( 'DB_COLLATE', '');

define( 'AUTH_KEY',         getenv_docker('WORDPRESS_AUTH_KEY',         '04423bc090645886dbe150f045a3e00a1dadcc31') );
define( 'SECURE_AUTH_KEY',  getenv_docker('WORDPRESS_SECURE_AUTH_KEY',  'a408eeea6efb84b9a1539d2a63eeffe4e7d177fc') );
define( 'LOGGED_IN_KEY',    getenv_docker('WORDPRESS_LOGGED_IN_KEY',    'ca711606ec5707e77fb678f53a5444d4f73fb0c4') );
define( 'NONCE_KEY',        getenv_docker('WORDPRESS_NONCE_KEY',        'f19032350e0b42c986255e33137f1d3a14ea2fae') );
define( 'AUTH_SALT',        getenv_docker('WORDPRESS_AUTH_SALT',        '9dbe4242217b1df2785a031021d96aa1d352a048') );
define( 'SECURE_AUTH_SALT', getenv_docker('WORDPRESS_SECURE_AUTH_SALT', 'fa9b4e0efd0959bed7fbfea3f0cb2a39267be6d8') );
define( 'LOGGED_IN_SALT',   getenv_docker('WORDPRESS_LOGGED_IN_SALT',   '443d927672ca58eafa120c24c634cd407a10c509') );
define( 'NONCE_SALT',       getenv_docker('WORDPRESS_NONCE_SALT',       '5293b7a629f307c6e935e0388e13c63455dda996') );

$table_prefix = getenv_docker('WORDPRESS_TABLE_PREFIX', 'wp_');

define( 'WP_DEBUG', !!getenv_docker('WORDPRESS_DEBUG', '') );

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false) {
	$_SERVER['HTTPS'] = 'on';
}

if ($configExtra = getenv_docker('WORDPRESS_CONFIG_EXTRA', '')) {
	eval($configExtra);
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';

define( 'DISALLOW_FILE_EDIT', false );
