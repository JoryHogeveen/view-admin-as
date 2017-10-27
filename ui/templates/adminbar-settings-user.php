<?php
/**
 * Add user setting items.
 *
 * @since    1.7.2
 * @version  1.7.4
 *
 * @var  \WP_Admin_Bar  $admin_bar  The toolbar object.
 * @var  string         $root       The current root item.
 * @var  string         $main_root  The main VAA root item.
 *
 * Settings order:
 * - admin_menu_location
 * - view_mode
 * - disable_super_admin
 * - hide_front
 * - freeze_locale
 * - force_group_users
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

if ( isset( $this ) &&
	 isset( $this->store ) &&
	 isset( $admin_bar ) && $admin_bar instanceof WP_Admin_Bar &&
	 isset( $root )
) {

	/**
	 * admin_menu_location setting.
	 *
	 * @since   1.5
	 */
	$admin_bar->add_node(
		array(
			'id'     => $root . '-admin-menu-location',
			'parent' => $root,
			'title'  => VAA_View_Admin_As_Form::do_select(
				array(
					'name'        => $root . '-admin-menu-location',
					'value'       => $this->store->get_userSettings( 'admin_menu_location' ),
					'label'       => __( 'Location', VIEW_ADMIN_AS_DOMAIN ) . ': &nbsp; ',
					'description' => __( 'Change the location of this menu node', VIEW_ADMIN_AS_DOMAIN ),
					'help'        => true,
					'values'      => array(
						array(
							'compare' => 'top-secondary',
							'label'   => __( 'Default', VIEW_ADMIN_AS_DOMAIN ),
						),
						array(
							'compare' => 'my-account',
							'label'   => __( 'My account', VIEW_ADMIN_AS_DOMAIN ),
						),
					),
					'auto_js' => array(
						'setting' => 'user_setting',
						'key'     => 'admin_menu_location',
						'refresh' => true,
					),
					'auto_showhide' => true,
				)
			),
			'href'   => false,
			'meta'   => array(
				'class' => 'auto-height',
			),
		)
	);

	/**
	 * view_mode setting.
	 *
	 * @since   1.5
	 */
	$admin_bar->add_node(
		array(
			'id'     => $root . '-view-mode',
			'parent' => $root,
			'title'  => VAA_View_Admin_As_Form::do_radio(
				array(
					'name'   => $root . '-view-mode',
					'value'  => $this->store->get_userSettings( 'view_mode' ),
					'values' => array(
						array(
							'compare'     => 'browse',
							'label'       => __( 'Browse mode', VIEW_ADMIN_AS_DOMAIN ),
							'description' => __( 'Store view and use WordPress with this view', VIEW_ADMIN_AS_DOMAIN ),
							'help'        => true,
						),
						array(
							'compare'     => 'single',
							'label'       => __( 'Single switch mode', VIEW_ADMIN_AS_DOMAIN ),
							'description' => __( 'Choose view on every pageload. This setting doesn\'t store views', VIEW_ADMIN_AS_DOMAIN ),
							'help'        => true,
						),
					),
					'auto_js' => array(
						'setting' => 'user_setting',
						'key'     => 'view_mode',
						'refresh' => false,
					),
					'auto_showhide' => true,
				)
			),
			'href'   => false,
			'meta'   => array(
				'class' => 'auto-height',
			),
		)
	);

	/**
	 * Disable super admin checks while switched.
	 *
	 * @since   1.7.3
	 */
	if ( VAA_API::is_super_admin() ) {
		$admin_bar->add_node(
			array(
				'id'     => $root . '-disable-super-admin',
				'parent' => $root,
				'title'  => VAA_View_Admin_As_Form::do_checkbox(
					array(
						'name'        => $root . '-disable-super-admin',
						'value'       => $this->store->get_userSettings( 'disable_super_admin' ),
						'compare'     => true,
						'label'       => __( 'Disable super admin', VIEW_ADMIN_AS_DOMAIN ),
						'description' => __( 'Disable super admin status while switched to another view', VIEW_ADMIN_AS_DOMAIN ),
						'help'        => true,
						'auto_js' => array(
							'setting' => 'user_setting',
							'key'     => 'disable_super_admin',
							'refresh' => ( $this->store->get_view() ) ? true : false,
						),
						'auto_showhide' => true,
					)
				),
				'href'   => false,
				'meta'   => array(
					'class' => 'auto-height',
				),
			)
		);
	}

	/**
	 * hide_front setting.
	 *
	 * @since   1.6
	 */
	$admin_bar->add_node(
		array(
			'id'     => $root . '-hide-front',
			'parent' => $root,
			'title'  => VAA_View_Admin_As_Form::do_checkbox(
				array(
					'name'        => $root . '-hide-front',
					'value'       => $this->store->get_userSettings( 'hide_front' ),
					'compare'     => true,
					'label'       => __( 'Hide on frontend', VIEW_ADMIN_AS_DOMAIN ),
					'description' => __( 'Hide on frontend when no view is selected and the admin bar is not shown', VIEW_ADMIN_AS_DOMAIN ),
					'help'        => true,
					'auto_js' => array(
						'setting' => 'user_setting',
						'key'     => 'hide_front',
						'refresh' => false,
					),
					'auto_showhide' => true,
				)
			),
			'href'   => false,
			'meta'   => array(
				'class' => 'auto-height',
			),
		)
	);

	/**
	 * freeze_locale setting.
	 * Force own locale on view, WP 4.7+ only.
	 *
	 * @see     https://github.com/JoryHogeveen/view-admin-as/issues/21
	 * @since   1.6.1
	 */
	if ( VAA_API::validate_wp_version( '4.7' ) ) {
		$admin_bar->add_node(
			array(
				'id'     => $root . '-freeze-locale',
				'parent' => $root,
				'title'  => VAA_View_Admin_As_Form::do_checkbox(
					array(
						'name'        => $root . '-freeze-locale',
						'value'       => $this->store->get_userSettings( 'freeze_locale' ),
						'compare'     => true,
						'label'       => __( 'Freeze locale', VIEW_ADMIN_AS_DOMAIN ),
						'description' => __( 'Force your own locale setting to the current view', VIEW_ADMIN_AS_DOMAIN ),
						'help'        => true,
						'auto_js' => array(
							'setting' => 'user_setting',
							'key'     => 'freeze_locale',
							'refresh' => ( $this->store->get_view( 'user' ) ) ? true : false,
						),
						'auto_showhide' => true,
					)
				),
				'href'   => false,
				'meta'   => array(
					'class' => 'auto-height',
				),
			)
		);
	}

	/**
	 * force_group_users setting.
	 *
	 * @since   1.5.2
	 */
	if ( empty( $this->groupUserRoles ) ||
	     15 >= ( count( $this->store->get_users() ) + count( $this->store->get_roles() ) )
	) {
		$admin_bar->add_node(
			array(
				'id'     => $root . '-force-group-users',
				'parent' => $root,
				'title'  => VAA_View_Admin_As_Form::do_checkbox(
					array(
						'name'        => $root . '-force-group-users',
						'value'       => $this->store->get_userSettings( 'force_group_users' ),
						'compare'     => true,
						'label'       => __( 'Group users', VIEW_ADMIN_AS_DOMAIN ),
						'description' => __( 'Group users under their assigned roles', VIEW_ADMIN_AS_DOMAIN ),
						'help'        => true,
						'auto_js' => array(
							'setting' => 'user_setting',
							'key'     => 'force_group_users',
							'refresh' => true,
						),
						'auto_showhide' => true,
					)
				),
				'href'   => false,
				'meta'   => array(
					'class'    => 'auto-height',
				),
			)
		);
	}

} // End if().
