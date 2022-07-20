( function($) {
	"use strict";

	function cf72ch_validate() {
		if ( jQuery( '.cf72ch-settings #cf72ch_use_2checkout' ).prop( 'checked' ) == true ) {
			jQuery( '.cf72ch-settings #cf72ch_two_checkout_order_name,.cf72ch-settings #cf72ch_secret_key,.cf72ch-settings #cf72ch_merchant_code' ).prop( 'required', true );
			jQuery('.cf72ch-settings .cf72ch-required-fields').each(function() {
				jQuery( jQuery(this) ).prop( 'required', true );
			});
		} else {
			jQuery( '.cf72ch-settings #cf72ch_two_checkout_order_name,.cf72ch-settings #cf72ch_secret_key,.cf72ch-settings #cf72ch_merchant_code' ).removeAttr( 'required' );
			jQuery('.cf72ch-settings .cf72ch-required-fields').each(function() {
				jQuery( jQuery(this) ).removeAttr( 'required' );
			});
		}
	}	
	
	if ( jQuery( '.cf72ch-settings #cf72ch_amount' ).val() == '' && jQuery( '.cf72ch-settings #cf72ch_use_2checkout' ).prop( 'checked' ) == true ) {
		cf72ch_validate();
	}

	/**
	 * Validate 2checkout admin option whene plugin functionality enabled for particular form
	 */
	jQuery( document ).on( 'change', '.cf72ch-settings .enable_required', function() {
		cf72ch_validate();		
	});

	 
	function check_2checkout_field_validation(){

		cf72ch_validate();

		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );

		if( jQuery( '.cf72ch-settings #cf72ch_use_2checkout' ).prop( 'checked' ) == true ){			
			var validate = false;
			jQuery('.cf72ch-settings .cf72ch-required-fields, .cf72ch-settings .required-fields').each(function() {
				if ( jQuery.trim( jQuery(this).val() ) == '' ) {
					validate = true;
				}
			});
			if(validate){
				jQuery("#two-checkout-add-on-tab .ui-tabs-anchor").find('span').remove();
				jQuery("#two-checkout-add-on-tab .ui-tabs-anchor").append('<span class="icon-in-circle" aria-hidden="true">!</span>');
			}else{
				jQuery("#two-checkout-add-on-tab .ui-tabs-anchor").find('span').remove();
			}

		}else{
			jQuery("#two-checkout-add-on-tab .ui-tabs-anchor").find('span').remove();
		}		
	}

	/**
	 * Validate 2checkout admin option required fields 
	 */
	jQuery( document ).ready( function() { 

		if(cf72ch_object.translate_string_cf72ch.cf72ch_review != 1){
			if (typeof Cookies.get('review_cf72ch') === 'undefined'){ // no cookie
				jQuery('#myModal').modal('show');
				Cookies.set('review_cf72ch', 'yes', { expires: 15 }); // set cookie expiry to 15 day
			}
		}

		jQuery(".review-cf72ch, .remind-cf72ch").click(function(){
			jQuery("#myModal").modal('hide');
		});

		jQuery(".review-cf72ch").click(function(){
			jQuery.ajax({
				type: "post",
				dataType: "json",
				url: cf72ch_object.ajax_url,
				data: 'action=cf72ch_review_done&value=1',
				success: function(){
				}
			});
		});

		check_2checkout_field_validation() 
	});
	jQuery( document ).on('click',".ui-state-default",function() { 
		check_2checkout_field_validation() 
	});
	
	/**
	 * Remove Conatct from 7 if plugin required field is there.
	 */
	jQuery(document).on('click','input[name="wpcf7-delete"]',function(){
		jQuery('.cf72ch-settings #cf72ch_two_checkout_order_name,.cf72ch-settings #cf72ch_merchant_code,.cf72ch-settings #cf72ch_secret_key').removeAttr( 'required' );

		jQuery('.cf72ch-settings .cf72ch-required-fields').each(function() {
			jQuery( jQuery(this) ).removeAttr( 'required' );
		});
	});

	/**
	 * Apply select2 dunctionality for dropdown box
	 */
	jQuery( document ).ready( function() { 
		jQuery('.cf72ch-settings #cf72ch_payment_mode, .cf72ch-settings #cf72ch_currency, .cf72ch-settings #cf72ch_success_returnurl, .cf72ch-settings #cf72ch_cancel_returnurl').select2();

		jQuery('.cf72ch-settings .cf72ch-required-fields').each(function() {
			jQuery( jQuery(this) ).select2();
		});
	});

	/**
	* Show and hide tooltip logic
	*/
	jQuery( '#cf72ch-currency' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cf72ch-currency' ).pointer({
			pointerClass: 'wp-pointer cf72ch-pointer',
			content: cf72ch_object.translate_string_cf72ch.currency,
			position: 'left center',
		}).pointer('open');
	});

	jQuery( '#cf72ch-order-name' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cf72ch-order-name' ).pointer({
			pointerClass: 'wp-pointer cf72ch-pointer',
			content: cf72ch_object.translate_string_cf72ch.order_name,
			position: 'left center',
		}).pointer('open');
	});

	jQuery( '#cf72ch-merchant-code' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cf72ch-merchant-code' ).pointer({
			pointerClass: 'wp-pointer cf72ch-pointer',
			content: cf72ch_object.translate_string_cf72ch.merchant_code,
			position: 'left center',
		}).pointer('open');
	});

	jQuery( '#cf72ch-secret-key' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cf72ch-secret-key' ).pointer({
			pointerClass: 'wp-pointer cf72ch-pointer',
			content: cf72ch_object.translate_string_cf72ch.secret_key,
			position: 'left center',
		}).pointer('open');
	});
	
	jQuery( '#cf72ch-amount' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cf72ch-amount' ).pointer({
			pointerClass: 'wp-pointer cf72ch-pointer',
			content: cf72ch_object.translate_string_cf72ch.amount,
			position: 'left center',
		}).pointer('open');
	});

	jQuery( '#cf72ch-quantity' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cf72ch-quantity' ).pointer({
			pointerClass: 'wp-pointer cf72ch-pointer',
			content: cf72ch_object.translate_string_cf72ch.quantity,
			position: 'left center',
		}).pointer('open');
	});

	jQuery( '#cf72ch-customer-email' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cf72ch-customer-email' ).pointer({
			pointerClass: 'wp-pointer cf72ch-pointer',
			content: cf72ch_object.translate_string_cf72ch.email,
			position: 'left center',
		}).pointer('open');
	});

	jQuery( '#cf72ch-success-returnurl' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cf72ch-success-returnurl' ).pointer({
			pointerClass: 'wp-pointer cf72ch-pointer',
			content: cf72ch_object.translate_string_cf72ch.success_returnurl,
			position: 'left center',
		}).pointer('open');
	});

	jQuery( '#cf72ch-cancel-returnurl' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cf72ch-cancel-returnurl' ).pointer({
			pointerClass: 'wp-pointer cf72ch-pointer',
			content: cf72ch_object.translate_string_cf72ch.cancel_returnurl,
			position: 'left center',
		}).pointer('open');
	});

	jQuery( '#cf72ch-billing-first-name' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cf72ch-billing-first-name' ).pointer({
			pointerClass: 'wp-pointer cf72ch-pointer',
			content: cf72ch_object.translate_string_cf72ch.billing_first_name,
			position: 'left center',
		}).pointer('open');
	});

	jQuery( '#cf72ch-billing-last-name' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cf72ch-billing-last-name' ).pointer({
			pointerClass: 'wp-pointer cf72ch-pointer',
			content: cf72ch_object.translate_string_cf72ch.billing_last_name,
			position: 'left center',
		}).pointer('open');
	});

	jQuery( '#cf72ch-billing-address' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cf72ch-billing-address' ).pointer({
			pointerClass: 'wp-pointer cf72ch-pointer',
			content: cf72ch_object.translate_string_cf72ch.billing_address,
			position: 'left center',
		}).pointer('open');
	});

	jQuery( '#cf72ch-billing-city' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cf72ch-billing-city' ).pointer({
			pointerClass: 'wp-pointer cf72ch-pointer',
			content: cf72ch_object.translate_string_cf72ch.billing_city,
			position: 'left center',
		}).pointer('open');
	});

	jQuery( '#cf72ch-billing-state' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cf72ch-billing-state' ).pointer({
			pointerClass: 'wp-pointer cf72ch-pointer',
			content: cf72ch_object.translate_string_cf72ch.billing_state,
			position: 'left center',
		}).pointer('open');
	});

	jQuery( '#cf72ch-billing-zipcode' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cf72ch-billing-zipcode' ).pointer({
			pointerClass: 'wp-pointer cf72ch-pointer',
			content: cf72ch_object.translate_string_cf72ch.billing_zipcode,
			position: 'left center',
		}).pointer('open');
	});

	jQuery( '#cf72ch-billing-country' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cf72ch-billing-country' ).pointer({
			pointerClass: 'wp-pointer cf72ch-pointer',
			content: cf72ch_object.translate_string_cf72ch.billing_country,
			position: 'left center',
		}).pointer('open');
	});

} )( jQuery );
