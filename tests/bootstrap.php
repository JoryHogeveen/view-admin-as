<?php
/**
 * View Admin As - Unit tests bootstrap
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( function_exists( 'xdebug_disable' ) ) {
	xdebug_disable();
}
// PHP < 5.3
if ( ! defined( '__DIR__' ) ) {
	define( '__DIR__', dirname( __FILE__ ) );
}

// Error reporting
error_reporting( E_ALL & ~E_DEPRECATED & ~E_STRICT );

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

define( 'TEST_VAA_PLUGIN_NAME'   , 'view-admin-as.php' );
define( 'TEST_VAA_PLUGIN_FOLDER' , basename( dirname( __DIR__ ) ) );
define( 'TEST_VAA_PLUGIN_PATH'   , TEST_VAA_PLUGIN_FOLDER . '/' . TEST_VAA_PLUGIN_NAME );
define( 'TEST_VAA_DIR', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );


// Activates this plugin in WordPress so it can be tested.
$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( TEST_VAA_PLUGIN_PATH ),
);

require_once TEST_VAA_DIR . 'functions.php';

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __DIR__ ) . '/' . TEST_VAA_PLUGIN_NAME;
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

echo 'Installing View Admin As' . PHP_EOL;

require_once TEST_VAA_DIR . 'factory.php';
VAA_UnitTest_Factory::get_instance();

if ( ! is_multisite() ) {
	activate_plugin( TEST_VAA_PLUGIN_PATH );
}

