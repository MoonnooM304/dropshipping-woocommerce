<?php
/**
 * The order-specific functions of the plugin.
 *
 * @package     Knawat_Dropshipping_Woocommerce
 * @subpackage  Knawat_Dropshipping_Woocommerce/includes
 * @copyright   Copyright (c) 2018, Knawat
 * @since       1.2.0
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Knawat_Dropshipping_Woocommerce_Orders {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
        // Create Suborder from front-end checkout
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'knawat_dropshipwc_create_sub_order' ), 10 );

        // Create separate shipping packages for knawat and non-knawat products.
        add_filter( 'woocommerce_cart_shipping_packages', array( $this, 'knawat_dropshipwc_split_knawat_shipping_packages' ) );
        // Add item meta into shipping item.
        add_action( 'woocommerce_checkout_create_order_shipping_item', array( $this, 'knawat_dropshipwc_add_shipping_meta_data' ), 10, 4 );

        // hide the item meta on the Order Items table
        add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'knawat_dropshipwc_hide_order_item_meta' ) );
        
        /* Order Table */
        add_filter( 'manage_shop_order_posts_columns', array( $this, 'knawat_dropshipwc_shop_order_columns' ), 20 );
        add_action( 'manage_shop_order_posts_custom_column', array( $this, 'knawat_dropshipwc_render_shop_order_columns' ) );

        /* Order MetaBoxes */
        add_action( 'add_meta_boxes', array( $this, 'knawat_dropshipwc_add_meta_boxes' ), 30 );
        
        /* Display Main orders only */
        add_action( 'load-edit.php', array( $this, 'knawat_dropshipwc_order_filter' ) );

        /* Count status for parent orders only */
        add_action( 'wp_count_posts', array( $this, 'knawat_dropshipwc_filter_count_orders' ), 10, 3 );

        /* Order Status sync */
        add_action( 'woocommerce_order_status_changed', array( $this, 'knawat_dropshipwc_order_status_change' ), 10, 3 );
        add_action( 'woocommerce_order_status_changed', array( $this, 'knawat_dropshipwc_child_order_status_change' ), 99, 3 );

        /* Remove sub orders from WooCommerce reports */
        add_filter( 'woocommerce_reports_get_order_report_query', array( $this, 'knawat_dropshipwc_admin_order_reports_remove_suborders' ) );
        
        /* WooCommerce Status Dashboard Widget */
        add_filter( 'woocommerce_dashboard_status_widget_top_seller_query', array( $this, 'knawat_dropshipwc_dashboard_status_widget_top_seller_query' ) );

        /* Order Trash, Untrash and Delete Operations. */
        add_action( 'wp_trash_post', array( $this, 'knawat_dropshipwc_trash_order' ) );
        add_action( 'wp_untrash_post', array( $this, 'knawat_dropshipwc_untrash_order' ) );
        add_action( 'delete_post', array( $this, 'knawat_dropshipwc_delete_order' ) );

        /* Override customer orders' query */
        add_filter( 'woocommerce_my_account_my_orders_query', array( $this, 'knawat_dropshipwc_get_customer_main_orders' ) );

        /* Disabled Emails for suborders */
        $email_ids = array(
            'new_order',
            'failed_order',
            'cancelled_order',
            'customer_refunded_order',
            'customer_processing_order',
            'customer_on_hold_order',
            'customer_completed_order',
        );

        foreach( $email_ids as $email_id ){
            add_filter( 'woocommerce_email_enabled_' . $email_id, array( $this, 'knawat_dropshipwc_disable_emails' ),10, 2 );
        }
    }   

    /**
     * Monitors a new order and attempts to create sub-orders
     *
     * If an order contains products from knawat and non-knawat products then divide it and create a sub-orders of order.
     *
     * @param int $parent_order_id
     * @return void
     *
     * @hooked woocommerce_checkout_update_order_meta - 10
     */
    public function knawat_dropshipwc_create_sub_order( $parent_order_id ) {

        if ( get_post_meta( $parent_order_id, '_knawat_sub_order' ) == true ) {
            $args = array(
                'post_parent' => $parent_order_id,
                'post_type'   => 'shop_order',
                'numberposts' => -1,
                'post_status' => 'any'
            );
            $child_orders = get_children( $args );

            foreach ( $child_orders as $child ) {
                wp_delete_post( $child->ID );
            }
        }
        
        $parent_order         = new WC_Order( $parent_order_id );
        $order_types          = $this->knawat_dropshipwc_order_contains_products( $parent_order_id );

        // return if we've only ONE seller
        if ( count( $order_types ) == 1 ) {
            $temp = array_keys( $order_types );
            $order_type = reset( $temp );
            
            if( 'knawat' === $order_type ){
                update_post_meta( $parent_order_id , '_knawat_order', 1 );
            }
            return;
        }

        // flag it as it has a suborder
        update_post_meta( $parent_order_id, '_knawat_sub_order', true );

        // seems like we've got knawat and non-knawat orders.
        foreach ( $order_types as $order_key => $order_items ) {
            $this->knawat_dropshipwc_create_type_order( $parent_order, $order_key, $order_items );
        }
    }

    /**
     * Return array of knawat and non-knawat with items
     *
     * @since 1.2.0
     *
     * @param int $order_id
     * @return array $items
     */
    public function knawat_dropshipwc_order_contains_products( $order_id ) {

        $order       = new WC_Order( $order_id );
        $order_items = $order->get_items();

        $items = array();
        foreach ( $order_items as $item ) {
            if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
                $product_id = $item->get_product_id();
                $dropshipping = get_post_meta( $product_id, 'dropshipping', true );
                if( $dropshipping == 'knawat' ){
                    $items['knawat'][] = $item;
                }else{
                    $items['non_knawat'][] = $item;
                }
            }
        }

        return $items;
    }
    
    /**
     * Creates a sub order
     *
     * @param int $parent_order
     * @param string $order_type
     * @param array $order_items
     */
    public function knawat_dropshipwc_create_type_order( $parent_order, $order_type, $order_items ) {

        $order_data = apply_filters( 'woocommerce_new_order_data', array(
            'post_type'     => 'shop_order',
            'post_title'    => sprintf( __( 'Order &ndash; %s', 'dropshipping-woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'dropshipping-woocommerce' ) ) ),
            'post_status'   => 'wc-pending',
            'ping_status'   => 'closed',
            'post_excerpt'  => isset( $posted['order_comments'] ) ? $posted['order_comments'] : '',
            'post_author'   => get_post_field( 'post_author', $parent_order->get_id() ),
            'post_parent'   => $parent_order->get_id(),
            'post_password' => uniqid( 'order_' )   // Protects the post just in case
        ) );

        $order_id = wp_insert_post( $order_data );

        if ( $order_id && !is_wp_error( $order_id ) ) {

            $order_total = $order_tax = 0;
            $product_ids = array();
            $items_tax = array();

            do_action( 'woocommerce_new_order', $order_id );

            // now insert line items
            foreach ( $order_items as $item ) {
                $order_total   += (float) $item->get_total();
                $order_tax     += (float) $item->get_total_tax();
                $product_ids[] = $item->get_product_id();

                $item_id = wc_add_order_item( $order_id, array(
                    'order_item_name' => $item->get_name(),
                    'order_item_type' => 'line_item'
                ) );

                // Mapping item wise tax data for perticular seller products
                $item_taxes = $item->get_taxes();
                foreach( $item_taxes['total'] as $key=>$value ) {
                    $items_tax[$key][] = $value;
                }

                if ( $item_id ) {
                    $item_meta_data = $item->get_data();
                    $meta_key_map = $this->knawat_dropshipwc_get_order_item_meta_map();
                    foreach ( $item->get_extra_data_keys() as $meta_key ) {
                        wc_add_order_item_meta( $item_id, $meta_key_map[$meta_key], $item_meta_data[$meta_key] );
                    }
                }
            }

            $bill_ship = array(
                '_billing_country', '_billing_first_name', '_billing_last_name', '_billing_company',
                '_billing_address_1', '_billing_address_2', '_billing_city', '_billing_state', '_billing_postcode',
                '_billing_email', '_billing_phone', '_shipping_country', '_shipping_first_name', '_shipping_last_name',
                '_shipping_company', '_shipping_address_1', '_shipping_address_2', '_shipping_city',
                '_shipping_state', '_shipping_postcode'
            );

            // save billing and shipping address
            foreach ( $bill_ship as $val ) {
                $order_key = 'get_' . ltrim( $val, '_' );
                update_post_meta( $order_id, $val, $parent_order->$order_key() );
            }

            // do shipping
            $shipping_values = $this->knawat_dropshipwc_create_sub_order_shipping( $parent_order, $order_id, $order_items, $order_type );
            $shipping_cost   = $shipping_values['cost'];
            $shipping_tax    = $shipping_values['tax'];

            // do tax
            $splited_order = wc_get_order( $order_id );
            
            foreach( $parent_order->get_items( array( 'tax' ) ) as $tax ) {
                $item_id = wc_add_order_item( $order_id, array(
                    'order_item_name' => $tax->get_name(),
                    'order_item_type' => 'tax'
                ) );

                $splited_shipping = $splited_order->get_items( 'shipping' );
                $splited_shipping = reset( $splited_shipping );

                $tax_metas = array(
                    'rate_id'             => $tax->get_rate_id(),
                    'label'               => $tax->get_label(),
                    'compound'            => $tax->get_compound(),
                    'tax_amount'          => wc_format_decimal( array_sum( $items_tax[$tax->get_rate_id()] ) ),
                    'shipping_tax_amount' => is_bool( $splited_shipping ) ? '' : $splited_shipping->get_total_tax()
                );

                foreach( $tax_metas as $meta_key => $meta_value ) {
                    wc_add_order_item_meta( $item_id, $meta_key, $meta_value );
                }

            }

            // add coupons if any
            $this->knawat_dropshipwc_create_sub_order_coupon( $parent_order, $order_id, $product_ids );
            $discount = $this->knawat_dropshipwc_sub_order_get_total_coupon( $order_id );

            // calculate the total
            $order_in_total = $order_total + $shipping_cost + $order_tax + $shipping_tax;
            
            // set order meta
            update_post_meta( $order_id, '_payment_method',         $parent_order->get_payment_method() );
            update_post_meta( $order_id, '_payment_method_title',   $parent_order->get_payment_method_title() );

            update_post_meta( $order_id, '_order_shipping',         wc_format_decimal( $shipping_cost ) );
            update_post_meta( $order_id, '_order_discount',         wc_format_decimal( $discount ) );
            update_post_meta( $order_id, '_cart_discount',          wc_format_decimal( $discount ) );
            update_post_meta( $order_id, '_order_tax',              wc_format_decimal( $order_tax ) );
            update_post_meta( $order_id, '_order_shipping_tax',     wc_format_decimal( $shipping_tax ) );
            update_post_meta( $order_id, '_order_total',            wc_format_decimal( $order_in_total ) );
            update_post_meta( $order_id, '_order_key',              apply_filters('woocommerce_generate_order_key', uniqid('order_') ) );
            update_post_meta( $order_id, '_customer_user',          $parent_order->get_customer_id() );
            update_post_meta( $order_id, '_order_currency',         get_post_meta( $parent_order->get_id(), '_order_currency', true ) );
            update_post_meta( $order_id, '_prices_include_tax',     $parent_order->get_prices_include_tax() );
            update_post_meta( $order_id, '_customer_ip_address',    get_post_meta( $parent_order->get_id(), '_customer_ip_address', true ) );
            update_post_meta( $order_id, '_customer_user_agent',    get_post_meta( $parent_order->get_id(), '_customer_user_agent', true ) );
            
            if( 'knawat' === $order_type ){
                update_post_meta( $order_id , '_knawat_order', 1 );
            }

            do_action( 'knawat_dropshipwc_checkout_update_order_meta', $order_id, $order_type );
        } // if order
    }

    /**
     * Map meta data for new item meta keys
     *
     */
    public function knawat_dropshipwc_get_order_item_meta_map() {
        return apply_filters( 'knawat_dropshipwc_get_order_item_meta_keymap', array(
            'product_id'   => '_product_id',
            'variation_id' => '_variation_id',
            'quantity'     => '_qty',
            'tax_class'    => '_tax_class',
            'subtotal'     => '_line_subtotal',
            'subtotal_tax' => '_line_subtotal_tax',
            'total'        => '_line_total',
            'total_tax'    => '_line_tax',
            'taxes'        => '_line_tax_data'
        ) );
    }
        
    /**
     * Create shipping for a sub-order if neccessary
     *
     * @param WC_Order $parent_order
     * @param int $order_id
     * @param array $product_ids
     * @return type
     */
    public function knawat_dropshipwc_create_sub_order_shipping( $parent_order, $order_id, $order_items, $order_type ) {

        $t_cost = $t_total_tax = 0;
        foreach( $parent_order->get_items( array( 'shipping' ) ) as $shipping ) {

            $ship_data = $shipping->get_data();
            $ship_meta_data = isset( $ship_data['meta_data'] ) ? $ship_data['meta_data'] : array();
            $package_type = 'non_knawat';
            foreach( $ship_meta_data as $ship_meta ){
                $ship_meta = $ship_meta->get_data();
                if( isset( $ship_meta['key'] ) && '_package_type' === $ship_meta['key'] ){
                    $package_type = 'knawat';
                }
            }
            if( $order_type != $package_type ){
                continue;
            }

            $item_id = wc_add_order_item( $order_id, array(
                'order_item_name' => $shipping->get_name(),
                'order_item_type' => 'shipping'
            ) );

            $shipping_metas = array(
                'method_id'    => isset( $ship_data['method_id'] ) ? $ship_data['method_id'] : '',
                'cost'         => isset( $ship_data['total'] ) ? $ship_data['total'] : '',
                'total_tax'    => isset( $ship_data['total_tax'] ) ? $ship_data['total_tax'] : '',
                'taxes'        => isset( $ship_data['taxes'] ) ? $ship_data['taxes'] : array(),
            );

            foreach( $shipping_metas as $meta_key => $meta_value ) {
                wc_add_order_item_meta( $item_id, $meta_key, $meta_value );
            }

            foreach( $ship_meta_data as $ship_meta ){
                $ship_meta = $ship_meta->get_data();
                if( isset( $ship_meta['key'] ) && isset( $ship_meta['value'] ) ){
                    wc_add_order_item_meta( $item_id, $ship_meta['key'], $ship_meta['value'] );
                }
            }
            $t_cost += $shipping_metas['cost'];
            $t_total_tax += $shipping_metas['total_tax'];

        }

        return array( 'cost' => $t_cost, 'tax' => $t_total_tax );
    }

    /**
     * Create coupons for a sub-order if neccessary
     *
     * @param WC_Order $parent_order
     * @param int $order_id
     * @param array $product_ids
     * @return type
     */
    public function knawat_dropshipwc_create_sub_order_coupon( $parent_order, $order_id, $product_ids ) {
        $used_coupons = $parent_order->get_used_coupons();
        
        if ( ! count( $used_coupons ) ) {
            return;
        }

        if ( $used_coupons ) {
            foreach ($used_coupons as $coupon_code) {
                $coupon = new WC_Coupon( $coupon_code );
                
                if ( $coupon && !is_wp_error( $coupon ) && array_intersect( $product_ids, $coupon->get_product_ids() ) ) {

                    // we found some match
                    $item_id = wc_add_order_item( $order_id, array(
                        'order_item_name' => $coupon_code,
                        'order_item_type' => 'coupon'
                    ) );

                    // Add line item meta
                    if ( $item_id ) {
                        wc_add_order_item_meta( $item_id, 'discount_amount', isset( WC()->cart->coupon_discount_amounts[ $coupon_code ] ) ? WC()->cart->coupon_discount_amounts[ $coupon_code ] : 0 );
                    }
                }
            }
        }
    }

    /**
     * Get discount coupon total from a order
     *
     * @global WPDB $wpdb
     * @param int $order_id
     * @return int
     */
    public function knawat_dropshipwc_sub_order_get_total_coupon( $order_id ) {
        global $wpdb;

        $sql = $wpdb->prepare( "SELECT SUM(oim.meta_value) FROM {$wpdb->prefix}woocommerce_order_itemmeta oim
                LEFT JOIN {$wpdb->prefix}woocommerce_order_items oi ON oim.order_item_id = oi.order_item_id
                WHERE oi.order_id = %d AND oi.order_item_type = 'coupon'", $order_id );

        $result = $wpdb->get_var( $sql );
        if ( $result ) {
            return $result;
        }

        return 0;
    }
   
    /**
     * Split all shipping class 'A' products in a separate package
     */
    public function knawat_dropshipwc_split_knawat_shipping_packages( $packages ) {

        // Reset all packages
        $packages              = array();
        $regular_package_items = array();
        $split_package_items   = array();

        // Split these products in a separate package
        foreach ( WC()->cart->get_cart() as $item_key => $item ) {
            if ( $item['data']->needs_shipping() ) {
                $dropshipping = get_post_meta( $item['product_id'], 'dropshipping', true );
                if ( 'knawat' === $dropshipping ) {
                    $split_package_items[ $item_key ] = $item;
                } else {
                    $regular_package_items[ $item_key ] = $item;
                }
            }
        }

        // Create shipping packages
        if ( $regular_package_items ) {
            $packages[] = array(
                'contents'        => $regular_package_items,
                'contents_cost'   => array_sum( wp_list_pluck( $regular_package_items, 'line_total' ) ),
                'applied_coupons' => WC()->cart->get_applied_coupons(),
                'user'            => array(
                    'ID' => get_current_user_id(),
                ),
                'destination'    => array(
                    'country'    => WC()->customer->get_shipping_country(),
                    'state'      => WC()->customer->get_shipping_state(),
                    'postcode'   => WC()->customer->get_shipping_postcode(),
                    'city'       => WC()->customer->get_shipping_city(),
                    'address'    => WC()->customer->get_shipping_address(),
                    'address_2'  => WC()->customer->get_shipping_address_2()
                )
            );
        }

        if ( $split_package_items ) {
            $packages[] = array(
                'contents'        => $split_package_items,
                'contents_cost'   => array_sum( wp_list_pluck( $split_package_items, 'line_total' ) ),
                'applied_coupons' => WC()->cart->get_applied_coupons(),
                'user'            => array(
                    'ID' => get_current_user_id(),
                ),
                'package_type'  => 'knawat',
                'destination'    => array(
                    'country'    => WC()->customer->get_shipping_country(),
                    'state'      => WC()->customer->get_shipping_state(),
                    'postcode'   => WC()->customer->get_shipping_postcode(),
                    'city'       => WC()->customer->get_shipping_city(),
                    'address'    => WC()->customer->get_shipping_address(),
                    'address_2'  => WC()->customer->get_shipping_address_2()
                )
            );
        }

        return $packages;
    }
    
    /**
     * Action hook to adjust item before save.
     *
     * @since 3.0.0
     */
    public function knawat_dropshipwc_add_shipping_meta_data( $item, $package_key, $package, $order ){
        if( empty( $item ) || empty( $package_key ) ){
            return;
        }

        if( isset( $package['package_type'] ) && 'knawat' === $package['package_type'] ){
            $item->add_meta_data( '_package_type', 'knawat', true );
        }
    }

    /**
	 * Hide cost of goods meta data fields from the order admin
	 */
	public function knawat_dropshipwc_hide_order_item_meta( $hidden_fields ) {
		return array_merge( $hidden_fields, array( '_package_type' ) );
    }
    
    /**
     * Add sub-orders in order table column
     *
     * @param $order_columns The order table column
     *
     * @return string           The label value
     */
    public function knawat_dropshipwc_shop_order_columns( $order_columns ) {
        

        //$order_number_col_name = YITH_Vendors()->is_wc_3_3_or_greather ? 'order_number' : 'order_title';
        $order_number_col_name = 'order_number';
        $suborder      = array( 'kwd_suborder' => _x( 'Suborders', 'Admin: Order table column', 'dropshipping-woocommerce' ) );
        $ref_pos       = array_search( $order_number_col_name, array_keys( $order_columns ) );
        $order_columns = array_slice( $order_columns, 0, $ref_pos + 1, true ) + $suborder + array_slice( $order_columns, $ref_pos + 1, count( $order_columns ) - 1, true );
        
        return $order_columns;
    }

    /**
     * Output custom columns for coupons
     *
     * @param  string $column
     */
    public function knawat_dropshipwc_render_shop_order_columns( $column, $order = false ) {
        global $post;
        if ( empty( $order ) ) {
            $order = wc_get_order( $post->ID );
        }
        $order_id = $order->get_id();

        switch ( $column ) {
            case 'kwd_suborder' :
                $suborder_ids = $this->knawat_dropshipwc_get_suborder( $order_id );

                if ( $suborder_ids ) {
                    foreach ( $suborder_ids as $suborder_id ) {
                        $suborder          = wc_get_order( $suborder_id );
                        $order_uri         = esc_url( 'post.php?post=' . absint( $suborder_id ) . '&action=edit' );
                        $order_status_name = wc_get_order_status_name( $suborder->get_status() );


                        printf( '<div class="kwd_suborder_item"><strong><a href="%s">#%s</a></strong> <mark class="order-status status-%s"><span>%s</span></mark></div>',
                            $order_uri,
                            $suborder->get_order_number(),
                            sanitize_title( $suborder->get_status() ),
                            $order_status_name
                        );

                    }
                } else {
                    echo '<span>&ndash;</span>';
                }

                break;
        }
    }

    /**
     * Get suborder from parent_order_id
     *
     *
     * @param bool|int $parent_order_id The parent id order
     *
     * @return array $suborder_ids
     */
    public function knawat_dropshipwc_get_suborder( $parent_order_id = false ) {
        $suborder_ids = array();
        if ( $parent_order_id ) {
            global $wpdb;

            $suborder_ids  = $wpdb->get_col(
                $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_type = 'shop_order'", $parent_order_id )
            );

        }
        return apply_filters( 'knawat_dropshipwc_get_suborder_ids', $suborder_ids, $parent_order_id );
    }

    /**
     * Add suborders metaboxe for order screen
     *
     * @return void
     */
    public function knawat_dropshipwc_add_meta_boxes() {
        if ( 'shop_order' != get_current_screen()->id ) {
            return;
        }

        global $post;
        $is_parentorder = $this->knawat_dropshipwc_get_suborder( absint( $post->ID ) );
        $is_suborder  = wp_get_post_parent_id( absint( $post->ID ) );
    
        if ( $is_parentorder ) {
            add_meta_box( 'knawat_dropshipwc-suborders', __( 'Suborders', 'dropshipping-woocommerce' ), array( $this, 'knawat_dropshipwc_render_order_metabox' ), 'shop_order', 'side', 'high' );
        } else if ( $is_suborder ) {
            add_meta_box( 'knawat_dropshipwc-parent-order',  __( 'Parent order', 'dropshipping-woocommerce' ), array( $this, 'knawat_dropshipwc_render_order_metabox' ), 'shop_order', 'side', 'high' );
        }

    }

    /**
     * Render the order metaboxes
     *
     * @param $post     The post object
     * @param $param    Callback args
     *
     * @return void
     */
    public function knawat_dropshipwc_render_order_metabox( $post, $args ) {
        
        switch ( $args['id'] ) {
            case 'knawat_dropshipwc-suborders':
                $suborder_ids = $this->knawat_dropshipwc_get_suborder( absint( $post->ID ) );
                foreach ( $suborder_ids as $suborder_id ) {
                    $suborder     = wc_get_order( absint( $suborder_id ) );
                    $suborder_uri = esc_url( 'post.php?post=' . absint( $suborder_id ) . '&action=edit' );
                  
                    printf( '<div class="kwd_suborder_item"><strong><a href="%s">#%s</a></strong> <mark class="order-status status-%s"><span>%s</span></mark></div>',
                            $suborder_uri,
                            $suborder->get_order_number(),
                            sanitize_title( $suborder->get_status() ),
                            wc_get_order_status_name( $suborder->get_status() )
                        );

                }
                break;

            case 'knawat_dropshipwc-parent-order':
                $parent_order_id  = wp_get_post_parent_id( absint( $post->ID ) );
                $parent_order_uri = esc_url( 'post.php?post=' . absint( $parent_order_id ) . '&action=edit' );
                printf( '<a href="%s">&#8592; %s (#%s)</a>', $parent_order_uri, __( 'Return to main order', 'dropshipping-woocommerce' ), $parent_order_id );
                break;
           
        }
    }

    /**
	 * Add `posts_where` filter if knawat orders need to filter
	 *
	 * @since 1.0
	 * @return void
	 */
	function knawat_dropshipwc_order_filter(){
	    global $typenow;
	    if( 'shop_order' != $typenow ){
	        return;
        }
        if ( !isset( $_GET[ 'knawat_orders' ] ) ){
            add_filter( 'posts_where' , array( $this, 'knawat_dropshipwc_posts_where_orders') );
        }
    }
    
    /**
	 * Add condtion in WHERE statement for filter only main orders in orders list table
	 *
	 * @since  1.0
	 * @param  string $where Where condition of SQL statement for orders query
	 * @return string $where Modified Where condition of SQL statement for orders query
	 */
	function knawat_dropshipwc_posts_where_orders( $where ){
	    global $wpdb;
        $where .= " AND {$wpdb->posts}.post_parent = 0";
	    return $where;	
	}

    /**
	 * Modify returned post counts by status for the shop_order
	 *
	 * @since 1.2.0
	 *
	 * @param object $counts An object containing the current post_type's post
	 *                       counts by status.
	 * @param string $type   Post type.
	 * @param string $perm   The permission to determine if the posts are 'readable'
	 *                       by the current user.
     * 
     * @return object $counts Modified post counts by status.
	 */
    public function knawat_dropshipwc_filter_count_orders( $counts, $type, $perm ) {
        if( 'shop_order' === $type && is_admin() && ( 'edit-shop_order' === get_current_screen()->id || 'dashboard' === get_current_screen()->id ) ){
            global $wpdb;
            
            if ( ! post_type_exists( $type ) )
		        return new stdClass;

            $query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s AND post_parent = 0";
            if ( 'readable' == $perm && is_user_logged_in() ) {
                $post_type_object = get_post_type_object($type);
                if ( ! current_user_can( $post_type_object->cap->read_private_posts ) ) {
                    $query .= $wpdb->prepare( " AND (post_status != 'private' OR ( post_author = %d AND post_status = 'private' ))",
                        get_current_user_id()
                    );
                }
            }
            $query .= ' GROUP BY post_status';

            $results = (array) $wpdb->get_results( $wpdb->prepare( $query, $type ), ARRAY_A );
            $counts = array_fill_keys( get_post_stati(), 0 );

            foreach ( $results as $row ) {
                $counts[ $row['post_status'] ] = $row['num_posts'];
            }

            $counts = (object) $counts;
        }
        return $counts;
    }

    /**
     * Update the child order status when a parent order status is changed
     *
     * @global object $wpdb
     * @param int $order_id
     * @param string $old_status
     * @param string $new_status
     */
    public function knawat_dropshipwc_order_status_change( $order_id, $old_status, $new_status ) {
        global $wpdb;

        // check for wc- prefix. add if its not there.
        if ( stripos( $new_status, 'wc-' ) === false ) {
            $new_status = 'wc-' . $new_status;
        }

        // if any child orders found, change the orders as well
        $sub_orders = $this->knawat_dropshipwc_get_suborder( $order_id );
        if ( $sub_orders ) {
            foreach ( $sub_orders as $order_post ) {
                $order = new WC_Order( $order_post );
                $order->update_status( $new_status );
            }
        }
    }

    /**
     * Mark the parent order as complete when all the child order are completed
     *
     * @param int $order_id
     * @param string $old_status
     * @param string $new_status
     * @return void
     */
    public function knawat_dropshipwc_child_order_status_change( $order_id, $old_status, $new_status ) {
        $order_post = get_post( $order_id );

        // Check for child orders only
        if ( $order_post->post_parent === 0 ) {
            return;
        }

        // get all the child orders and monitor the status
        $parent_order_id = $order_post->post_parent;
        $sub_order_ids   = $this->knawat_dropshipwc_get_suborder( $parent_order_id );

        // return if any child order is not completed
        $all_complete = true;

        if ( $sub_order_ids ) {
            foreach ($sub_order_ids as $sub_id ) {
                $order = new WC_Order( $sub_id );
                if ( version_compare( WC_VERSION, '2.7', '>' ) ) {
                    $order_status = $order->get_status();
                }else{
                    $order_status = $order->status;
                }
                if ( $order_status != 'completed' ) {
                    $all_complete = false;
                }
            }
        }

        // seems like all the child orders are completed
        // mark the parent order as complete
        if ( $all_complete ) {
            $parent_order = new WC_Order( $parent_order_id );
            $parent_order->update_status( 'wc-completed', __( 'Mark main order completed when all suborders are completed.', 'dropshipping-woocommerce' ) );
        }
    }

    /**
     * Remove sub orders from WC reports
     *
     * @param array $query
     * @return array
     */
    public function knawat_dropshipwc_admin_order_reports_remove_suborders( $query ) {

        $query['where'] .= ' AND posts.post_parent = 0';

        return $query;
    }

    /**
     * Filter TopSeller query for WooCommerce Dashboard Widget
     *
     * @param Array $query
     *
     * @return Array $query Altered Array 
     */
    public function knawat_dropshipwc_dashboard_status_widget_top_seller_query( $query ){
        $query['where']  .= "AND posts.post_parent = 0";
        return $query;
    }

    /**
     * Delete sub orders when parent order is trashed
     *
     * @param int $post_id
     */
    function knawat_dropshipwc_trash_order( $post_id ) {
        $post = get_post( $post_id );

        if ( $post->post_type == 'shop_order' && $post->post_parent == 0 ) {
            $sub_order_ids = $this->knawat_dropshipwc_get_suborder( $post_id );

            if ( !empty( $sub_order_ids ) ){
                foreach ($sub_order_ids as $sub_order_id ) {
                    wp_trash_post( $sub_order_id );
                }
            }
        }
    }

    /**
     * Untrash sub orders when parent orders are untrashed
     *
     * @param int $post_id
     */
    function knawat_dropshipwc_untrash_order( $post_id ) {
        $post = get_post( $post_id );
        
        if ( $post->post_type == 'shop_order' && $post->post_parent == 0 ) {
            $sub_order_ids = $this->knawat_dropshipwc_get_suborder( $post_id );
            
            if ( !empty( $sub_order_ids ) ){
                foreach ( $sub_order_ids as $sub_order_id ) {
                    wp_untrash_post( $sub_order_id );
                }
            }
        }
    }

    /**
     * Delete sub orders and when parent order is deleted
     *
     * @param int $post_id
     */
    function knawat_dropshipwc_delete_order( $post_id ) {
        $post = get_post( $post_id );

        if ( $post->post_type == 'shop_order' ) {
            
            $sub_order_ids = $this->knawat_dropshipwc_get_suborder( $post_id );

            if ( !empty( $sub_order_ids ) ){
                foreach ($sub_order_ids as $sub_order_id ) {
                    wp_delete_post( $sub_order_id );
                }
            }
        }
    }

    /**
     * Disable email for suborders.
     *
     * @param bool      $is_enabled     Email is enabled or not.
     * @param object    $object         Object this email is for, for example a customer, product, or email.
     *
     * @return bool
     */
    public function knawat_dropshipwc_disable_emails( $is_enabled, $object ){
        if( !empty( $object ) && is_a( $object, 'WC_Order' ) ){
            $order_id = $object->get_id();
            $parent_id = wp_get_post_parent_id( $order_id );
            if( $parent_id > 0 ){
                return false;
            }
        }
        return $is_enabled;
    }

    /**
     * Override Customer Orders array
     *
     * @param array customer orders args query
     *
     * @return array modified customer orders args query
     */
    public function knawat_dropshipwc_get_customer_main_orders( $customer_orders ) {
        $customer_orders['post_parent'] = 0;
        return $customer_orders;
    }
}

$knawat_dropshipwc_orders = new Knawat_Dropshipping_Woocommerce_Orders();
