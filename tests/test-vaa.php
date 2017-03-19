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
//            VAA TESTS
///////////////////////////////////////////////

	/**
	 * Check that activation doesn't break.
	 */
	function test_vaa_activated() {

		$this->assertTrue( is_plugin_active( TEST_VAA_PLUGIN_PATH ) );

		$this->assertTrue( function_exists( 'view_admin_as' ) );

		$this->assertFalse( view_admin_as()->is_enabled() );
	}

	/**
	 * Tests for when the current user is an editor without VAA capabilities.
	 */
	function test_vaa_user_visitor() {
		$this->vaa_reinit();

		// Tests
		$this->vaa_assert_enabled( false );
	}

	/**
	 * Tests for when the current user is an editor.
	 */
	function test_vaa_user_editor() {
		$this->vaa_set_current_user( 'Editor', 'editor' );

		// Tests
		$this->vaa_assert_enabled( false );
		$this->vaa_assert_super_admin( false );
	}

	/**
	 * @todo Network installations?
	 * Tests for when the current user is an editor with VAA capabilities.
	 */
	function test_vaa_user_editor_vaa() {
		$this->vaa_set_current_user( 'VAA Editor', 'editor', array( 'view_admin_as', 'edit_users' ) );

		// Tests
		if ( is_multisite() ) {
			// Requires 'edit_users' && 'manage_network_users'.
			$this->vaa_assert_enabled( false );
			$this->vaa_assert_super_admin( false );
		} else {
			$this->vaa_assert_enabled( true );
			$this->vaa_assert_super_admin( false );
		}
	}

	/**
	 * Tests for when the current user is an administrator.
	 */
	function test_vaa_user_admin() {
		$this->vaa_set_current_user( 'Administrator', 'administrator' );

		// Tests
		if ( is_multisite() ) {
			$this->vaa_assert_enabled( false );
			$this->vaa_assert_super_admin( false );
		} else {
			$this->vaa_assert_enabled( true );
			$this->vaa_assert_super_admin( true );
		}
	}

	/**
	 * Tests for when the current user is an administrator.
	 */
	function test_vaa_user_super_admin() {
		$this->vaa_set_current_user( 'Super Admin', 'administrator', array(), true );

		// Tests
		$this->vaa_assert_enabled( true );
		$this->vaa_assert_super_admin( true );
	}

///////////////////////////////////////////////
//            VAA ASSERT HELPERS
///////////////////////////////////////////////

	/**
	 * Assert if VAA main is enabled.
	 * @param bool $bool
	 */
	function vaa_assert_enabled( $bool ) {
		$this->assertEquals( $bool, $this->vaa_main->is_enabled() );
	}

	/**
	 * Assert if the current user is a super admin within VAA.
	 * @param bool $bool
	 */
	function vaa_assert_super_admin( $bool ) {
		$this->assertEquals( $bool, VAA_View_Admin_As_Store::is_super_admin() );
	}

///////////////////////////////////////////////
//            HELPER FUNCTIONS
///////////////////////////////////////////////

	/**
	 * Set the current user.
	 *
	 * @param   string  $name
	 * @param   string  $role          (optional) Only needed for a new user.
	 * @param   array   $capabilities  (optional) Only needed for a new user.
	 * @param   bool    $super_admin   (optional) Only needed for a new user.
	 * @return  WP_User
	 */
	function vaa_set_current_user( $name, $role = '', $capabilities = array(), $super_admin = false ) {
		global $current_user;
		$username = strtolower( preg_replace( "/[^a-zA-Z0-9]+/", "", $name ) );

		$user = get_user_by( 'login', $username );
		if ( ! $user ) {

			$id = wp_create_user( $username, 'test' );
			$current_user = new WP_User( $id );

			$current_user->set_role( $role );
			if ( ! empty( $capabilities ) ) {
				foreach( $capabilities as $cap => $grant ) {
					if ( is_string( $grant ) ) {
						$cap = $grant;
						$grant = true;
					}
					$current_user->add_cap( $cap, $grant );
					// WP 4.1 issue
					$current_user->get_role_caps();
				}
			}
			$current_user->display_name = $name;


			if ( $super_admin && $role === 'administrator' && is_multisite() ) {
				grant_super_admin( $current_user->ID );
			}

		} else {
			$current_user = $user;
		}

		echo PHP_EOL . 'User set: ' . $current_user->display_name . ' | ID: ' . $current_user->ID . ' | username: ' . $current_user->user_login . PHP_EOL;

		$this->vaa_reinit();

		return $current_user;
	}

	/**
	 * Re-init VAA.
	 */
	function vaa_reinit() {
		remove_all_actions( 'vaa_view_admin_as_init' );
		$this->vaa_main = view_admin_as();
		$this->vaa_main->init( true );

		$this->vaa_store = $this->vaa_main->store();

		if ( $this->vaa_store ) {
			// Make a force reinit for the store user data.

			$this->vaa_store->init( true );
			$this->vaa_store->set_curUserSession( 'test' );

			// Resets is_enabled. Required because of force reinit.
			$this->vaa_main->validate_user();

			// Reload VAA type data.
			$this->vaa_store->store_users();
			$this->vaa_store->store_roles();
			$this->vaa_store->store_caps();
		}

		echo PHP_EOL . 'View Admin As re-init done.' . PHP_EOL;
	}

}
