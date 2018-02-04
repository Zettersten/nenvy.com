<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'wsazureeast038');

/** MySQL database username */
define('DB_USER', 'zettersten@wsazureeast038');

/** MySQL database password */
define('DB_PASSWORD', 'Erik5388z!');

/** MySQL hostname */
define('DB_HOST', 'wsazureeast038.mysql.database.azure.com');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'W[AHZYZI?k4<:5U,Yx<T.{Oey^idn!yU=XF4:N[{^{sJIjjLZIj5fm1S.q1s~5#-');
define('SECURE_AUTH_KEY',  ' f@=d=K7jxiY`%+`y[?{5<ufb2%alkaSe:vN.^i9WnbfjI7PJFE@sug3g8gdq=|$');
define('LOGGED_IN_KEY',    '=fW&i_C8@R]p+bA~ZU~cBOpLYS8*vEWg4<-3Sd9;)J8wHYs8 }OtI-?`PW?pmaxw');
define('NONCE_KEY',        '0~XZ`K)i<y5Q)$O_|yT?;]`=PTdhuih=xkYt2AE</fj),74IAie$!O-d*ORh4<xY');
define('AUTH_SALT',        'euGQ.$gcO>pL/j[UD.yx#=297>5i#>$Q+CjY[J^AjI AUo6}7L5u};>3la%iM_O!');
define('SECURE_AUTH_SALT', 'HEWsb~^G3t}ZN-?j37e&zRaI0GGX|{#j<FFJNEXhdHN;cSLQm$ ?O>$b5qwE5]BV');
define('LOGGED_IN_SALT',   'q+mTl]jB2,/67(QKUwMw*QF@n=)3Rjf;#~6b]GB+gVI@dY,*O(6AmTjpgW%(=wn7');
define('NONCE_SALT',       'YG?eali*tUw5}Q4igBWR1)i)_4*b$J.zawN~i,6mA})ANDWF,5UDA8GZ<h@*<~U}');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);


/* That's all, stop editing! Happy blogging. */   

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
