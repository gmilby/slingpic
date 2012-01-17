<?php
/*
Plugin Name: Slingpic
Plugin URI: http://slingpic.com/
Description: Make it easy to share images from your website. Slingpic makes it easy for visitors to your website to share images across social networks, email and blogging platforms. A visitor simply needs to roll over an image on your site and they can quickly share an image in two clicks. Benefit from incremental traffic from shared images and links back to your website from popular social networks like Facebook and Twitter, Email  and blogging platforms.
Version: 1.0
Author: Alex Cragg
Author URI: http://slingpic.com
License: GPL2
*/
?>
<?php
/**
  * SlingPic Options page using the WordPress Settings API
  * based on the work by Aliso the Geek
  * http://alisothegeek.com/
  *
  * Copyright (C) 2011 Alex Cragg.
  * http://neverbland.com
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
		add_action( 'wp_head', array( &$this, 'script_vars' ) );
		add_action( 'wp_print_styles', array( &$this, 'styles' ) );
		
		if ( ! get_option( 'slingpic_options' ) )
			$this->initialize_settings();
		
	}
	
	/**
	 * Add options page
	 *
	 * @since 1.0
	 */
	public function add_pages() {
		
		$admin_page = add_options_page( __( 'SlingPic Options' ), __( 'SlingPic Options' ), 'manage_options', 'slingpic-options', array( &$this, 'display_page' ) );
		
		add_action( 'admin_print_scripts-' . $admin_page, array( &$this, 'admin_scripts' ) );
		//add_action( 'admin_print_styles-' . $admin_page, array( &$this, 'admin_styles' ) );
		
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
	<h2>' . __( 'SlingPic Options' ) . '</h2>';
		
		echo '<form action="options.php" method="post">';
	
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
					echo '<input class="radio' . $field_class . '" type="radio" name="slingpic_options[' . $id . ']" id="' . $id . $i . '" value="' . esc_attr( $value ) . '" ' . checked( $options[$id], $value, false ) . '> <label for="' . $id . $i . '">' . $label . '</label>';
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
			'std'     => '',
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
			'std'     => '',
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
			'std'     => '',
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
			'title'   => __( 'Animation Type' ),
			'type'    => 'radio',
			'desc'    => __( 'Choose how the bar should appear' ),
			'std'     => 'slide',
			'choices' => array(
				'fade' => 'Fade',
				'slide' => 'Slide',
				'always-on' => 'Always On'
			),
			'section' => 'advanced',
		);	

		$this->settings['animationEffects'] = array(
			'title'   => __( 'Animation Effects' ),
			'desc'    => __( 'Turn animations on or off.' ),
			'type'    => 'radio',
			'std'     => '1',
			'choices' => array(
				'0' => 'Off',
				'1' => 'On'
			),
			'section' => 'advanced',
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
			'title'   => __( 'Position of Bar' ),
			'desc'    => __( 'Should the bar appear at the top or bottom?' ),
			'type'    => 'radio',
			'std'     => 'bottom',
			'choices' => array(
				'top' => 'Top',
				'bottom' => 'Bottom',
			),
			'section' => 'advanced',
		);		
		
		$this->settings['sliderOverlayColor'] = array(
			'title'   => __( 'Overlay Colour' ),
			'desc'    => __( 'Choose the colour of the bar which appears over the image.' ),
			'std'     => '#000000',
			'type'    => 'colour',
			'section' => 'advanced'
		);
		
		$this->settings['popupBox'] = array(
			'title'   => __( 'Privacy Popup Speed' ),
			'desc'    => __( 'How fast should the privacy popup appear?' ),
			'std'     => 'fast',
			'type'    => 'radio',
			'choices' => array(
				'slow' => 'Slow',
				'fast' => 'Fast',
			),
			'section' => 'advanced',
		);	

		$this->settings['shareClasses'] = array(
			'title'   => __( 'Apply to which Image Class?' ),
			'desc'    => __( 'The image class that the plugin will work on' ),
			'std'     => 'img',
			'type'    => 'text',
			'section' => 'general'
		);	
		
		$this->settings['share_sites_default'] = array(
			'section' => 'general',
			'title'   => __( 'Default Share Options' ),
			'desc'    => __( 'Choose the main 3 sharing options here.' ),
			'type'    => 'social_choice',
			'choices' => array (
				'twitter' 	=> 'Twitter',
				'facebook' 	=> 'Facebook',
				'email' 	=> 'Email',
				'delicious' => 'Delicious',
				'designfloat' => 'Designfloat',
				'digg' 		=> 'Digg',
				'friendfeed' => 'Friendfeed',
				'linkedin' 	=> 'Linkedin',
				'myspace' 	=> 'Myspace',
				'netvibes' 	=> 'Netvibes',
				'reddit' 	=> 'Reddit',
				'stumbleupon' => 'Stumbleupon',
				'technorati' => 'Technorati',
				'yahoobuzz' => 'Yahoobuzz',
			),
			'std'     => 0
		);		
		
		$this->settings['share_sites_box'] = array(
			'section' => 'general',
			'title'   => __( 'All Share Options' ),
			'desc'    => __( 'Choose the sharing options that appear when you click share.' ),
			'type'    => 'social_choice',
			'choices' => array (
				'twitter' 	=> 'Twitter',
				'facebook' 	=> 'Facebook',
				'email' 	=> 'Email',
				'delicious' => 'Delicious',
				'designfloat' => 'Designfloat',
				'digg' 		=> 'Digg',
				'friendfeed' => 'Friendfeed',
				'linkedin' 	=> 'Linkedin',
				'myspace' 	=> 'Myspace',
				'netvibes' 	=> 'Netvibes',
				'reddit' 	=> 'Reddit',
				'stumbleupon' => 'Stumbleupon',
				'technorati' => 'Technorati',
				'yahoobuzz' => 'Yahoobuzz',
			),
			'std'     => 0 
		);
		
		/* Advanced
		========================================*/
		
		$this->settings['spanWidth'] = array(
			'title'   => __( 'Image Span Width' ),
			'desc'    => __( 'What percentage of the image should the bar cover?' ),
			'type'    => 'text',
			'std'     => '100%',
			'section' => 'advanced',
		);	

		$this->settings['className'] = array(
			'title'   => __( 'What class to assign to caption' ),
			'desc'    => __( 'Useful for adding custom CSS.' ),
			'std'     => '',
			'type'    => 'text',
			'section' => 'advanced'
		);	

		$this->settings['prefix'] = array(
			'title'   => __( 'Custom HTML prefix' ),
			'desc'    => __( 'Add custom HTML or text to the beginning of the overlay.' ),
			'std'     => '',
			'type'    => 'text',
			'section' => 'advanced'
		);	
		
				
		/* Reset
		===========================================*/
		
		/* $this->settings['reset_slingpic'] = array(
			'section' => 'reset',
			'title'   => __( 'Reset SlingPic' ),
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
		
		wp_deregister_script( 'jquery' );
		wp_register_script( 'jquery', 'http://code.jquery.com/jquery-latest.min.js');
		wp_enqueue_script( 'jquery' );
		wp_register_script( 'slingpic', 'http://cdn.slingpic.com/slingpic.js', array('jquery'));
		wp_enqueue_script( 'slingpic' );
	
	}
	
	public function script_vars() {

		$shareOptions = get_option( 'slingpic_options' );
		$shareDefault = $shareOptions['share_sites_default'];
		$shareBox = $shareOptions['share_sites_box'];
		
		echo '<script type="text/javascript">
		
		var shareDefault = [';
			foreach ( $shareDefault as $site => $value )
			echo '"' . $site . '", ';
			
		echo '];
		
		var shareBox = [';
			foreach ( $shareBox as $site => $value )
			echo '"' . $site . '", ';
			
		echo '];
		
		</script>';
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
	* Styling for the options page
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
?>
