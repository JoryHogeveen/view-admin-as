<?php
/**
 * View Admin As - Unit tests
 *
 * Controller manager/register.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

class VAA_Controller_UnitTest extends VAA_UnitTestCase {

	public static $is_action_done = false;

	public static function get_instance() {
		return view_admin_as()->controller();
	}

	public function setUp() {
		parent::setUp();
		add_action( 'vaa_view_admin_as_update_view', array( 'VAA_Controller_UnitTest', 'action_callback' ) );
		add_action( 'vaa_view_admin_as_reset_view', array( 'VAA_Controller_UnitTest', 'action_callback' ) );
		add_action( 'vaa_view_admin_as_cleanup_views', array( 'VAA_Controller_UnitTest', 'action_callback' ) );
		add_action( 'vaa_view_admin_as_reset_all_views', array( 'VAA_Controller_UnitTest', 'action_callback' ) );
	}

	/**
	 * Test Controller class methods
	 */
	function test_reset() {
		$controller = self::get_instance();

		$test_view = array(
			'role' => 'editor',
		);

		view_admin_as()->store()->set_view( $test_view );
		$this->assertTrue( (bool) $controller->update_view() );
		$this->assertTrue( self::$is_action_done ); // vaa_view_admin_as_update_view.
		self::$is_action_done = false;

		$this->assertEquals( view_admin_as()->store()->get_view(), $test_view );
		$this->assertEquals( $controller->get_view(), $test_view );

		$controller->reset_view();
		$this->assertTrue( self::$is_action_done ); // vaa_view_admin_as_reset_view.
		self::$is_action_done = false;

		view_admin_as()->store()->set_view( null );

	}

	/**
	 * Test Controller class methods
	 */
	function test_reset_all() {
		$controller = self::get_instance();

		$test_view = array(
			'role' => 'author',
		);

		view_admin_as()->store()->set_view( $test_view );
		$this->assertTrue( (bool) $controller->update_view() );
		$this->assertTrue( self::$is_action_done ); // vaa_view_admin_as_update_view.
		self::$is_action_done = false;

		$this->assertEquals( view_admin_as()->store()->get_view(), $test_view );
		$this->assertEquals( $controller->get_view(), $test_view );

		$controller->reset_all_views();
		$this->assertTrue( self::$is_action_done ); // vaa_view_admin_as_reset_all_views.
		self::$is_action_done = false;

		view_admin_as()->store()->set_view( null );

	}

	static function action_callback() {
		var_dump( 'yay' );
		self::$is_action_done = true;
	}
}
