<?php
/**
 * My Transit Lines
 * Comment editing module
 *
 * @package My Transit Lines
 */
 
/* created by Johannes Bouchain, 2014-09-25 */

/* ### STILL TO DO ###
 * don't know yet 
 */

 /**
 * comment editing
 */
 function mtl_edit_comments($content) {
	$comment_frontend_edit_link = '<div class="frontend-edit">'.get_comment_frontend_edit_link().'</div>';
	return $content.$comment_frontend_edit_link;
 }
add_filter('comment_text','mtl_edit_comments');
 
 /**
 * Retrieve HTML content for the comment frontend edit link.
 */
function get_comment_frontend_edit_link($args = array(), $comment = null, $post = null) {

	$defaults = array(
		'add_below'  => 'comment',
		'frontend_edit_id' => 'edit',
		'frontend_edit_text' => __('Edit this comment','my-transit-lines'),
		'before'     => '',
		'after'      => ''
	);

	$args = wp_parse_args($args, $defaults);

	extract($args, EXTR_SKIP);

	$comment = get_comment($comment);
	if ( empty($post) )
		$post = $comment->comment_post_ID;
	$post = get_post($post);

	if ( !comments_open($post->ID) )
		return false;

	$link = '';
	
	$current_userid = wp_get_current_user()->id;
	$comment_id = $comment->comment_ID;

	if (current_user_can( 'manage_options' ) || $current_userid == $comment->user_id)
		$link = "<a class='comment-frontend-edit-link' href='" . esc_url( add_query_arg( 'editcom', $comment->comment_ID ) ) . "#" . $frontend_edit_id . "' onclick='return addComment.moveForm(\"$add_below-$comment->comment_ID\", \"$comment->comment_ID\", \"$frontend_edit_id\", \"$post->ID\")'>$frontend_edit_text</a>";

	/**
	 * Filter the comment frontend edit link.
	 */
	return apply_filters( 'comment_frontend_edit_link', $before . $link . $after, $args, $comment, $post );
}
?>