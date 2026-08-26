<?php
/**
 * Example WordPress configuration for local development.
 *
 * Copy this file to wp-config.php and replace placeholder values.
 * Never commit the real wp-config.php file.
 */

define( 'DB_NAME', 'your_local_database' );
define( 'DB_USER', 'your_local_database_user' );
define( 'DB_PASSWORD', 'your_local_database_password' );
define( 'DB_HOST', 'localhost' );

define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

/*
 * Generate fresh authentication keys and salts for your local environment:
 * https://api.wordpress.org/secret-key/1.1/salt/
 */
define( 'AUTH_KEY',         'replace-with-random-value' );
define( 'SECURE_AUTH_KEY',  'replace-with-random-value' );
define( 'LOGGED_IN_KEY',    'replace-with-random-value' );
define( 'NONCE_KEY',        'replace-with-random-value' );
define( 'AUTH_SALT',        'replace-with-random-value' );
define( 'SECURE_AUTH_SALT', 'replace-with-random-value' );
define( 'LOGGED_IN_SALT',   'replace-with-random-value' );
define( 'NONCE_SALT',       'replace-with-random-value' );

$table_prefix = 'wp_';

define( 'WP_DEBUG', false );

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
