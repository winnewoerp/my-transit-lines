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
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

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
            array( $this, 'mtl_submenu_page_intro' )
        );
    }
	
	/**
	 * Add MTL subpages
	 */
	public function add_mtl_submenu_pages()
	 {
		// Creates new menu subpages
		add_submenu_page(
			'mtl_settings_page',
			__('Instructions','my-transit-lines'),
			__('Instructions','my-transit-lines'),
			'edit_posts',
			'mtl-instructions',
			array( $this, 'mtl_submenu_page_instructions')
		);
		add_submenu_page(
			'mtl_settings_page',
			__('General settings','my-transit-lines'),
			__('General settings','my-transit-lines'),
			'edit_posts',
			'mtl-general-settings',
			array( $this, 'mtl_submenu_page_general_settings')
		);
		add_submenu_page(
			'mtl_settings_page',
			__('Map and category settings','my-transit-lines'),
			__('Map and category settings','my-transit-lines'),
			'edit_posts',
			'mtl-settings',
			array( $this, 'mtl_submenu_page_settings')
		);
	}
	
    /**
     * MTL Admin Page page callback
     */
    public function mtl_menu_page()
    {
        ?>
        <div class="wrap">
            <h1 class="mtl-admin-page-title"><span class="logo"></span> <?php echo wp_get_theme(); ?></h1>          
        </div>
        <?php
    }
	
	/**
     * MTL Admin Settings instructions subpage callback
     */
    public function mtl_submenu_page_instructions()
    {
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
        $this->options = get_option( 'mtl-option-name3' ); ?>
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
				do_settings_sections( 'mtl-settings-general');
				submit_button(); 
			?>
			</form>
        </div>
        <?php
    }
	
	/**
     * MTL Admin Settings intro subpage callback
     */
	public function mtl_submenu_page_intro()
    {
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
     * MTL Admin Settings settings subpage callback
     */
    public function mtl_submenu_page_settings()
    {
        // Set class property
        $this->options = get_option( 'mtl-option-name' );
        ?>
        <div class="wrap">
            <h1 class="mtl-admin-page-title"><span class="logo"></span> <?php echo wp_get_theme(); ?></h1>
            <h2><?php _e('Settings','my-transit-lines'); ?></h2>             
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'mtl-settings-group-map1' ); 
				settings_fields( 'mtl-settings-group-map2' );  
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
        add_settings_section('mtl-settings-group-general', __('Logo Settings','my-transit-lines'), array( $this, 'print_general_section_content' ), 'mtl-settings-general');  
        add_settings_field('mtl-main-logo', __('Load the main site logo','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-settings-general','mtl-settings-group-general',array('field_name' => 'mtl-main-logo','type' => 'image','option_name'=>'mtl-option-name3')); 

		// settings section general 2
        add_settings_section('mtl-settings-group-general2', __('Other settings','my-transit-lines'), array( $this, 'print_general_section_content' ), 'mtl-settings-general');
		add_settings_field('mtl-allowed-drafts', __('Number of allowed drafts','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-settings-general','mtl-settings-group-general2',array('field_name' => 'mtl-allowed-drafts','type' => 'number','option_name'=>'mtl-option-name3'));
		add_settings_field('mtl-show-districts', __('Show administrative subdivision selection','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-settings-general','mtl-settings-group-general2',array('field_name' => 'mtl-show-districts','type' => 'checkbox','option_name'=>'mtl-option-name3'));
		add_settings_field('mtl-country-source', __('Country areas file', 'my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-settings-general', 'mtl-settings-group-general2', array('field_name' => 'mtl-country-source','type' => 'text','option_name'=>'mtl-option-name3'));
		add_settings_field('mtl-state-source', __('State areas file', 'my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-settings-general', 'mtl-settings-group-general2', array('field_name' => 'mtl-state-source','type' => 'text','option_name'=>'mtl-option-name3'));
		add_settings_field('mtl-district-source', __('District areas file', 'my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-settings-general', 'mtl-settings-group-general2', array('field_name' => 'mtl-district-source','type' => 'text','option_name'=>'mtl-option-name3'));
		
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
			$catname = $single_category->name;
			add_settings_field('mtl-use-cat'.$catid, sprintf(__('Use category <strong>%s</strong>','my-transit-lines'),$catname), array( $this, 'mtl_field_callback' ), 'mtl-settings','mtl-settings-group-categories', array('field_name' => 'mtl-use-cat'.$catid,'type' => 'checkbox','class' => 'category-checkbox','option_name'=>'mtl-option-name'));  
			add_settings_field('mtl-color-cat'.$catid, sprintf(__('Color for category <strong>%s</strong>','my-transit-lines'),$catname), array( $this, 'mtl_field_callback' ), 'mtl-settings','mtl-settings-group-categories', array('field_name' => 'mtl-color-cat'.$catid,'type' => 'colorpicker','option_name'=>'mtl-option-name'));  
			add_settings_field('mtl-image-cat'.$catid, sprintf(__('Map Icon for category <strong>%s</strong>','my-transit-lines'),$catname), array( $this, 'mtl_field_callback' ), 'mtl-settings','mtl-settings-group-categories', array('field_name' => 'mtl-image-cat'.$catid,'type' => 'image','option_name'=>'mtl-option-name'));      
			add_settings_field('mtl-image-selected-cat'.$catid, sprintf(__('Map icon (selected) for category <strong>%s</strong>','my-transit-lines'),$catname), array( $this, 'mtl_field_callback' ), 'mtl-settings','mtl-settings-group-categories', array('field_name' => 'mtl-image-selected-cat'.$catid,'type' => 'image','option_name'=>'mtl-option-name','separator' => true));      
		}
		
		// settings section page IDs
		add_settings_section('mtl-settings-group-pageids', __('Page IDs Settings','my-transit-lines'), array( $this, 'print_pageids_section_content' ), 'mtl-settings');  
		add_settings_field('mtl-addpost-page', __('Page ID for page to add proposal','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-settings','mtl-settings-group-pageids',array('field_name' => 'mtl-addpost-page','type' => 'text','option_name'=>'mtl-option-name'));      
		add_settings_field('mtl-postlist-page', __('Page ID for proposal list page','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-settings','mtl-settings-group-pageids',array('field_name' => 'mtl-postlist-page','type' => 'text','option_name'=>'mtl-option-name'));  
		add_settings_field('mtl-postmap-page', __('Page ID for proposal map page','my-transit-lines'), array($this, 'mtl_field_callback' ), 'mtl-settings','mtl-settings-group-pageids',array('field_name' => 'mtl-postmap-page','type' => 'text','option_name'=>'mtl-option-name'));

		// settings section general texts
        add_settings_section('mtl-settings-group-general3', __('General texts settings','my-transit-lines'), array( $this, 'print_general_section_content' ), 'mtl-settings-general');  
		add_settings_field('mtl-proposal-contact-form-title', __('Title for intro of proposal contact form','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-settings-general','mtl-settings-group-general3',array('field_name' => 'mtl-proposal-contact-form-title','option_name'=>'mtl-option-name3','type' => 'text'));
		add_settings_field('mtl-proposal-contact-form-intro', __('Intro text for proposal contact form','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-settings-general','mtl-settings-group-general3',array('field_name' => 'mtl-proposal-contact-form-intro','option_name'=>'mtl-option-name3','type' => 'textarea'));

		// settings section reCAPTCHA texts
        add_settings_section('mtl-settings-group-general4', __('ReCAPTCHA settings','my-transit-lines'), array( $this, 'print_general_section_content' ), 'mtl-settings-general');  
		add_settings_field('mtl-recaptcha-website-key', __('ReCAPTCHA website key','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-settings-general','mtl-settings-group-general4',array('field_name' => 'mtl-recaptcha-website-key','option_name'=>'mtl-option-name3','type' => 'text'));
		add_settings_field('mtl-recaptcha-secret-key', __('ReCAPTCHA secret key','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-settings-general','mtl-settings-group-general4',array('field_name' => 'mtl-recaptcha-secret-key','option_name'=>'mtl-option-name3','type' => 'text'));
		
		// settings section import texts
        add_settings_section('mtl-settings-group-general5', __('GeoJSON import settings','my-transit-lines'), array( $this, 'print_general_section_content' ), 'mtl-settings-general');  
		add_settings_field('mtl-geojson-import-hints', __('GeoJSON import hints','my-transit-lines'), array( $this, 'mtl_field_callback' ), 'mtl-settings-general','mtl-settings-group-general5',array('field_name' => 'mtl-geojson-import-hints','option_name'=>'mtl-option-name3','type' => 'textarea'));
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
		if( isset( $input['mtl-allowed-drafts']) ) $new_input['mtl-allowed-drafts'] = $input['mtl-allowed-drafts'];

		if( isset( $input['mtl-show-districts'] ) ) $new_input['mtl-show-districts'] = $input['mtl-show-districts'];
		if( isset( $input['mtl-country-source'] ) ) $new_input['mtl-country-source'] = $input['mtl-country-source'];
		if( isset( $input['mtl-state-source'] ) ) $new_input['mtl-state-source'] = $input['mtl-state-source'];
		if( isset( $input['mtl-district-source'] ) ) $new_input['mtl-district-source'] = $input['mtl-district-source'];
		
		if( isset( $input['mtl-proposal-contact-form-title'] ) ) $new_input['mtl-proposal-contact-form-title'] = $input['mtl-proposal-contact-form-title'];
		if( isset( $input['mtl-proposal-contact-form-intro'] ) ) $new_input['mtl-proposal-contact-form-intro'] = $input['mtl-proposal-contact-form-intro'];
		
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
			if( isset( $input['mtl-use-cat'.$catid] ) ) $new_input['mtl-use-cat'.$catid] = $input['mtl-use-cat'.$catid];
			if( isset( $input['mtl-color-cat'.$catid] ) ) $new_input['mtl-color-cat'.$catid] = $input['mtl-color-cat'.$catid];
			if( isset( $input['mtl-image-cat'.$catid] ) && $input['mtl-image-cat'.$catid] != 'http://') $new_input['mtl-image-cat'.$catid] = $input['mtl-image-cat'.$catid];
			if( isset( $input['mtl-image-selected-cat'.$catid] ) && $input['mtl-image-selected-cat'.$catid] != 'http://') $new_input['mtl-image-selected-cat'.$catid] = $input['mtl-image-selected-cat'.$catid];
		}
		if( isset( $input['mtl-addpost-page']) ) $new_input['mtl-addpost-page'] = $input['mtl-addpost-page'];
		if( isset( $input['mtl-postlist-page']) ) $new_input['mtl-postlist-page'] = $input['mtl-postlist-page'];
		if( isset( $input['mtl-postmap-page']) ) $new_input['mtl-postmap-page'] = $input['mtl-postmap-page'];
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
	
	public function print_project_phase_section_content() {
		echo '<p>'.__('<strong>Note:</strong> You should start with the collecting phase and switch to Rating phase after approx. 6 months.','my-transit-lines');
	}
	
	public function print_pageids_section_content() {
	}

    /** 
     * Get the settings option array and print one of its values
     */
    public function mtl_field_callback(array $args)
    {
		// option_name
		$option_name = '';
		if(isset($args['option_name'])) $option_name = $args['option_name'];
		
		// field_name
		$field_name = '';
		if(isset($args['field_name'])) $field_name = $args['field_name'];
		
		// type
		$type = '';
		if(isset($args['type'])) $type = $args['type'];
		
		// separator
		$separator = '';
		if(isset($args['separator'])) $separator = $args['separator'];
		
		// class
		$class = '';
		if(isset($args['class'])) $class = $args['class'];
		
		// options
		$items = '';
		if(isset($args['options'])) $options= $args['options'];
		
		// field output by type
		if($type == 'text' || $type == 'hidden' || $type == 'number') printf( '<input'.($class != '' ? ' class="'.$class.'"' : '').' type="'.$type.'" id="'.$field_name.'" name="'.$option_name.'['.$field_name.']" value="%s" />', isset( $this->options[$field_name] ) ? esc_attr( $this->options[$field_name]) : '');
		
		if($type == 'textarea') printf( '<textarea'.($class != '' ? ' class="'.$class.'"' : '').' id="'.$field_name.'" name="'.$option_name.'['.$field_name.']">%s</textarea>', isset( $this->options[$field_name] ) ? esc_attr( $this->options[$field_name]) : '');
		
		if($type == 'colorpicker')  printf( '<input'.($class != '' ? ' class="'.$class.'"' : '').' type="text" id="'.$field_name.'" name="'.$option_name.'['.$field_name.']" value="%s" class="mtl-color-picker-field" data-default-color="#000000" />', isset( $this->options[$field_name] ) ? esc_attr( $this->options[$field_name]) : ''); 
		
		if($type == 'image') printf( '<input class="upload_image '.$class.'" type="text" size="36" name="'.$option_name.'['.$field_name.']" value="%s" /><input class="upload_image_button" class="button" type="button" value="'.__('Select Image','my-transit-lines').'" />'.($this->options[$field_name] ? ' &nbsp; <span style="height:30px;overflow:visible;display:inline-block"><img src="'.esc_attr( $this->options[$field_name]).'" style="vertical-align:top;margin-top:-3px;max-height:60px" alt="'.__('image for this category','my-transit-lines').'" /></span>' : '').'<br />'.__('Enter URL or upload image','my-transit-lines'),  isset( $this->options[$field_name] ) ? esc_attr( $this->options[$field_name]) : 'http://'); 
		
		if($type == 'checkbox') printf( '<input'.($class != '' ? ' class="'.$class.'"' : '').' type="'.$type.'" name="'.$option_name.'['.$field_name.']" '.( $this->options[$field_name] == true ? 'checked="checked"' : '').' />');
		
		if($type == 'select') {
			$options_output = '';
			foreach($options as $option) $options_output .= '<option'.($this->options[$field_name] == $option[0] ? ' selected="selected"' : '').' value="'.$option[0].'">'.$option[1].'</option>';
			printf( '<select'.($class != '' ? ' class="'.$class.'"' : '').' id="'.$field_name.'" name="'.$option_name.'['.$field_name.']" />'.$options_output.'</select>');
		}
		
		if($separator==true) echo '<hr />';
	}
	
}

?>