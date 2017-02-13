<?php
/**
 * Add caps actions.
 *
 * @var  WP_Admin_Bar  $admin_bar  The toolbar object.
 * @var  string        $root       The current root item.
 * @var  string        $main_root  The main VAA root item.
 */

$admin_bar->add_node(
	array(
		'id'     => $root . '-filtercaps',
		'parent' => $root . '-select',
		'title'  => VAA_View_Admin_As_Admin_Bar::do_input(
			array(
				'name'        => $root . '-filtercaps',
				'placeholder' => esc_attr__( 'Filter', VIEW_ADMIN_AS_DOMAIN ),
			)
		),
		'href'   => false,
		'meta'   => array(
			'class' => 'ab-vaa-filter filter-caps vaa-column-one-half vaa-column-first',
		),
	)
);

$role_select_options = array(
	array(
		'value' => 'default',
		'label' => __( 'Default', VIEW_ADMIN_AS_DOMAIN ),
	),
);
if ( $this->store->get_view() ) {
	$data_caps = wp_json_encode( $this->store->get_selectedCaps() );
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
foreach ( $this->store->get_roles() as $role_key => $role ) {
	$data_caps = wp_json_encode( $role->capabilities );
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
		'parent' => $root . '-select',
		'title'  => VAA_View_Admin_As_Admin_Bar::do_select(
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

$admin_bar->add_node(
	array(
		'id'     => $root . '-bulkselectcaps',
		'parent' => $root . '-select',
		'title'  => VAA_View_Admin_As_Admin_Bar::do_button(
			array(
				'name'    => 'select-all-caps',
				'label'   => __( 'Select', VIEW_ADMIN_AS_DOMAIN ),
				'classes' => 'button-secondary',
			)
		) . ' ' . VAA_View_Admin_As_Admin_Bar::do_button(
			array(
				'name'    => 'deselect-all-caps',
				'label'   => __( 'Deselect', VIEW_ADMIN_AS_DOMAIN ),
				'classes' => 'button-secondary',
			)
		),
		'href'   => false,
		'meta'   => array(
			'class' => 'vaa-button-container vaa-clear-float',
		),
	)
);