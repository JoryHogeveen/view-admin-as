<?php
/**
 * View Admin As - Unit tests case
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

class VAA_UnitTestCase extends WP_UnitTestCase
{
	protected function setUp(): void {
		parent::setUp();
		wp_set_current_user( 1 );
		VAA_UnitTest_Factory::vaa_reinit();
	}
}
