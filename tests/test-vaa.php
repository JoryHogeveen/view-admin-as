<?php
/**
 * View Admin As - Unit tests
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

class VAA_UnitTest extends WP_UnitTestCase {

	/**
	 * @var VAA_View_Admin_As
	 */
	public $vaa_main = null;

	/**
	 * @var VAA_View_Admin_As_Store
	 */
	public $vaa_store = null;

///////////////////////////////////////////////
//           VAA TESTS
///////////////////////////////////////////////

	/**
	 * Check that activation doesn't break.
	 */
	function test_vaa_activated() {

		$this->assertTrue( is_plugin_active( TEST_VAA_PLUGIN_PATH ) );

		$this->assertTrue( function_exists( 'view_admin_as' ) );

		wp_set_current_user( 0 );
		VAA_UnitTest_Factory::vaa_reinit();

		$this->assertFalse( view_admin_as()->is_enabled() );
	}

	/**
	 * Tests for when the current user is an editor without VAA capabilities.
	 */
	function test_vaa_user_visitor() {
		VAA_UnitTest_Factory::vaa_reinit();

		// Tests
		$this->vaa_assert_enabled( false );
		$this->vaa_assert_super_admin( false );
		$this->vaa_assert_superior_admin( false );
	}

	/**
	 * Tests for when the current user is an editor.
	 */
	function test_vaa_user_editor() {
		VAA_UnitTest_Factory::set_current_user( 'Editor' );

		// Tests
		$this->vaa_assert_enabled( false );
		$this->vaa_assert_super_admin( false );
		$this->vaa_assert_superior_admin( false );
	}

	/**
	 * @todo Network installations?
	 * Tests for when the current user is an editor with VAA capabilities.
	 */
	function test_vaa_user_editor_vaa() {
		$user = VAA_UnitTest_Factory::set_current_user( 'VAA Editor' );

		// Tests
		if ( is_multisite() ) {
			/**
			 * Requires 'edit_users' && 'manage_network_users'.
			 * @hack Getting users fixed in tests/functions.php get_user_by().
			 */
			$this->vaa_assert_enabled( true );
			$this->vaa_assert_super_admin( false );
			$this->vaa_assert_superior_admin( false );
		} else {
			/**
			 * Requires 'edit_users'.
			 */
			$this->vaa_assert_enabled( true );
			$this->vaa_assert_super_admin( false );
			$this->vaa_assert_superior_admin( false );
		}
	}

	/**
	 * Tests for when the current user is an administrator.
	 */
	function test_vaa_user_admin() {
		$user = VAA_UnitTest_Factory::set_current_user( 'Administrator' );

		// Tests
		if ( is_multisite() ) {
			$this->vaa_assert_enabled( false );
			$this->vaa_assert_super_admin( false );
			$this->vaa_assert_superior_admin( false );
		} else {
			$this->vaa_assert_enabled( true );
			$this->vaa_assert_super_admin( true );
			$this->vaa_assert_superior_admin( false );
		}
	}

	/**
	 * Tests for when the current user is an administrator.
	 */
	function test_vaa_user_super_admin() {
		$user = VAA_UnitTest_Factory::set_current_user( 'Super Admin' );

		// Tests
		$this->vaa_assert_enabled( true );
		$this->vaa_assert_super_admin( true );
		$this->vaa_assert_superior_admin( false );
	}

	/**
	 * Tests for when the current user is an author.
	 * Put this as second last to make sure VAA is fully reset again.
	 */
	function test_vaa_user_author() {
		VAA_UnitTest_Factory::set_current_user( 'Author' );

		// Tests
		$this->vaa_assert_enabled( false );
		$this->vaa_assert_super_admin( false );
		$this->vaa_assert_superior_admin( false );
	}

	/**
	 * Tests for when the current user is an administrator.
	 */
	function test_vaa_user_superior_admin() {
		$user = VAA_UnitTest_Factory::set_current_user( 'Admin' );

		// Tests
		$this->vaa_assert_enabled( true );
		$this->vaa_assert_super_admin( true );
		$this->vaa_assert_superior_admin( true );
	}

///////////////////////////////////////////////
//           VAA ASSERT HELPERS
///////////////////////////////////////////////

	/**
	 * Assert if VAA main is enabled.
	 * @param bool $bool
	 */
	function vaa_assert_enabled( $bool ) {
		$this->assertEquals( $bool, VAA_UnitTest_Factory::$vaa->is_enabled() );
	}

	/**
	 * Assert if the current user is a super admin within VAA.
	 * @param bool $bool
	 */
	function vaa_assert_super_admin( $bool ) {
		$this->assertEquals( $bool, VAA_API::is_super_admin() );
	}

	/**
	 * Assert if the current user is a superior admin within VAA.
	 * @param bool $bool
	 */
	function vaa_assert_superior_admin( $bool ) {
		$this->assertEquals( $bool, VAA_API::is_superior_admin() );
	}

}
