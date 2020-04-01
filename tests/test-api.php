<?php
/**
 * View Admin As - Unit tests
 *
 * API class.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

view_admin_as()->include_file( VIEW_ADMIN_AS_DIR . 'includes/class-util.php', 'VAA_Util' );
view_admin_as()->include_file( VIEW_ADMIN_AS_DIR . 'includes/class-api.php', 'VAA_API' );

class VAA_API_UnitTest extends WP_UnitTestCase {

	/**
	 * Test methods:
	 * @see VAA_API::starts_with()
	 * @see VAA_API::ends_with()
	 */
	function test_end_starts_with() {

		$this->assertTrue(  VAA_API::starts_with( 'test_string', 'te' ) );
		$this->assertTrue(  VAA_API::starts_with( 'test_string', 'test_s' ) );
		$this->assertTrue(  VAA_API::starts_with( 'test_string', 'test_string' ) );
		$this->assertFalse( VAA_API::starts_with( 'test_string', 'est' ) );
		$this->assertFalse( VAA_API::starts_with( 'test_string', 'est_s' ) );
		$this->assertFalse( VAA_API::starts_with( 'test_string', 'string' ) );

		$this->assertTrue(  VAA_API::ends_with( 'test_string', 'ing' ) );
		$this->assertTrue(  VAA_API::ends_with( 'test_string', '_string' ) );
		$this->assertTrue(  VAA_API::ends_with( 'test_string', 'test_string' ) );
		$this->assertFalse( VAA_API::ends_with( 'test_string', 'te' ) );
		$this->assertFalse( VAA_API::ends_with( 'test_string', 'test_s' ) );
		$this->assertFalse( VAA_API::ends_with( 'test_string', 'rin' ) );

		// Double check for when a search string occurs multiple times.
		$this->assertTrue(  VAA_API::starts_with( 'test_test_string', 'test' ) );
		$this->assertFalse( VAA_API::starts_with( 'test_string_string', 'string' ) );
		$this->assertTrue(  VAA_API::ends_with( 'test_string_string', 'string' ) );
		$this->assertFalse( VAA_API::ends_with( 'test_test_string', 'test' ) );

	}

	/**
	 * Test methods:
	 * @see VAA_API::is_request()
	 * @see VAA_API::get_request()
	 */
	function test_requests() {

		$nonce = wp_create_nonce( 'view_admin_as_nonce' );

		// Check
		$this->assertFalse( VAA_API::is_request( 'view_admin_as' ) );

		$this->assertNull( VAA_API::get_request( 'view_admin_as' ) );
		$this->assertNull( VAA_API::get_request( $nonce, 'view_admin_as' ) );

		$data = array( 'foo' => 'bar' );

		$_POST['view_admin_as_post'] = $data;
		$_GET['view_admin_as_get'] = $data;

		$this->assertTrue( VAA_API::is_request( 'view_admin_as_post' ) ); // post is default
		$this->assertFalse( VAA_API::is_request( 'view_admin_as_get' ) );
		$this->assertTrue( VAA_API::is_request( 'view_admin_as_get', 'get' ) );

		$this->assertNull( VAA_API::get_request( 'view_admin_as_post' ) );
		$this->assertNull( VAA_API::get_request( 'view_admin_as_get' ) );
		$this->assertNull( VAA_API::get_request( $nonce, 'view_admin_as_post' ) );
		$this->assertNull( VAA_API::get_request( $nonce, 'view_admin_as_post', 'post' ) );
		$this->assertNull( VAA_API::get_request( $nonce, 'view_admin_as_get' ) );
		// No nonce is set yet.
		$this->assertNull( VAA_API::get_request( $nonce, 'view_admin_as_get', 'get' ) );

		$_GET['_vaa_nonce'] = $nonce;
		$_POST['_vaa_nonce'] = $nonce;

		$this->assertEquals( $data, VAA_API::get_request( 'view_admin_as_nonce', 'view_admin_as_post' ) );
		$this->assertEquals( $data, VAA_API::get_request( 'view_admin_as_nonce', 'view_admin_as_post', 'post' ) );
		$this->assertEquals( $data, VAA_API::get_request( 'view_admin_as_nonce', 'view_admin_as_get', 'get' ) );

		// Check Json data
		$_GET['view_admin_as_get'] = json_encode( $data );
		$_POST['view_admin_as_post'] = json_encode( $data );

		$this->assertEquals( $data, VAA_API::get_request( 'view_admin_as_nonce', 'view_admin_as_post' ) );
		$this->assertEquals( $data, VAA_API::get_request( 'view_admin_as_nonce', 'view_admin_as_get', 'get' ) );

		// @todo More types of request data
	}

	/**
	 * Test method:
	 * @see VAA_API::get_array_data()
	 */
	function test_get_array_data() {

		$arr = array( 'no_key', 'key' => 'test', 'key2' => true, 'key3' => array( 'yay' ) );

		$this->assertEquals( 'no_array', VAA_API::get_array_data( 'no_array' ) );
		$this->assertNull( VAA_API::get_array_data( 'no_array', 'test' ) );

		$this->assertEquals( $arr, VAA_API::get_array_data( $arr ) );
		$this->assertEquals( 'test', VAA_API::get_array_data( $arr, 'key' ) );
		$this->assertEquals( true, VAA_API::get_array_data( $arr, 'key2' ) );
		$this->assertEquals( array( 'yay' ), VAA_API::get_array_data( $arr, 'key3' ) );
		$this->assertEquals( 'no_key', VAA_API::get_array_data( $arr, 0 ) );

		$this->assertNotEquals( 'no_key', VAA_API::get_array_data( $arr, true ) );

		$this->assertNull( VAA_API::get_array_data( $arr, 'should_not_exist' ) );
		$this->assertNull( VAA_API::get_array_data( $arr, true ) );

		// @since  1.7.5  Multiple keys.
		$this->assertEquals( array( 'key' => 'test', 'key2' => true ), VAA_API::get_array_data( $arr, array( 'key', 'key2' ) ) );
		$this->assertEquals( array( 'key2' => true ), VAA_API::get_array_data( $arr, array( 'should_not_exist', 'key2' ) ) );
		// Empty array if keys not found.
		$this->assertEquals( array(), VAA_API::get_array_data( $arr, array( 'should_not_exist' ) ) );
		// Null if required keys not found.
		$this->assertEquals( null, VAA_API::get_array_data( $arr, array( 'should_not_exist', 'key2' ), true ) );

		try {
			// $arr contains non-key values so should trigger a PHP error.
			$this->assertNull( VAA_API::get_array_data( $arr, $arr ) );

			// The above didn't cause an error :(
			$this->assertTrue( false );
		} catch ( Exception $e ) {
			// Above caused an error!
			$this->assertTrue( true );
		}

	}

	/**
	 * Test method:
	 * @see VAA_API::set_array_data()
	 */
	function test_set_array_data() {

		// No is_callable check needed.
		remove_action( 'doing_it_wrong_run', array( $this, 'doing_it_wrong_run' ) );

		$arr = array( 'key' => 'test' );

		$this->assertEquals( $arr, VAA_API::set_array_data( $arr, array( 'key' => 'test' ) ) );
		$this->assertEquals( $arr, VAA_API::set_array_data( $arr, 'test', 'key' ) );
		$this->assertNotEquals( $arr, VAA_API::set_array_data( $arr, 'test2', 'key' ) ); // Change value
		$this->assertNotEquals( $arr, VAA_API::set_array_data( $arr, 'test2', 'key', true ) ); // Append key

		$this->assertEquals( array( 'test' ), VAA_API::set_array_data( array(), array( 'test' ) ) );
		$this->assertEquals( array( 'test' ), VAA_API::set_array_data( array(), 'test', 0, true ) );
		$this->assertEquals( array( 'key' => 'test' ), VAA_API::set_array_data( array(), 'test', 'key', true ) );

		// Trigger _doing_it_wrong().
		$this->assertEquals( $arr, VAA_API::set_array_data( $arr, 'test', 'test2' ) ); // No changes (no append)

		if ( is_callable( array( $this, 'doing_it_wrong_run' ) ) ) {
			add_action( 'doing_it_wrong_run', array( $this, 'doing_it_wrong_run' ) );
		}

	}

	/**
	 * Test method:
	 * @see VAA_API::array_equal()
	 */
	function test_array_equal() {

		$arr1 = array( 'key' => 'test' );
		$arr2 = array( 'key' => 'test' );

		$this->assertTrue( VAA_API::array_equal( $arr1, $arr2 ) );

		$arr2 = array( 'key' => 1 );
		$this->assertFalse( VAA_API::array_equal( $arr1, $arr2 ) );

		$arr2 = array( 'key' => true );
		$this->assertFalse( VAA_API::array_equal( $arr1, $arr2 ) );

		$arr1 = array( 'key' => 'test', 'key2' => 1 );
		$arr2 = array( 'key' => 'test', 'key2' => 1 );

		$this->assertTrue( VAA_API::array_equal( $arr1, $arr2 ) );

		// array_diff_assoc converts all types to strings before comparison.
		$arr2 = array( 'key' => 'test', 'key2' => true );
		$this->assertTrue( VAA_API::array_equal( $arr1, $arr2 ) );

		$arr2 = array( 'key' => 'test', 'key_key' => true );
		$this->assertFalse( VAA_API::array_equal( $arr1, $arr2 ) );

		$arr2 = array( 'key' => 'test', 1 => true );
		$this->assertFalse( VAA_API::array_equal( $arr1, $arr2 ) );

		$arr2 = array( 'test', 1 );
		$this->assertFalse( VAA_API::array_equal( $arr1, $arr2 ) );

		// Recursive arrays.
		$arr1 = array( 'key' => array(), 'key2' => 1 );
		$arr2 = array( 'key' => array(), 'key2' => 1 );
		$this->assertTrue( VAA_API::array_equal( $arr1, $arr2 ) );

		$arr1 = array( 'key' => array( 'test' => true, 'yep' => true ), 'key2' => 1 );
		$arr2 = array( 'key' => array( 'test' => true, 'yep' => true ), 'key2' => 1 );
		$this->assertTrue( VAA_API::array_equal( $arr1, $arr2 ) );

		$arr1 = array( 'key' => array( 'test', 'test2' ), 'key2' => 1 );
		$arr2 = array( 'key' => array( 'test' ), 'key2' => 1 );
		$this->assertFalse( VAA_API::array_equal( $arr1, $arr2 ) );

		$arr1 = array( 'key' => array( 'test' ), 'key2' => 1 );
		$arr2 = array( 'key' => array( 'test', 'test2' ), 'key2' => 1 );
		$this->assertFalse( VAA_API::array_equal( $arr1, $arr2 ) );

		$arr1 = array( 'key' => array( 'test' => true, 'yep' => true ), 'key2' => 1 );
		$arr2 = array( 'key' => array( 'test' => true, 'nope' => true ), 'key2' => 1 );
		$this->assertFalse( VAA_API::array_equal( $arr1, $arr2 ) );

		$arr1 = array( 'key' => array( 'test' => true, 'yep' => true ), 'key2' => 1 );
		$arr2 = array( 'key' => array( 'test' => true, 'nope' => true ), 'key2' => 1 );
		$this->assertFalse( VAA_API::array_equal( $arr1, $arr2 ) );

		// Recursive arrays strict comparison.
		$arr1 = array( 'key' => array( 'test', 1 ), 'key2' => 1 );
		$arr2 = array( 'key' => array( 'test', '1' ), 'key2' => 1 );
		$this->assertFalse( VAA_API::array_equal( $arr1, $arr2, true, true ) );

		$arr1 = array( 'key' => array( 'test', '1' ), 'key2' => 1 );
		$arr2 = array( 'key' => array( 'test', 1 ), 'key2' => 1 );
		$this->assertFalse( VAA_API::array_equal( $arr1, $arr2, true, true ) );

	}

	/**
	 * Test method:
	 * @see VAA_API::set_array_data()
	 */
	function test_array_has() {

		$arr = array( 'key' => 'test' );

		$this->assertTrue( VAA_API::array_has( $arr, 'key' ) );
		$this->assertTrue( VAA_API::array_has( $arr, 'key', array( 'validation' => 'is_string' ) ) );
		$this->assertTrue( VAA_API::array_has( $arr, 'key', array( 'compare' => 'test' ) ) );

		$this->assertTrue( VAA_API::array_has( $arr, 'key' ) );
		$this->assertFalse( VAA_API::array_has( $arr, 'key', array( 'validation' => 'is_bool' ) ) );
		$this->assertFalse( VAA_API::array_has( $arr, 'key', array( 'compare', 'not the same' ) ) );
		$this->assertFalse( VAA_API::array_has( $arr, 'key', array( 'compare', 'test' ) ) );

		// Custom Callbacks
		$this->assertTrue( VAA_API::array_has( $arr, 'key', array( 'validation' => array( $this, 'vaa_callback_array_has' ) ) ) );
		$arr = array( 'key' => false );
		$this->assertFalse( VAA_API::array_has( $arr, 'key', array( 'validation' => array( $this, 'vaa_callback_array_has' ) ) ) );

		// Invalid callback
		$this->assertFalse( VAA_API::array_has( $arr, 'key', array( 'validation' => array( $this, 'vaa_invalid_callback_array_has' ) ) ) );
	}

	/**
	 * Validation callback helper for test_array_has()
	 * @see test_array_has()
	 * @param $val
	 * @return bool
	 */
	function vaa_callback_array_has( $val ) {
		return (bool) $val;
	}
}
