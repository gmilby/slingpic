<?php
/*
Plugin Name: Slingpic
Plugin URI: http://slingpic.com/
Description: Make it easy to share images from your website through a widget. Image sharing tool, Slingpic, makes it easy for visitors to your website to share images across social networks, email and blogging platforms. A visitor simply needs to roll over an image on your site and they can quickly share an image in two clicks. Benefit from incremental traffic from shared images and links back to your website from popular social networks like Pinterest, Facebook, Twitter, Email and blogging platforms.
Version: 4.0.0
Author: Ben Jackson
Author URI: http://slingpic.com
License: GPL2
*/
?>
<?php
/**
  * Slingpic Options page using the WordPress Settings API
  *
  * - added correct highlighting for select, radio and checkbox inputs
  * - added variable names for rapid reuse
  * - added per section description
  * 
  * This program is free software: you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation, either version 3 of the License, or
  * (at your option) any later version.
  * 
  * This program is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  * GNU General Public License for more details.
  * 
  * See <http://www.gnu.org/licenses/> for a full copy of the license.
  */

class Slingpic_Options {
	
	private $sections;
	private $checkboxes;
	private $settings;
	
	/**
	 * Construct
	 *
	 * @since 1.0
	 */
	public function __construct() {
		
		// This will keep track of the checkbox options for the validate_settings function.
		$this->checkboxes = array();
		$this->settings = array();
		$this->get_settings();
		
		$this->sections['general']      = __( 'General Settings' );
		//$this->sections['advanced']      = __( 'Advanced Settings' );
		
		add_action( 'admin_menu', array( &$this, 'add_pages' ) );
		add_action( 'admin_init', array( &$this, 'register_settings' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'scripts' ) );
		add_action( 'wp_footer', array( &$this, 'script_vars' ) );
		add_action( 'wp_print_styles', array( &$this, 'styles' ) );
		
		add_filter('plugin_action_links', array(&$this, 'add_settings_link'), 10, 2 );
		
		if ( ! get_option( 'slingpic_options' ) )
			$this->initialize_settings();
		
	}
	
	/**
	 * Add options page
	 *
	 * @since 1.0
	 */
	public function add_pages() {
		
		$admin_page = add_options_page( __( 'Slingpic Options' ), __( 'Slingpic Options' ), 'manage_options', 'slingpic-options', array( &$this, 'display_page' ) );
		
		add_action( 'admin_print_scripts-' . $admin_page, array( &$this, 'admin_scripts' ) );
		//add_action( 'admin_print_styles-' . $admin_page, array( &$this, 'admin_styles' ) );
		
	}
	
	/**
	 * Add Settings link to plugins - code from GD Star Ratings
	 */
	public function add_settings_link($links, $file) {
		
		$plugin_base = plugin_basename(__FILE__);
		 
		if ($file == $plugin_base){
			$settings_link = '<a href="options-general.php?page=slingpic-options">'.__("Settings", "slingpic-options").'</a>';
			array_unshift($links, $settings_link);
		}
		return $links;
	}	
	
	/**
	 * Create settings field
	 *
	 * @since 1.0
	 */
	public function create_setting( $args = array() ) {
		
		$defaults = array(
			'id'      => 'default_field',
			'title'   => __( 'Default Field' ),
			'desc'    => __( 'This is a default description.' ),
			'std'     => '',
			'type'    => 'text',
			'section' => 'general',
			'choices' => array(),
			'class'   => ''
		);
			
		extract( wp_parse_args( $args, $defaults ) );
		
		$field_args = array(
			'type'      => $type,
			'id'        => $id,
			'desc'      => $desc,
			'std'       => $std,
			'choices'   => $choices,
			'label_for' => $id,
			'class'     => $class
		);
		
		if ( $type == 'checkbox' )
			$this->checkboxes[] = $id;
		
		add_settings_field( $id, $title, array( $this, 'display_setting' ), 'slingpic-options', $section, $field_args );
	}
	
	/**
	 * Display options page
	 *
	 * @since 1.0
	 */
	public function display_page() {
		
		echo '<div class="wrap">
	<div class="icon32" id="icon-options-general"></div>
	<h2>' . __( 'Slingpic Options' ) . '</h2>';
		
		echo '<form action="options.php" method="post">';
		echo '<h3 style="background-color: rgba(255,102,51,0.4); padding: 10px; border-radius: 3px;">Enjoying Slingpic? Why not leave us a <a href="http://wordpress.org/support/view/plugin-reviews/slingpic">review</a> :)</h3>';
		settings_fields( 'slingpic_options' );
		echo '<div>';
		do_settings_sections( $_GET['page'] );
		
		echo '</div>
		<p class="submit"><input name="Submit" type="submit" class="button-primary" value="' . __( 'Save Changes' ) . '" /></p>
		
	</form>';
	
	/* echo "<script type=\"text/javascript\">
			jQuery(document).ready(function($) {
				$('#colourpicker').hide();
				$('#colourpicker').farbtastic('#colour');

					$('#colour').click(function() {
						$('#colourpicker').fadeIn();
					});

					$(document).mousedown(function() {
						$('#colourpicker').each(function() {
							var display = $(this).css('display');
							if ( display == 'block' )
								$(this).fadeOut();
						});
					});
			});
		</script>
</div>"; */
		
	}
	
	/**
	 * Description for section
	 *
	 * @since 1.0
	 */
	public function display_section() {
		
	}
	
	/**
	 * HTML output for each field
	 *
	 * @since 1.0
	 */
	public function display_setting( $args = array() ) {
		extract( $args );
		
		$options = get_option( 'slingpic_options' );
		
		if ( ! isset( $options[$id] ) && $type != 'checkbox' )
			$options[$id] = $std;
		elseif ( ! isset( $options[$id] ) )
			$options[$id] = 0;
		
		$field_class = '';
		if ( $class != '' )
			$field_class = ' ' . $class;
		
		switch ( $type ) {
			
			case 'heading':
				echo '</td></tr><tr valign="top"><td colspan="2"><h4>' . $desc . '</h4>';
				break;
			
			case 'checkbox':
				
				echo '<input class="checkbox' . $field_class . '" type="checkbox" id="' . $id . '" name="slingpic_options[' . $id . ']" value="1" ' . checked( $options[$id], 1, false ) . ' /> <label for="' . $id . '">' . $desc . '</label>';
				
				break;

			case 'social_choice':
				
				foreach ( $choices as $value => $label )
					echo '<input class="checkbox' . $field_class . '" type="checkbox" id="' . $id . '[' . $value . ']" name="slingpic_options[' . $id . '][' . $value . ']" value="1" ' . checked( $options[$id][$value], 1, false ) . ' /> <label for="' . $id . '_' . $value . '">' . $label . '</label><br>';
				
				if ( $desc != '' )
					echo '<br /><span class="description">' . $desc . '</span>';				
				
				break;
			
			case 'select':
				echo '<select class="select' . $field_class . '" name="slingpic_options[' . $id . ']">';
				
				foreach ( $choices as $value => $label )
					echo '<option value="' . esc_attr( $value ) . '"' . selected( $options[$id], $value, false ) . '>' . $label . '</option>';
				
				echo '</select>';
				
				if ( $desc != '' )
					echo '<br /><span class="description">' . $desc . '</span>';
				
				break;
			
			case 'radio':
				$i = 0;
				foreach ( $choices as $value => $label ) {
					if($id == 'themes'){
						echo '<input class="radio' . $field_class . '" type="radio" name="slingpic_options[' . $id . ']" id="' . $id . $i . '" value="' . esc_attr( $value ) . '" ' . checked( $options[$id], $value, false ) . '> <label style="display: inline-block; border-bottom: 1px solid gray; margin-bottom: 15px;" for="' . $id . $i . '"><img src="/wp-content/plugins/slingpic/img/'.$value.'.jpg" /></label>';
					}else{
						echo '<input class="radio' . $field_class . '" type="radio" name="slingpic_options[' . $id . ']" id="' . $id . $i . '" value="' . esc_attr( $value ) . '" ' . checked( $options[$id], $value, false ) . '> <label for="' . $id . $i . '">' . $label . '</label>';
					}
					
					if ( $i < count( $options ) - 1 )
						echo '<br />';
					$i++;
				}
				
				if ( $desc != '' )
					echo '<br /><span class="description">' . $desc . '</span>';
				
				break;
			
			case 'textarea':
				echo '<textarea class="' . $field_class . '" id="' . $id . '" name="slingpic_options[' . $id . ']" placeholder="' . $std . '" rows="5" cols="30">' . wp_htmledit_pre( $options[$id] ) . '</textarea>';
				
				if ( $desc != '' )
					echo '<br /><span class="description">' . $desc . '</span>';
				
				break;
			
			case 'password':
				echo '<input class="regular-text' . $field_class . '" type="password" id="' . $id . '" name="slingpic_options[' . $id . ']" value="' . esc_attr( $options[$id] ) . '" />';
				
				if ( $desc != '' )
					echo '<br /><span class="description">' . $desc . '</span>';
				
				break;
			
			case 'text':
			default:
		 		echo '<input class="regular-text' . $field_class . '" type="text" id="' . $id . '" name="slingpic_options[' . $id . ']" placeholder="' . $std . '" value="' . esc_attr( $options[$id] ) . '" />';
		 		
		 		if ( $desc != '' )
		 			echo '<br /><span class="description">' . $desc . '</span>';
		 		
		 		break;

				case 'colour':
			default:
		 		echo '<input class="colourpicker' . $field_class . '" type="text" id="' . $id . '" name="slingpic_options[' . $id . ']" placeholder="' . $std . '" value="' . esc_attr( $options[$id] ) . '" />
					<div id="colourpicker"></div>';
		 		
		 		if ( $desc != '' )
		 			echo '<br /><span class="description">' . $desc . '</span>';
		 		
		 		break;
		 	
		}
		
	}
	
	/**
	 * Settings and defaults
	 * 
	 * @since 1.0
	 */
	public function get_settings() {
		
		/* General Settings
		===========================================*/
	
		 $this->settings['speedOver'] = array(
			'section' => 'advanced',
			'title'   => __( 'In Speed' ),
			'desc'    => __( 'How fast should the bar appear?' ),
			'type'    => 'radio',
			'std'     => 'slow',
			'choices' => array(
				'fast' => 'Fast',
				'slow' => 'Slow',
			)
		);

		 $this->settings['speedOver'] = array(
			'section' => 'advanced',
			'title'   => __( 'In Speed' ),
			'desc'    => __( 'How fast should the bar appear?' ),
			'type'    => 'radio',
			'std'     => 'slow',
			'choices' => array(
				'fast' => 'Fast',
				'slow' => 'Slow',
			)
		);
		
		 $this->settings['speedOut'] = array(
			'section' => 'advanced',
			'title'   => __( 'Out Speed' ),
			'desc'    => __( 'How fast should the bar disappear?' ),
			'type'    => 'radio',
			'std'     => 'slow',
			'choices' => array(
				'fast' => 'Fast',
				'slow' => 'Slow',
			)
		);
				
		$this->settings['hideDelay'] = array(
			'section' => 'advanced',
			'title'   => __( 'Delay appearance' ),
			'desc'    => __( 'How long should the animation delay for after hover?' ),
			'type'    => 'text',
			'std'     => '0',
		);
		
		 $this->settings['animation'] = array(
			'section' => 'advanced',
			'title'   => __( 'Animation Type' ),
			'type'    => 'radio',
			'desc'    => __( 'Choose how the bar should appear' ),
			'std'     => 'slide',
			'choices' => array(
				'fade' => 'Fade',
				'slide' => 'Slide',
				'always-on' => 'Always On'
			)
		);	

		$this->settings['animationEffects'] = array(
			'section' => 'advanced',
			'title'   => __( 'Animation Effects' ),
			'desc'    => __( 'Turn animations on or off.' ),
			'type'    => 'radio',
			'std'     => '1',
			'choices' => array(
				'0' => 'Off',
				'1' => 'On'
			)
		);				
	
		 $this->settings['opacity'] = array(
			'section' => 'advanced',
			'title'   => __( 'Bar Opacity' ),
			'desc'    => __( 'How transparent should the bar be?' ),
			'type'    => 'radio',
			'std'     => '0.45',
			'choices' => array(
				'0.45' => '45%',
				'0.75' => '75%',
			)
		);			
	
		 $this->settings['position'] = array(
			'section' => 'advanced',
			'title'   => __( 'Position of Bar' ),
			'desc'    => __( 'Should the bar appear at the top or bottom?' ),
			'type'    => 'radio',
			'std'     => 'bottom',
			'choices' => array(
				'top' => 'Top',
				'bottom' => 'Bottom',
			)
		);		
		
		$this->settings['sliderOverlayColor'] = array(
			'section' => 'advanced',
			'title'   => __( 'Overlay Colour' ),
			'desc'    => __( 'Choose the colour of the bar which appears over the image.' ),
			'std'     => '#000000',
			'type'    => 'colour',
		);
		
		$this->settings['popupBox'] = array(
			'section' => 'advanced',
			'title'   => __( 'Privacy Popup Speed' ),
			'desc'    => __( 'How fast should the privacy popup appear?' ),
			'std'     => 'fast',
			'type'    => 'radio',
			'choices' => array(
				'slow' => 'Slow',
				'fast' => 'Fast',
			),
		);	

		$this->settings['showShare'] = array(
			'section' => 'general',
			'title'   => __( 'Turn on image sharing?' ),
			'desc'    => __( 'Quickly activate or deactivate image sharing.' ),
			'std'     => 'true',
			'type'    => 'radio',
			'choices' => array(
				'true' => 'On',
				'false' => 'Off',
			),
		);

		$this->settings['autoShowShare'] = array(
			'section' => 'general',
			'title'   => __( 'Automatically expand the sharing tools?' ),
			'desc'    => __( 'By default your visitors are required to click the share button to view the sharing options, however by turning on autoShowShare the sharing options will be visible when the vistor hovers over the image.' ),
			'std'     => 'false',
			'type'    => 'radio',
			'choices' => array(
				'true' => 'On',
				'false' => 'Off',
			),
		);

		$this->settings['copyright'] = array(
			'section' => 'general',
			'title'   => __( 'Add a personalised copyright message' ),
			'desc'    => __( 'When enabled Slingpic will look at the Alt tag of the image and append its content (prefixed by the © symbol) to the end of the generated share message.' ),
			'std'     => 'false',
			'type'    => 'radio',
			'choices' => array(
				'true' => 'On',
				'false' => 'Off',
			),
		);

		$this->settings['share_sites_default'] = array(
			'section' => 'general',
			'title'   => __( 'Primary Share Options' ),
			'desc'    => __( 'Choose the main sharing options here.' ),
			'type'    => 'social_choice',
			'choices' => array (
				'facebook'		=> 'Facebook',
				'twitter' 		=> 'Twitter',
				'tumblr' 		=> 'Tumblr',
				'email' 		=> 'Email',
				'delicious' 	=> 'Delicious',
				'linkedin' 		=> 'Linkedin',
				'stumbleupon' 	=> 'Stumbleupon',
				'reddit' 		=> 'Reddit',
				'friendfeed' 	=> 'Friendfeed',
				'digg' 			=> 'Digg',
				'pinterest' 	=> 'Pinterest',
				'print' 		=> 'Print',
				'myspace' 		=> 'MySpace'
			),
			'std'     =>  array (
				'twitter' 	=> 1,
				'facebook' 	=> 1,
				'email' 	=> 1,
				'pinterest'	=> 1
			)
		);		
		
		$this->settings['share_sites_box'] = array(
			'section' => 'general',
			'title'   => __( 'Secondary Share Options' ),
			'desc'    => __( 'Choose the sharing options that appear when you click "more".' ),
			'type'    => 'social_choice',
			'choices' => array (
				'facebook'		=> 'Facebook',
				'twitter' 		=> 'Twitter',
				'tumblr' 		=> 'Tumblr',
				'email' 		=> 'Email',
				'delicious' 	=> 'Delicious',
				'linkedin' 		=> 'Linkedin',
				'stumbleupon' 	=> 'Stumbleupon',
				'reddit' 		=> 'Reddit',
				'friendfeed' 	=> 'Friendfeed',
				'digg' 			=> 'Digg',
				'pinterest' 	=> 'Pinterest',
				'print' 		=> 'Print',
				'myspace' 		=> 'MySpace'
			),
			'std'     =>  array (
				'twitter' 	=> 1,
				'facebook' 	=> 1,
				'email' 	=> 1,
				'pinterest' => 1
			)
		);

		$this->settings['themes'] = array(
			'section' => 'general',
			'title'   => __( 'Select a theme' ),
			'desc'    => __( 'Choose the theme which best matches your site' ),
			'std'     => 'default',
			'type'    => 'radio',
			'choices' => array(
				'default' => 'Default',
				'subtle' => 'Subtle',
				'dark' => 'Dark'
			)
		);

		$this->settings['context'] = array(
			'section' => 'general',
			'title'   => __( 'Enable context awareness?' ),
			'desc'    => __( 'Allow Slingpic to use the content around the image to provide a better sharing template' ),
			'std'     => 'true',
			'type'    => 'radio',
			'choices' => array(
				'true' => 'On',
				'false' => 'Off',
			),
		);

		$this->settings['alignShare'] = array(
			'section' => 'general',
			'title'   => __( 'Left or Right Align?' ),
			'desc'    => __( 'Align the sharing options to the right or the left.' ),
			'std'     => 'left',
			'type'    => 'radio',
			'choices' => array(
				'left' => 'Left',
				'right' => 'Right'
			)
		);	

		$this->settings['show'] = array(
			'section' => 'general',
			'title'   => __( 'Choose which images show Slingpic based on a class name.' ),
			'desc'    => __( 'This option is very useful if your blog is using other image related plugins (overrides minShareWidth and minShareHeight)' ),
			'std'     => '',
			'type'    => 'text'
		);

		$this->settings['dontShow'] = array(
			'section' => 'general',
			'title'   => __( 'Filter out certain image classes?' ),
			'desc'    => __( 'The image classes that the plugin will filter out. Enter a comma separated list.' ),
			'std'     => 'dontshow',
			'type'    => 'text'
		);

		$this->settings['noScroll'] = array(
			'section' => 'general',
			'title'   => __( 'Disable auto scroll?' ),
			'desc'    => __( 'If you would not like Slingpic to scroll your visitors to the relevant shared image set this option to True' ),
			'std'     => 'false',
			'type'    => 'radio',
			'choices' => array(
				'true' => 'On',
				'false' => 'Off',
			),
		);

		$this->settings['minShareWidth'] = array(
			'section' => 'general',
			'title'   => __( 'Minimum image width?' ),
			'desc'    => __( 'The minimum width of an image in pixels to add the share tool to.' ),
			'std'     => '200',
			'type'    => 'text',
		);	

		$this->settings['minShareHeight'] = array(
			'section' => 'general',
			'title'   => __( 'Minimum image height?' ),
			'desc'    => __( 'The minimum height of an image in pixels to add the share tool to.' ),
			'std'     => '200',
			'type'    => 'text',
		);
				
		/* Reset
		===========================================*/
		
		/* $this->settings['reset_slingpic'] = array(
			'section' => 'reset',
			'title'   => __( 'Reset Slingpic' ),
			'type'    => 'checkbox',
			'std'     => 0,
			'class'   => 'warning', // Custom class for CSS
			'desc'    => __( 'Check this box and click "Save Changes" below to reset options to their defaults.' )
		); */
	}
	
	/**
	 * Initialize settings to their default values
	 * 
	 * @since 1.0
	 */
	public function initialize_settings() {
		
		$default_settings = array();
		foreach ( $this->settings as $id => $setting ) {
			if ( $setting['type'] != 'heading' )
				$default_settings[$id] = $setting['std'];
		}
		
		update_option( 'slingpic_options', $default_settings );
		
	}
	
	/**
	* Register settings
	*
	* @since 1.0
	*/
	public function register_settings() {
		
		register_setting( 'slingpic_options', 'slingpic_options', array ( &$this, 'validate_settings' ) );
		
		foreach ( $this->sections as $slug => $title ) {
				add_settings_section( $slug, $title, array( &$this, 'display_section' ), 'slingpic-options' );
		}
		
		$this->get_settings();
		
		foreach ( $this->settings as $id => $setting ) {
			$setting['id'] = $id;
			$this->create_setting( $setting );
		}
		
	}
	
	/**
	* Farbtastic
	*
	* @since 1.0
	*/
	public function admin_scripts() {
		
		//wp_print_scripts( 'farbtastic' );

	}

	/**
	* Frontend Scripts
	*
	* @since 1.0
	*/
	public function scripts() {
		
		wp_enqueue_script( 'jquery' );
	
	}

	public function script_vars() {

		$shareOptions = get_option('slingpic_options');
		$shareDefault = $shareOptions['share_sites_default'];
		$shareBox = $shareOptions['share_sites_box']; ?>
		
		<script src="http://cdn.slingpic.com/js/slingpic.plugin.js?v=4"></script>
		<script>

			jQuery(window).load(function(){
				jQuery("img").slingPic({
					primary_links: [<?php
						$counter = 0;
						foreach ( $shareDefault as $site => $value ){
							if($counter > 0){
								echo ", ";
							}
							echo '"' . $site . '"';
							$counter++;
						}
					?>],
					secondary_links: [<?php
						$counter = 0;
						foreach ( $shareBox as $site => $value ){
							if($counter > 0){
								echo ", ";
							}
							echo '"' . $site . '"';
							$counter++;
						}
					?>],
					theme: '<?php echo ($shareOptions['themes'] == 'default' ? '' : $shareOptions['themes']); ?>',
					minShareWidth: <?php echo (!$shareOptions['minShareWidth'] || $shareOptions['minShareWidth'] == '' ? 0 : $shareOptions['minShareWidth']); ?>, // Minimum img width to show share
					minShareHeight: <?php echo (!$shareOptions['minShareHeight'] || $shareOptions['minShareHeight'] == '' ? 0 : $shareOptions['minShareHeight']); ?>, // Minimum img height to show share
					alignShare: '<?php echo $shareOptions['alignShare'] ;?>', // 'left' or 'right' only
					context: <?php 
						if($shareOptions['context']) {
							echo $shareOptions['context'];
						}
						else
						{
							echo "false";
						}
					?>,
					copyright: <?php
						if($shareOptions['copyright']) {
							echo $shareOptions['copyright'];
						}
						else
						{
							echo "false";
						}
					?>,
					noScroll: <?php
						if($shareOptions['noScroll']){
							echo $shareOptions['noScroll'];
						}
						else
						{
							echo "false";
						}
					?>,
					autoShowShare: <?php
						if($shareOptions['autoShowShare']){
							echo $shareOptions['autoShowShare'];
						}
						else
						{
							echo "false";
						}
					?>,
					showShare: <?php
						if($shareOptions['showShare']){
							echo $shareOptions['showShare'];
						}
						else
						{
							echo "false";
						}
					?>,
					show: '<?php echo $shareOptions['show']; ?>', // Ability to filter out certain images again (only use a class)
					dontShow: '<?php echo $shareOptions['dontShow']; ?>' // Ability to filter out certain images again (only use a class)
				});
			});

		</script>

		<?php	
	}


	
	/**
	* Styling for the options page
	*
	* @since 1.0
	*/
	public function admin_styles() {
		
		//wp_register_style( 'slingpic-admin', WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . 'slingpic-options.css' );
		//wp_enqueue_style( 'slingpic-admin' );
		//wp_enqueue_style( 'farbtastic' );
		
	}

	/**
	* Styling for the front
	*
	* @since 1.0
	*/
	public function styles() {

	}
	
	/**
	* Validate settings
	*
	* @since 1.0
	*/
	public function validate_settings( $input ) {
		
		if ( ! isset( $input['reset_slingpic'] ) ) {
			$options = get_option( 'slingpic_options' );
			
			foreach ( $this->checkboxes as $id ) {
				if ( isset( $options[$id] ) && ! isset( $input[$id] ) )
					unset( $options[$id] );
			}
			
			return $input;
		}
		return false;
		
	}
	
}

$theme_options = new Slingpic_Options();

function slingpic_option( $option ) {
	$options = get_option( 'slingpic_options' );
	if ( isset( $options[$option] ) )
		return $options[$option];
	else
		return false;
}
