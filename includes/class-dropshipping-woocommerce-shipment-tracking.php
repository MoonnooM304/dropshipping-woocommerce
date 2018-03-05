<?php
/**
 * Class for handle shipment tracking in WooCommerce
 *
 * @link       http://knawat.com/
 * @since      1.1.0
 *
 * @package    Knawat_Dropshipping_Woocommerce
 * @subpackage Knawat_Dropshipping_Woocommerce/includes
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Knawat_Dropshipping_Woocommerce_Shipment_Traking {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'knawat_dropshipwc_register_order_meta' ) );
		add_action( 'add_meta_boxes_shop_order', array( $this, 'knawat_dropshipwc_add_meta_box' ), 10 );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'knawat_dropshipwc_save_tracking_details' ), 0, 2 );
		add_action( 'woocommerce_view_order', array( $this, 'knawat_dropshipwc_display_shipment_tracking' ) );
		
	}

	/**
	 * Add 'Knawat Shipment Traking' Metabox to Order.
	 *
	 * @since    1.1.0
	 */
	public function knawat_dropshipwc_add_meta_box( $post ){
		$order_id = $post->ID;
		if( empty( $order_id ) ){
			return;
		}
		$tracking_data = $this->knawat_dropshipwc_get_tracking_details( $order_id );
		$is_shipment_data = ( $tracking_data['_shipment_provider_name'] != '' && $tracking_data['_shipment_tracking_number'] );
		if( $is_shipment_data ){
			add_meta_box( 'knawat-shipment-tracking', __( 'Knawat Shipment Tracking', 'dropshipping-woocommerce' ), array( $this, 'knawat_dropshipwc_meta_box' ), 'shop_order', 'side', 'high' );
		}
	}

	/**
	 * Render 'Knawat Shipment Traking' Metabox
	 *
	 * @since    1.1.0
	 */
	public function knawat_dropshipwc_meta_box(){
		global $post;
		$order_id = $post->ID;
		if( empty( $order_id ) ){
			return;
		} 

		$tracking_data = $this->knawat_dropshipwc_get_tracking_details( $order_id );
		$is_shipment_data = ( $tracking_data['_shipment_provider_name'] != '' && $tracking_data['_shipment_tracking_number'] && $tracking_data['_shipment_tracking_link'] );
		?>
		<div class="knawat-shipment-wrap">
			<?php if( $is_shipment_data ) { ?>
				<div class="knawat-shipment-info">
					<p><a href="#" class="edit_shipment_traking"><?php _e( 'Edit', 'dropshipping-woocommerce' ); ?></a></p>
					<p>
						<strong><?php _e( 'Provider Name:', 'dropshipping-woocommerce' ); ?></strong><br/>
						<span><?php echo $tracking_data['_shipment_provider_name']; ?></span>
					</p>
					<p>
						<strong><?php _e( 'Tracking number:', 'dropshipping-woocommerce' ); ?></strong><br/>
						<span><?php echo $tracking_data['_shipment_tracking_number']; ?></span>
					</p>
					<?php if( $tracking_data['_shipment_tracking_link'] != '' ){ ?>
					<p>
						<strong><?php _e( 'Tracking Link:', 'dropshipping-woocommerce' ); ?></strong><br/>
						<a href="<?php echo esc_url( $tracking_data['_shipment_tracking_link'] ); ?>" target="_blank"><?php _e( 'Track', 'dropshipping-woocommerce' ); ?></a>
					</p>
					<?php } ?>
					<?php if( $tracking_data['_shipment_date_shipped'] != '' ){ ?>
					<p>
						<strong><?php _e( 'Date shipped:', 'dropshipping-woocommerce' ); ?></strong><br/>
						<span><?php echo $tracking_data['_shipment_date_shipped']; ?></span>
					</p>
					<?php } ?>
				</div>
			<?php } ?>
			<div class="knawat-shipment-edit" <?php if( $is_shipment_data ) { echo 'style="display: none;"'; } ?>">
				<?php
				woocommerce_wp_text_input( array(
					'id'          => '_shipment_provider_name',
					'label'       => __( 'Provider Name:', 'dropshipping-woocommerce' ),
					'placeholder' => '',
					'value'       => isset( $tracking_data['_shipment_provider_name'] ) ? $tracking_data['_shipment_provider_name'] : '',
				) );

				woocommerce_wp_text_input( array(
					'id'          => '_shipment_tracking_number',
					'label'       => __( 'Tracking number:', 'dropshipping-woocommerce' ),
					'placeholder' => '',
					'value'       => isset( $tracking_data['_shipment_tracking_number'] ) ? $tracking_data['_shipment_tracking_number'] : '',
				) );

				woocommerce_wp_text_input( array(
					'id'          => '_shipment_tracking_link',
					'label'       => __( 'Tracking link:', 'dropshipping-woocommerce' ),
					'placeholder' => 'http://track.provider.com/?track=9876543210',
					'value'       => isset( $tracking_data['_shipment_tracking_link'] ) ? esc_url( $tracking_data['_shipment_tracking_link'] ) : '',
				) );

				woocommerce_wp_text_input( array(
					'id'          => '_shipment_date_shipped',
					'label'       => __( 'Date shipped:', 'dropshipping-woocommerce' ),
					'placeholder' => date_i18n( __( 'Y-m-d', 'dropshipping-woocommerce' ), time() ),
					'class'       => 'date-picker',
					'value'       => isset( $tracking_data['_shipment_date_shipped'] ) ? $tracking_data['_shipment_date_shipped'] : date_i18n( 'Y-m-d', current_time( 'timestamp' ) ),
				) );

				echo '<button class="button button-primary button-save-form">' . __( 'Save Tracking', 'dropshipping-woocommerce' ) . '</button>';
				?>
			</div>
		</div>
		<?php
	}


	/**
	 * Gets order shipment details.
	 *
	 * @param int  $order_id  Order ID
	 *
	 */
	public function knawat_dropshipwc_get_tracking_details( $order_id ) {
		global $wpdb;

		$tracking_data = array();
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			
			$tracking_data['_shipment_provider_name'] 	= get_post_meta( $order_id, '_shipment_provider_name', true );
			$tracking_data['_shipment_tracking_number'] = get_post_meta( $order_id, '_shipment_tracking_number', true );
			$tracking_data['_shipment_tracking_link'] 	= get_post_meta( $order_id, '_shipment_tracking_link', true );
			$tracking_data['_shipment_date_shipped'] 	= get_post_meta( $order_id, '_shipment_date_shipped', true );

		} else {

			$order = new WC_Order( $order_id );
			$tracking_data['_shipment_provider_name'] 	= $order->get_meta( '_shipment_provider_name', true );
			$tracking_data['_shipment_tracking_number'] = $order->get_meta( '_shipment_tracking_number', true );
			$tracking_data['_shipment_tracking_link'] 	= $order->get_meta( '_shipment_tracking_link', true );
			$tracking_data['_shipment_date_shipped'] 	= $order->get_meta( '_shipment_date_shipped', true );
		}
		if( empty( $tracking_data['_shipment_tracking_link'] ) && ( $tracking_data['_shipment_provider_name'] && $tracking_data['_shipment_tracking_number'] )){
			$tracking_data['_shipment_tracking_link'] = $this->knawat_dropshipwc_generate_tracking_link(  $tracking_data['_shipment_provider_name'], $tracking_data['_shipment_tracking_number'] );
		}

		return $tracking_data;
	}


	/**
	 * Saves the tracking items array to post_meta.
	 *
	 * @param int   $order_id       Order ID
	 * @param array $tracking_items List of tracking item
	 */
	public function knawat_dropshipwc_save_tracking_details( $order_id, $post ) {

		if ( isset( $_POST['_shipment_tracking_number'] ) && strlen( $_POST['_shipment_tracking_number'] ) > 0 ) {

			$provider_name 	= wc_clean( $_POST['_shipment_provider_name'] );
			$tracking_number= wc_clean( $_POST['_shipment_tracking_number'] );
			$date_shipped 	= wc_clean( $_POST['_shipment_date_shipped'] );
			$tracking_link 	= wc_clean( $_POST['_shipment_tracking_link'] );

			if( empty( $tracking_link ) ){
				$tracking_link = $this->knawat_dropshipwc_generate_tracking_link( $provider_name, $tracking_number );
			}
			if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
				update_post_meta( $order_id, '_shipment_provider_name', $provider_name );
				update_post_meta( $order_id, '_shipment_tracking_number', $tracking_number );
				update_post_meta( $order_id, '_shipment_tracking_link', $tracking_link );
				update_post_meta( $order_id, '_shipment_date_shipped', $date_shipped );

			} else {
				$order = new WC_Order( $order_id );
				$order->update_meta_data( '_shipment_provider_name', $provider_name );
				$order->update_meta_data( '_shipment_tracking_number',  $tracking_number );
				$order->update_meta_data( '_shipment_tracking_link', $tracking_link );
				$order->update_meta_data( '_shipment_date_shipped', $date_shipped );
				$order->save_meta_data();
			}
		}
	}

	/**
	 * Generate Tracking links based on provider
	 *
	 * @param int   $provider_name      Provider Name
	 * @param array $tracking_number 	Shipment Tracking Number
	 */
	public function knawat_dropshipwc_generate_tracking_link( $provider_name, $tracking_number ) {
		if( $provider_name == '' || $tracking_number == '' ){
			return '';
		}
		$tracking_link = '';
		$providers_link = apply_filters( 'knawat_dropshipping_woocommerce_providers_traking_links', array( 
				'dhl' => 'http://www.dhl.com/content/g0/en/express/tracking.shtml?brand=DHL&AWB={TRACKINGNUMBER}',
				'aramex' => 'https://www.aramex.com/track/results?ShipmentNumber={TRACKINGNUMBER}',
			)
		);
		$provider_name = trim( strtolower( $provider_name ) );
		if( isset( $providers_link[$provider_name] ) ){
			$tracking_link = str_replace( '{TRACKINGNUMBER}', $tracking_number, $providers_link[$provider_name] );
		}else{
			$tracking_link = 'https://track24.net/?code='.$tracking_number;
		}
		return $tracking_link;
	}

	/**
	 * Display Order Shipment Tracking Information at frontend on order view page
	 *
	 * @since 1.1.0
	 *
	 */
	public function knawat_dropshipwc_display_shipment_tracking( $order_id ) {

		$tracking_data = $this->knawat_dropshipwc_get_tracking_details( $order_id );
		if( $tracking_data['_shipment_tracking_number'] != '' && $tracking_data['_shipment_provider_name'] != '' ){
			wc_get_template( 'myaccount/shipment-tracking.php', array( 'tracking_data' => $tracking_data ), 'dropshipping-woocommerce/', KNAWAT_DROPWC_PLUGIN_DIR . '/templates/' );	
		}		
	}

	/**
	 * Register Order meta or shippment tracking details.
	 *
	 * @since 1.1.0
	 *
	 */
	public function knawat_dropshipwc_register_order_meta(){
		$args = array(
		    'sanitize_callback' => 'sanitize_text_field',
		    'type' => 'string',
		    'single' => true,
		    'show_in_rest' => true,
		);

		register_meta( 'shop_order', '_shipment_provider_name', $args );
		register_meta( 'shop_order', '_shipment_tracking_number', $args );
		register_meta( 'shop_order', '_shipment_tracking_link', $args );
		register_meta( 'shop_order', '_shipment_date_shipped', $args );
	}
}

$knawat_shipment_traking = new Knawat_Dropshipping_Woocommerce_Shipment_Traking();