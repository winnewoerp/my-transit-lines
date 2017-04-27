<?php
/**
 * Custom template tags for this theme.
 *
 * Eventually, some of the functionality here could be replaced by core features.
 *
 * @package My Transit Lines
 */

if ( ! function_exists( 'my_transit_lines_paging_nav' ) ) :
/**
 * Display navigation to next/previous set of posts when applicable.
 */
function my_transit_lines_paging_nav() {
	// Don't print empty markup if there's only one page.
	if ( $GLOBALS['wp_query']->max_num_pages < 2 ) {
		return;
	}
	?>
	<nav class="navigation paging-navigation" role="navigation">
		<h1 class="screen-reader-text"><?php _e( 'Posts navigation', 'my-transit-lines' ); ?></h1>
		<div class="nav-links">

			<?php if ( get_next_posts_link() ) : ?>
			<div class="nav-previous"><?php next_posts_link( __( '<span class="meta-nav">&larr;</span> Older posts', 'my-transit-lines' ) ); ?></div>
			<?php endif; ?>

			<?php if ( get_previous_posts_link() ) : ?>
			<div class="nav-next"><?php previous_posts_link( __( 'Newer posts <span class="meta-nav">&rarr;</span>', 'my-transit-lines' ) ); ?></div>
			<?php endif; ?>

		</div><!-- .nav-links -->
	</nav><!-- .navigation -->
	<?php
}
endif;

if ( ! function_exists( 'my_transit_lines_post_nav' ) ) :
/**
 * Display navigation to next/previous post when applicable.
 */
function my_transit_lines_post_nav() {
	// Don't print empty markup if there's nowhere to navigate.
	$previous = ( is_attachment() ) ? get_post( get_post()->post_parent ) : get_adjacent_post( false, '', true );
	$next     = get_adjacent_post( false, '', false );

	if ( ! $next && ! $previous ) {
		return;
	}
	?>
	<nav class="navigation post-navigation" role="navigation">
		<h1 class="screen-reader-text"><?php _e( 'Post navigation', 'my-transit-lines' ); ?></h1>
		<div class="nav-links">
			<?php
				previous_post_link( '<div class="nav-previous">%link</div>', _x( '<span class="meta-nav">&larr;</span>&nbsp;%title', 'Previous post link', 'my-transit-lines' ) );
				next_post_link(     '<div class="nav-next">%link</div>',     _x( '%title&nbsp;<span class="meta-nav">&rarr;</span>', 'Next post link',     'my-transit-lines' ) );
			?>
		</div><!-- .nav-links -->
	</nav><!-- .navigation -->
	<?php
}
endif;

if ( ! function_exists( 'my_transit_lines_posted_on' ) ) :
/**
 * Prints HTML with meta information for the current post-date/time and author.
 */
function my_transit_lines_posted_on() {
	global $post;
	$time_string = get_the_date( 'd.m.Y' );
	unset($author);
	if(get_post_meta($post->ID,'author-name',true)) $author = get_post_meta($post->ID,'author-name',true);
	else $author = esc_html( get_the_author() );
	printf( __( '<span class="posted-on">Posted on %1$s</span><span class="byline"> by %2$s</span>', 'my-transit-lines' ),
		$time_string, 
		$author
		
	);
}
endif;

/**
 * Returns true if a blog has more than 1 category.
 *
 * @return bool
 */
function my_transit_lines_categorized_blog() {
	if ( false === ( $all_the_cool_cats = get_transient( 'my_transit_lines_categories' ) ) ) {
		// Create an array of all the categories that are attached to posts.
		$all_the_cool_cats = get_categories( array(
			'fields'     => 'ids',
			'hide_empty' => 1,

			// We only need to know if there is more than one category.
			'number'     => 2,
		) );

		// Count the number of categories that are attached to the posts.
		$all_the_cool_cats = count( $all_the_cool_cats );

		set_transient( 'my_transit_lines_categories', $all_the_cool_cats );
	}

	if ( $all_the_cool_cats > 1 ) {
		// This blog has more than 1 category so my_transit_lines_categorized_blog should return true.
		return true;
	} else {
		// This blog has only 1 category so my_transit_lines_categorized_blog should return false.
		return false;
	}
}

/**
 * Flush out the transients used in my_transit_lines_categorized_blog.
 */
function my_transit_lines_category_transient_flusher() {
	// Like, beat it. Dig?
	delete_transient( 'my_transit_lines_categories' );
}
add_action( 'edit_category', 'my_transit_lines_category_transient_flusher' );
add_action( 'save_post',     'my_transit_lines_category_transient_flusher' );
