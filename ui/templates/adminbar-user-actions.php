<?php
/**
 * Add user actions.
 *
 * @since    1.8.0
 * @version  1.8.2
 *
 * @var  \VAA_View_Admin_As_Users  $this
 * @var  \WP_Admin_Bar             $admin_bar  The toolbar object.
 * @var  string                    $root       The current root item.
 * @var  string                    $main_root  The main VAA root item.
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

if ( isset( $admin_bar ) && $admin_bar instanceof WP_Admin_Bar && isset( $root ) ) {

	if ( ! isset( $main_root ) ) {
		$main_root = $root;
	}
	if ( ! isset( $parent ) ) {
		$parent = $root;
	}

	if ( ! isset( $title_submenu ) ) {
		$title_submenu = false;
	}

	if ( $title_submenu || $this->ajax_search() || $this->group_user_roles() ) {

		$title = '';
		if ( $this->group_user_roles() ) {
			$title = VAA_View_Admin_As_Form::do_description( __( 'Users are grouped under their roles', VIEW_ADMIN_AS_DOMAIN ) );
		}
		if ( $this->ajax_search() ) {
			$title .= VAA_View_Admin_As_Form::do_select( array(
				'name'   => $root . '-search-by',
				'values' => $this->search_users_by_select_values(),
				'class'  => 'vaa-wide',
			) );
		}
		$title .= VAA_View_Admin_As_Form::do_input( array(
			'name'        => $root . '-search',
			'placeholder' => __( 'Search', VIEW_ADMIN_AS_DOMAIN ),
			'class'       => 'vaa-wide',
		) );

		$admin_bar->add_node( array(
			'id'     => $root . '-search',
			'parent' => $root,
			'title'  => $title,
			'href'   => false,
			'meta'   => array(
				'class' => 'ab-vaa-search search-users' . ( ( $this->ajax_search() ) ? ' search-ajax' : '' ),
				'html'  => '<ul id="vaa-searchuser-results" class="ab-sub-secondary vaa-auto-max-height ab-submenu ab-vaa-results"></ul>',
			),
		) );
	}

} else {
	_doing_it_wrong( __FILE__, esc_html__( 'No toolbar resources found.', VIEW_ADMIN_AS_DOMAIN ), '1.7' );
} // End if().
