<?php
/**
 * @package My Transit Lines
 */

$category = get_the_category($post->ID);
$post_category = $category[0]->slug;

// get the mtl options
$mtl_options = get_option('mtl-option-name');

$post_link = get_permalink($post->ID);
if($post->post_status=='draft' && $post->post_author == get_current_user_id()) $post_link = get_permalink($mtl_options['mtl-addpost-page']).'?edit_proposal='.$post->ID;
?>
<article id="post-<?php the_ID(); ?>">
	<?php 
	unset($thumbnail_src);
	$thumbnail_src = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'medium' );
	if($thumbnail_src) { ?>
	<div class="entry-thumbnail" style="background-image:url('<?php echo $thumbnail_src[0]; ?>');"></div>
	<?php } else { ?>
	<div class="entry-thumbnail placeholder"></div>
	<?php } ?>
	<header class="entry-header">
		<h1 class="entry-title"><a href="<?php echo $post_link; ?>" rel="bookmark"><?php if('mtlproposal' == get_post_type() && ($post->post_status == 'draft' || (get_post_meta($post->ID,'mtl-under-construction',true)=='on' || get_post_meta($post->ID,'mtl-proposal-phase',true)=='elaboration-phase'))) echo '<span class="draft-flag">'.esc_html__('Draft','my-transit-lines').'</span> '; the_title(); ?></a></h1>

		<?php if ( 'post' == get_post_type() || 'mtlproposal' == get_post_type()) : ?>
		<div class="entry-meta">
			<?php mtl_posted_on2(); ?>
		</div><!-- .entry-meta -->
		<?php endif; ?>
		<?php if ( has_post_thumbnail() && ! post_password_required() ) : ?>
		
		<?php endif; ?>
	</header><!-- .entry-header -->
	<?php the_content(); ?>

	<footer class="entry-footer">
		<?php if ( ! post_password_required() && ( comments_open() || '0' != get_comments_number() ) && $post->post_status == 'publish') : ?>
		<span class="comments-link"><strong><?php comments_popup_link( __( 'Leave a comment', 'my-transit-lines' ), __( '1 Comment', 'my-transit-lines' ), __( '% Comments', 'my-transit-lines' ) ); ?></strong></span>
		<?php endif; ?>

		<?php edit_post_link( __( 'Edit', 'my-transit-lines' ), '<span class="edit-link"> - ', '</span>' ); ?>
	</footer><!-- .entry-footer -->
</article><!-- #post-## -->
