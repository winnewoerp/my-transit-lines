<?php
/**
 * My Transit Lines
 * Custom post types module
 *
 * @package My Transit Lines
 */
 
/* created by Johannes Bouchain, 2014-09-07 */

/* ### STILL TO DO ###
 * maybe make a nice function for creating new custom posttypes?
 */

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
		/*array(
			'name' => 'mtlproposal2',
			'slug' => 'proposal2',
			'singular' => __('External Proposal','my-transit-lines'),
			'plural' => __('External Proposals','my-transit-lines'),
			'menu' => __('Ext. Proposals','my-transit-lines'),
		),
		array(
			'name' => 'mtlconcept',
			'slug' => 'concept',
			'singular' => __('Concept','my-transit-lines'),
			'plural' => __('Concepts','my-transit-lines')
		),
		array(
			'name' => 'mtlconcept2',
			'slug' => 'concept2',
			'singular' => __('External Concept','my-transit-lines'),
			'plural' => __('External Concepts','my-transit-lines'),
			'menu' => __('Ext. Concepts','my-transit-lines'),
		)*/		
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


?>