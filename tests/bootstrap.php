<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Nvm\Timologio\Tests
 */

// Define testing constants
define( 'TIMOLOGIO_TESTS_DIR', __DIR__ );
define( 'TIMOLOGIO_PLUGIN_DIR', dirname( __DIR__ ) . '/' );

// Load Composer autoloader
require_once TIMOLOGIO_PLUGIN_DIR . 'prefixed/vendor/autoload.php';

// Initialize Brain Monkey for WordPress function mocking
\Brain\Monkey\setUp();

// Mock WordPress constants that are commonly used
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );
}
