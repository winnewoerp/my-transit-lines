<?php
// our include
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

// our loop
if (have_posts()) {
 while (have_posts()){
 the_post();
 get_template_part( 'content', get_post_format() );
 }
}
?>