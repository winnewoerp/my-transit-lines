<?php
/**
 * @package My Transit Lines
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header" data-mtl-replace-with="header.entry-header">
		<h1 class="entry-title"><?php echo get_the_title_flags(); ?></h1>

		<div class="entry-meta">
			<?php my_transit_lines_posted_on(); ?>
		</div><!-- .entry-meta -->
	</header><!-- .entry-header -->

	<div class="entry-content">
		<?php the_content(); ?>
		<?php
			wp_link_pages( array(
				'before' => '<div class="page-links">' . __( 'Pages:', 'my-transit-lines' ),
				'after'  => '</div>',
			) );
		?>
	</div><!-- .entry-content -->

	<?php edit_post_link( __( 'Edit', 'my-transit-lines' ), '<span class="edit-link" data-mtl-replace-with="span.edit-link">', '</span>' ); ?>
</article><!-- #post-## -->
