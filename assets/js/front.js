( function(jQuery) {
	"use strict";

	if (jQuery('body').find(".cfspzw-form-code").length == 0 ){
		return false;
	}

	function cardFormValidate(){
		var cardValid = 0;

		//card number validation
		jQuery('#twocheckout_card_no').validateCreditCard(function(result){
			var cardType = (result.card_type == null)?'':result.card_type.name;

			if(cardType == 'visa'){
				var backPosition = result.valid?'15px -161px, calc(100% + 90px) -87px':'15px -129px, calc(100% + 90px) -68px;';
				jQuery('#two_checkout_cardtype').val('visa');
			}else if(cardType == 'visa_electron'){
				var backPosition = result.valid?'15px -205px, calc(100% + 90px) -87px':'15px -129px, calc(100% + 90px) -68px;';
				jQuery('#two_checkout_cardtype').val('visaelectron');
			}else if(cardType == 'mastercard'){
				var backPosition = result.valid?'15px -245px, calc(100% + 90px) -86px':'15px -129px, calc(100% + 90px) -68px;';
				jQuery('#two_checkout_cardtype').val('mastercard');
			}else if(cardType == 'maestro'){
				var backPosition = result.valid?'15px -289px, calc(100% + 90px) -87px':'15px -129px, calc(100% + 90px) -68px;';
				jQuery('#two_checkout_cardtype').val('maestro');
			}else if(cardType == 'discover'){
				var backPosition = result.valid?'15px -329px, calc(100% + 90px) -87px':'15px -129px, calc(100% + 90px) -68px;';
				jQuery('#two_checkout_cardtype').val('discover');
			}else if(cardType == 'amex'){
				var backPosition = result.valid?'15px -371px, calc(100% + 90px) -87px':'15px -129px, calc(100% + 90px) -68px;';
				jQuery('#two_checkout_cardtype').val('amex');
			}else if(cardType == 'jcb'){
				var backPosition = result.valid?'15px -414px, calc(100% + 90px) -87px':'15px -129px, calc(100% + 90px) -68px;';
				jQuery('#two_checkout_cardtype').val('jcb');
			}else{
				var backPosition = result.valid?'15px -121px, calc(100% + 90px) -87px':'15px -129px, calc(100% + 90px) -68px;';
				jQuery('#two_checkout_cardtype').val('');
			}				
			if(result.valid){
				jQuery("#card_type").val(cardType);
				jQuery('#twocheckout_card_no').css("background-position", backPosition);
				jQuery("#twocheckout_card_no").removeClass('required');
				cardValid = 1;
			}else{
				var backPosition = '10px -118px, calc(100% + 90px) -58px';
				jQuery('#twocheckout_card_no').css("background-position", backPosition);
				jQuery("#twocheckout_card_no").addClass('required');
				cardValid = 0;
			}
		});

		return cardValid;
	}

	// Card Expiry validation
	function card_expired_check(){
		var month = String(jQuery("#twocheckout_expMonth").val()).trim();
		var year = String(jQuery("#twocheckout_expYear").val()).trim();

		if (!/^\d+$/.test(month)) {
			return false;
		}
		if (!/^\d+$/.test(year)) {
			return false;
		}
		if (!((1 <= month && month <= 12))) {
			return false;
		}
		if (year.length === 2) {
			if (year < 70) {
				year = "20" + year;
			} else {
				year = "19" + year;
			}
		}
		if (year.length !== 4) {
			return false;
		}

		var expiry = new Date(year, month);
		var currentTime = new Date;
		expiry.setMonth(expiry.getMonth() - 1);
		expiry.setMonth(expiry.getMonth() + 1, 1);

		if(expiry < currentTime) {
			jQuery("#twocheckout_expMonth").addClass('required');
			jQuery("#twocheckout_expYear").addClass('required');
			return 1;
		}else{
			jQuery("#twocheckout_expMonth").removeClass('required');
			jQuery("#twocheckout_expYear").removeClass('required');
			return 0;
		}
	}
	
	jQuery(function() {


		if (jQuery('body').find(".two-checkout-country").length > 0){ 

			jQuery('.two-checkout-country').select2();

		}

		jQuery(document).on('keyup','.cfspzw-form-code #twocheckout_card_no',function(){
			cardFormValidate();
		});


		jQuery(document).on('click','.wpcf7-submit', function(e) {

			if( jQuery('.ajax-loader').hasClass('is-active') ) {

				e.preventDefault();
				return false;

			}else{

				// load conatct form loader function
				jQuery('div.wpcf7 .ajax-loader').addClass('is-active');
				jQuery('div.wpcf7 .ajax-loader').css("visibility", 'visible');

				//Check Card detail  empty fields
				jQuery('.chechout_cardInfo .payment-required-fields').each(function() {
					if ( jQuery.trim( jQuery(this).val() ) == '' ) {
						jQuery(this).addClass('required');
					}else{
						jQuery(this).removeClass('required');  
					}
				});

				// Cvv validation
				var CVV_validate = jQuery("#twocheckout_card_CVV").val().trim();
				if(CVV_validate.length <= 2 ){
					jQuery("#twocheckout_card_CVV").addClass('required');
				}else{
					jQuery("#twocheckout_card_CVV").removeClass('required');
				}

				// get unique card detail which use during payment
				if(  jQuery("#twocheckout_card_no").val() != '' && 
					jQuery("#twocheckout_card_CVV").val() != ''  && 
					jQuery("#twocheckout_expMonth").val() != '' && 
					jQuery("#twocheckout_expYear").val() != '' &&
					jQuery("#twocheckout_card_holder_name").val() != ''
				){
					if( card_expired_check() == '' ){
						
						setTimeout(function() {
							if (jQuery('body').find(".wpcf7-not-valid-tip").length > 0 ){
								jQuery('.wpcf7-submit').removeAttr('disabled');
								jQuery('div.wpcf7 .ajax-loader').removeClass('is-active');
								jQuery('div.wpcf7 .ajax-loader').css("visibility", 'hidden');
							}else{
								jQuery('.wpcf7-submit').attr('disabled', 'disabled');
								jQuery('div.wpcf7 .ajax-loader').addClass('is-active');
								jQuery('div.wpcf7 .ajax-loader').css("visibility", 'visible');
							}
						}, 700);
						
					}else{
						
						jQuery('.wpcf7-submit').removeAttr('disabled');
						jQuery('div.wpcf7 .ajax-loader').css("visibility", 'hidden');

						setTimeout(function() {
							jQuery('.wpcf7-submit').attr('disabled', 'disabled');
							jQuery('div.wpcf7 .ajax-loader').addClass('is-active');
							jQuery('div.wpcf7 .ajax-loader').css("visibility", 'visible');
						}, 700);	
					}

				}else{

					card_expired_check();

					jQuery('div.wpcf7 .ajax-loader').addClass('is-active');
					jQuery('div.wpcf7 .ajax-loader').css("visibility", 'visible');

					setTimeout(function() {
						if (jQuery('body').find(".wpcf7-not-valid-tip").length > 0 ){
							jQuery('.wpcf7-submit').removeAttr('disabled');
							jQuery('div.wpcf7 .ajax-loader').removeClass('is-active');
							jQuery('div.wpcf7 .ajax-loader').css("visibility", 'hidden');
						}else{
							jQuery('.wpcf7-submit').attr('disabled', 'disabled');
							jQuery('div.wpcf7 .ajax-loader').addClass('is-active');
							jQuery('div.wpcf7 .ajax-loader').css("visibility", 'visible');
						}
					}, 700);
				}
			}
		});
	});

	// After email send redirect page which we set in form option
	document.addEventListener('wpcf7mailsent', function( event ) {
		jQuery('.wpcf7-submit').removeAttr('disabled');

		jQuery('div.wpcf7 .ajax-loader').removeClass('is-active');
		jQuery('div.wpcf7 .ajax-loader').css("visibility", 'hidden');

		var backPosition_tick ='15px -119px,calc(100% + 90px) -60px';
		jQuery('#twocheckout_card_no').css( "background-position", backPosition_tick );

		var contactform_id = event.detail.contactFormId;
		var redirection_url = event.detail.apiResponse.redirection_url;
		if ( redirection_url != '' && redirection_url != undefined ) {
			setTimeout(function() {
				window.location = redirection_url;
			}, 1000);
		}
	} );
	
	// Remove disable submit button aftre proccess done
	document.addEventListener('wpcf7submit', function( event ) {
		
		setTimeout(function() {
			if (jQuery('body').find(".wpcf7-not-valid-tip").length > 0 ){

				jQuery('.wpcf7-submit').removeAttr('disabled');
				
				jQuery('div.wpcf7 .ajax-loader').removeClass('is-active');
				jQuery('div.wpcf7 .ajax-loader').css("visibility", 'hidden');

			}
		}, 700);

	} );

	// Remove disable submit button aftre proccess done
	document.addEventListener('wpcf7mailfailed', function( event ) {

		jQuery('.wpcf7-submit').removeAttr('disabled');

		jQuery('div.wpcf7 .ajax-loader').removeClass('is-active');
		jQuery('div.wpcf7 .ajax-loader').css("visibility", 'hidden');

		var contactform_id = event.detail.contactFormId;
		var redirection_url = event.detail.apiResponse.redirection_url;
		if ( redirection_url != '' && redirection_url != undefined ) {
			setTimeout(function() {
				window.location = redirection_url;
			}, 1000);
		}
	} );

	// Stop User to enter vale character in CSV in card detail.
	jQuery("#twocheckout_card_CVV").keypress(function (e) {
		//if the letter is not digit then display error and don't type anything
		if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
			return false;
		}
	});

} )( jQuery );