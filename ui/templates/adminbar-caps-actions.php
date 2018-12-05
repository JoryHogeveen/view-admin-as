<?php
/**
 * Add caps actions.
 *
 * @since    1.7.0
 * @version  1.7.4
 *
 * @var  \VAA_View_Admin_As_Caps  $this
 * @var  \WP_Admin_Bar            $admin_bar  The toolbar object.
 * @var  string                   $root       The current root item.
 * @var  string                   $main_root  The main VAA root item.
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

	// Text filter
	$admin_bar->add_node(
		array(
			'id'     => $root . '-filtercaps',
			'parent' => $parent,
			'title'  => VAA_View_Admin_As_Form::do_input(
				array(
					'name'        => $root . '-filtercaps',
					'placeholder' => esc_attr__( 'Filter', VIEW_ADMIN_AS_DOMAIN ),
				)
			),
			'href'   => false,
			'meta'   => array(
				'class' => 'ab-vaa-input ab-vaa-filter filter-caps vaa-column-one-half vaa-column-first',
			),
		)
	);

	// Select filter
	$role_select_options = array(
		array(
			'value' => 'default',
			'label' => __( 'Default', VIEW_ADMIN_AS_DOMAIN ),
		),
	);
	// View filter
	if ( $this->store->get_view() ) {
		$data_caps             = wp_json_encode( $this->store->get_selectedCaps() );
		$role_select_options[] = array(
			'compare' => 'vaa',
			'label'   => '= ' . __( 'Current view', VIEW_ADMIN_AS_DOMAIN ),
			'attr'    => array(
				'data-caps' => $data_caps,
			),
		);
		$role_select_options[] = array(
			'compare' => 'reversed-vaa',
			'label'   => '≠ ' . __( 'Current view', VIEW_ADMIN_AS_DOMAIN ),
			'attr'    => array(
				'data-caps'    => $data_caps,
				'data-reverse' => '1',
			),
		);
	}
	// Role filters
	foreach ( $this->store->get_roles() as $role_key => $role ) {
		$data_caps             = wp_json_encode( $role->capabilities );
		$role_select_options[] = array(
			'compare' => esc_attr( $role_key ),
			'label'   => '= ' . $this->store->get_rolenames( $role_key ),
			'attr'    => array(
				'data-caps' => $data_caps,
			),
		);
		$role_select_options[] = array(
			'compare' => 'reversed-' . esc_attr( $role_key ),
			'label'   => '≠ ' . $this->store->get_rolenames( $role_key ),
			'attr'    => array(
				'data-caps'    => $data_caps,
				'data-reverse' => '1',
			),
		);
	}
	$admin_bar->add_node(
		array(
			'id'     => $root . '-selectrolecaps',
			'parent' => $parent,
			'title'  => VAA_View_Admin_As_Form::do_select(
				array(
					'name'   => $root . '-selectrolecaps',
					'values' => $role_select_options,
				)
			),
			'href'   => false,
			'meta'   => array(
				'class' => 'ab-vaa-select select-role-caps vaa-column-one-half vaa-column-last',
				'html'  => '',
			),
		)
	);

	// Select/deselect
	$admin_bar->add_node(
		array(
			'id'     => $root . '-bulkselectcaps',
			'parent' => $parent,
			'title'  => VAA_View_Admin_As_Form::do_button(
				array(
					'name'    => 'select-all-caps',
					'label'   => __( 'Select', VIEW_ADMIN_AS_DOMAIN ),
					'classes' => 'button-secondary',
				)
			) . ' ' . VAA_View_Admin_As_Form::do_button(
				array(
					'name'    => 'deselect-all-caps',
					'label'   => __( 'Deselect', VIEW_ADMIN_AS_DOMAIN ),
					'classes' => 'button-secondary',
				)
			),
			'href'   => false,
			'meta'   => array(
				'class' => 'ab-vaa-input vaa-button-container vaa-clear-float',
			),
		)
	);

} else {
	_doing_it_wrong( __FILE__, esc_html__( 'No toolbar resources found.', VIEW_ADMIN_AS_DOMAIN ), '1.7' );
} // End if().
