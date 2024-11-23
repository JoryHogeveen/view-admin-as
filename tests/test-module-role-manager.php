<?php
/**
 * View Admin As - Unit tests
 *
 * Module: Role Manager
 *
 * @todo Save methods + existing roles vs new roles
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

view_admin_as()->include_file( VIEW_ADMIN_AS_DIR . 'modules/class-role-manager.php', 'VAA_View_Admin_As_Role_Manager' );

class VAA_Module_Role_Manager_UnitTest extends VAA_UnitTestCase {

	/**
	 * @return VAA_View_Admin_As_Role_Manager
	 */
	static function get_instance() {
		return VAA_View_Admin_As_Role_Manager::get_instance();
	}

	/**
	 * Test sanitizing.
	 * @see VAA_View_Admin_As_Role_Manager::sanitize_role_slug()
	 * @see VAA_View_Admin_As_Role_Manager::sanitize_role_name()
	 */
	function test_sanitize() {

		/**
		 * Sanitize role names.
		 */

		// Capitalize
		$this->assertEquals( 'Test', VAA_View_Admin_As_Role_Manager::sanitize_role_name( 'test' ) );
		// Capitalize and convert underscores to spaces
		$this->assertEquals( 'Test Yay', VAA_View_Admin_As_Role_Manager::sanitize_role_name( 'test_yay' ) );

		/**
		 * Sanitize role slugs.
		 */

		// Remove caps
		$this->assertEquals( 'test', VAA_View_Admin_As_Role_Manager::sanitize_role_slug( 'Test' ) );
		// Special chars, keep underscores
		$this->assertEquals( 'test', VAA_View_Admin_As_Role_Manager::sanitize_role_slug( 'Test@_' ) );
		// Lowercase and underscores only
		$this->assertEquals( 'test_yay', VAA_View_Admin_As_Role_Manager::sanitize_role_slug( 'Test@_Yay!' ) );
		$this->assertEquals( 'test_yay', VAA_View_Admin_As_Role_Manager::sanitize_role_slug( 'Test Yay!' ) );

	}

	/**
	 * Test export roles.
	 * @see VAA_View_Admin_As_Role_Manager::export_roles()
	 */
	function test_export() {
		$class = self::get_instance();

		// Full export.
		$export = $class->export_roles( array( 'role' => '__all__' ) );
		$roles = array_keys( $export );
		$compare = array(
			'administrator',
			'editor',
			'author',
			'contributor',
			'subscriber',
		);

		$this->assertEquals( $roles, $compare );

		// Single role export.
		$export = $class->export_roles( array( 'role' => 'editor' ) );

		$roles = array_keys( $export );
		$compare = array( 'editor' );

		$this->assertEquals( $roles, $compare );

		// Non-existing role export.
		$export = $class->export_roles( array( 'role' => 'non_existing_role' ) );
		$compare = __( 'Role not found', VIEW_ADMIN_AS_DOMAIN );

		$this->assertEquals( $export, $compare );

	}

	/**
	 * Test import roles.
	 * @see VAA_View_Admin_As_Role_Manager::import_roles()
	 * @todo Import methods
	 * @todo Caps only
	 */
	function test_import() {
		$class = self::get_instance();
		$role = 'test_import';

		/**
		 * Incorrect.
		 */
		$result = $class->import_roles( 'test' );
		// We expect an error string.
		$this->assertNotSame( true, $result );

		$caps = array(
			'read' => array( 'yay' ),
			'test' => true,
		);
		$result = $class->import_roles( $caps );
		// We expect an error array.
		$this->assertNotSame( true, $result );

		/**
		 * Correct.
		 */
		$caps = array(
			'read' => true,
			'test' => true,
		);

		$result = $class->import_roles( array(
			'data' => array( $role => $caps ),
		) );
		$this->assertSame( true, $result );

		$test_import = get_role( $role );

		$this->assertEquals( $caps, $test_import->capabilities );

	}

	/**
	 * Test clone roles.
	 * @see VAA_View_Admin_As_Role_Manager::clone_role()
	 */
	function test_clone() {
		$class = self::get_instance();
		$role = 'test_clone';

		/**
		 * Incorrect.
		 */
		$result = $class->clone_role( 'non_existing_role', $role );
		// We expect an error string.
		$this->assertNotSame( true, $result );

		/**
		 * Correct.
		 */
		$result = $class->clone_role( 'editor', $role );
		// We expect an error string.
		$this->assertSame( true, $result );

		$editor = get_role( 'editor' );
		$test_clone = get_role( $role );

		$this->assertEquals( $editor->capabilities, $test_clone->capabilities );

	}

	/**
	 * Test rename roles.
	 * @see VAA_View_Admin_As_Role_Manager::rename_role()
	 */
	function test_rename() {
		$class = self::get_instance();
		$rename = 'Rename';

		/**
		 * Incorrect.
		 */
		$result = $class->rename_role( 'non_existing_role', $rename );
		// We expect an error string.
		$this->assertNotSame( true, $result );

		/**
		 * Correct.
		 */
		$result = $class->rename_role( 'editor', $rename );
		// We expect an error string.
		$this->assertSame( true, $result );

		$editor = get_role( 'editor' );
		// The editor name should be the new name.
		$this->assertSame( $editor->name, $rename );

		// Revert change.
		$class->rename_role( 'editor', 'Editor' );
		$editor = get_role( 'editor' );
		// Verify that the role is renamed.
		$this->assertSame( $editor->name, 'Editor' );

	}

	/**
	 * Test delete roles.
	 * @see VAA_View_Admin_As_Role_Manager::delete_role()
	 */
	function test_delete() {
		$class = self::get_instance();
		// Load all roles again.
		VAA_UnitTest_Factory::vaa_reinit();

		/**
		 * Incorrect.
		 */
		$result = $class->delete_role( 'non_existing_role' );
		// We expect an error string.
		$this->assertNotSame( true, $result );

		/**
		 * Protected roles.
		 */
		$result = $class->delete_role( 'administrator' );
		// We expect an error string.
		$this->assertNotSame( true, $result );

		$result = $class->delete_role( get_option( 'default_role' ) );
		// We expect an error string.
		$this->assertNotSame( true, $result );

		/**
		 * Correct.
		 */
		$result = $class->delete_role( 'test_import' );
		$this->assertEquals( true, $result );

		$result = $class->delete_role( 'test_clone' );
		$this->assertEquals( true, $result );

		// Load all roles again after removal.
		VAA_UnitTest_Factory::vaa_reinit();
	}

	/**
	 * Test migrate users
	 * @see VAA_View_Admin_As_Role_Manager::save_role()
	 * @see VAA_View_Admin_As_Role_Manager::migrate_users()
	 * @todo Test the actual user roles.
	 */
	function test_migrate_users() {
		$class = self::get_instance();

		$dummy_role = 'Test Migrate';
		$class->save_role( $dummy_role, array( 'read' ) );

		// Load all roles again after removal.
		VAA_UnitTest_Factory::vaa_reinit();

		$dummy_role = get_role( 'test_migrate' );
		// Check if the role exists.
		$this->assertNotEquals( null, $dummy_role );

		/**
		 * Incorrect.
		 */
		$result = $class->delete_role( 'editor', 'non_existing_role' );
		// We expect an error string.
		$this->assertNotSame( true, $result );

		$result = $class->delete_role( 'editor', 'editor' );
		// We expect an error string.
		$this->assertNotSame( true, $result );

		/**
		 * Correct.
		 */
		$editor = get_role( 'editor' );

		$result = $class->delete_role( 'editor', 'test_migrate' );
		// Should be ok!
		$this->assertSame( true, $result );

		/**
		 * Revert
		 */
		$class->save_role( $editor->name, $editor->capabilities );


		$result = $class->delete_role( 'test_migrate', array(
			'migrate' => true,
			'new_role' => 'editor',
		) );
		// Should be ok!
		$this->assertSame( true, $result );

		// Load all roles again after removal.
		VAA_UnitTest_Factory::vaa_reinit();
	}
}
