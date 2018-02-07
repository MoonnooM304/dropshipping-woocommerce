<?php
/**
 * Knawat Merlin configuration file.
 *
 * @package Dropshipping WooCommerce
 * @version 1.0.0
 * @author  Knawat.com
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU Public License
 */

if ( ! class_exists( 'Knawat_Merlin' ) ) {
	return;
}

/**
 * Set directory locations, text strings, and other settings for Knawat Merlin.
 */
$knawat_setup = new Knawat_Merlin(
	
	// Configure Knawat Merlin with custom settings.
	$config = array(
		'directory'			=> 'knawat-merlin',	// Location where the 'knawat-merlin' directory is placed.
		'merlin_url'		=> 'knawat_setup',	// Customize the page URL where Merlin WP loads.
		'dev_mode'			=> true,			// Set to true if you're testing or developing.
	),

	// Text strings.
	$strings = array(
		'admin-menu' 			=> esc_html__( 'Knawat Setup' , 'dropshipping-woocommerce' ),
		'title%s%s%s' 		=> esc_html__( '%s%s Knawat &lsaquo; Knawat Setup %s' , 'dropshipping-woocommerce' ),

		'return-to-dashboard' 		=> esc_html__( 'Return to the dashboard' , 'dropshipping-woocommerce' ),

		'btn-skip' 			=> esc_html__( 'Skip' , 'dropshipping-woocommerce' ),
		'btn-next' 			=> esc_html__( 'Next' , 'dropshipping-woocommerce' ),
		'btn-start' 			=> esc_html__( 'Start' , 'dropshipping-woocommerce' ),
		'btn-no' 			=> esc_html__( 'Cancel' , 'dropshipping-woocommerce' ),
		'btn-plugins-install' 		=> esc_html__( 'Install' , 'dropshipping-woocommerce' ),
		'btn-knawat-connect' 		=> esc_html__( 'Next' , 'dropshipping-woocommerce' ),
		'btn-content-install' 		=> esc_html__( 'Install' , 'dropshipping-woocommerce' ),
		'btn-license-activate' 		=> esc_html__( 'Activate' , 'dropshipping-woocommerce' ),

		'welcome-header%s' 		=> esc_html__( 'Welcome to %s' , 'dropshipping-woocommerce' ),
		'welcome-header-success%s' 	=> esc_html__( 'Hi. Welcome back' , 'dropshipping-woocommerce' ),
		'welcome%s' 			=> esc_html__( 'This wizard will connect your store with Knawat and install plugins. It is optional & should take only a few minutes.' , 'dropshipping-woocommerce' ),
		'welcome-success%s' 		=> esc_html__( 'You may have already run this knawat setup wizard. If you would like to proceed anyway, click on the "Start" button below.' , 'dropshipping-woocommerce' ),

		
		'knawat-header' 			=> esc_html__( 'Connect Your store with Knawat' , 'dropshipping-woocommerce' ),
		'knawat-header-success' 		=> esc_html__( 'You\'re good to go!' , 'dropshipping-woocommerce' ),
		'knawat' 			=> esc_html__( 'Please follow Step 1 & Step 2 then after to connect you site with Knawat' , 'dropshipping-woocommerce' ),
		'knawat-success%s' 		=> esc_html__( 'Your store has already connected to Knawat.' , 'dropshipping-woocommerce' ),
		'knawat-action-link' 		=> esc_html__( 'Learn about child themes' , 'dropshipping-woocommerce' ),
		
		'plugins-header' 		=> esc_html__( 'Install Plugins' , 'dropshipping-woocommerce' ),
		'plugins-header-success' 	=> esc_html__( 'You\'re up to speed!' , 'dropshipping-woocommerce' ),
		'plugins' 			=> esc_html__( 'Let’s install some essential WordPress plugins to get your site up to speed.' , 'dropshipping-woocommerce' ),
		'plugins-success%s' 		=> esc_html__( 'The required WordPress plugins are all installed and up to date. Press "Next" to continue the setup wizard.' , 'dropshipping-woocommerce' ),
		'plugins-action-link' 		=> esc_html__( 'Advanced' , 'dropshipping-woocommerce' ),


		'ready-header' 			=> esc_html__( 'All done. Have fun!' , 'dropshipping-woocommerce' ),
		'ready' 				=> esc_html__( 'Your store has been all set up.' , 'dropshipping-woocommerce' ),
		'ready-action-link' 		=> esc_html__( 'Extras' , 'dropshipping-woocommerce' ),
		'ready-big-button' 		=> esc_html__( 'Start Adding Products' , 'dropshipping-woocommerce' ),

		'ready-link-1'              	=> wp_kses( sprintf( __( '<a href="https://knawat.com/" target="_blank">%s</a>', 'dropshipping-woocommerce' ), 'Explore Knawat' ), array( 'a' => array( 'href' => array(), 'target' => array() ) ) ),
		'ready-link-2'              	=> wp_kses( sprintf( __( '<a href="https://help.knawat.com/hc/en-us/" target="_blank">%s</a>', 'dropshipping-woocommerce' ), 'Help Center' ), array( 'a' => array( 'href' => array(), 'target' => array() ) ) ),
		'ready-link-3'             	=> wp_kses( sprintf( __( '<a href="'.admin_url( 'customize.php' ).'" target="_blank">%s</a>', 'dropshipping-woocommerce' ), 'Start Customizing' ), array( 'a' => array( 'href' => array(), 'target' => array() ) ) ),
	)
);
