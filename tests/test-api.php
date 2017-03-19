<?php
/**
 * View Admin As - Unit tests
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

class VAA_API_UnitTest extends WP_UnitTestCase {

	/**
	 * Test methods:
	 * - starts_with( $haystack, $needle )
	 * - ends_with( $haystack, $needle )
	 */
	function test_end_starts_with() {
		$this->assertTrue(  VAA_API::starts_with( 'test_string', 'te' ) );
		$this->assertTrue(  VAA_API::starts_with( 'test_string', 'test_s' ) );
		$this->assertFalse( VAA_API::starts_with( 'test_string', 'est_s' ) );
		$this->assertFalse( VAA_API::starts_with( 'test_string', 'string' ) );
		$this->assertFalse( VAA_API::ends_with( 'test_string', 'te' ) );
		$this->assertFalse( VAA_API::ends_with( 'test_string', 'test_s' ) );
		$this->assertFalse( VAA_API::ends_with( 'test_string', 'est_s' ) );
		$this->assertTrue(  VAA_API::ends_with( 'test_string', '_string' ) );
		$this->assertTrue(  VAA_API::ends_with( 'test_string', 'ing' ) );
	}

	/**
	 * Test methods:
	 * - is_request( $key = null, $type = 'post' )
	 * - get_request( $nonce, $key = null, $type = 'post' )
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
	 * - set_array_data( $array, $key = null )
	 */
	function test_get_array_data() {

		$arr = array( 'no_key', 'key' => 'test', 'key2' => true, 'key3' => array( 'yay' ) );

		$this->assertEquals( $arr, VAA_API::get_array_data( $arr ) );
		$this->assertEquals( 'test', VAA_API::get_array_data( $arr, 'key' ) );
		$this->assertEquals( true, VAA_API::get_array_data( $arr, 'key2' ) );
		$this->assertEquals( array( 'yay' ), VAA_API::get_array_data( $arr, 'key3' ) );
		$this->assertEquals( 'no_key', VAA_API::get_array_data( $arr, 0 ) );

		$this->assertNotEquals( 'no_key', VAA_API::get_array_data( $arr, true ) );

		$this->assertNull( VAA_API::get_array_data( $arr, 'should_not_exist' ) );
		$this->assertNull( VAA_API::get_array_data( $arr, true ) );
		//$this->assertNull( VAA_API::get_array_data( $arr, array() ) );
		//$this->assertNull( VAA_API::get_array_data( $arr, $arr ) );

	}

	/**
	 * Test method:
	 * - set_array_data( $array, $var, $key = null, $append = false )
	 */
	function test_set_array_data() {

		$arr = array( 'key' => 'test' );

		$this->assertEquals( $arr, VAA_API::set_array_data( $arr, array( 'key' => 'test' ) ) );
		$this->assertEquals( $arr, VAA_API::set_array_data( $arr, 'test', 'key' ) );
		$this->assertNotEquals( $arr, VAA_API::set_array_data( $arr, 'test2', 'key' ) ); // Change value
		//$this->assertEquals( $arr, VAA_API::set_array_data( $arr, 'test', 'test2' ) ); // No changes (no append)
		$this->assertNotEquals( $arr, VAA_API::set_array_data( $arr, 'test2', 'key', true ) ); // Append key

		$this->assertEquals( array( 'test' ), VAA_API::set_array_data( array(), array( 'test' ) ) );
		$this->assertEquals( array( 'test' ), VAA_API::set_array_data( array(), 'test', 0, true ) );
		$this->assertEquals( array( 'key' => 'test' ), VAA_API::set_array_data( array(), 'test', 'key', true ) );

	}

	/**
	 * Test method:
	 * - array_equal( $array1, $array2 )
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

		// Recursive arrays are not supported.
		$arr1 = array( 'key' => array(), 'key2' => 1 );
		$arr2 = array( 'key' => array(), 'key2' => 1 );
		$this->assertFalse( VAA_API::array_equal( $arr1, $arr2 ) );

	}

	/**
	 * Test method:
	 * - set_array_data( $array, $var, $key = null, $append = false )
	 */
	function test_array_has() {

		$arr = array( 'key' => 'test' );

		$this->assertTrue( VAA_API::array_has( $arr, 'key' ) );
		$this->assertTrue( VAA_API::array_has( $arr, 'key', array( 'validation' => 'is_string' ) ) );
		$this->assertFalse( VAA_API::array_has( $arr, 'key', array( 'validation' => 'is_bool' ) ) );
		$this->assertFalse( VAA_API::array_has( $arr, 'key', array( 'compare', 'not the same' ) ) );
		$this->assertTrue( VAA_API::array_has( $arr, 'key', array( 'compare' => 'test' ) ) );

		// @todo More enhanced validation checks
	}
}
