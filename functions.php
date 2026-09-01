<?php
/**
 * My Transit Lines functions and definitions
 *
 * @package My Transit Lines
 */
 
/* created by Johannes Bouchain, 2014-09-06 */
 
/* ### PLEASE NOTE ###
 * 1) all basic and general functions are included in this file. Functions for special My Transit Lines modules are (and for future stuff: should be) put into /modules/nameofmodule/filename.php|js|css|etc.
 * 2) all custom functions for this theme should start with the theme name abbreviation mtl: e.g. mtl-custom-function()
 * 3) please enqueue all scripts and styles for *all* modules using the respective section below
 * 4) please include all strings needed for l10n in the theme and theme module js files into the mtl_localize_script() funtion below
 */
 
 /**
 * include module functions files
 */
include( get_template_directory() . '/modules/common.php'); // Common functions for all modules
include( get_template_directory() . '/modules/mtl-the-map/mtl-the-map.php'); // The map module
include( get_template_directory() . '/modules/mtl-login-register/mtl-login-register.php'); // Login/Register module
include( get_template_directory() . '/modules/mtl-admin-menu/mtl-admin-menu.php'); // dashboard admin section module
include( get_template_directory() . '/modules/mtl-proposal-form/mtl-proposal-form.php'); // proposal form
include( get_template_directory() . '/modules/mtl-single-proposal/mtl-single-proposal.php'); // single proposal custom display
include( get_template_directory() . '/modules/mtl-search-bar/mtl-search-bar.php'); // search bar
include( get_template_directory() . '/modules/mtl-custom-posttypes/mtl-custom-posttypes.php'); // create the custom posttypes necessary for the theme
include( get_template_directory() . '/modules/mtl-comment-notification/mtl-comment-notification.php'); // custom comment notification, to be extended
include( get_template_directory() . '/modules/mtl-metaboxes/mtl-metaboxes.php'); // proposal meta boxes for dashboard post edit view
include( get_template_directory() . '/modules/mtl-flextiles/mtl-flextiles.php'); // flexible tiles e.g. for menues
include( get_template_directory() . '/modules/mtl-download-geojson/mtl-download-geojson.php'); // download geojson functioanlity
include( get_template_directory() . '/modules/mtl-update-old-proposals/mtl-update-old-proposals.php'); // automatic updating for old proposals
include( get_template_directory() . '/modules/mtl-show-metadata/mtl-show-metadata.php'); // metadata box for proposals
include( get_template_directory() . '/modules/mtl-proposal-list/mtl-proposal-list.php'); // proposal list with multiple tabs
include( get_template_directory() . '/modules/mtl-sorting-phase-form/mtl-sorting-phase-form.php'); // sorting phase form
include( get_template_directory() . '/modules/mtl-user-settings/mtl-user-settings.php'); // user settings

/**
 * Set the content width based on the theme's design and stylesheet.
 */
if ( ! isset( $content_width ) ) {
	$content_width = 640; /* pixels */
}

if ( ! function_exists( 'my_transit_lines_setup' ) ) {
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function my_transit_lines_setup() {

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link http://codex.wordpress.org/Function_Reference/add_theme_support#Post_Thumbnails
		*/
		add_theme_support( 'post-thumbnails' );

		/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
		add_theme_support( 'html5', array(
			'search-form', 'comment-form', 'comment-list', 'gallery', 'caption',
		) );

		/*
		* Enable support for Post Formats.
		* See http://codex.wordpress.org/Post_Formats
		*/
		add_theme_support( 'post-formats', array(
			'aside', 'image', 'video', 'quote', 'link',
		) );

		// Setup the WordPress core custom background feature.
		add_theme_support( 'custom-background', apply_filters( 'my_transit_lines_custom_background_args', array(
			'default-color' => 'ffffff',
			'default-image' => '',
		) ) );
	}
}
add_action( 'after_setup_theme', 'my_transit_lines_setup' );

/**
 * Implement the Custom Header feature.
 */
//require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Custom functions that act independently of the theme templates.
 */
require get_template_directory() . '/inc/extras.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
require get_template_directory() . '/inc/jetpack.php';

/* END basic functions from underscores.me package generator */

/* BEGIN My Transit Lines functions */

/**
 * Enqueue scripts and styles for the My Transit Line theme.
 */
function my_transit_lines_scripts() {

	wp_enqueue_style( 'my-transit-lines-style', get_stylesheet_uri(), array(), wp_get_theme()->version );
	
	// enable jQuery
	wp_enqueue_script( 'jquery');
	
	// enable WP suggest script
	wp_enqueue_script( 'suggest' );
	
	// AJAX form plugin
	wp_enqueue_script( 'jquery-form', get_template_directory_uri() . '/js/jquery.form.min.js', array(), wp_get_theme()->version);

	wp_enqueue_script( 'my-transit-lines-navigation', get_template_directory_uri() . '/js/navigation.js', array(), wp_get_theme()->version, true );
	wp_enqueue_script( 'my-transit-lines-skip-link-focus-fix', get_template_directory_uri() . '/js/skip-link-focus-fix.js', array(), wp_get_theme()->version, true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	wp_enqueue_script( 'mtl-util', get_template_directory_uri() . '/js/util.js', array(), wp_get_theme()->version);
	wp_enqueue_script( 'mtl-sorting-phase-form', get_template_directory_uri() . '/modules/mtl-sorting-phase-form/mtl-sorting-phase-form.js', [], wp_get_theme()->version, [ 'strategy' => 'defer', 'in_footer' => true ] );

	wp_register_script( 'openlayers', get_template_directory_uri() . '/openlayers/dist/ol.js', array(), wp_get_theme()->version, true);
	wp_register_script( 'my-transit-lines', get_template_directory_uri() . '/js/my-transit-lines.js', array('openlayers', 'mtl-util', 'wp-util'), wp_get_theme()->version, true);

	wp_enqueue_script( 'mtl-search-bar', get_template_directory_uri() . '/modules/mtl-search-bar/mtl-search-bar.js', array(), wp_get_theme()->version, ['strategy' => 'defer']);
	wp_enqueue_script( 'mtl-proposal-list', get_template_directory_uri() . '/modules/mtl-proposal-list/mtl-proposal-list.js', ['my-transit-lines'], wp_get_theme()->version, ['strategy' => 'defer', 'in_footer' => true]);
}
add_action( 'wp_enqueue_scripts', 'my_transit_lines_scripts' );

/**
 * Register widget area.
 *
 * @link http://codex.wordpress.org/Function_Reference/register_sidebar
 */
function mtl_widgets_init() {
	$sidebars = array(
		array(__( 'Sidebar', 'my-transit-lines' ), 'sidebar-1'),
		array(__( 'Top Menu', 'my-transit-lines' ), 'sidebar-2'),
		array(__( 'Footer', 'my-transit-lines' ), 'footer'),
	);
	foreach($sidebars as $sidebar) {
		register_sidebar( array(
			'name'          => $sidebar[0],
			'id'            => $sidebar[1],
			'description'   => '',
			'before_widget' => '<aside id="%1$s" class="widget %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h1 class="widget-title">',
			'after_title'   => '</h1>',
		) );
	}
}
add_action( 'widgets_init', 'mtl_widgets_init' );

/**
 * start session - needed for frontend form captcha and for other things.
 */
function mtl_session_start() {
	if( !session_id() ) { 
		session_cache_limiter ('private, must-revalidate');
		session_start();
	}

	/*
	 * Make theme available for translation.
	 * Translations can be filed in the /languages/ directory.
	 * If you're building a theme based on My Transit Lines, use a find and replace
	 * to change 'my-transit-lines' to the name of your theme in all the template files
	 */
	load_theme_textdomain( 'my-transit-lines', get_template_directory() . '/languages' );

	/**
	 * register all nav menus needed for the theme
	 */
	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'my-transit-lines' ),
		'secondary' => __( 'Secondary Menu', 'my-transit-lines' ),
	) );
}
add_action( 'init', 'mtl_session_start', -1 );

/**
 * Uses output buffering to return the echoed output of the function passed as a variable
 * @param callable $function
 * @return string
 */
function mtl_get_output($function) {
	ob_start();
	$function();
	return ob_get_clean();
}

/**
 * create domain output for automatically created e-mail addresses like noreply@...
 */
function mtl_maildomain() {
	$maildomain = str_replace('http://','',get_bloginfo('url'));
	$maildomain = str_replace('https://','',get_bloginfo('url'));
	$maildomain = str_replace('www.','',$maildomain);
	$maildomain = explode('/',$maildomain);
	$maildomain = $maildomain[0];
	return $maildomain;
}

/**
 * change comment form defaults. More default stuff might be added here later
 */
function mtl_commentform_defaults($defaults) {
	$defaults['title_reply'] = __('Leave a comment for this post','my-transit-lines');
	return $defaults;
}
add_filter('comment_form_defaults', 'mtl_commentform_defaults');

/**
 * redirect all possible domains to the main domain, needed e.g. for correct webfont display. Is there a better solution?
 */
function mtl_main_domain_redirect() {
	$checkurl='http://'.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
	$other_domains = array(); // put all possible domains that can be used except the site_url to this array, without http:// and www.
	$blog_domain = str_replace('http://www.','',get_bloginfo('url'));
	foreach($other_domains as $other_domain) {
		if(str_replace($other_domain,$blog_domain,$checkurl) != $checkurl) {
			$new_url = str_replace($other_domain,$blog_domain,$checkurl);
			header("Location: $new_url");
			exit();
		}
	}
}

/**
 * l10n of all scripts needed in js files
 */
function mtl_localize_script($getVar = false) {
	$translatedStrings = array(
		'titleOPNV'=>__('Public transit map','my-transit-lines'),
		'attributionOPNV'=>__('Map data <a href="http://www.openstreetmap.org">OpenStreetMap</a> (© OpenStreetMap contributors), Map: CC-BY-SA license (© by <a href="http://memomaps.de/">MeMoMaps</a>)','my-transit-lines'),
		'titleOpentopomap'=>__('OpenTopoMap','my-transit-lines'),
		'attributionOpentopomap'=>__('Map data: © <a href="https://openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Style: © <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)','my-transit-lines'),
		'titleBasemap' => __('Basemap (Germany)','my-transit-lines'),
		'attributionBasemap' => __('© GeoBasis-DE / <a href="https://www.bkg.bund.de">BKG</a>, <a href="https://creativecommons.org/licenses/by/4.0/">CC BY 4.0</a>','my-transit-lines'),
		'titleOpenrailwaymap'=>__('OpenRailwayMap (standard)','my-transit-lines'),
		'attributionOpenrailwaymap'=>__('OpenRailwayMap Standard overlay style: <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA 2.0</a> <a href="http://www.openrailwaymap.org/">OpenRailwayMap</a>','my-transit-lines'),
		'titleOpenrailwaymapMaxspeed'=>__('OpenRailwayMap (maxspeed)','my-transit-lines'),
		'attributionOpenrailwaymapMaxspeed'=>__('OpenRailwayMap maxspeed overlay style: <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA 2.0</a> <a href="http://www.openrailwaymap.org/">OpenRailwayMap</a>','my-transit-lines'),
		'titleOpenrailwaymapSignals'=>__('OpenRailwayMap (signals)','my-transit-lines'),
		'attributionOpenrailwaymapSignals'=>__('OpenRailwayMap signals overlay style: <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA 2.0</a> <a href="http://www.openrailwaymap.org/">OpenRailwayMap</a>','my-transit-lines'),
		'titleOpenrailwaymapElectrified'=>__('OpenRailwayMap (electrification)','my-transit-lines'),
		'attributionOpenrailwaymapElectrified'=>__('OpenRailwayMap electrification overlay style: <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA 2.0</a> <a href="http://www.openrailwaymap.org/">OpenRailwayMap</a>','my-transit-lines'),
		'titleOpenrailwaymapGauge'=>__('OpenRailwayMap (gauge)','my-transit-lines'),
		'attributionOpenrailwaymapGauge'=>__('OpenRailwayMap gauge overlay style: <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA 2.0</a> <a href="http://www.openrailwaymap.org/">OpenRailwayMap</a>','my-transit-lines'),
		'titleESRISatellite'=>__('Satellite images (ESRI)','my-transit-lines'),
		'attributionESRISatellite'=>__('Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community','my-transit-lines'),	
		'titleOSM'=>__('OpenStreetMap standard (Mapnik)','my-transit-lines'),
		'attributionIcons'=>__('Silk Icons: <a href="http://creativecommons.org/licenses/by/2.5/">CC-BY 2.5</a> <a href="https://github.com/legacy-icons/famfamfam-silk/">Silk on Github</a>'),
		'vectorLayerTitle'=>__('Line proposal (vector data)','my-transit-lines'),
		'buildLine'=>__('Build line','my-transit-lines'),
		'buildStations'=>__('Build stations','my-transit-lines'),
		'editObjects'=>__('Edit line/stations','my-transit-lines'),
		'selectObjects'=>__('Select line/stations','my-transit-lines'),
		'moveObjects'=>__('Move selected line/stations','my-transit-lines'),
		'deleteObjects'=>__('Delete selected line/stations','my-transit-lines'),
		'baselayersTitle'=>__('Base layers','my-transit-lines'),
		'overlaysTitle'=>__('Overlays','my-transit-lines'),
		'changeToSubmit'=>__('Please select another tool on the map to submit your proposal.','my-transit-lines'),
		'none'=>__('None','my-transit-lines'),
		'snapping'=>__('Snapping','my-transit-lines'),
		'confirmLeaveWebsite'=>__('Confirm that you want to leave? All unsaved data will be lost!', 'my-transit-lines'),
		'Point'=>__('Add point', 'my-transit-lines'),
		'LineString'=>__('Add line', 'my-transit-lines'),
		'Polygon'=>__('Add polygon', 'my-transit-lines'),
		'Circle'=>__('Add circle', 'my-transit-lines'),
		'Modify'=>__('Edit features', 'my-transit-lines'),
		'Select'=>__('Select features', 'my-transit-lines'),
		'Delete'=>__('Delete selected features', 'my-transit-lines'),
		'Navigate'=>__('Navigate map', 'my-transit-lines'),
		'AddSnapping'=>__('Enable snapping', 'my-transit-lines'),
		'RemoveSnapping'=>__('Disable snapping', 'my-transit-lines'),
		'lengthString'=>__('Length: ', 'my-transit-lines'),
		'radius'=>__('Radius: ', 'my-transit-lines'),
		'speed'=>__('Speed: ', 'my-transit-lines'),
		'area'=>__('Area: ', 'my-transit-lines'),
		'decimalSeparator'=>_x('.', 'decimal separator', 'my-transit-lines'),
		'vectorLayerToggle'=>__('Show feature data', 'my-transit-lines'),
		'billion'=>__('billion', 'my-transit-lines'),
		'million'=>__('million', 'my-transit-lines'),
		'thousand'=>__('thousand', 'my-transit-lines'),
	);
	$localizeScript = '<script type="text/javascript">'."\r\n".'/* <![CDATA[ */'."\r\n".'var objectL10n = {';
	foreach($translatedStrings as $key => $value) {
		$localizeScript .= '"'.$key.'":"'.addslashes($value).'",';
	}
	$localizeScript .= '};'."\r\n".'/* ]]> */'."\r\n".'</script>'."\r\n";
	if($getVar) return $localizeScript;
	else echo $localizeScript;
}

function add_tax_to_pll( $taxonomies, $is_settings ) {
	// removes categories from being translated by Polylang
	unset( $taxonomies['category'] );
	unset( $taxonomies['sorting-phase-status'] );
    return $taxonomies;
}
add_filter( 'pll_get_taxonomies', 'add_tax_to_pll', 10, 2 );

function add_cpt_to_pll( $post_types, $is_settings ) {
	// removes mtlproposals from being translated by Polylang
	unset( $post_types['mtlproposal'] );
	return $post_types;
}
add_filter( 'pll_get_post_types', 'add_cpt_to_pll', 10, 2);

if (!function_exists("pll_get_post")) {
	function pll_get_post($post_id, $lang = '') {
		return get_post($post_id);
	}
}

function get_site_locale() {
	$locale = get_option( 'WPLANG' );

	if ( empty($locale) ) {
		$locale = 'en_US';
	}

	return $locale;
}

/**
 * custom post meta display for not-single content view
 */
function mtl_posted_on_list($getVar) {
	global $post;

	$time_string = get_the_date( 'd.m.Y' );
	unset($author);

	if(get_post_meta($post->ID,'author-name',true)) $author = get_post_meta($post->ID,'author-name',true);
	else $author = esc_html( get_the_author() );
	
	$output = sprintf( __( '<span class="posted-on">%1$s</span>, <span class="byline"> by %2$s</span>', 'my-transit-lines' ),
		$time_string, 
		$author
	);

	if ($getVar) {
		return $output;
	} else {
		echo $output;
	}
}

/**
 * make all users selectable for user dropdown in admin edit proposal page
 */
add_filter('wp_dropdown_users_args', 'mtl_switch_user_args');
function mtl_switch_user_args($query_args) {
	$query_args['capability'] = [];
	return $query_args;
}

/**
 * add tinyMCE editor to comment form
 */
function gk_comment_form( $fields ) {
	$fields['comment_field'] = mtl_get_output(function() {
		$settings = array( 'media_buttons' => false, 'textarea_name' => 'comment', 'teeny'=>true, 'tinymce' => array( 'height' => 200 ));
		wp_editor('', 'comment', $settings);
	});
    return $fields;
}
add_filter( 'comment_form_defaults', 'gk_comment_form' );

// new fix for comment reply visual editor bug
// @source: https://wordpress.org/support/topic/reply-to-comments-no-longer-works-2/
global $wp_version;
if (version_compare($wp_version, '5.1.1', '>=')) {
    add_filter('comment_reply_link', 'haremu_replace_comment_reply_link', 10, 4);
    function haremu_replace_comment_reply_link($link, $args, $comment, $post)
    {
        if (get_option('comment_registration') && !is_user_logged_in()) {
            $link = sprintf(
                '<a rel="nofollow" class="comment-reply-login" href="%s">%s</a>',
                esc_url(wp_login_url(get_permalink())),
                $args['login_text']
            );
        } else {
            $onclick = sprintf(
                'return addComment.moveForm( "%1$s-%2$s", "%2$s", "%3$s", "%4$s" )',
                $args['add_below'],
                $comment->comment_ID,
                $args['respond_id'],
                $post->ID
            );
            $link = sprintf(
                "<a rel='nofollow' class='comment-reply-link' href='%s' onclick='%s' aria-label='%s'>%s</a>",
                esc_url(add_query_arg('replytocom', $comment->comment_ID, get_permalink($post->ID))) . "#" . $args['respond_id'],
                $onclick,
                esc_attr(sprintf($args['reply_to_text'], $comment->comment_author)),
                $args['reply_text']
            );
        }
        return $link;
    }
}

// wp_editor doesn't work when clicking reply. Here is the fix.
add_action( 'wp_enqueue_scripts', '__THEME_PREFIX__scripts' );
function __THEME_PREFIX__scripts() {
    wp_enqueue_script('jquery');
}
add_filter( 'comment_reply_link', '__THEME_PREFIX__comment_reply_link' );
function __THEME_PREFIX__comment_reply_link($link) {
    return str_replace( 'onclick=', 'data-onclick=', $link );
}
add_action( 'wp_head', '__THEME_PREFIX__wp_head' );
function __THEME_PREFIX__wp_head() {
?>
<script type="text/javascript">
	jQuery(function($) {
		$('.comment-reply-link, .comment-edit-link').on("click", function(e) {
			e.preventDefault();
			var args = $(this).data('onclick');
			args = args.replace(/.*\(|\)/gi, '').replace(/\"|\s+/g, '');
			args = args.split(',');
			tinymce.EditorManager.execCommand('mceRemoveEditor', true, 'comment');
			addComment.moveForm.apply( addComment, args );
			tinymce.EditorManager.execCommand('mceAddEditor', true, 'comment');
		});
	});
</script>
<?php
}

function mtl_tiles_empty_content($content) {
	global $post;
	if(get_post_type($post->ID)=='mtlproposal' && !is_single()) return "";
	else return $content;
}
add_filter('the_content','mtl_tiles_empty_content', 9);

// current page url
function curPageURL() {
	$pageURL = 'http';
	if ($_SERVER["HTTPS"] == "on") {
		$pageURL .= "s";
	}
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} else {
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
	return $pageURL;
}

/* disable comments for posts */
function mtl_filter_media_comment_status( $open, $post_id ) {
    $post = get_post( $post_id );
    if( !$post || $post->post_type == 'post' ) {
        return false;
    }
    return $open;
}
add_filter( 'comments_open', 'mtl_filter_media_comment_status', 10 , 2 );

function filter_comment($content) {
	return custom_trim(wp_kses_post($content), array_merge(CUSTOM_TRIM_DEFAULT_REMOVE, ["&nbsp;", "<p></p>", "<p>&nbsp;</p>"]));
}
add_filter( 'pre_comment_content', 'filter_comment' );

function allowed_html_tags() {
	return array(
		'a' => array(
			'href' => true,
			'title' => true,
			'style' => true,
		),
		'blockquote' => array(
			'style' => true,
		),
		'br' => array(
			'style' => true,
		),
		'code' => array(
			'style' => true,
		),
		'del' => array(
			'style' => true,
		),
		'div' => array(
			'style' => true,
		),
		'em' => array(
			'style' => true,
		),
		'img' => array(
			'style' => true,
			'src' => true,
			'alt' => true,
			'height' => true,
			'width' => true,
		),
		'li' => array(
			'style' => true,
		),
		'ol' => array(
			'style' => true,
		),
		'p' => array(
			'style' => true,
		),
		'span' => array(
			'style' => true,
		),
		'strong' => array(
			'style' => true,
		),
		'table' => array(
			'style' => true,
		),
		'td' => array(
			'style' => true,
		),
		'th' => array(
			'style' => true,
		),
		'tr' => array(
			'style' => true,
		),
		'ul' => array(
			'style' => true,
		),
	);
}
add_filter( 'wp_kses_allowed_html', 'allowed_html_tags');

function allowed_css_styles() {
	return array(
		'text-align',
		'text-decoration',
		'font-style',
		'font-weight',
		'font-size',
	);
}
add_filter( 'safe_style_css', 'allowed_css_styles');
