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
			'menu_position'      => 6,
			'taxonomies' 		 => array('category','post_tag'),
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields', 'revisions'  )
		);

		register_post_type( $mtl_posttype['name'], $args );
		add_post_type_support( $mtl_posttype['name'],  array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields', 'revisions' ) );
	}
}

add_action( 'admin_menu', 'mtl_proposal_add_moderate_count' );
function mtl_proposal_add_moderate_count() {
	global $menu;

	foreach ($menu as $key => $value) {
		if (($value[0] ?? '') === __('Proposals', 'my-transit-lines')) {
			$awaiting_mod      = new WP_Query([
				'post_type' => 'mtlproposal',
				'post_status' => 'pending',
			]);
			$awaiting_mod      = $awaiting_mod->post_count;
			$awaiting_mod_i18n = number_format_i18n( $awaiting_mod );
			/* translators: %s: Number of proposals. */
			$awaiting_mod_text = sprintf( _n( '%s Proposal in moderation', '%s Proposals in moderation', $awaiting_mod, 'my-transit-lines' ), $awaiting_mod_i18n );
			
			$menu[$key][0] .= '<span class="awaiting-mod count-' . absint( $awaiting_mod ) . '"><span class="pending-count" aria-hidden="true">' . $awaiting_mod_i18n . '</span><span class="proposals-in-moderation-text screen-reader-text">' . $awaiting_mod_text . '</span></span>';
		}
	}
}

add_action( 'admin_bar_menu', 'wp_admin_bar_proposals_menu', 65 );
function wp_admin_bar_proposals_menu( $wp_admin_bar ) {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	$awaiting_mod      = new WP_Query([
		'post_type' => 'mtlproposal',
		'post_status' => 'pending',
	]);
	$awaiting_mod      = $awaiting_mod->post_count;
	$awaiting_mod_i18n = number_format_i18n( $awaiting_mod );
	/* translators: %s: Number of proposals. */
	$awaiting_mod_text = sprintf( _n( '%s Proposal in moderation', '%s Proposals in moderation', $awaiting_mod, 'my-transit-lines' ), $awaiting_mod_i18n );

	$icon   = '<span class="ab-icon" aria-hidden="true"></span>';
	$title  = '<span class="ab-label awaiting-mod pending-count count-' . $awaiting_mod . '" aria-hidden="true">' . $awaiting_mod_i18n . '</span>';
	$title .= '<span class="screen-reader-text comments-in-moderation-text">' . $awaiting_mod_text . '</span>';

	$wp_admin_bar->add_node(
		array(
			'id'    => 'proposals',
			'title' => $icon . $title,
			'href'  => admin_url( 'edit.php?post_type=mtlproposal' ),
		)
	);
}

add_filter( 'post_class', 'mtl_add_proposal_post_class', 10, 3);
function mtl_add_proposal_post_class($classes, $css_classes, $post_id) {
	if ( get_post_type( $post_id ) === 'mtlproposal' ) {
		if ( get_post_status( $post_id ) === 'pending') {
			$classes[] = 'unapproved';
		}
	}
	
	return $classes;
}

add_filter( 'admin_title', 'mtl_admin_title_proposals', 10, 2 );
function mtl_admin_title_proposals($admin_title, $title) {
	$awaiting_mod      = new WP_Query([
		'post_type' => 'mtlproposal',
		'post_status' => 'pending',
	]);
	$awaiting_mod      = $awaiting_mod->post_count;
	$awaiting_mod_i18n = number_format_i18n( $awaiting_mod );
	
	if (is_admin() && $awaiting_mod > 0 && str_ends_with( parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), 'edit.php' ) && isset($_GET['post_type']) && $_GET['post_type'] === 'mtlproposal') {
		$title_parts = explode('&lsaquo;', $admin_title, 2);
		$title_parts[0] .= '(' . $awaiting_mod_i18n . ') ';
		$admin_title = implode('&lsaquo;', $title_parts);
	}

	return $admin_title;
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
