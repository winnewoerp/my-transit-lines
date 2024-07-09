<?php
/**
 * My Transit Lines
 * Custom post types module
 *
 * @package My Transit Lines
 */
 
/* created by Johannes Bouchain, 2014-09-07 */

/**
 * create mtl-proposal post type for main entries
 */
add_action( 'init', 'mtl_posttype_init' );
function mtl_posttype_init() {
	$mtl_posttypes = array(
		array(
			'name' => 'mtlproposal',
			'slug' => 'proposal',
			'singular' => __('Proposal','my-transit-lines'),
			'plural' => __('Proposals','my-transit-lines')
		),
	);
	foreach($mtl_posttypes as $mtl_posttype) {
		$menu_name = (isset($mtl_posttype['menu']) ? $mtl_posttype['menu'] : $mtl_posttype['plural']);
		$labels = array(
			'name'               => _x( $mtl_posttype['plural'], 'post type general name', 'my-transit-lines' ),
			'singular_name'      => _x( $mtl_posttype['singular'], 'post type singular name', 'my-transit-lines' ),
			'menu_name'          => _x( $menu_name, 'admin menu', 'my-transit-lines' ),
			'name_admin_bar'     => _x( $mtl_posttype['singular'], 'add new on admin bar', 'my-transit-lines' ),
			'add_new'            => _x( 'Add New', 'mtlproposal', 'my-transit-lines' ),
			'add_new_item'       => sprintf(__( 'Add New %s', 'my-transit-lines' ),$mtl_posttype['singular']),
			'new_item'           => sprintf(__( 'New %s', 'my-transit-lines' ),$mtl_posttype['singular']),
			'edit_item'          => sprintf(__( 'Edit %s', 'my-transit-lines' ),$mtl_posttype['singular']),
			'view_item'          => sprintf(__( 'View %s', 'my-transit-lines' ),$mtl_posttype['singular']),
			'all_items'          => sprintf(__( 'All %s', 'my-transit-lines' ),$mtl_posttype['plural']),
			'search_items'       => sprintf(__( 'Search %s', 'my-transit-lines' ),$mtl_posttype['plural']),
			'parent_item_colon'  => sprintf(__( 'Parent %s:', 'my-transit-lines' ),$mtl_posttype['plural']),
			'not_found'          => sprintf(__( 'No %s found.', 'my-transit-lines' ),$mtl_posttype['plural']),
			'not_found_in_trash' => sprintf(__( 'No %s found in Trash.', 'my-transit-lines' ),$mtl_posttype['plural'])
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => $mtl_posttype['slug'] ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 5,
			'taxonomies' => array('category','post_tag'),
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields', 'revisions'  )
		);

		register_post_type( $mtl_posttype['name'], $args );
		add_post_type_support( $mtl_posttype['name'],  array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields', 'revisions' ) );
	}
}

// create a taxonomy to distinguish if 
add_action( 'init', 'create_sorting_phase_status_taxonomy', 0);
function create_sorting_phase_status_taxonomy() {
	$labels = array(
		'name' => __( 'Sorting Phase Status', 'my-transit-lines' ),
		'singular_name' => __( 'Sorting Phase Status','my-transit-lines' ),
		'search_items' =>  __( 'Search items','my-transit-lines' ),
		'all_items' => __( 'All items','my-transit-lines' ),
		'parent_item' => __( 'Parent item','my-transit-lines' ),
		'parent_item_colon' => __( 'Parent item:','my-transit-lines' ),
		'edit_item' => __( 'Edit Sorting Phase Status','my-transit-lines' ), 
		'update_item' => __( 'Update Sorting Phase Status','my-transit-lines' ),
		'add_new_item' => __( 'Add New Sorting Phase Status','my-transit-lines' ),
		'new_item_name' => __( 'New Sorting Phase Status','my-transit-lines' ),
		'menu_name' => __( 'Sorting Phase Statuses' ),
		);

	switch_to_locale(get_site_locale());
	// Now register the taxonomy
	register_taxonomy('sorting-phase-status',array('mtlproposal'), array(
		'hierarchical' => true,
		'labels' => $labels,
		'show_ui' => true,
		'show_admin_column' => true,
		'rewrite' => array('slug'),
		'default_term' => array('name' => __( 'Not Submitted','my-transit-lines'), 'slug' => 'not-submitted'),
		));
	restore_previous_locale();
}

?>