<?php
/**
 * TGMPA Required Plugins.
 *
 * Register the required plugins for this plugin.
 *
 * @package Dropshipping Woocommerce
 * @version 1.0.0
 * @author  Knawat.com
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU Public License
 */

/**
 * Register the required plugins for this plugin.
 *
 * This function is hooked into `tgmpa_register`, which is fired on the WP `init` action on priority 10.
 */
function knawat_dropshipwc_register_required_plugins() {
	/*
	 * Array of plugin arrays. Required keys are name and slug.
	 * If the source is NOT from the .org repo, then source is also required.
	 */
	global $knawat_dropshipwc;
	$plugins = array(

		array(
			'name'         	=> esc_html__( 'WooCoomerce', 'dropshipping-woocommerce' ),
			'slug'          => 'woocommerce',
			'required'     	=> true,
			'recommended_by'=> 'knawat'
		),
		array(
			'name'         	=> esc_html__( 'WooCommerce PDF Invoices & Packing Slips', 'dropshipping-woocommerce' ),
			'slug'         	=> 'woocommerce-pdf-invoices-packing-slips',
			'required'     	=> true,
			'recommended_by'=> 'knawat'
		),
		array(
			'name'         	=> esc_html__( 'Featured Image by URL', 'dropshipping-woocommerce' ),
			'slug'         	=> 'featured-image-by-url',
			'required'     	=> false,
			'recommended_by'=> 'knawat'
		)
	);

	if( !$knawat_dropshipwc->common->knawat_dropshipwc_is_woomulti_currency_activated() ){
		$plugins[] = array(
			'name'         	=> esc_html__( 'WooCommerce Currency Switcher', 'dropshipping-woocommerce' ),
			'slug'         	=> 'woocommerce-currency-switcher',
			'required'     	=> false,
			'recommended_by'=> 'knawat'
		);	
	}
	
	$plugins[] = array(
			'name'         	=> esc_html__( 'qTranslate X', 'dropshipping-woocommerce' ),
			'slug'         	=> 'qtranslate-x',
			'required'     	=> false,
			'recommended_by'=> 'knawat'
		);
	/*
	 * Array of configuration settings. Amend each line as needed.
	 *
	 * TGMPA will start providing localized text strings soon. If you already have translations of our standard
	 * strings available, please help us make TGMPA even better by giving us access to these translations or by
	 * sending in a pull-request with .po file(s) with the translations.
	 *
	 * Only uncomment the strings in the config array if you want to customize the strings.
	 */
	$config = array(
		'id'           => 'dropshipping-woocommerce', // Unique ID for hashing notices for multiple instances of TGMPA.
		'default_path' => '',                      // Default absolute path to bundled plugins.
		'menu'         => 'tgmpa-install-plugins', // Menu slug.
		'parent_slug'  => 'plugins.php',            // Parent menu slug.
		'has_notices'  => true,                    // Show admin notices or not.
		'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
		'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be at the top of nag.
		'is_automatic' => false,                   // Automatically activate plugins after installation or not.
		'message'      => '',                      // Message to output right before the plugins table.

		'strings'      => array(
			
			'notice_can_install_required'     => _n_noop(
				/* translators: 1: plugin name(s). */
				'Knawat Dropshipping requires the following plugin: %1$s.',
				'Knawat Dropshipping requires the following plugins: %1$s.',
				'dropshipping-woocommerce'
			),
			'notice_can_install_recommended'  => _n_noop(
				/* translators: 1: plugin name(s). */
				'Knawat Dropshipping recommends the following plugin: %1$s.',
				'Knawat Dropshipping recommends the following plugins: %1$s.',
				'dropshipping-woocommerce'
			)
		),
	);

	tgmpa( $plugins, $config );
}

add_action( 'tgmpa_register', 'knawat_dropshipwc_register_required_plugins' );
