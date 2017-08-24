<?php
/**
 * View Admin As - Unit tests
 *
 * Module: Role Defaults.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

view_admin_as()->include_file( VIEW_ADMIN_AS_DIR . 'modules/class-role-defaults.php', 'VAA_View_Admin_As_Role_Defaults' );

class VAA_Module_Role_Defaults_UnitTest extends WP_UnitTestCase {

	/**
	 * @return VAA_View_Admin_As_Role_Defaults
	 */
	static function get_instance() {
		return VAA_View_Admin_As_Role_Defaults::get_instance();
	}

	/**
	 * Test meta handling
	 * @see VAA_View_Admin_As_Role_Defaults::set_meta()
	 * @see VAA_View_Admin_As_Role_Defaults::validate_meta()
	 */
	function test_set_meta() {
		$class = self::get_instance();

		$org_meta = $class->get_meta();

		// Check if forbidden meta keys aren't added.
		$forbidden_meta = array(
			'vaa-view-admin-as' => true,
			'session_tokens' => true,
		);

		$class->set_meta( $forbidden_meta );
		$this->assertEquals( $org_meta, $class->get_meta() );

		// Check if allowed meta keys are added properly.
		$allowed_meta = array(
			'test' => true,
		);
		$check_meta = $org_meta;
		$check_meta['test'] = true;
		ksort( $check_meta );

		$class->set_meta( $allowed_meta );
		$this->assertEquals( $check_meta, $class->get_meta() );

		// Check if active meta keys are properly overwritten.
		$overwrite_meta = array(
			'metaboxhidden_%%' => false,
			'admin_color' => false,
		);
		$check_meta = array_merge( $org_meta, $overwrite_meta );

		$class->set_meta( $overwrite_meta );
		$this->assertEquals( $check_meta, $class->get_meta() );

		// Reset.
		$class->set_meta( array() );
		$this->assertEquals( $org_meta, $class->get_meta() );

	}

	/**
	 * Test metakey compare.
	 * @see VAA_View_Admin_As_Role_Defaults::compare_metakey()
	 */
	function test_compare_metakey() {
		$class = self::get_instance();

		$org_meta = $class->get_meta();

		// Valid keys.
		$check_valid = array(
			'rich_editing',
			'metaboxhidden_test', // `metaboxhidden_%%`
			'edit_test_per_page', // `edit_%%_per_page`
		);

		foreach ( $check_valid as $check ) {
			$this->assertTrue( $class->compare_metakey( $check ) );
		}

		// Invalid keys.
		$check_invalid = array(
			'foo_bar',
			'metaboxhidden', // `metaboxhidden_%%`
			'metaboxhidden_', // `metaboxhidden_%%`
			'edit_per_page', // `edit_%%_per_page`
			'edit__per_page', // `edit_%%_per_page`
			'metaboxhidden_%%',
			'edit_%%_per_page',
		);

		foreach ( $check_invalid as $check ) {
			$this->assertFalse( $class->compare_metakey( $check ) );
		}

		// Small tests to verify double checks and valid custom meta keys.
		$check_extra = array(
			'foo_bar' => true,
			'edit__per_page' => true,
			'edit_per_page' => true,
			'meta-box-order_' => true,
			'meta-box-order' => true,
		);
		$class->set_meta( $check_extra );

		foreach ( array_keys( $check_extra ) as $check ) {
			$this->assertTrue( $class->compare_metakey( $check ) );
		}

		// Reset.
		$class->set_meta( array() );
		$this->assertEquals( $org_meta, $class->get_meta() );

	}

	/**
	 * Tests for importing and exporting.
	 * @see VAA_View_Admin_As_Role_Defaults::import_role_defaults()
	 * @see VAA_View_Admin_As_Role_Defaults::export_role_defaults()
	 */
	function test_import_export() {
		$class = self::get_instance();

		// Data only >> invalid
		$editor_import = array(
			'admin_color' => 'light',
			'screen_layout_post' => 4,
			'meta-box-order_dashboard' => array(
				'normal' => 'dashboard_right_now,dashboard_maybe_later',
				'side' => 'dashboard_activity',
			)
		);

		// @todo Patch if data only is possible.
		$result = $class->import_role_defaults( $editor_import );
		$this->assertNotEquals( true, $result );

		$import = array(
			'editor' => $editor_import,
		);

		$result = $class->import_role_defaults( $import );
		$this->assertEquals( true, $result );
		$this->assertEquals( $import, $class->get_role_defaults() );

		/**
		 * Invalid meta key.
		 */
		$import['editor']['invalid_key'] = 'nope';
		$result = $class->import_role_defaults( $import );

		// Should return error list array because of the invalid meta key.
		$this->assertNotEquals( true, $result );

		// Make sure the key doesn't exists in the role defaults.
		unset( $import['editor']['invalid_key'] );
		$this->assertEquals( $import, $class->get_role_defaults() );

		/**
		 * Import overwrite.
		 */
		$overwrite = $import;
		$overwrite['editor']['admin_color'] = 'dark';

		$result = $class->import_role_defaults( $overwrite, 'merge' );
		$this->assertEquals( true, $result );

		// Check export import overwrite.
		$this->assertEquals( $overwrite, $class->export_role_defaults() );
		$this->assertNotEquals( $import, $class->export_role_defaults() );

		/**
		 * Import append.
		 */
		$result = $class->import_role_defaults( $import, 'append' );
		$this->assertEquals( true, $result );

		// Check export import append. Editor admin color should still be `dark`.
		$this->assertEquals( $overwrite, $class->export_role_defaults() );
		$this->assertNotEquals( $import, $class->export_role_defaults() );

		$import_admin = array(
			'administrator' => array(
				'admin_color' => 'keraweb',
			),
		);
		$result = $class->import_role_defaults( $import_admin );
		$this->assertEquals( true, $result );

		/**
		 * Check export for a single role.
		 */
		$this->assertArrayNotHasKey( 'editor', $class->export_role_defaults( 'administrator' ) );
		$this->assertEquals( $import_admin, $class->export_role_defaults( 'administrator' ) );

		/**
		 * Check export for non existing data.
		 */
		$this->assertTrue( is_string( $class->export_role_defaults( 'non_existing_role' ) ) );

	}

	/**
	 * Tests for getting and copying.
	 * @see VAA_View_Admin_As_Role_Defaults::get_role_defaults()
	 * @see VAA_View_Admin_As_Role_Defaults::copy_role_defaults()
	 */
	function test_get_copy() {
		$class = self::get_instance();

		/**
		 * Editor role should still have defaults.
		 * @see VAA_Module_Role_Defaults_UnitTest::test_import_export
		 */
		$defaults = $class->get_role_defaults();

		/**
		 * Invalid, non existing role.
		 */
		$result = $class->copy_role_defaults( 'editor', 'non_existing_role' );
		$this->assertNotEquals( true, $result );

		/**
		 * Copy defaults.
		 */
		$result = $class->copy_role_defaults( 'editor', 'author' );
		$this->assertEquals( true, $result );

		// Check if copy was actually successful. Also checks getting role defaults with role parameter.
		$check = array(
			'author' => $defaults['editor'],
		);
		$this->assertEquals( $check['author'], $class->get_role_defaults( 'author' ) );

		/**
		 * Check full data.
		 */
		$check = array_merge( $check, $defaults );
		$this->assertEquals( $check, $class->get_role_defaults() );

	}

	/**
	 * @see VAA_View_Admin_As_Role_Defaults::clear_role_defaults()
	 */
	function test_clear() {
		$class = self::get_instance();

		$defaults = $class->get_role_defaults();

		/**
		 * Invalid, non existing role.
		 */
		$result = $class->clear_role_defaults( 'non_existing_role' );
		// @todo Currently still returns true, maybe return false if role doesn't exists?
		$this->assertEquals( true, $result );
		// Should still be the same.
		$this->assertEquals( $defaults, $class->get_role_defaults() );

		/**
		 * Clear defaults, single role.
		 */
		$result = $class->clear_role_defaults( 'author' );
		$this->assertEquals( true, $result );

		$new_defaults = $defaults;
		unset( $new_defaults['author'] );

		$this->assertEquals( $new_defaults, $class->get_role_defaults() );

		/**
		 * Clear all.
		 */
		$result = $class->clear_role_defaults( '__all__' );
		$this->assertEquals( true, $result );

		$this->assertEquals( array(), $class->get_role_defaults() );

	}
}
