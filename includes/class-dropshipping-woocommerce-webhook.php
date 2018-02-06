<?php
/**
 * Class for add new custom Webhook topics in WooCommerce
 *
 * @link       http://knawat.com/
 * @since      1.0.0
 *
 * @package    Knawat_Dropshipping_Woocommerce
 * @subpackage Knawat_Dropshipping_Woocommerce/includes
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Knawat_Dropshipping_Woocommerce_Webhook {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		add_filter( 'woocommerce_webhook_topic_hooks', array( $this, 'add_new_topic_hooks' ) );
		add_filter( 'woocommerce_valid_webhook_events', array( $this, 'add_new_webhook_events' ) );
		add_filter( 'woocommerce_webhook_topics', array( $this, 'add_new_webhook_topics' ) );

		// Handles Knawat Order Created & Updated Topic.
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'knawat_order_created_updated_callback' ) );
		add_action( 'woocommerce_new_order', array( $this, 'knawat_order_created_updated_callback' ) );
		add_action( 'woocommerce_update_order', array( $this, 'knawat_order_created_updated_callback' ) );

		add_action( 'woocommerce_checkout_order_processed', array( $this, 'knawat_order_created_callback' ), 10, 3 );

		// Handles Knawat Order Delete & Restore.
		add_action( 'wp_trash_post', array( $this, 'knawat_order_deleted_callback' ) );
		add_action( 'untrashed_post', array( $this, 'knawat_order_restored_callback' ) );
	}

	/**
	 * Adds new webhook topic hooks.
	 * 
	 * @param  Array  $topic_hooks 	Existing topic hooks.
	 *
	 * @return Array
	 */
	function add_new_topic_hooks( $topic_hooks ) {
		// Array that has the topic as resource.event with arrays of actions that call that topic.
		$new_hooks = array(
			'order.knawatcreated' => array(
				'knawat_order_created', 
			),
			'order.knawatupdated' => array(
				'knawat_order_updated', 
			),
			'order.knawatdeleted' => array(
				'knawat_order_deleted', 
			),
			'order.knawatrestored' => array(
				'knawat_order_restored', 
			),
		);
		return array_merge( $topic_hooks, $new_hooks );
	}

	/**
	 * Adds new events for topic resources.
	 * 
	 * @param  Array  $events 	Existing Events.
	 *
	 * @return Array
	 */
	function add_new_webhook_events( $events ) {
		// new resource
		$new_events = array( 'knawatcreated', 'knawatupdated', 'knawatdeleted', 'knawatrestored' );
		return array_merge( $events, $new_events );
	}

	/**
	 * add_new_webhook_topics adds the new webhook to the dropdown list on the Webhook page.
	 *
	 * @param array $topics Array of topics with the i18n proper name.
	 *
	 * @return Array 
	 */
	function add_new_webhook_topics( $topics ) {
		// New topic array to add to the list, must match hooks being created.
		$new_topics = array( 
			'order.knawatcreated' => __( 'Knawat Order created', 'dropshipping-woocommerce' ),
			'order.knawatupdated' => __( 'Knawat Order updated', 'dropshipping-woocommerce' ),
			'order.knawatdeleted' => __( 'Knawat Order deleted', 'dropshipping-woocommerce' ),
			'order.knawatrestored' => __( 'Knawat Order restored', 'dropshipping-woocommerce' ),
		);
		return array_merge( $topics, $new_topics );
	}


	/**
	 * knawat_order_created_updated_callback will run on order create/update.
	 * if it has knawat product as one of the items, it will fire off the action `knawat_order_created`
	 * 
	 * @param  int    $order_id    The ID of the order that was just created.
	 *
	 * @return null
	 */
	function knawat_order_created_updated_callback( $order_id ) {
		
		$current_action = current_action();
		if( $current_action == 'woocommerce_process_shop_order_meta' ){
			// the `woocommerce_process_shop_*` and `woocommerce_process_product_*` hooks
			// fire for create and update of products and orders, so check the post
			// creation date to determine the actual event
			$resource = get_post( absint( $order_id ) );

			// Drafts don't have post_date_gmt so calculate it here
			$gmt_date = get_gmt_from_date( $resource->post_date );

			// a resource is considered created when the hook is executed within 10 seconds of the post creation date
			$resource_created = ( ( time() - 10 ) <= strtotime( $gmt_date ) );
		}

		$order = wc_get_order( $order_id );
		$items = $order->get_items();
		foreach ( $items as $item ) {
			if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
				$product_id = $item->get_product_id();
				$dropshipping = get_post_meta( $product_id, 'dropshipping', true );
				if( $dropshipping == 'knawat' ){
					if( $current_action == 'woocommerce_process_shop_order_meta' && $resource_created ){
						do_action( 'knawat_order_created', $order_id );
						return;

					}elseif( $current_action == 'woocommerce_update_order' ){
						do_action( 'knawat_order_updated', $order_id );
						return;

					}elseif( $current_action == 'woocommerce_new_order' ){
						do_action( 'knawat_order_created', $order_id );
						return;

					}else{
						do_action( 'knawat_order_updated', $order_id );
						return;
					}
				}
			}
		}
	}

	/**
	 * knawat_order_created_callback will run on order create.
	 * if it has knawat product as one of the items, it will fire off the action `knawat_order_created`
	 * 
	 * @param  int    $order_id    The ID of the order that was just created.
	 *
	 * @return null
	 */
	function knawat_order_created_callback( $order_id ) {
		
		$order = wc_get_order( $order_id );
		$items = $order->get_items();
		foreach ( $items as $item ) {
			
			if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
				$product_id = $item->get_product_id();
				$dropshipping = get_post_meta( $product_id, 'dropshipping', true );
				if( $dropshipping == 'knawat' ){
					do_action( 'knawat_order_created', $order_id, $posted_data, $order );
					return;
				}
			}
		}
	}

	/**
	 * knawat_order_deleted_callback will run on order delete.
	 * if it has knawat product as one of the items, it will fire off the action `knawat_order_deleted`
	 * 
	 * @param  int    $order_id    The ID of the order that was just deleted.
	 *
	 * @return null
	 */
	function knawat_order_deleted_callback( $order_id ) {

		$post_type = get_post_type( $order_id );
		if( 'shop_order' !== $post_type ){
			return;
		}

		$order = wc_get_order( $order_id );
		$items = $order->get_items();
		foreach ( $items as $item ) {
			
			if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
				$product_id = $item->get_product_id();
				$dropshipping = get_post_meta( $product_id, 'dropshipping', true );
				if( $dropshipping == 'knawat' ){
					do_action( 'knawat_order_deleted', $order_id );
					return;
				}
			}
		}
	}

	/**
	 * knawat_order_restored_callback will run on order restore.
	 * if it has knawat product as one of the items, it will fire off the action `knawat_order_restored`
	 * 
	 * @param  int    $order_id    The ID of the order that was just restored.
	 *
	 * @return null
	 */
	function knawat_order_restored_callback( $order_id ) {

		$post_type = get_post_type( $order_id );
		if( 'shop_order' !== $post_type ){
			return;
		}

		$order = wc_get_order( $order_id );
		$items = $order->get_items();
		foreach ( $items as $item ) {
			
			if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
				$product_id = $item->get_product_id();
				$dropshipping = get_post_meta( $product_id, 'dropshipping', true );
				if( $dropshipping == 'knawat' ){
					do_action( 'knawat_order_restored', $order_id );
					return;
				}
			}
		}
	}

}

$knawat_webhooks = new Knawat_Dropshipping_Woocommerce_Webhook();