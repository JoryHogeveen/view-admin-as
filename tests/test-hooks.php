<?php
/**
 * View Admin As - Unit tests
 *
 * Hooks manager/register.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

class VAA_Hooks_UnitTest extends VAA_UnitTestCase {

	/**
	 * Test Hooks class methods
	 */
	function test_hooks() {

		$hooks = new VAA_View_Admin_As_Hooks();

		$hooks->add_action( 'vaa-test', '__return_false', 10, 3 );
		$hooks->add_action( 'vaa-test-2', array( $this, 'test' ), 300, 2 );
		$hooks->add_action( 'vaa-test', array( 'stdClass', 'test' ) );

		$actions = $hooks->_get_actions();

		// __return_false is set on prio 10.
		// array( 'stdClass', 'test' ) doesn't have a prio to should be set to default, also 10.
		$this->assertEquals( 2, count( $actions['vaa-test'][10] ) );
		// Also check WP.
		$this->assertTrue( has_action( 'vaa-test' ) );

		$callable_to_string = 'stdClass::test';
		$this->assertTrue( array_key_exists( $callable_to_string, $actions['vaa-test'][10] ) );

		// There should be one action in `vaa-test-2`
		$this->assertEquals( 1, count( $actions['vaa-test-2'][300] ) );

		/**
		 * Removing an action without a known priority.
		 */

		// Remove an action with passing `null` as priority. The hooks class should be able to find it.
		$hooks->remove_action( 'vaa-test-2', array( $this, 'test' ), null );

		// Reload actions.
		$actions = $hooks->_get_actions();

		// `vaa-test-2` should now be empty.
		$this->assertEquals( 0, count( $actions['vaa-test-2'] ) );
		// Also check in WP.
		$this->assertFalse( has_action( 'vaa-test-2' ) );

		/**
		 * Only remove own actions.
		 */

		// Add an action to `vaa-test` without the use of the hooks class.
		// Make sure this callback is unique so the test works properly.
		add_action( 'vaa-test', '__return_null' );

		// Remove all actions known in the hooks class.
		$hooks->remove_own_actions( 'vaa-test' );

		// Reload actions.
		$actions = $hooks->_get_actions();

		// The hooks registry should now be empty.
		$this->assertFalse( array_key_exists( 'vaa-test', $actions ) );
		// But WP still has one action!
		$this->assertTrue( has_action( 'vaa-test' ) );

		$hooks->remove_all_actions( 'vaa-test' );

		// Reload actions.
		$actions = $hooks->_get_actions();

		// The hooks registry should still be empty.
		$this->assertFalse( array_key_exists( 'vaa-test', $actions ) );
		// And WP should be empty now as well.
		$this->assertFalse( has_action( 'vaa-test' ) );

	}
}
