<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) exit;

$knawat_options = get_option( KNAWAT_DROPWC_OPTIONS, array() );
$knawat_connected = 0;
if( isset( $knawat_options['knawat_status'] ) && $knawat_options['knawat_status'] != '' && $knawat_options['knawat_status'] == 'connected' ) {
	$knawat_connected = 1;
}
?>
<div class="knawat_dropshipwc_settings">
	<table class="form-table">
		<tbody>
			<tr class="knawat_dropshipwc_row">
				<th scope="row">
					<label for="knawat_dropshipwc_connect">
						<?php esc_html_e( 'Connect Your store with Knawat', 'dropshipping-woocommerce' ); ?>
					</label>
				</th>
				<td>
					<?php if( $knawat_connect ){ ?>
						<div class="knawat_dropshipwc_connected">
							<span class="dashicons dashicons-yes" style="background-color: green;color: #fff;border-radius: 50%;padding: 4px 4px 3px 3px;"></span> 
							<strong style="color: green; font-size: 18px;" > <?php esc_html_e( 'Connected', 'dropshipping-woocommerce' ); ?></strong>
						</div>
					<?php }else{ ?>
						<a class="button button-primary" href="<?php echo admin_url('admin.php?page=knawat_setup&step=knawat_connect'); ?>">
							<?php esc_html_e( 'Connect', 'dropshipping-woocommerce' ); ?>
						</a>
					<?php } ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>