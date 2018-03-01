/* Shipping Address Edit button */
jQuery( document).ready( function(){
	jQuery( ".knawat-shipment-wrap a.edit_shipment_traking" ).on("click", function(){
		jQuery( ".knawat-shipment-wrap .knawat-shipment-info" ).hide();
		jQuery( ".knawat-shipment-wrap .knawat-shipment-edit" ).show();
	});
});