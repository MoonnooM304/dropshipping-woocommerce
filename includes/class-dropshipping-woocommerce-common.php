<?php
/**
 * Common functions class for Knawat Dropshipping Woocommerce.
 *
 * @link       http://knawat.com/
 * @since      1.0.0
 *
 * @package    Knawat_Dropshipping_Woocommerce
 * @subpackage Knawat_Dropshipping_Woocommerce/includes
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Knawat_Dropshipping_Woocommerce_Common {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		// Do anything Here. 
	}

	/**
	 * Check is WooCommerce Activate or not.
	 *
	 * @since    1.0.0
	 * @return 	 boolean
	 */
	public function knawat_dropshipwc_is_woocommerce_activated() {
		if( !function_exists( 'is_plugin_active' ) ){
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return true;
		}else{
			return false;
		}
	}

	/**
	 * Check is WooCommerce Activate or not.
	 *
	 * @since    1.0.0
	 * @return 	 boolean
	 */
	public function knawat_dropshipwc_is_woomulti_currency_activated() {
		if( !function_exists( 'is_plugin_active' ) ){
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		if ( is_plugin_active( 'woocommerce-currency-switcher/index.php' ) || is_plugin_active( 'currency-switcher-woocommerce/currency-switcher-woocommerce.php' ) || is_plugin_active( 'woocommerce-multilingual/wpml-woocommerce.php' ) || is_plugin_active( 'woo-multi-currency/woo-multi-currency.php' ) ) {
			return true;
		}else{
			return false;
		}
	}
}

/*
 * Woocommerce WebHooks Utilities
 */
add_filter( 'http_request_args', function( $args ) {
    $args['reject_unsafe_urls'] = false;

    return $args;
});