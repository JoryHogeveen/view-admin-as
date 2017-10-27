<?php
/**
 * Add role items.
 *
 * @since    1.7
 * @version  1.7.4
 *
 * @var  \WP_Admin_Bar  $admin_bar  The toolbar object.
 * @var  string         $root       The current root item.
 * @var  string         $main_root  The main VAA root item.
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

if ( isset( $admin_bar ) && $admin_bar instanceof WP_Admin_Bar && isset( $root ) ) {

	if ( ! isset( $main_root ) ) {
		$main_root = $root;
	}

	foreach ( $this->store->get_roles() as $role_key => $role ) {
		$parent = $root;
		$href   = VAA_API::get_vaa_action_link( array( 'role' => $role_key ), $this->store->get_nonce( true ) );
		$class  = 'vaa-role-item';
		$title  = VAA_View_Admin_As_Form::do_view_title( $this->store->get_rolenames( $role_key ), 'role', $role_key );
		// Check if the users need to be grouped under their roles.
		if ( true === $this->groupUserRoles ) {
			// make sure items are aligned properly when some roles don't have users.
			$class .= ' vaa-menupop';
			// Check if the current view is a user with this role.
			if ( $this->store->get_view( 'user' ) &&
			     in_array( $role_key, $this->store->get_selectedUser()->roles, true )
			) {
				$class .= ' current-parent';
			}
			// If there are users with this role, add a counter.
			$user_count = 0;
			foreach ( $this->store->get_users() as $user ) {
				if ( in_array( $role_key, $user->roles, true ) ) {
					$user_count ++;
				}
			}
			if ( 0 < $user_count ) {
				$title = $title . ' <span class="user-count ab-italic">(' . $user_count . ')</span>';
			}
		}
		// Check if this role is the current view.
		if ( VAA_API::is_current_view( $role_key, 'role' ) ) {
			$class .= ' current';
			if ( 1 === count( $this->store->get_view() ) ) {
				$href = false;
			}
		}
		$admin_bar->add_node(
			array(
				'id' => $root . '-role-' . $role_key,
				'parent' => $parent,
				'title' => $title,
				'href' => $href,
				'meta' => array(
					// Translators: %s stands for the translated role name.
					'title' => sprintf( __( 'View as %s', VIEW_ADMIN_AS_DOMAIN ), $this->store->get_rolenames( $role_key ) ),
					'class' => $class,
					'rel'   => $role_key,
				),
			)
		);
	} // End foreach().

} else {
	_doing_it_wrong( __FILE__, esc_html__( 'No toolbar resources found.', VIEW_ADMIN_AS_DOMAIN ), '1.7' );
} // End if().
