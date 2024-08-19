<?php
/**
 * My Transit Lines
 * Comment notification module
 *
 * @package My Transit Lines
 */
 
/* created by Johannes Bouchain, 2014-09-06 */

/* ### STILL TO DO ###
 * Add checkbox to comment form for users who want to follow the proposal - and add an extra notification function for this 
 */

 /**
 * custom notify comment author, based upon a script by Jonathan Penny http://www.jonathanpenny.co.uk
 */
 update_option('comments_notify',0);
 update_option('moderation_notify',1);
function mtl_notify_postauthor($comment_id, $comment_type='') {
	$comment = get_comment($comment_id);
	$post    = get_post($comment->comment_post_ID);
	$user    = get_userdata( $post->post_author );
 
	if ('' == $user->user_email) return false; // If there's no email to send the comment to
 
	$comment_author_domain = @gethostbyaddr($comment->comment_author_IP);
 
	$blogname = get_option('blogname');
 
	if ( empty( $comment_type ) ) $comment_type = 'comment';
 
	if ('comment' == $comment_type) {
		$notify_message  = sprintf( __('New comment on your post "%2$s"','my-transit-lines'), $comment->comment_post_ID, $post->post_title ) . "\r\n";
		$notify_message .= sprintf( __('Author : %1$s','my-transit-lines'), $comment->comment_author ) . "\r\n";
		$notify_message .= __('Comment: ','my-transit-lines') . "\r\n" . $comment->comment_content . "\r\n\r\n";
		$notify_message .= __('You can see all comments on this post here: ','my-transit-lines') . "\r\n";
		$subject = sprintf( __('[%1$s] Comment: "%2$s"','my-transit-lines'), $blogname, $post->post_title );
	} elseif ('trackback' == $comment_type) {
		$notify_message  = sprintf( __('New trackback on your post #%1$s "%2$s"','my-transit-lines'), $comment->comment_post_ID, $post->post_title ) . "\r\n";
		$notify_message .= sprintf( __('Website: %1$s','my-transit-lines'), $comment->comment_author ) . "\r\n";
		$notify_message .= __('Excerpt: ','my-transit-lines') . "\r\n" . $comment->comment_content . "\r\n\r\n";
		$notify_message .= __('You can see all trackbacks on this post here: ','my-transit-lines') . "\r\n";
		$subject = sprintf( __('[%1$s] Trackback: "%2$s"','my-transit-lines'), $blogname, $post->post_title );
	} elseif ('pingback' == $comment_type) {
		$notify_message  = sprintf( __('New pingback on your post #%1$s "%2$s"','my-transit-lines'), $comment->comment_post_ID, $post->post_title ) . "\r\n";
		$notify_message .= sprintf( __('Website: %1$s','my-transit-lines'), $comment->comment_author ) . "\r\n";
		$notify_message .= __('Excerpt: ','my-transit-lines') . "\r\n" . sprintf('[...] %s [...]', $comment->comment_content ) . "\r\n\r\n";
		$notify_message .= __('You can see all pingbacks on this post here: ','my-transit-lines') . "\r\n";
		$subject = sprintf( __('[%1$s] Pingback: "%2$s"','my-transit-lines'), $blogname, $post->post_title );
	}
	$notify_message .= get_permalink($comment->comment_post_ID) . "#comments\r\n\r\n";
	 
	//$wp_email = 'wordpress@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));
	$wp_email = get_option('admin_email');
 
	$from = "From: \"$blogname\" <$wp_email>";
		 
	$message_headers = "$from\n"
		. "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
 
	$notify_message = apply_filters('comment_notification_text', $notify_message, $comment_id);
	$subject = apply_filters('comment_notification_subject', $subject, $comment_id);
	$message_headers = apply_filters('comment_notification_headers', $message_headers, $comment_id);
	
	if(!get_post_meta($post->ID,'author-name',true)) {
		$to = $user->user_email;
		$message_headers .= 'Bcc: '.get_option('admin_email')."\r\n";
	}
	else $to = get_option('admin_email');
	@wp_mail($to, $subject, $notify_message, $message_headers);
 
	return true;
}
add_action('comment_post', 'mtl_notify_postauthor');
