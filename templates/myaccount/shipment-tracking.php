<?php
/**
 * Template for Shipment Treacking informtion on order page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $tracking_data ){
	?>
	<h2 class="woocommerce-order-shipment_tracking_title"><?php _e( 'Tracking Information', 'dropshipping-woocommerce' ); ?></h2>

	<table class="woocommerce-table woocommerce-table--shipment_tracking shop_table knawat_shipment_tracking shop_table_responsive">
		<thead>
			<tr>
				<th class="woocommerce-table__provider-name provider-name">
					<?php _e( 'Provider', 'dropshipping-woocommerce' ); ?>
				</th>
				<th class="woocommerce-table__provider-name tracking-number tracking-number">
					<?php _e( 'Tracking Number', 'dropshipping-woocommerce' ); ?>
				</th>
				<th class="woocommerce-table__provider-name date-shipped date-shipped">
					<?php _e( 'Date', 'dropshipping-woocommerce' ); ?>
				</th>
				<th class="woocommerce-table__provider-name order-actions order-actions">&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<tr class="tracking">
				<td class="provider-name" data-title="<?php _e( 'Provider', 'dropshipping-woocommerce' ); ?>">
					<?php echo esc_html( $tracking_data['_shipment_provider_name'] ); ?>
				</td>
				<td class="tracking-number" data-title="<?php _e( 'Tracking Number', 'dropshipping-woocommerce' ); ?>">
					<?php echo esc_html( $tracking_data['_shipment_tracking_number'] ); ?>
				</td>
				<td class="date-shipped" data-title="<?php _e( 'Date', 'dropshipping-woocommerce' ); ?>">	
					<?php
					if( $tracking_data['_shipment_date_shipped'] != '' ){
						echo date_i18n( get_option( 'date_format' ), strtotime( $tracking_data['_shipment_date_shipped'] ) );
					}
					?>
				</td>
				<td class="order-actions">
					<a href="<?php echo esc_url( $tracking_data['_shipment_tracking_link'] ); ?>" target="_blank" class="button">
						<?php _e( 'Track', 'dropshipping-woocommerce' ); ?>
					</a>
				</td>
			</tr>
		</tbody>
	</table>

<?php
}
