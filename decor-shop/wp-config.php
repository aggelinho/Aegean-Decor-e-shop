<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'decor_shop_db' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '~VwJSZiEASbnI$6S31lxHiC^:3QmutpHvcMoWdcv?;Q=UJx_88! `Kq+G53qm(XT' );
define( 'SECURE_AUTH_KEY',  '*IA6zC0 *J6YL?{.t#ErX#:3_,A(v-?=N=Qf,)hdSjyw^k}y7=&xk2cbY=dE%A[{' );
define( 'LOGGED_IN_KEY',    '`DxGZGxnK%%K!<!qUdYT~b.i$ecBx>:t!D _x_KNF!]yezP[p>*(V6]+ ytn|T|a' );
define( 'NONCE_KEY',        '1k*cUAIGt,9En7pp}{H#&SB;#/xvf}}wr=|/DSL*zl^,c/]TF]4O<8<H:D_RJ.J.' );
define( 'AUTH_SALT',        '.E:]x}4sTwL1s/M+!~?Sf@ $%[Q?|p6=0aJ>}00+IS9hob;dS_(/M26*)mp%|@vw' );
define( 'SECURE_AUTH_SALT', '3QsS(:ggldCJ+=D _ I,6 roUd^:u<Lg%%yT=o(bU;l%_I&v2& &x*J_SocSUm;t' );
define( 'LOGGED_IN_SALT',   'orzEREP$?Ub1[9IYUXBGQAi98TGyaMcF&D@8a=n+<D,%99dd]5E1Vo`quT5cS>h;' );
define( 'NONCE_SALT',       'z9sCAdT!<CC~CY]g|^]1j17uBBnS&f$o19/1dl}pPuVyl+2?IXdhQ D>ArLT)$Df' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
