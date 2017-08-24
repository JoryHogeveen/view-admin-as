<?php
/**
 * View Admin As - Unit tests
 *
 * Module: Role Defaults.
 *
 * @todo
 * - Import
 * - Export
 * - Copy
 * - Clear/Delete
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
}
