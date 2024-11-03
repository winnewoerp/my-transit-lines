<?php
/**
 * My Transit Lines
 * Dashboard admin section module class
 *
 * @package My Transit Lines
 */
 
/* created by Johannes Bouchain, 2014-09-06 */

class MtlSettingsPage
{
	public function __construct()
	{
		/* add menu page */
		add_action( 'admin_menu', array( $this, 'add_mtl_menu_page' ) );
		add_action( 'admin_menu', array( $this, 'add_mtl_submenu_pages' ) );
		
		add_action( 'admin_init', array( $this, 'page_init' ) );
	}

	/**
	 * Add MTL main admin page
	 */
	public function add_mtl_menu_page()
	{
		// Creates new menu point
		add_menu_page(
			'My Transit Lines', 
			'My Transit Lines', 
			'manage_options', 
			'mtl_settings_page', 
			array( $this, 'mtl_menu_page' )
		);
	}

	/**
	 * Add MTL subpages
	 */
	public function add_mtl_submenu_pages() {
		// Creates new menu subpages
		add_submenu_page(
			'mtl_settings_page',
			__('Instructions','my-transit-lines'),
			__('Instructions','my-transit-lines'),
			'manage_options',
			'mtl-instructions',
			array( $this, 'mtl_submenu_page_instructions')
		);
		add_submenu_page(
			'mtl_settings_page',
			__('General settings','my-transit-lines'),
			__('General settings','my-transit-lines'),
			'manage_options',
			'mtl-general-settings',
			array( $this, 'mtl_submenu_page_general_settings')
		);
		add_submenu_page(
			'mtl_settings_page',
			__('Map and category settings','my-transit-lines'),
			__('Map and category settings','my-transit-lines'),
			'manage_options',
			'mtl-settings',
			array( $this, 'mtl_submenu_page_settings')
		);
	}

	/**
	 * MTL Admin Settings main page callback
	 */
	public function mtl_menu_page()
	{
		if (!current_user_can('manage_options'))
			return;
		?>
		<div class="wrap">
			<h1 class="mtl-admin-page-title"><span class="logo"></span> <?php echo wp_get_theme(); ?></h1>
			<h2><?php _e('Introduction','my-transit-lines'); ?></h2>
			<ul>
				<li><a href="?page=mtl-instructions"><?php _e('Instructions','my-transit-lines'); ?></a></li>
				<li><a href="?page=mtl-general-settings"><?php _e('General settings','my-transit-lines'); ?></a></li>
				<li><a href="?page=mtl-settings"><?php _e('Settings for map and categories','my-transit-lines'); ?></a></li>
			</ul>
		</div>
		<?php
	}

	/**
	 * MTL Admin Settings instructions subpage callback
	 */
	public function mtl_submenu_page_instructions()
	{
		if (!current_user_can('manage_options'))
			return;
		?>
		<div class="wrap">
			<h1 class="mtl-admin-page-title"><span class="logo"></span> <?php echo wp_get_theme(); ?></h1>
			<h2><?php _e('Instructions','my-transit-lines'); ?></h2>
			<p><?php _e('Page content under preparation for upcoming versions.','my-transit-lines'); ?></p>
		</div>
		<?php
	}

	/**
	 * MTL Admin Settings logo settings subpage callback
	 */
	public function mtl_submenu_page_general_settings()
	{
		if (!current_user_can('manage_options'))
			return;
		
		?>
		<div class="wrap">
			<h1 class="mtl-admin-page-title"><span class="logo"></span> <?php echo wp_get_theme(); ?></h1>
			<h2><?php _e('General settings','my-transit-lines'); ?></h2>
			<form method="post" action="options.php">
			<?php
				// This prints out all hidden setting fields
				settings_fields('mtl-settings-group-general');
				settings_fields('mtl-settings-group-general2');
				settings_fields('mtl-settings-group-general3');
				settings_fields('mtl-settings-group-general4');
				settings_fields('mtl-settings-group-general5');
				do_settings_sections('mtl-general-settings');
				submit_button();
			?>
			</form>
		</div>
		<?php
	}

	/**
	 * MTL Admin Settings settings subpage callback
	 */
	public function mtl_submenu_page_settings()
	{
		if (!current_user_can('manage_options'))
			return;

		?>
		<div class="wrap">
			<h1 class="mtl-admin-page-title"><span class="logo"></span> <?php echo wp_get_theme(); ?></h1>
			<h2><?php _e('Settings','my-transit-lines'); ?></h2>
			<form method="post" action="options.php">
			<?php
				// This prints out all hidden setting fields
				settings_fields( 'mtl-settings-group-map1' );
				settings_fields( 'mtl-settings-group-map2' );
				settings_fields( 'mtl-settings-group-categories' );
				do_settings_sections( 'mtl-settings' );
				submit_button();
			?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init()
	{
		register_setting('mtl-settings-group-general', 'mtl-option-name3', array( $this, 'sanitize' ));
		register_setting('mtl-settings-group-general2', 'mtl-option-name3', array( $this, 'sanitize' ));
		register_setting('mtl-settings-group-general3', 'mtl-option-name3', array( $this, 'sanitize' ));
		register_setting('mtl-settings-group-general4', 'mtl-option-name3', array( $this, 'sanitize' ));
		register_setting('mtl-settings-group-general5', 'mtl-option-name3', array( $this, 'sanitize' ));
		register_setting('mtl-settings-group-map1', 'mtl-option-name', array( $this, 'sanitize' ));
		register_setting('mtl-settings-group-map2', 'mtl-option-name', array( $this, 'sanitize' ));
		register_setting('mtl-settings-group-categories', 'mtl-option-name', array( $this, 'sanitize' ));
		register_setting('mtl-addpost-page', 'mtl-option-name', array( $this, 'sanitize' ));

		// settings section general
		add_settings_section('mtl-settings-group-general', __('Logo Settings','my-transit-lines'), array( $this, 'print_general_section_content' ), 'mtl-general-settings');
		add_settings_field('mtl-main-logo', __('Load the main site logo','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-general-settings','mtl-settings-group-general',array('field_name' => 'mtl-main-logo','type' => 'image','option_name'=>'mtl-option-name3'));
		add_settings_field('mtl-currency-text', __('The currency for the website'), array( $this, 'mtl_field_callback' ), 'mtl-general-settings','mtl-settings-group-general',array('field_name' => 'mtl-currency-text','type' => 'text','option_name'=>'mtl-option-name3'));
		add_settings_field('mtl-currency-symbol', __('The currency symbol for the website'), array( $this, 'mtl_field_callback' ), 'mtl-general-settings','mtl-settings-group-general',array('field_name' => 'mtl-currency-symbol','type' => 'text','option_name'=>'mtl-option-name3'));

		// settings section general 2
		add_settings_section('mtl-settings-group-general2', __('Other settings','my-transit-lines'), array( $this, 'print_general_section_content' ), 'mtl-general-settings');
		add_settings_field('mtl-allowed-drafts', __('Number of allowed drafts','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-general-settings','mtl-settings-group-general2',array('field_name' => 'mtl-allowed-drafts','type' => 'number','step' => '1', 'option_name'=>'mtl-option-name3'));
		add_settings_field('mtl-show-districts', __('Show administrative subdivision selection for','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-general-settings','mtl-settings-group-general2',array('field_name' => 'mtl-show-districts','type' => 'select','options'=>[['all',__('everyone','my-transit-lines')],['admin',__('only admins','my-transit-lines')],['none',__('no one','my-transit-lines')]],'option_name'=>'mtl-option-name3'));
		add_settings_field('mtl-country-source', __('Country areas file', 'my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-general-settings', 'mtl-settings-group-general2', array('field_name' => 'mtl-country-source','type' => 'text','option_name'=>'mtl-option-name3'));
		add_settings_field('mtl-state-source', __('State areas file', 'my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-general-settings', 'mtl-settings-group-general2', array('field_name' => 'mtl-state-source','type' => 'text','option_name'=>'mtl-option-name3'));
		add_settings_field('mtl-district-source', __('District areas file', 'my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-general-settings', 'mtl-settings-group-general2', array('field_name' => 'mtl-district-source','type' => 'text','option_name'=>'mtl-option-name3'));
		
		// settings section map1
		add_settings_section('mtl-settings-group-map1', __('Map Settings','my-transit-lines'), array( $this, 'print_map_section_content1' ), 'mtl-settings');
		add_settings_field('mtl-center-lon', __('Map center longitude (decimal)','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-settings','mtl-settings-group-map1',array('field_name' => 'mtl-center-lon','type' => 'text','option_name'=>'mtl-option-name'));
		add_settings_field('mtl-center-lat', __('Map center latitude (decimal)','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-settings','mtl-settings-group-map1',array('field_name' => 'mtl-center-lat','type' => 'text','option_name'=>'mtl-option-name'));

		// settings section map2
		add_settings_section('mtl-settings-group-map2', '', array( $this, 'print_map_section_content2' ), 'mtl-settings');
		add_settings_field('mtl-standard-zoom', __('Standard zoom level for overview map','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-settings','mtl-settings-group-map2',array('field_name' => 'mtl-standard-zoom','type' => 'text','option_name'=>'mtl-option-name'));

		// settings section categories
		add_settings_section('mtl-settings-group-categories', __('Transit Categories Settings','my-transit-lines'), array( $this, 'print_categories_section_content' ), 'mtl-settings');
		$all_categories = get_categories('show_option_none=Category&hide_empty=0&tab_index=4&taxonomy=category');
		foreach($all_categories as $single_category) {
			$catid = $single_category->term_id;
			$catname = __($single_category->name, 'my-transit-lines');
			add_settings_field('mtl-cat-use'.$catid, sprintf(__('How to use category <strong>%s</strong>','my-transit-lines'),$catname), array( $this, 'mtl_field_callback' ), 'mtl-settings','mtl-settings-group-categories', array('field_name' => 'mtl-cat-use'.$catid,'type' => 'select','class' => 'category-select','options'=>array(['use', __('Use everywhere','my-transit-lines')],['only-in-map', __('Use only in map','my-transit-lines')],['only-in-search', __('Use only for searching','my-transit-lines')],['no',__('Don\'t use category','my-transit-lines')]),'option_name'=>'mtl-option-name'));

			add_settings_field('mtl-color-cat'.$catid, sprintf(__('Color for category <strong>%s</strong>','my-transit-lines'),$catname), array( $this, 'mtl_field_callback' ), 'mtl-settings','mtl-settings-group-categories', array('field_name' => 'mtl-color-cat'.$catid,'type' => 'colorpicker','class' => 'category-setting'.$catid,'option_name'=>'mtl-option-name'));
			add_settings_field('mtl-image-cat'.$catid, sprintf(__('Map Icon for category <strong>%s</strong>','my-transit-lines'),$catname), array( $this, 'mtl_field_callback' ), 'mtl-settings','mtl-settings-group-categories', array('field_name' => 'mtl-image-cat'.$catid,'type' => 'image','class' => 'category-setting'.$catid,'option_name'=>'mtl-option-name'));
			add_settings_field('mtl-image-selected-cat'.$catid, sprintf(__('Map icon (selected) for category <strong>%s</strong>','my-transit-lines'),$catname), array( $this, 'mtl_field_callback' ), 'mtl-settings','mtl-settings-group-categories', array('field_name' => 'mtl-image-selected-cat'.$catid,'type' => 'image','class' => 'category-setting'.$catid,'option_name'=>'mtl-option-name'));
			add_settings_field('mtl-costs-cat'.$catid, sprintf(__('Costs per kilometer in million %ss for category <strong>%s</strong>', 'my-transit-lines'),get_option( 'mtl-option-name3' )['mtl-currency-text'],$catname), array( $this, 'mtl_field_callback' ), 'mtl-settings','mtl-settings-group-categories', array('field_name' => 'mtl-costs-cat'.$catid,'type' => 'number','step' => 'any','class' => 'category-setting'.$catid,'option_name'=>'mtl-option-name'));
			add_settings_field('mtl-allow-others-cat'.$catid, sprintf(__('Allow other categories (comma separated id-list) to be drawn for category <strong>%s</strong>', 'my-transit-lines'),$catname), array( $this, 'mtl_field_callback' ), 'mtl-settings','mtl-settings-group-categories', array('field_name' => 'mtl-allow-others-cat'.$catid,'type' => 'text','class' => 'category-setting'.$catid,'option_name'=>'mtl-option-name'));
			add_settings_field('mtl-also-search-for-cat'.$catid, sprintf(__('Also search for these categories (comma separated id-list) when searching for category <strong>%s</strong>', 'my-transit-lines'),$catname), array( $this, 'mtl_field_callback' ), 'mtl-settings','mtl-settings-group-categories', array('field_name' => 'mtl-also-search-for-cat'.$catid,'type' => 'text','option_name'=>'mtl-option-name','separator'=>true));
		}
		
		$all_pages = array_map(function($page) {
			return [$page->ID, $page->post_title];
		}, get_pages());

		// settings section page IDs
		add_settings_section('mtl-settings-group-pageids', __('Page IDs Settings','my-transit-lines'), array( $this, 'print_pageids_section_content' ), 'mtl-settings');
		add_settings_field('mtl-addpost-page', __('Page ID for page to add proposal','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-settings','mtl-settings-group-pageids',array('field_name' => 'mtl-addpost-page','type' => 'select','option_name'=>'mtl-option-name','options'=>$all_pages));
		add_settings_field('mtl-postlist-page', __('Page ID for proposal list page','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-settings','mtl-settings-group-pageids',array('field_name' => 'mtl-postlist-page','type' => 'select','option_name'=>'mtl-option-name','options'=>$all_pages));

		// settings section general texts
		add_settings_section('mtl-settings-group-general3', __('General texts settings','my-transit-lines'), array( $this, 'print_general_section_content' ), 'mtl-general-settings');
		add_settings_field('mtl-proposal-contact-form-title', __('Title for intro of proposal contact form','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-general-settings','mtl-settings-group-general3',array('field_name' => 'mtl-proposal-contact-form-title','option_name'=>'mtl-option-name3','type' => 'text'));
		add_settings_field('mtl-proposal-contact-form-intro', __('Intro text for proposal contact form','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-general-settings','mtl-settings-group-general3',array('field_name' => 'mtl-proposal-contact-form-intro','option_name'=>'mtl-option-name3','type' => 'textarea'));
		add_settings_field('mtl-proposal-metadata-contents', __('Contents of the metadata display','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-general-settings','mtl-settings-group-general3',array('field_name' => 'mtl-proposal-metadata-contents','option_name'=>'mtl-option-name3','type' => 'textarea'));

		// settings section reCAPTCHA texts
		add_settings_section('mtl-settings-group-general4', __('ReCAPTCHA settings','my-transit-lines'), array( $this, 'print_general_section_content' ), 'mtl-general-settings');
		add_settings_field('mtl-recaptcha-website-key', __('ReCAPTCHA website key','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-general-settings','mtl-settings-group-general4',array('field_name' => 'mtl-recaptcha-website-key','option_name'=>'mtl-option-name3','type' => 'text'));
		add_settings_field('mtl-recaptcha-secret-key', __('ReCAPTCHA secret key','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-general-settings','mtl-settings-group-general4',array('field_name' => 'mtl-recaptcha-secret-key','option_name'=>'mtl-option-name3','type' => 'text'));
		
		// settings section import texts
		add_settings_section('mtl-settings-group-general5', __('GeoJSON import settings','my-transit-lines'), array( $this, 'print_general_section_content' ), 'mtl-general-settings');
		add_settings_field('mtl-geojson-import-hints', __('GeoJSON import hints','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-general-settings','mtl-settings-group-general5',array('field_name' => 'mtl-geojson-import-hints','option_name'=>'mtl-option-name3','type' => 'textarea'));
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 */
	public function sanitize( $input )
	{
		$new_input = array();
		if( isset( $input['mtl-main-logo'] ) && $input['mtl-main-logo'] != 'http://') $new_input['mtl-main-logo'] = $input['mtl-main-logo'];
		if( isset( $input['mtl-currency-text']) ) $new_input['mtl-currency-text'] = $input['mtl-currency-text'];
		if( isset( $input['mtl-currency-symbol']) ) $new_input['mtl-currency-symbol'] = $input['mtl-currency-symbol'];
		if( isset( $input['mtl-allowed-drafts']) ) $new_input['mtl-allowed-drafts'] = $input['mtl-allowed-drafts'];

		if( isset( $input['mtl-show-districts'] ) ) $new_input['mtl-show-districts'] = $input['mtl-show-districts'];
		else $new_input['mtl-show-districts'] = false;
		if( isset( $input['mtl-country-source'] ) ) $new_input['mtl-country-source'] = $input['mtl-country-source'];
		if( isset( $input['mtl-state-source'] ) ) $new_input['mtl-state-source'] = $input['mtl-state-source'];
		if( isset( $input['mtl-district-source'] ) ) $new_input['mtl-district-source'] = $input['mtl-district-source'];
		
		if( isset( $input['mtl-proposal-contact-form-title'] ) ) $new_input['mtl-proposal-contact-form-title'] = $input['mtl-proposal-contact-form-title'];
		if( isset( $input['mtl-proposal-contact-form-intro'] ) ) $new_input['mtl-proposal-contact-form-intro'] = $input['mtl-proposal-contact-form-intro'];
		if( isset( $input['mtl-proposal-metadata-contents'] ) ) $new_input['mtl-proposal-metadata-contents'] = $input['mtl-proposal-metadata-contents'];
		
		if( isset( $input['mtl-recaptcha-website-key'] ) ) $new_input['mtl-recaptcha-website-key'] = $input['mtl-recaptcha-website-key'];
		if( isset( $input['mtl-recaptcha-secret-key'] ) ) $new_input['mtl-recaptcha-secret-key'] = $input['mtl-recaptcha-secret-key'];
		
		if( isset( $input['mtl-geojson-import-hints'] ) ) $new_input['mtl-geojson-import-hints'] = $input['mtl-geojson-import-hints'];
		
		if( isset( $input['mtl-center-lon'] ) ) $new_input['mtl-center-lon'] = floatval( $input['mtl-center-lon'] );
		if( isset( $input['mtl-center-lat'] ) ) $new_input['mtl-center-lat'] = floatval( $input['mtl-center-lat'] );
		if( isset( $input['mtl-standard-zoom'] ) ) {
			$new_input['mtl-standard-zoom'] = intval( $input['mtl-standard-zoom'] );
			if($new_input['mtl-standard-zoom'] < 0) $new_input['mtl-standard-zoom'] = 0;
			elseif($new_input['mtl-standard-zoom'] > 19) $new_input['mtl-standard-zoom'] = 19;
		}

		$all_categories = get_categories('show_option_none=Category&hide_empty=0&tab_index=4&taxonomy=category');
		foreach($all_categories as $single_category) {
			$catid = $single_category->term_id;

			$new_input['mtl-cat-use'.$catid] = isset( $input['mtl-cat-use'.$catid] ) ? $input['mtl-cat-use'.$catid] : 'no';

			if( isset( $input['mtl-color-cat'.$catid] ) ) $new_input['mtl-color-cat'.$catid] = $input['mtl-color-cat'.$catid];
			if( isset( $input['mtl-image-cat'.$catid] ) && $input['mtl-image-cat'.$catid] != 'http://') $new_input['mtl-image-cat'.$catid] = $input['mtl-image-cat'.$catid];
			if( isset( $input['mtl-image-selected-cat'.$catid] ) && $input['mtl-image-selected-cat'.$catid] != 'http://') $new_input['mtl-image-selected-cat'.$catid] = $input['mtl-image-selected-cat'.$catid];
			if( isset( $input['mtl-costs-cat'.$catid] ) ) $new_input['mtl-costs-cat'.$catid] = $input['mtl-costs-cat'.$catid];
			if( isset( $input['mtl-allow-others-cat'.$catid] ) ) $new_input['mtl-allow-others-cat'.$catid] = $input['mtl-allow-others-cat'.$catid];
			if( isset( $input['mtl-also-search-for-cat'.$catid] ) ) $new_input['mtl-also-search-for-cat'.$catid] = $input['mtl-also-search-for-cat'.$catid];
		}
		
		if( isset( $input['mtl-addpost-page']) ) $new_input['mtl-addpost-page'] = $input['mtl-addpost-page'];
		if( isset( $input['mtl-postlist-page']) ) $new_input['mtl-postlist-page'] = $input['mtl-postlist-page'];
		return $new_input;
	}

	// Print the Section text
	public function print_general_section_content() {
	}

	public function print_map_section_content1() {
		$mtl_options = get_option('mtl-option-name');
		echo '<p>'.__('Click on the map to set the marker to the default map center or input/paste the values to the fields below','my-transit-lines').':</p>';
		
		echo '<script type="text/javascript" src="'.get_template_directory_uri().'/openlayers/dist/ol.js"></script>'."\r\n";
		echo '<div id="mtl-admin-map-center" style=" max-width:500px; height:300px; "></div>'."\r\n";
		echo '<script type="text/javascript"> var themeUrl = "'.get_template_directory_uri().'"; var mapCenterLon = '.($mtl_options['mtl-center-lon'] ? $mtl_options['mtl-center-lon'] : '0').'; var mapCenterLat = '.($mtl_options['mtl-center-lat'] ? $mtl_options['mtl-center-lat'] : '0').'; var mapStandardZoom = '.($mtl_options['mtl-standard-zoom'] ? $mtl_options['mtl-standard-zoom'] : '6').'; </script>'."\r\n";
	}

	public function print_map_section_content2() {
	}

	public function print_categories_section_content() {
	}

	public function print_pageids_section_content() {
	}

	/** 
	 * Get the settings option array and print one of its values
	 */
	public function mtl_field_callback(array $args)
	{
		if(!isset($args['option_name']))
			return;

		$option_name = $args['option_name'];

		// field_name
		$field_name = '';
		if(isset($args['field_name'])) $field_name = $args['field_name'];

		// value
		$option = get_option($option_name);
		if($field_name) {
			$has_value = isset($option[$field_name]);
			if($has_value) $value = $option[$field_name];
		}else {
			$has_value = ($option !== false);
			$value = $option;
		}

		// type
		$type = '';
		if(isset($args['type'])) $type = $args['type'];

		// separator
		$separator = false;
		if(isset($args['separator'])) $separator = $args['separator'];

		// class
		$class = '';
		if(isset($args['class'])) $class = $args['class'];

		// options for the select input
		$options = [];
		if(isset($args['options'])) $options = $args['options'];

		// step size for number input
		$step = '';
		if(isset($args['step'])) $step = $args['step'];

		// name for the input
		$name = $option_name.($field_name ? '['.$field_name.']' : '');

		// field output by type
		if($type == 'text' || $type == 'hidden' || $type == 'number') printf( '<input'.($class != '' ? ' class="'.$class.'"' : '').' type="'.$type.'" id="'.$field_name.'" name="'.$name.'" value="%s" step="'.$step.'" />', $has_value ? esc_attr($value) : '');

		if($type == 'textarea') printf( '<textarea'.($class != '' ? ' class="'.$class.'"' : '').' id="'.$field_name.'" name="'.$name.'">%s</textarea>', $has_value ? esc_attr($value) : '');

		if($type == 'colorpicker')  printf( '<input'.($class != '' ? ' class="'.$class.'"' : '').' type="text" id="'.$field_name.'" name="'.$name.'" value="%s" class="mtl-color-picker-field" data-default-color="#000000" />', $has_value ? esc_attr($value) : '');

		if($type == 'image') printf( '<input class="upload_image '.$class.'" type="text" size="36" name="'.$name.'" value="%s" /><input class="upload_image_button" class="button" type="button" value="'.__('Select Image','my-transit-lines').'" />'.($has_value && $value ? ' &nbsp; <span style="height:30px;overflow:visible;display:inline-block"><img src="'.esc_attr($value).'" style="vertical-align:top;margin-top:-3px;max-height:60px" alt="'.__('image for this category','my-transit-lines').'" /></span>' : '').'<br />'.__('Enter URL or upload image','my-transit-lines'), $has_value ? esc_attr($value) : 'http://');

		if($type == 'checkbox') printf( '<input'.($class != '' ? ' class="'.$class.'"' : '').' type="'.$type.'" name="'.$name.'" '.( $has_value && $value == true ? 'checked' : '').' />');

		if($type == 'select') {
			$options_output = '';
			foreach($options as $option) $options_output .= '<option'.($has_value && $value == $option[0] ? ' selected' : '').' value="'.$option[0].'">'.$option[1].'</option>';
			printf( '<select'.($class != '' ? ' class="'.$class.'"' : '').' id="'.$field_name.'" name="'.$name.'" />'.$options_output.'</select>');
		}

		if($separator) echo '<hr />';
	}
}

?>