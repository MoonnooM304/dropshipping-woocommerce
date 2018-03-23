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

	/**
	 * Check order contains knawat products or not.
	 *
	 * @since    1.0.0
	 * @return 	 boolean
	 */
	public function is_knawat_order( $order_id ) {
		if( empty( $order_id ) ){ return false; }

		$order = wc_get_order( $order_id );
		$items = $order->get_items();
		foreach ( $items as $item ) {
			if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
				$product_id = $item->get_product_id();
				$dropshipping = get_post_meta( $product_id, 'dropshipping', true );
				if( $dropshipping == 'knawat' ){
					return true;
				}
			}
		}
		return false;
	}
}

/*
 * Woocommerce WebHooks Utilities
 */
add_filter( 'http_request_args', function( $args ) {
    $args['reject_unsafe_urls'] = false;

    return $args;
});

/*
 * Store is contected to knawat.com or not
 */
function knawat_dropshipwc_is_connected(){
	global $wpdb, $knawat_dropshipwc;

	if( $knawat_dropshipwc->is_woocommerce_activated() ){
		$t_api_keys = $wpdb->prefix.'woocommerce_api_keys';
		$api_key_query = "SELECT COUNT( key_id ) as count FROM {$t_api_keys} WHERE `description` LIKE '%Knawat - API%'";
		$key_count = $wpdb->get_var( $api_key_query );
		if( $key_count > 0 ){
			return true;
		}
	}

	return false;
}