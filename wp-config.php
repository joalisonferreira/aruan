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
define( 'DB_NAME', 'srvlojavirtual1_wp651' );

/** Database username */
define( 'DB_USER', 'srvlojavirtual1_wp651' );

/** Database password */
define( 'DB_PASSWORD', '4@S2Y5pBp!' );

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
define( 'AUTH_KEY',         'rvxt6cmdiuwrphm8ulgsvyizuyveffopbdhgbjfv70q6nqwttq1xt8wla8vdzhks' );
define( 'SECURE_AUTH_KEY',  'bxhlkorhlirpvwcx3fka7uofs71ruq49t9yttgomla93qmzdzaoxnayvnbem2edw' );
define( 'LOGGED_IN_KEY',    'vz8fgp4uvcivx5cmvehaxfvtf9fm64mpnaxlyftezjbcadpeytmfnpsy5tpqkn6p' );
define( 'NONCE_KEY',        '64uim9zetvej8euyxzasg6j6qfg9992z4ba5dj5lmghuyz7tzjjxlab5tc8w1hgd' );
define( 'AUTH_SALT',        'fnfoy8sxok0h12t8de0llbf5fk5b8a6hogccakwg4elmu8dvogyytxdwywyux9pl' );
define( 'SECURE_AUTH_SALT', 'y4yjahsoxwvpvhfewvmykrd1x3dv1jabwl9prabkct6emf8cnb3gppcf0oslrowr' );
define( 'LOGGED_IN_SALT',   'fazztoqupsqpna1lu7brckcf1b981ymj5mdaia67ncny8xzhozxoliwqkhzuxo35' );
define( 'NONCE_SALT',       'xyhagmzcxdnwvtv7a29tbwvckjjecktgcfrdwckokllxwatzjxkmkfptkhllqonj' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wptq_';

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
