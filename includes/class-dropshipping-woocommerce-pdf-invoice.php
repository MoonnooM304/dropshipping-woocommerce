<?php
/**
 * Class for PDF invoice Generation & invoice_url in order API
 *
 * @link       http://knawat.com/
 * @since      1.0.0
 *
 * @package    Knawat_Dropshipping_Woocommerce
 * @subpackage Knawat_Dropshipping_Woocommerce/includes
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Knawat_Dropshipping_Woocommerce_PDF_Invoice {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'knawat_dropshipwc_order_pdf_invoice' ), 9999 );
		add_filter( 'woocommerce_rest_prepare_shop_order', array( $this, 'knawat_dropshipwc_add_invoice_url_api' ), 10, 3 );
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( $this, 'knawat_dropshipwc_add_invoice_url_api' ), 10, 3 );		
	}

	/**
	 * Generate pdf invoice for the order on the fly
	 *
	 * @since    1.0.0
	 */
	function knawat_dropshipwc_order_pdf_invoice() {
		
		if( isset( $_GET['knawat_action'] ) && trim($_GET['knawat_action']) != '' ){
			
			$knawat_action = explode( '-', trim($_GET['knawat_action']) );
			$action 	= base64_decode( $knawat_action[0] );
			$order_id 	= base64_decode( $knawat_action[1] );
			$order_key	= base64_decode( $knawat_action[2] );
			
			if( $action != 'knawat_pdf_invoice' ){
				return;
			}

			if( empty( $order_id ) || $order_id <= 0 ){
				wp_die( __( "Something went wrong during PDF generation.", 'dropshipping-woocommerce' ) );
			}
			$order_key_order = '';
			if( function_exists( 'wc_get_order' ) ){
				$order = wc_get_order( $order_id );
				$order_key_order = $order->get_order_key();	
			}
			
			if( $order_key != $order_key_order ){
				wp_die( __( "Order key not matching. better luck next time :)", 'dropshipping-woocommerce' ) );	
			}

			if( !function_exists( 'wcpdf_get_document' ) ){
				wp_die( __( "Woocommece PDF Invoice Plugin is not installed.", 'dropshipping-woocommerce' ) );
			}

			// if we got here, we're safe to go!
			$order_ids = array( $order_id );
			try {
				if( function_exists( 'wcpdf_get_document' ) ){
					$document = wcpdf_get_document( 'invoice', $order_ids, true );
					if ( $document ) {

						if ( has_action( 'wpo_wcpdf_created_manually' ) ) {
							do_action( 'wpo_wcpdf_created_manually', $document->get_pdf(), $document->get_filename() );
						}
						$document->output_pdf( 'inline' );

					} else {
						wp_die( __( "Invoice for the selected order(s) could not be generated", 'dropshipping-woocommerce' ) );
					}
				}
			} catch (Exception $e) {
				echo $e->getMessage();
			}
			exit();
		}
	}
	
	/**
	 * Add 'invoice_pdf_url' to the REST API.
	 *
	 * @since    1.0.0
	 *
	 * @param \WP_REST_Response $response The response object.
	 * @param \WP_Post $post Post object.
	 * @param \WP_REST_Request $request Request object.
	 * @return object updated response object
	 */
	function knawat_dropshipwc_add_invoice_url_api( $response, $post, $request ){
		
		if( empty( $response->data ) ){
			return $response;	
		}
        
		$pdf_invoice_url = get_site_url() . '/?knawat_action=' . base64_encode( 'knawat_pdf_invoice' ) . '-' . base64_encode( $response->data['id'] ) . '-' . base64_encode( $response->data['order_key'] );
		$response->data['pdf_invoice_url'] = $pdf_invoice_url;

		return $response;
	}

}

$knawat_pdf_invoice = new Knawat_Dropshipping_Woocommerce_PDF_Invoice();