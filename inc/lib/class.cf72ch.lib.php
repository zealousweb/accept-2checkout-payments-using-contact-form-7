<?php
/**
 * CF72CH_Lib Class
 *
 * Handles the Library functionality.
 *
 * @package WordPress
 * @package Accept 2Checkout Payments Using Contact Form 7
 * @since 1.2
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF72CH_Lib' ) ) {

	class CF72CH_Lib {

		var $data_fields = array(
			'_form_id'				=> 'Form ID/Name',
			'_user_name'			=> 'User Name',
			'_email'				=> 'Email Address',
			'_transaction_id'		=> 'Transaction ID',
			'_invoice_no'			=> 'Invoice ID',
			'_order_id'				=> 'Order ID',
			'_amount'				=> 'Amount',
			'_quantity'				=> 'Quantity',
			'_total'				=> 'Total',
			'_submit_time'			=> 'Submit Time',
			'_request_ip'			=> 'Request IP',
			'_currency'				=> 'Currency code',
			'_card_holder_name'		=> 'Card Holder Name',
			'_form_data'			=> 'Seralize Data',
			'_transaction_status'	=> 'Transaction status',
			'_transaction_response'	=> 'Transaction response',
		);

		function __construct() {

			add_action( 'wpcf7_init', array( $this, 'action__cf72ch_wpcf7_verify_version' ), 10, 0 );

			add_action( 'wpcf7_init', array( $this, 'action__cf72ch_wpcf7_init' ), 10, 0 );

			add_action( 'wpcf7_before_send_mail', array( $this, 'action__wpcf7_before_send_mail' ), 20, 3 );

			add_shortcode( 'two-checkout-details', array( $this, 'shortcode__two_checkout_details' ) );

			add_action('wp_ajax_order__update_status', array( $this, 'order__update_status' ) );
			add_action('wp_ajax_nopriv_order__update_status', array( $this, 'order__update_status' ) );

		}

		/*
		   ###     ######  ######## ####  #######  ##    ##  ######
		  ## ##   ##    ##    ##     ##  ##     ## ###   ## ##    ##
		 ##   ##  ##          ##     ##  ##     ## ####  ## ##
		##     ## ##          ##     ##  ##     ## ## ## ##  ######
		######### ##          ##     ##  ##     ## ##  ####       ##
		##     ## ##    ##    ##     ##  ##     ## ##   ### ##    ##
		##     ##  ######     ##    ####  #######  ##    ##  ######
		*/

		/**
		 * 2checkout Verify CF7 dependencies.
		 *
		 * @method action__cf72ch_wpcf7_verify_version
		 *
		 */
		function action__cf72ch_wpcf7_verify_version(){

			$cf7_verify = $this->wpcf7_version();

			if ( version_compare( $cf7_verify, '5.2' ) >= 0 ) {
				add_filter( 'wpcf7_feedback_response',	array( $this, 'filter__cf72ch_wpcf7_ajax_json_echo' ), 20, 2 );				
			} else{
				add_filter( 'wpcf7_ajax_json_echo',	array( $this, 'filter__cf72ch_wpcf7_ajax_json_echo' ), 20, 2 );
			}

		}
		

		/**
		 * Update 2checkout Order status after payment done 
		 *
		 * @method order__update_status
		 *
		 *  @param  array
		 *
		 * @return	boolean
		 */
		function order__update_status() {
			
			global $wpdb;

			$metas = array(
				'_invoice_no', '_transaction_status','_form_id',
			);

			foreach ( $metas as $i=>$meta_key ) {
				$meta_fields[] = 'm' . $i . '.meta_value as ' . $meta_key;
				$meta_joins[] = ' left join ' . $wpdb->postmeta . ' as m' . $i . ' on m' . $i . '.post_id=' . $wpdb->posts . '.ID and m' . $i . '.meta_key="' . $meta_key . '"';
			}
			$request = "SELECT *, " .  join(',', $meta_fields) . " FROM $wpdb->posts ";
			$request .= join(' ', $meta_joins);
			$request .= " WHERE post_status='pending' AND post_type='cf72ch_data' ";

			$pageposts = $wpdb->get_results($request, OBJECT);

			if ( $pageposts ) {

				global $post;

				foreach ( $pageposts as $post ) {

					if( $post->_transaction_status == 'AUTHRECEIVED' ) {

						$merchant_code 	= sanitize_text_field( get_post_meta( $post->_form_id, CF72CH_META_PREFIX . 'merchant_code', true ) );
						$secret_key 	= sanitize_text_field( get_post_meta( $post->_form_id, CF72CH_META_PREFIX . 'secret_key', true ) );

						$order_info = $this->getOrderInfo( $post->_invoice_no, $merchant_code, $secret_key );

						if( isset( $order_info['Status'] ) && 
							isset( $order_info['OrderNo'] ) && 
							$order_info['Status'] == 'COMPLETE' && 
							$order_info['OrderNo'] != '' 
						) {

							update_post_meta( $post->ID, '_transaction_status', sanitize_text_field( $order_info['Status'] ) );
							update_post_meta( $post->ID, '_order_id', sanitize_text_field( $order_info['OrderNo'] ) );
							update_post_meta( $post->ID, '_transaction_response', json_encode( $order_info ) );

							$post = array( 'ID' => $post->ID, 'post_status' => 'publish' );
							wp_update_post($post);
						}
					}
				}

			} else {
				return 0;
			}

			exit;
		}


		/**
		 * Initialize 2checkout tag
		 *
		 * @method action__cf72ch_wpcf7_init
		 *
		 *  @param  array form_tag
		 *
		 * @return	mixed
		 */
		function action__cf72ch_wpcf7_init() {

			wpcf7_add_form_tag(
				array( 'two_checkout', 'two_checkout*' ),
				array( $this, 'wpcf7_two_checkout_form_tag_handler' ),
				array( 'name-attr' => true )
			);

			wpcf7_add_form_tag(
				array( 'two_checkout_country', 'two_checkout_country*' ),
				array( $this, 'wpcf7_two_checkout_country_form_tag_handler' ),
				array( 'name-attr' => true )
			);

		}


		/**
		 * Action: CF7 before send email
		 *
		 * @method action__wpcf7_before_send_mail
		 *
		 * @param  object $contact_form WPCF7_ContactForm::get_instance()
		 * @param  bool   $abort
		 * @param  object $contact_form WPCF7_Submission class
		 *
		 */
		function action__wpcf7_before_send_mail( $contact_form, $abort, $wpcf7_submission ) {

			$submission		= WPCF7_Submission::get_instance(); // CF7 Submission Instance
			$form_ID		= $contact_form->id();
			$form_instance	= WPCF7_ContactForm::get_instance($form_ID); // CF7 From Instance

			if ( $submission ) {
				// CF7 posted data
				$posted_data = $submission->get_posted_data();
			}
			
			if ( !empty( $form_ID ) ) {

				$use_2checkout = intval( get_post_meta( $form_ID, CF72CH_META_PREFIX . 'use_2checkout', true ) );

				if ( empty( $use_2checkout ) )
					return;

				$use_2checkout				= intval( get_post_meta( $form_ID, CF72CH_META_PREFIX . 'use_2checkout', true ) );
				$payment_mode				= intval( get_post_meta( $form_ID, CF72CH_META_PREFIX . 'payment_mode', true ) );
				$merchant_code				= sanitize_text_field( get_post_meta( $form_ID, CF72CH_META_PREFIX . 'merchant_code', true ) );
				$secret_key					= sanitize_text_field( get_post_meta( $form_ID, CF72CH_META_PREFIX . 'secret_key', true ) );
				$currency					= sanitize_text_field( get_post_meta( $form_ID, CF72CH_META_PREFIX . 'currency', true ) );
				$success_returnurl			= sanitize_text_field( get_post_meta( $form_ID, CF72CH_META_PREFIX . 'success_returnurl', true ) );
				$cancel_returnurl			= sanitize_text_field( get_post_meta( $form_ID, CF72CH_META_PREFIX . 'cancel_returnurl', true ) );
				$country					= sanitize_text_field( get_post_meta( $form_ID, CF72CH_META_PREFIX . 'country', true ) );
				$two_checkout_order_name	= sanitize_text_field( get_post_meta( $form_ID, CF72CH_META_PREFIX . 'two_checkout_order_name', true ) );
				
				$amount						= sanitize_text_field( get_post_meta( $form_ID, CF72CH_META_PREFIX . 'amount', true ) );
				$quantity					= sanitize_text_field( get_post_meta( $form_ID, CF72CH_META_PREFIX . 'quantity', true ) );
				$email						= sanitize_text_field( get_post_meta( $form_ID, CF72CH_META_PREFIX . 'customer_email', true ) );

				$billing_first_name			= sanitize_text_field( get_post_meta( $form_ID, CF72CH_META_PREFIX . 'billing_first_name', true ) );
				$billing_last_name			= sanitize_text_field( get_post_meta( $form_ID, CF72CH_META_PREFIX . 'billing_last_name', true ) );
				$billing_address			= sanitize_text_field( get_post_meta( $form_ID, CF72CH_META_PREFIX . 'billing_address', true ) );
				$billing_city				= sanitize_text_field( get_post_meta( $form_ID, CF72CH_META_PREFIX . 'billing_city', true ) );
				$billing_state				= sanitize_text_field( get_post_meta( $form_ID, CF72CH_META_PREFIX . 'billing_state', true ) );
				$billing_zipcode			= sanitize_text_field( get_post_meta( $form_ID, CF72CH_META_PREFIX . 'billing_zipcode', true ) );
				$billing_country			= sanitize_text_field( get_post_meta( $form_ID, CF72CH_META_PREFIX . 'billing_country', true ) );

				$amount_val		= ( ( !empty( $amount ) && array_key_exists( $amount, $posted_data ) ) ? floatval( $posted_data[$amount] ) : '0' );
				$quanity_val	= ( ( !empty( $quantity ) && array_key_exists( $quantity, $posted_data ) ) ? floatval( $posted_data[$quantity] ) : '' );
				$exceed_ct		= sanitize_text_field( substr( get_option( '_exceed_cf72ch_l' ), 6 ) );
				$email			= ( ( !empty( $email ) && array_key_exists( $email, $posted_data ) ) ? $posted_data[$email] : '' );

				if( isset( $posted_data['two_checkout'] ) && !empty( $posted_data['two_checkout'] ) ){
					$form_post_data = $posted_data['two_checkout'];
				}else{
					add_filter( 'wpcf7_skip_mail', array( $this, 'filter__wpcf7_skip_mail' ), 20 );
					$_SESSION[ CF72CH_META_PREFIX . 'form_tag_error' . $form_ID ] =  __( '2checkout creadit card info not there, please contact admin', 'accept-2checkout-payments-using-contact-form-7' );
					return;
				}

				extract( $form_post_data );

				if( empty( $merchant_code ) || empty( $secret_key ) ){
					add_filter( 'wpcf7_skip_mail', array( $this, 'filter__wpcf7_skip_mail' ), 20 );
					$_SESSION[ CF72CH_META_PREFIX . 'failed' . $form_ID ] = true;
					$_SESSION[ CF72CH_META_PREFIX . 'form_message' . $form_ID ] =  __( '2checkout API credentials are missing', 'accept-2checkout-payments-using-contact-form-7' );
					return;
				}

				if( empty( $two_checkout_order_name ) || empty( $billing_first_name ) || empty( $billing_last_name ) ||empty( $billing_address ) || empty( $billing_city ) || empty( $billing_state ) || empty( $billing_zipcode ) ||empty( $billing_country ) ){

					add_filter( 'wpcf7_skip_mail', array( $this, 'filter__wpcf7_skip_mail' ), 20 );
					$_SESSION[ CF72CH_META_PREFIX . 'failed' . $form_ID ] = true;
					$_SESSION[ CF72CH_META_PREFIX . 'form_message' . $form_ID ] =  __( 'Account not configured properly, please contact admin', 'accept-2checkout-payments-using-contact-form-7' );
					return;
				}

				if (
					!empty( $amount )
					&& array_key_exists( $amount, $posted_data )
					&& is_array( $posted_data[$amount] )
					&& !empty( $posted_data[$amount] )
				) {
					$val = 0;
					foreach ( $posted_data[$amount] as $k => $value ) {
						$val = $val + floatval($value);
					}
					$amount_val = $val;
				}

				if (
					!empty( $quantity )
					&& array_key_exists( $quantity, $posted_data )
					&& is_array( $posted_data[$quantity] )
					&& !empty( $posted_data[$quantity] )
				) {
					$qty_val = 0;
					foreach ( $posted_data[$quantity] as $k => $qty ) {
						$qty_val = $qty_val + floatval($qty);
					}
					$quanity_val = $qty_val;
				}

				 $amountPayable = (float) $amount_val;

				// Check whether 2checkout merchant_code & secret_key is not empty
				if ( !empty( $merchant_code ) &&  !empty( $secret_key ) ) {
					
					if($payment_mode == 'sandbox'){
						$testMode = 'TEST';
					}else{
						$testMode = 'CC';
					}

					$userdata = array();

					if (
						!empty( $billing_first_name )
						and $get_billing_first_name = ( ( !empty( $billing_first_name ) && array_key_exists( $billing_first_name, $posted_data ) ) ? $posted_data[$billing_first_name] : '' )
					){
						$userdata['billing_first_name'] = $get_billing_first_name;
					}

					if (
						!empty( $billing_last_name )
						and $get_billing_last_name = ( ( !empty( $billing_last_name ) && array_key_exists( $billing_last_name, $posted_data ) ) ? $posted_data[$billing_last_name] : '' )
					){
						$userdata['billing_last_name'] = $get_billing_last_name;
					}

					if (
						!empty( $billing_address )
						and $get_billing_address = ( ( !empty( $billing_address ) && array_key_exists( $billing_address, $posted_data ) ) ? $posted_data[$billing_address] : '' )
					){
						$userdata['billing_address'] = $get_billing_address;
					}

					if (
						!empty( $billing_city )
						and $get_billing_city = ( ( !empty( $billing_city ) && array_key_exists( $billing_city, $posted_data ) ) ? $posted_data[$billing_city] : '' )
					){
						$userdata['billing_city'] = $get_billing_city;
					}

					if (
						!empty( $billing_state )
						and $get_billing_state = ( ( !empty( $billing_state ) && array_key_exists( $billing_state, $posted_data ) ) ? $posted_data[$billing_state] : '' )
					){
						$userdata['billing_state'] = $get_billing_state;
					}

					if (
						!empty( $billing_zipcode )
						and $get_billing_zipcode = ( ( !empty( $billing_zipcode ) && array_key_exists( $billing_zipcode, $posted_data ) ) ? $posted_data[$billing_zipcode] : '' )
					){
						$userdata['billing_zipcode'] = $get_billing_zipcode;
					}

					if (
						!empty( $billing_country )
						and $get_billing_country = ( ( !empty( $billing_country ) && array_key_exists( $billing_country, $posted_data ) ) ? $posted_data[$billing_country] : '' )
					){
						$userdata['billing_country'] = $get_billing_country;
					}



					$field_error = array();

					if( empty( $get_billing_first_name ) ) {
						$field_error[] = $billing_first_name;
					}
					if( empty( $get_billing_last_name ) ) {
						$field_error[] = $billing_last_name;
					}
					if( empty( $get_billing_address ) ) {
						$field_error[] = $billing_address;
					}
					if( empty( $get_billing_city ) ) {
						$field_error[] = $billing_city;
					}
					if( empty( $get_billing_state ) ) {
						$field_error[] = $billing_state;
					}
					if( empty( $get_billing_zipcode ) ) {
						$field_error[] = $billing_zipcode;
					}
					if( empty( $get_billing_country ) ) {
						$field_error[] = $billing_country;
					}
					if ( empty( $amountPayable ) ) {
						$field_error['amount'] = $amount;
					}
					if ( $amountPayable < 0 && $amountPayable != 0 ) {
						$field_error['amount'] = $amount;
					}


					if(!empty($field_error)){
						add_filter( 'wpcf7_skip_mail', array( $this, 'filter__wpcf7_skip_mail' ), 20 );
						$_SESSION[ CF72CH_META_PREFIX . 'failed' . $form_ID ] = false;
						$_SESSION[ CF72CH_META_PREFIX . 'field_error' . $form_ID ] = $field_error;
						return;
					}

					$post_data = array(
						"Country" => $userdata['billing_country'],
						"Currency" => $currency,
						"CustomerIP" => $this->getUserIpAddr(),
						"ExternalReference" => "REST_API_AVANGTE",
						"Language" => "en",
						"Source" => 'Order Form '. get_bloginfo( 'name' ),
						"BillingDetails" => array(
							"Address1" => $userdata['billing_address'],
							"City" => $userdata['billing_city'],
							"State" => $userdata['billing_state'],
							"CountryCode" => $userdata['billing_country'],
							"Email" => $email,
							"FirstName" => $userdata['billing_first_name'],
							"LastName" => $userdata['billing_last_name'],
							"Zip" => $userdata['billing_zipcode']
						),
						"Items" => array(
							0 => array(
								"Name" => trim( $two_checkout_order_name ),
								"Quantity" =>  $quanity_val ? $quanity_val  : '1',
								"IsDynamic" => true,
								"Tangible" => false,
								"PurchaseType" => "PRODUCT",
								"Price" => array(
									"Amount" => $amount_val,
								)
							)
						),
						"PaymentDetails" => array(
							"Type" => $testMode,
							"Currency" => $currency,
							"CustomerIP" => $this->getUserIpAddr(),
							"PaymentMethod" => array(
								"CardNumber" => trim( $twocheckout_card_no ),
								"CardType" => $twocheckout_cardtype,
								"ExpirationYear" => $twocheckout_expYear,
								"ExpirationMonth" => $twocheckout_expMonth,
								"CCID" => trim( $twocheckout_card_CVV ),
								"HolderName" => $twocheckout_card_holder_name,
								"RecurringEnabled" => false,	
								"Vendor3DSReturnURL" => ( ( !empty( $success_returnurl ) && $success_returnurl != "Select page"  ) ? ( get_permalink( $success_returnurl ) ) : get_permalink( $submission->get_meta('container_post_id') ) ),
      							"Vendor3DSCancelURL" => ( ( !empty( $cancel_returnurl ) && $cancel_returnurl != "Select page" ) ? ( get_permalink( $cancel_returnurl ) ) : get_permalink( $submission->get_meta('container_post_id') ) ),
							)
						)
					);

					$post_json_encode = json_encode( $post_data );
					
					$place_order = $this->placeOrder( $merchant_code, $secret_key, $post_json_encode );
					
					if( isset( $place_order['RefNo'] )
						&& !empty( $place_order['RefNo'] )
					)
					{	
						$RefNo = $place_order['RefNo'];
						$OrderNo = $place_order['OrderNo'];
						$CustomerIP = $place_order['PaymentDetails']['CustomerIP'];
						$BillingDetails_Email = $place_order['BillingDetails']['Email'];
						$Currency = strtoupper( $place_order['Currency'] );
						$NetPrice = $place_order['NetPrice'];
						$UnitNetPrice = $place_order['Items'][0]['Price']['UnitNetPrice'];
						$Quantity = $place_order['Items'][0]['Quantity'];

						if( !empty( $place_order['Errors'] ) ) {

							$fetch_error_case = array_keys( $place_order['Errors'] );

							$error_case = implode(" ", $fetch_error_case );

							$error_msg = implode(" ", $place_order['Errors'] );

							$Status =  $error_case.' : '.$error_msg;	

							add_filter( 'wpcf7_skip_mail', array( $this, 'filter__wpcf7_skip_mail' ), 20 );
							$_SESSION[ CF72CH_META_PREFIX . 'failed' . $form_ID ] = true;
							$_SESSION[ CF72CH_META_PREFIX . 'form_message' . $form_ID ] = __( $Status );
						
							if ( !empty( $cancel_returnurl ) && $cancel_returnurl != "Select page" ) {

								$redirect_url = add_query_arg(
									array(
										'form'		=> $form_ID,
										'invoice'	=> '',
										'amount'	=> $amountPayable.' '.$Currency,
										'message'	=> $Status,
									),
									esc_url( get_permalink( $cancel_returnurl ) )
								);

								$_SESSION[ CF72CH_META_PREFIX . 'return_url' . $form_ID ] = $redirect_url;

								if ( !$submission->is_restful() ) {
									wp_redirect( $redirect_url );
									exit;
								}

							} else {

								$_SESSION[ CF72CH_META_PREFIX . 'return_url' . $form_ID ] = "";

							}
							return;

						} else {

							if( $place_order['Status'] == 'AUTHRECEIVED' ) {
								$Status = 'Payment is in process from 2checkout side.';
								$Status_set_indb = $place_order['Status'];
							} else {
								$Status = $place_order['Status'];
							}

							$post_status = 'pending';
							
						}

						
						$attachment = '';

						if ( !empty( $submission->uploaded_files() ) ) {

							$cf7_verify = $this->wpcf7_version();

							if ( version_compare( $cf7_verify, '5.4' ) >= 0 ) {
								$uploaded_files = $this->zw_cf7_upload_files( $submission->uploaded_files(), 'new' );
							}else{
								$uploaded_files = $this->zw_cf7_upload_files( array( $submission->uploaded_files() ), 'old' );
							}

							if ( !empty( $uploaded_files ) ) {
								$attachment = serialize( str_replace('\\', '/', $uploaded_files ) );
							}
						}


						$ch_post_id = wp_insert_post( array (
							'post_type' => CF72CH_POST_TYPE,
							'post_title' => ( !empty( $BillingDetails_Email ) ? $BillingDetails_Email : $RefNo ), // email/invoice_no
							'post_status' => 'pending',
							'comment_status' => 'closed',
							'ping_status' => 'closed',
						) );

						if ( !empty( $ch_post_id ) ) {

							$stored_data = $posted_data;
							unset( $stored_data['two_checkout'] );						

							$total_amount =  $NetPrice.' '.$Currency;

							if(!get_option('_exceed_cf72ch')){
								sanitize_text_field( add_option('_exceed_cf72ch', '1') );
							}else{
								$exceed_val = sanitize_text_field( get_option( '_exceed_cf72ch' ) ) + 1;
								update_option( '_exceed_cf72ch', $exceed_val );								
							}
							
							if ( !empty( sanitize_text_field( get_option( '_exceed_cf72ch' ) ) ) && sanitize_text_field( get_option( '_exceed_cf72ch' ) ) > $exceed_ct ) {
								$stored_data['_exceed_num_cf72ch'] = '1';
							}

							add_post_meta( $ch_post_id, '_form_id', sanitize_text_field( $form_ID ) );
							add_post_meta( $ch_post_id, '_email', sanitize_text_field( $BillingDetails_Email ) );
							add_post_meta( $ch_post_id, '_user_name', sanitize_text_field( $userdata['billing_first_name'].' '.$userdata['billing_last_name'] ));
							add_post_meta( $ch_post_id, '_invoice_no', sanitize_text_field( $RefNo ) );
							add_post_meta( $ch_post_id, '_order_id', sanitize_text_field( $OrderNo ) );
							add_post_meta( $ch_post_id, '_amount', sanitize_text_field( $amount_val.' '.$Currency ) );
							add_post_meta( $ch_post_id, '_quantity', sanitize_text_field( $quanity_val ) );
							add_post_meta( $ch_post_id, '_total', sanitize_text_field( $total_amount ) );
							add_post_meta( $ch_post_id, '_request_ip', sanitize_text_field( $CustomerIP ) );
							add_post_meta( $ch_post_id, '_currency', sanitize_text_field( $Currency ) );
							add_post_meta( $ch_post_id, '_card_holder_name', sanitize_text_field( $twocheckout_card_holder_name ) );
							add_post_meta( $ch_post_id, '_form_data', serialize( $stored_data ) );
							add_post_meta( $ch_post_id, '_transaction_response', json_encode( $place_order ) );
							add_post_meta( $ch_post_id, '_transaction_status', sanitize_text_field( $Status_set_indb ) );
							add_post_meta( $ch_post_id, '_attachment', sanitize_text_field( $attachment ) );							
						}

						add_filter( 'wpcf7_mail_tag_replaced_two_checkout*', function( $replaced, $submitted, $html, $mail_tag ) use ( $Status, $RefNo, $total_amount) {
								$data = array();
								$data[] = 'Invoice Number: ' . sanitize_text_field( $RefNo );
								$data[] = 'Amount: ' . sanitize_text_field( $total_amount );
								$data[] = 'Transaction Status: ' . sanitize_text_field( $Status );

								if ( !empty( $html ) ) {
									return implode( '<br/>', $data );
								} else {
									return implode( "\n", $data );
								}
							return $replaced;
						}, 10, 5 );

						if( $Status != 'Pending' ) {
							$returnurl = $success_returnurl;
							$form_message = false;
						}else{
							$returnurl = $cancel_returnurl;
							$form_message = true;
						}

						$_SESSION[ CF72CH_META_PREFIX . 'failed' . $form_ID ] = $form_message;
						$_SESSION[ CF72CH_META_PREFIX . 'form_message' . $form_ID ] = __( $Status );

						if ( !empty( $returnurl ) && $returnurl != "Select page" ) {

							$redirect_url = add_query_arg(
								array(
									'form'		=> sanitize_text_field( $form_ID ),
									'invoice'	=> sanitize_text_field( $RefNo ),
									'amount'	=> sanitize_text_field( $total_amount ),
								),
								esc_url( get_permalink( $returnurl ) )
							);

							$_SESSION[ CF72CH_META_PREFIX . 'return_url' . $form_ID ] = sanitize_text_field( $redirect_url );

							if ( !$submission->is_restful() ) {
								wp_redirect( $redirect_url );
								exit;
							}
						} else {

							$_SESSION[ CF72CH_META_PREFIX . 'return_url' . $form_ID ] = "";

						}
						
					}else{

						add_filter( 'wpcf7_skip_mail', array( $this, 'filter__wpcf7_skip_mail' ), 20 );

						if( isset( $place_order['error'] ) && $place_order['error'] ) {
							$_SESSION[ CF72CH_META_PREFIX . 'form_message' . $form_ID ] = __( 'Due to Some technical issue, please try again!', 'accept-2checkout-payments-using-contact-form-7' );
						}
						if( isset( $place_order['error_code'] ) && $place_order['error_code'] ) {
							$_SESSION[ CF72CH_META_PREFIX . 'form_message' . $form_ID ] = $place_order['message'];
						}

						$_SESSION[ CF72CH_META_PREFIX . 'failed' . $form_ID ] = true;
						return;
					}

				} else {
						add_filter( 'wpcf7_skip_mail', array( $this, 'filter__wpcf7_skip_mail' ), 20 );
						$_SESSION[ CF72CH_META_PREFIX . 'failed' . $form_ID ] = true;
						$_SESSION[ CF72CH_META_PREFIX . 'field_error' . $form_ID ] = __( 'Due to Some technical issue, please try again!', 'accept-2checkout-payments-using-contact-form-7' );
						return;
				}				
			}
			return $submission;
		}


		/*
		######## #### ##       ######## ######## ########   ######
		##        ##  ##          ##    ##       ##     ## ##    ##
		##        ##  ##          ##    ##       ##     ## ##
		######    ##  ##          ##    ######   ########   ######
		##        ##  ##          ##    ##       ##   ##         ##
		##        ##  ##          ##    ##       ##    ##  ##    ##
		##       #### ########    ##    ######## ##     ##  ######
		*/

		/**
		* 2Checkout Response Display usig shortcode
		*
		* @method shortcode__two_checkout_details
		*
		* @param  string
		*
		* @return html
		*/

		function shortcode__two_checkout_details() {

			$form_id 	= (int)( isset( $_REQUEST['form'] ) ? sanitize_text_field( $_REQUEST['form'] ) : '' ) ;
			$invoice 	= ( isset( $_REQUEST['invoice'] ) ? sanitize_text_field( $_REQUEST['invoice'] ) : '' ) ;
			$amount 	= ( isset( $_REQUEST['amount'] ) ? sanitize_text_field( $_REQUEST['amount'] ) : '' ) ;
			$message 	= ( isset( $_REQUEST['message'] ) ? sanitize_text_field( $_REQUEST['message'] ) : '' ) ;

			ob_start();

			if ( empty( $invoice ) || empty( $form_id ) )				

				return '<table class="cf72ch-transaction-details" align="center">' .
					'<tr>'.
						'<th align="left">' . __( 'Response :', 'accept-2checkout-payments-using-contact-form-7' ) . '</th>'.
						'<td align="left" style="color: #f00">' . $message . '</td>'.
					'</tr>' .
				'</table>';

			if( !empty( $invoice ) && !empty( $form_id ) ) {

				$merchant_code 	= esc_attr( get_post_meta( $form_id, CF72CH_META_PREFIX . 'merchant_code', true ) );
				$secret_key 	= esc_attr( get_post_meta( $form_id, CF72CH_META_PREFIX . 'secret_key', true ) );

				$order_info = $this->getOrderInfo( $invoice, $merchant_code, $secret_key );
				
				if( isset( $order_info['Status'] ) && 
					isset( $order_info['OrderNo'] ) && 
					$order_info['Status'] == 'COMPLETE' && 
					$order_info['OrderNo'] != '' 
				) {
					$message = __( 'Payment Successfully Done.', 'accept-2checkout-payments-using-contact-form-7' );
				}else{
					$message = __( 'Payment is in process from 2checkout side.', 'accept-2checkout-payments-using-contact-form-7' );
				}

				echo '<table class="cf72ch-transaction-details" align="center">' .
					'<tr>'.
						'<th align="left">' . __( 'Transaction Amount :', 'accept-2checkout-payments-using-contact-form-7' ) . '</th>'.
						'<td align="left">' . $amount . '</td>'.
					'</tr>' .					
					'<tr>'.
						'<th align="left">' . __( 'Invoice No :', 'accept-2checkout-payments-using-contact-form-7' ) . '</th>'.
						'<td align="left">' . $invoice . '</td>'.
					'</tr>' .
					'<tr>'.
						'<th align="left">' . __( 'Payment Status :', 'accept-2checkout-payments-using-contact-form-7' ) . '</th>'.
						'<td align="left">' . $message . '</td>'.
					'</tr>' .
				'</table>';
			}
			return ob_get_clean();

		}		

		/**
		 * Filter: Modify the contact form 7 response.
		 *
		 * @method filter__cf72ch_wpcf7_ajax_json_echo
		 *
		 * @param  array $response
		 * @param  array $result
		 *
		 * @return array
		 */
		function filter__cf72ch_wpcf7_ajax_json_echo( $response, $result ) {

			$cf7_verify = $this->wpcf7_version();
			
			if (
				   array_key_exists( 'contact_form_id' , $result )
				&& array_key_exists( 'status' , $result )
				&& !empty( $result[ 'contact_form_id' ] )
				&& !empty( $_SESSION[ CF72CH_META_PREFIX . 'form_message' . $result[ 'contact_form_id' ] ] )
				&& $result[ 'status' ] == 'mail_sent'
			) {

				$tmp = $response[ 'message' ];
				$response[ 'message' ] = $_SESSION[ CF72CH_META_PREFIX . 'form_message' . $result[ 'contact_form_id' ] ];
				unset( $_SESSION[ CF72CH_META_PREFIX . 'form_message' . $result[ 'contact_form_id' ] ] );

				if ( isset( $_SESSION[ CF72CH_META_PREFIX . 'return_url' . $result[ 'contact_form_id' ] ] ) ) {
					$response[ 'redirection_url' ] = $_SESSION[ CF72CH_META_PREFIX . 'return_url' . $result[ 'contact_form_id' ] ];
					unset( $_SESSION[ CF72CH_META_PREFIX . 'return_url' . $result[ 'contact_form_id' ] ] );
				}

				if (
					!empty( $_SESSION[ CF72CH_META_PREFIX . 'failed' . $result[ 'contact_form_id' ] ] )
				) {
					$response[ 'status' ] = 'mail_failed';
					unset( $_SESSION[ CF72CH_META_PREFIX . 'failed' . $result[ 'contact_form_id' ] ] );
				} else {
					$response[ 'message' ] = $response[ 'message' ] . ' ' . $tmp;
				}

			}
			
			if (
				   array_key_exists( 'contact_form_id' , $result )
				&& array_key_exists( 'status' , $result )
				&& !empty( $result[ 'contact_form_id' ] )
				&& !empty( $_SESSION[ CF72CH_META_PREFIX . 'field_error' . $result[ 'contact_form_id' ] ] )
				&& $result[ 'status' ] == 'mail_sent'
			) {
				$response[ 'message' ] = __('One or more fields have an error. Please check and try again.', 'accept-2checkout-payments-using-contact-form-7' );
				$response[ 'status' ] = 'validation_failed';
				$fields_msg = array();

				foreach ($_SESSION[ CF72CH_META_PREFIX . 'field_error' . $result[ 'contact_form_id' ] ] as $key => $value) {
					$field_error_message['into'] = 'span.wpcf7-form-control-wrap.'.$value;
					
					if( $key == 'amount' && $key != 0){
						$field_error_message['message'] = __('Please enter amount or valid amount.', 'accept-2checkout-payments-using-contact-form-7');
					}else{
						$field_error_message['message'] = __('The field is required.', 'accept-2checkout-payments-using-contact-form-7');
					}
					$fields_msg[] = $field_error_message;
				}				

				if ( version_compare( $cf7_verify, '5.2' ) >= 0 ) {
					$response[ 'invalid_fields' ] = $fields_msg;
				} else {
					$response[ 'invalidFields' ] = $fields_msg;
				}

				unset( $_SESSION[ CF72CH_META_PREFIX . 'field_error' . $result[ 'contact_form_id' ] ] );
			}

			if (
				   array_key_exists( 'contact_form_id' , $result )
				&& array_key_exists( 'status' , $result )
				&& !empty( $result[ 'contact_form_id' ] )
				&& !empty( $_SESSION[ CF72CH_META_PREFIX . 'form_tag_error' . $result[ 'contact_form_id' ] ] )
				&& $result[ 'status' ] == 'mail_sent'
			) {

				$response[ 'status' ] = 'mail_failed';
				$response[ 'message' ] = $_SESSION[ CF72CH_META_PREFIX . 'form_tag_error' . $result[ 'contact_form_id' ] ];
				unset( $_SESSION[ CF72CH_META_PREFIX . 'form_tag_error' . $result[ 'contact_form_id' ] ] );
			}			

			return $response;
		}

		/**
		 * Filter: Skip email when Stripe enable.
		 *
		 * @method filter__wpcf7_skip_mail
		 *
		 * @param  bool $bool
		 *
		 * @return bool
		 */
		function filter__wpcf7_skip_mail( $bool ) {
			return true;
		}

		/*
		######## ##     ## ##    ##  ######  ######## ####  #######  ##    ##  ######
		##       ##     ## ###   ## ##    ##    ##     ##  ##     ## ###   ## ##    ##
		##       ##     ## ####  ## ##          ##     ##  ##     ## ####  ## ##
		######   ##     ## ## ## ## ##          ##     ##  ##     ## ## ## ##  ######
		##       ##     ## ##  #### ##          ##     ##  ##     ## ##  ####       ##
		##       ##     ## ##   ### ##    ##    ##     ##  ##     ## ##   ### ##    ##
		##        #######  ##    ##  ######     ##    ####  #######  ##    ##  ######
		*/

		/**
		 * - Render CF7 Shortcode on front end.
		 *
		 * @method wpcf7_two_checkout_country_form_tag_handler
		 *
		 * @param $tag
		 *
		 * @return html
		 */
		
		function wpcf7_two_checkout_country_form_tag_handler( $tag ) {

			if ( empty( $tag->name ) ) {
				return '';
			}

			$validation_error = wpcf7_get_validation_error( $tag->name );

			$class = wpcf7_form_controls_class( $tag->type, 'wpcf7-text' );

			if ( in_array( $tag->basetype, array( 'email', 'url', 'tel' ) ) ) {
				$class .= ' wpcf7-validates-as-' . $tag->basetype;
			}

			if ( $validation_error ) {
				$class .= ' wpcf7-not-valid';
			}

			$atts = array();

			if ( $tag->is_required() ) {
				$atts['aria-required'] = 'true';
			}

			$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

			$atts['value'] = 1;

			$atts['type'] = 'hidden';
			$atts['name'] = $tag->name;
			$atts = wpcf7_format_atts( $atts );

			$form_instance = WPCF7_ContactForm::get_current();
			$form_id = $form_instance->id();

			$use_2checkout	= get_post_meta( $form_id, CF72CH_META_PREFIX . 'use_2checkout', true );
			
			if ( empty( $use_2checkout ) ) {
				return;
			}

			if ( !empty( $this->_validate_fields( $form_id ) ) )
				return $this->_validate_fields( $form_id );

			wp_enqueue_style( CF72CH_PREFIX . '_select2' );
			wp_enqueue_script( CF72CH_PREFIX . '_select2' );

			$value = (string) reset( $tag->values );

			$found = 0;
			$html = '';

			ob_start();

			if ( $contact_form = wpcf7_get_current_contact_form() ) {
				$form_tags = $contact_form->scan_form_tags();

				foreach ( $form_tags as $k => $v ) {

					if ( $v['type'] == $tag->type ) {
						$found++;
					}

					if ( $v['name'] == $tag->name ) {
						if ( $found <= 1 ) {
							echo '<span class="select-country wpcf7-form-control-wrap '.sanitize_html_class( $tag->name ).'">
								<select name="' . $tag->name . '" class="wpcf7-form-control two-checkout-country">
									<option value="">Select Country</option>';
									echo $this->get_country();
							echo '</select></span>';
						}
						break;
					}
				}
			}

			return ob_get_clean();
		}

		
		/**
		 * 
		 * @method Get Country
		 *
		 * @param array
		 *
		 * @return html
		 */
		function get_country(){

			$country_lists	= array( 'AF' => 'Afghanistan',
									'AX' => 'Aland Islands',
									'AL' => 'Albania',
									'DZ' => 'Algeria',
									'AS' => 'American Samoa',
									'AD' => 'Andorra',
									'AO' => 'Angola',
									'AI' => 'Anguilla',
									'AQ' => 'Antarctica',
									'AG' => 'Antigua and Barbuda',
									'AR' => 'Argentina',
									'AM' => 'Armenia',
									'AW' => 'Aruba',
									'AU' => 'Australia',
									'AT' => 'Austria',
									'AZ' => 'Azerbaijan',
									'BS' => 'Bahamas',
									'BH' => 'Bahrain',
									'BD' => 'Bangladesh',
									'BB' => 'Barbados',
									'BY' => 'Belarus',
									'BE' => 'Belgium',
									'BZ' => 'Belize',
									'BJ' => 'Benin',
									'BM' => 'Bermuda',
									'BT' => 'Bhutan',
									'BO' => 'Bolivia',
									'BQ' => 'Bonaire, Saint Eustatius and Saba',
									'BA' => 'Bosnia and Herzegovina',
									'BW' => 'Botswana',
									'BV' => 'Bouvet Island',
									'BR' => 'Brazil',
									'IO' => 'British Indian Ocean Territory',
									'VG' => 'British Virgin Islands',
									'BN' => 'Brunei',
									'BG' => 'Bulgaria',
									'BF' => 'Burkina Faso',
									'BI' => 'Burundi',
									'KH' => 'Cambodia',
									'CM' => 'Cameroon',
									'CA' => 'Canada',
									'CV' => 'Cape Verde',
									'KY' => 'Cayman Islands',
									'CF' => 'Central African Republic',
									'TD' => 'Chad',
									'CL' => 'Chile',
									'CN' => 'China',
									'CX' => 'Christmas Island',
									'CC' => 'Cocos Islands',
									'CO' => 'Colombia',
									'KM' => 'Comoros',
									'CK' => 'Cook Islands',
									'CR' => 'Costa Rica',
									'HR' => 'Croatia',
									'CU' => 'Cuba',
									'CW' => 'Curacao',
									'CY' => 'Cyprus',
									'CZ' => 'Czech Republic',
									'CD' => 'Democratic Republic of the Congo',
									'DK' => 'Denmark',
									'DJ' => 'Djibouti',
									'DM' => 'Dominica',
									'DO' => 'Dominican Republic',
									'TL' => 'East Timor',
									'EC' => 'Ecuador',
									'EG' => 'Egypt',
									'SV' => 'El Salvador',
									'GQ' => 'Equatorial Guinea',
									'ER' => 'Eritrea',
									'EE' => 'Estonia',
									'ET' => 'Ethiopia',
									'FK' => 'Falkland Islands',
									'FO' => 'Faroe Islands',
									'FJ' => 'Fiji',
									'FI' => 'Finland',
									'FR' => 'France',
									'GF' => 'French Guiana',
									'PF' => 'French Polynesia',
									'TF' => 'French Southern Territories',
									'GA' => 'Gabon',
									'GM' => 'Gambia',
									'GE' => 'Georgia',
									'DE' => 'Germany',
									'GH' => 'Ghana',
									'GI' => 'Gibraltar',
									'GR' => 'Greece',
									'GL' => 'Greenland',
									'GD' => 'Grenada',
									'GP' => 'Guadeloupe',
									'GU' => 'Guam',
									'GT' => 'Guatemala',
									'GG' => 'Guernsey',
									'GN' => 'Guinea',
									'GW' => 'Guinea-Bissau',
									'GY' => 'Guyana',
									'HT' => 'Haiti',
									'HM' => 'Heard Island and McDonald Islands',
									'HN' => 'Honduras',
									'HK' => 'Hong Kong',
									'HU' => 'Hungary',
									'IS' => 'Iceland',
									'IN' => 'India',
									'ID' => 'Indonesia',
									'IR' => 'Iran',
									'IQ' => 'Iraq',
									'IE' => 'Ireland',
									'IM' => 'Isle of Man',
									'IL' => 'Israel',
									'IT' => 'Italy',
									'CI' => 'Ivory Coast',
									'JM' => 'Jamaica',
									'JP' => 'Japan',
									'JE' => 'Jersey',
									'JO' => 'Jordan',
									'KZ' => 'Kazakhstan',
									'KE' => 'Kenya',
									'KI' => 'Kiribati',
									'XK' => 'Kosovo',
									'KW' => 'Kuwait',
									'KG' => 'Kyrgyzstan',
									'LA' => 'Laos',
									'LV' => 'Latvia',
									'LB' => 'Lebanon',
									'LS' => 'Lesotho',
									'LR' => 'Liberia',
									'LY' => 'Libya',
									'LI' => 'Liechtenstein',
									'LT' => 'Lithuania',
									'LU' => 'Luxembourg',
									'MO' => 'Macao',
									'MK' => 'Macedonia',
									'MG' => 'Madagascar',
									'MW' => 'Malawi',
									'MY' => 'Malaysia',
									'MV' => 'Maldives',
									'ML' => 'Mali',
									'MT' => 'Malta',
									'MH' => 'Marshall Islands',
									'MQ' => 'Martinique',
									'MR' => 'Mauritania',
									'MU' => 'Mauritius',
									'YT' => 'Mayotte',
									'MX' => 'Mexico',
									'FM' => 'Micronesia',
									'MD' => 'Moldova',
									'MC' => 'Monaco',
									'MN' => 'Mongolia',
									'ME' => 'Montenegro',
									'MS' => 'Montserrat',
									'MA' => 'Morocco',
									'MZ' => 'Mozambique',
									'MM' => 'Myanmar',
									'NA' => 'Namibia',
									'NR' => 'Nauru',
									'NP' => 'Nepal',
									'NL' => 'Netherlands',
									'NC' => 'New Caledonia',
									'NZ' => 'New Zealand',
									'NI' => 'Nicaragua',
									'NE' => 'Niger',
									'NG' => 'Nigeria',
									'NU' => 'Niue',
									'NF' => 'Norfolk Island',
									'KP' => 'North Korea',
									'MP' => 'Northern Mariana Islands',
									'NO' => 'Norway',
									'OM' => 'Oman',
									'PK' => 'Pakistan',
									'PW' => 'Palau',
									'PS' => 'Palestinian Territory',
									'PA' => 'Panama',
									'PG' => 'Papua New Guinea',
									'PY' => 'Paraguay',
									'PE' => 'Peru',
									'PH' => 'Philippines',
									'PN' => 'Pitcairn',
									'PL' => 'Poland',
									'PT' => 'Portugal',
									'PR' => 'Puerto Rico',
									'QA' => 'Qatar',
									'CG' => 'Republic of the Congo',
									'RE' => 'Reunion',
									'RO' => 'Romania',
									'RU' => 'Russia',
									'RW' => 'Rwanda',
									'BL' => 'Saint Barthelemy',
									'SH' => 'Saint Helena',
									'KN' => 'Saint Kitts and Nevis',
									'LC' => 'Saint Lucia',
									'MF' => 'Saint Martin',
									'PM' => 'Saint Pierre and Miquelon',
									'VC' => 'Saint Vincent and the Grenadines',
									'WS' => 'Samoa',
									'SM' => 'San Marino',
									'ST' => 'Sao Tome and Principe',
									'SA' => 'Saudi Arabia',
									'SN' => 'Senegal',
									'RS' => 'Serbia',
									'SC' => 'Seychelles',
									'SL' => 'Sierra Leone',
									'SG' => 'Singapore',
									'SX' => 'Sint Maarten',
									'SK' => 'Slovakia',
									'SI' => 'Slovenia',
									'SB' => 'Solomon Islands',
									'SO' => 'Somalia',
									'ZA' => 'South Africa',
									'GS' => 'South Georgia and the South Sandwich Islands',
									'KR' => 'South Korea',
									'SS' => 'South Sudan',
									'ES' => 'Spain',
									'LK' => 'Sri Lanka',
									'SD' => 'Sudan',
									'SR' => 'Suriname',
									'SJ' => 'Svalbard and Jan Mayen',
									'SZ' => 'Swaziland',
									'SE' => 'Sweden',
									'CH' => 'Switzerland',
									'SY' => 'Syria',
									'TW' => 'Taiwan',
									'TJ' => 'Tajikistan',
									'TZ' => 'Tanzania',
									'TH' => 'Thailand',
									'TG' => 'Togo',
									'TK' => 'Tokelau',
									'TO' => 'Tonga',
									'TT' => 'Trinidad and Tobago',
									'TN' => 'Tunisia',
									'TR' => 'Turkey',
									'TM' => 'Turkmenistan',
									'TC' => 'Turks and Caicos Islands',
									'TV' => 'Tuvalu',
									'VI' => 'U.S. Virgin Islands',
									'UG' => 'Uganda',
									'UA' => 'Ukraine',
									'AE' => 'United Arab Emirates',
									'GB' => 'United Kingdom',
									'US' => 'United States',
									'UM' => 'United States Minor Outlying Islands',
									'UY' => 'Uruguay',
									'UZ' => 'Uzbekistan',
									'VU' => 'Vanuatu',
									'VA' => 'Vatican',
									'VE' => 'Venezuela',
									'VN' => 'Vietnam',
									'WF' => 'Wallis and Futuna',
									'EH' => 'Western Sahara',
									'YE' => 'Yemen',
									'ZM' => 'Zambia',
									'ZW' => 'Zimbabwe',
							);

			$country_lists = apply_filters( CF72CH_META_PREFIX .'country', $country_lists );

			$country_list_html = '';

			foreach ($country_lists as $iso => $country_name) {
				$country_list_html .='<option value="'.$iso.'">'.$country_name.'</option>';
			}
			return $country_list_html;

		}

		/**
		 * - Render CF7 Shortcode on front end.
		 *
		 * @method wpcf7_two_checkout_form_tag_handler
		 *
		 * @param $tag
		 *
		 * @return html
		 */

		function wpcf7_two_checkout_form_tag_handler( $tag ) {

			if ( empty( $tag->name ) ) {
				return '';
			}

			$validation_error = wpcf7_get_validation_error( $tag->name );

			$class = wpcf7_form_controls_class( $tag->type, 'wpcf7-text' );

			if ( in_array( $tag->basetype, array( 'email', 'url', 'tel' ) ) ) {
				$class .= ' wpcf7-validates-as-' . $tag->basetype;
			}

			if ( $validation_error ) {
				$class .= ' wpcf7-not-valid';
			}

			$atts = array();

			if ( $tag->is_required() ) {
				$atts['aria-required'] = 'true';
			}

			$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

			$atts['value'] = 1;

			$atts['type'] = 'hidden';
			$atts['name'] = $tag->name;
			$atts = wpcf7_format_atts( $atts );

			$form_instance = WPCF7_ContactForm::get_current();
			$form_id = $form_instance->id();

			$use_2checkout					= sanitize_text_field( get_post_meta( $form_id, CF72CH_META_PREFIX . 'use_2checkout', true ) );
			$payment_mode					= sanitize_text_field( get_post_meta( $form_id, CF72CH_META_PREFIX . 'payment_mode', true ) );
			$merchant_code					= sanitize_text_field( get_post_meta( $form_id, CF72CH_META_PREFIX . 'merchant_code', true ) );
			$secret_key						= sanitize_text_field( get_post_meta( $form_id, CF72CH_META_PREFIX . 'secret_key', true ) );
			$currency						= sanitize_text_field( get_post_meta( $form_id, CF72CH_META_PREFIX . 'currency', true ) );

			wp_enqueue_style( CF72CH_PREFIX . '_front_css');
			wp_enqueue_script( CF72CH_PREFIX . '_CardValidator_js');
			wp_enqueue_script( CF72CH_PREFIX . '_front_js');
			wp_localize_script( CF72CH_PREFIX . '_front_js', 'cf72ch_object',
				array(
					'environment' => $payment_mode,
					'merchant_code' => $secret_key,
				)
			);

			if ( empty( $use_2checkout ) ) {
				return;
			}

			if ( !empty( $this->_validate_fields( $form_id ) ) )
				return $this->_validate_fields( $form_id );


			if( empty( $merchant_code ) || empty( $secret_key ) ){
				return $this->_validate_merchent( $form_id , '2checkout API credentials are missing.' );
			}

			$value = (string) reset( $tag->values );

			$found = 0;
			$html = '';

			ob_start();

			if ( $contact_form = wpcf7_get_current_contact_form() ) {
				$form_tags = $contact_form->scan_form_tags();

				foreach ( $form_tags as $k => $v ) {

					if ( $v['type'] == $tag->type ) {
						$found++;
					}

					if ( $v['name'] == $tag->name ) {
						if ( $found <= 1 ) {

							$cvv_field_placeholder	 = __( 'Card Verification Number (CVV)', 'accept-2checkout-payments-using-contact-form-7' );
							$cvv_field_placeholder	 = apply_filters( 'twocheckout_cvv_field_placeholder', $cvv_field_placeholder );

							$card_number_field_placeholder	 = __( 'Card Number', 'accept-2checkout-payments-using-contact-form-7' );
							$card_number_field_placeholder	 = apply_filters( 'twocheckout_card_number_field_placeholder', $card_number_field_placeholder );

							$card_expMonth_field_placeholder	 = __( 'MM', 'accept-2checkout-payments-using-contact-form-7' );
							$card_expMonth_field_placeholder	 = apply_filters( 'twocheckout_card_expm_field_placeholder', $card_expMonth_field_placeholder );

							$card_expYear_field_placeholder	 = __( 'YY', 'accept-2checkout-payments-using-contact-form-7' );
							$card_expYear_field_placeholder	 = apply_filters( 'twocheckout_card_expy_field_placeholder', $card_expYear_field_placeholder );

							$card_cvv_field_placeholder	 = __( 'CVV', 'accept-2checkout-payments-using-contact-form-7' );
							$card_cvv_field_placeholder	 = apply_filters( 'twocheckout_card_cvv_field_placeholder', $card_cvv_field_placeholder );

							$card_hoder_name_field_placeholder	 = __( 'Card Holder Name', 'accept-2checkout-payments-using-contact-form-7' );
							$card_hoder_name_field_placeholder	 = apply_filters( 'twocheckout_card_holder_name_field_placeholder', $card_hoder_name_field_placeholder );

							$card_section_title	 = __( 'Pay via 2Checkout. Accept Credit Cards, Debit Cards', 'accept-2checkout-payments-using-contact-form-7' );
							$card_section_title	 = apply_filters( 'two_checkout_section_title', $card_section_title );

							echo '<div class="cfspzw-form-code chechout_cardInfo card_payment_info">' .
								'<span class="credit_card_details wpcf7-form-control-wrap '.sanitize_html_class( $tag->name ).'">'.

								'<h4>'. $card_section_title .'</h4>

								<div class="card_payment_field_group">
									<div class="card_payment_field_holder">
										<label class="card_payment_field_label">'. __( 'Card Holder Name', 'accept-2checkout-payments-using-contact-form-7' ) .'</label>
										<div class="card_payment_field_element">
											<input id="twocheckout_card_holder_name" class="payment-required-fields cp_field_element cp_field_name" type="text" name="' . $tag->basetype . '[twocheckout_card_holder_name]" value="" size="20" value="" autocomplete="off" required placeholder="' . $card_hoder_name_field_placeholder . '" />
										</div>
									</div>
								</div>

								<div class="card_payment_field_group">
									<div class="card_payment_field_number">
										<label class="card_payment_field_label">'. __( 'Card Number', 'accept-2checkout-payments-using-contact-form-7' ) .'</label>
										<div class="card_payment_field_element">
											<input id="twocheckout_card_no" class="payment-required-fields cp_field_element cp_field_number" type="text" name="' . $tag->basetype . '[twocheckout_card_no]" size="20" value="" autocomplete="off" required placeholder="' . $card_number_field_placeholder . '" />
										</div>
										<p class="logme"></p>
									</div>
								</div>

								<div class="card_payment_field_group card_payment_field_group_flex credit_card_mid_detail">
									<div class="card_payment_field_auto card_payment_field_expire exp_month">
										<label class="card_payment_field_label">'. __( 'Expiration Date' ,'accept-2checkout-payments-using-contact-form-7' ) .'</label>
										<div class="card_payment_field_element">
											<select id="twocheckout_expMonth" class="payment-required-fields cp_field_element cp_field_month" name="' . $tag->basetype . '[twocheckout_expMonth]" required placeholder="' . $card_expMonth_field_placeholder . '">';
												for ($i = 1; $i <= 12; $i++) {
													$monthValue = $i;
													if (strlen($i) < 2) {
														$monthValue = "0" . $monthValue;
													}
													echo '<option value="'.$monthValue.'">'.$monthValue.'</option>';
												}
											echo '</select>
											<select id="twocheckout_expYear" class="payment-required-fields cp_field_element card_payment_field_year" name="' . $tag->basetype . '[twocheckout_expYear]" required placeholder="' . $card_expYear_field_placeholder . '">';
												$today	= (int) date( 'Y', time() );
												for ( $i = 0; $i < 24; $i ++ ) {
													echo '<option value='.$today.'> '.$today.' </option>';
													$today++;
												}
											echo '</select>
										</div>
									</div>
									<div class="card_payment_field_auto card_payment_field_cvv exp_cvv">
										<label class="card_payment_field_label">'. __( 'CVV', 'accept-2checkout-payments-using-contact-form-7' ) .'</label>
										<div class="card_payment_field_element">
											<input id="twocheckout_card_CVV" class="payment-required-fields cp_field_element cp_field_cvv" value="" type="text" name="' . $tag->basetype . '[twocheckout_card_CVV]" size="" value="" maxlength="4" autocomplete="off" required placeholder="' . $card_cvv_field_placeholder . '" />
										</div>
									</div>
								</div>
								<input id="two_checkout_cardtype" name="' . $tag->basetype . '[twocheckout_cardtype]" type="hidden" value="">'.
								'<div id="cf7_two_checkout_erros"></div>'.
								$validation_error.
							'</div>';
						}
						break;
					}
				}
			}

			return ob_get_clean();
		}

		/**
		 * Function: validate_merchant
		 *
		 * - Used to validate the Merchant information to show the card form.
		 *
		 * @return string
		 */
		function _validate_merchent( $form_id , $message) {
				return __( $message , CF72CH_PREFIX );
		}

		/**
		 * Function: _validate_fields
		 *
		 * @method _validate_fields
		 *
		 * @param int $form_id
		 *
		 * @return string
		 */
		function _validate_fields( $form_id ) {

			$use_2checkout					= sanitize_text_field( get_post_meta( $form_id, CF72CH_META_PREFIX . 'use_2checkout', true ) );
			$payment_mode					= sanitize_text_field( get_post_meta( $form_id, CF72CH_META_PREFIX . 'payment_mode', true ) );
			$merchant_code					= sanitize_text_field( get_post_meta( $form_id, CF72CH_META_PREFIX . 'merchant_code', true ) );
			$secret_key						= sanitize_text_field( get_post_meta( $form_id, CF72CH_META_PREFIX . 'secret_key', true ) );

			if ( !empty( $use_2checkout ) ) {

				if( empty( $merchant_code ) || empty( $secret_key ) )
					return __( 'Please enter Merchant Code or Secret Key.', CF72CH_PREFIX );
			}

			return false;
		}


		/**
		 * Function: validDate
		 *
		 * @method validDate
		 *
		 * @return string
		 */
		function validDate($year, $month)
		{
			$month = str_pad($month, 2, '0', STR_PAD_LEFT);

			if (! preg_match('/^20\d\d$/', $year)) {
				return false;
			}

			if (! preg_match('/^(0[1-9]|1[0-2])$/', $month)) {
				return false;
			}

			// past date
			if ($year < date('Y') || $year == date('Y') && $month < date('m')) {
				return false;
			}

			return true;
		}



		/**
		 * Function: getUserIpAddr
		 *
		 * @method getUserIpAddr
		 *
		 * @return string
		 */
		
		function getUserIpAddr() {
			$ip = false;

			if ( ! empty( $_SERVER['HTTP_X_REAL_IP'] ) ) {
				$ip = filter_var( $_SERVER['HTTP_X_REAL_IP'], FILTER_VALIDATE_IP );
			} elseif ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				// Check ip from share internet.
				$ip = filter_var( $_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP );
			} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				$ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
				if ( is_array( $ips ) ) {
					$ip = filter_var( $ips[0], FILTER_VALIDATE_IP );
				}
			} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
				$ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP );
			}

			$ip			= false !== $ip ? $ip : '127.0.0.1';
			$ip_array	= explode( ',', $ip );
			$ip_array	= array_map( 'trim', $ip_array );

			if($ip_array[0] == '::1' || $ip_array[0] == '127.0.0.1'){
				$ipser = array('http://ipv4.icanhazip.com','http://v4.ident.me','http://bot.whatismyipaddress.com');
				shuffle($ipser);
				$ipservices = array_slice($ipser, 0,1);
				$ret = wp_remote_get($ipservices[0]);
				if(!is_wp_error($ret)){
					if (isset($ret['body'])) {
						return sanitize_text_field( $ret['body'] );
					}
				}
			}

			return sanitize_text_field( apply_filters( 'cf72ch_get_ip', $ip_array[0] ) );
		}


		/**
		* Get the attachment upload directory from plugin.
		*
		* @method zw_wpcf7_upload_tmp_dir
		*
		* @return string
		*/
		function zw_wpcf7_upload_tmp_dir() {

			$upload = wp_upload_dir();
			$upload_dir = $upload['basedir'];
			$cf7sa_upload_dir = $upload_dir . '/cf72ch-uploaded-files';

			if ( !is_dir( $cf7sa_upload_dir ) ) {
				mkdir( $cf7sa_upload_dir, 0755 );
			}

			return $cf7sa_upload_dir;
		}


		/**
		 * Copy the attachment into the plugin folder.
		 *
		 * @method zw_cf7_upload_files
		 *
		 * @param  array $attachment
		 *
		 * @uses $this->zw_wpcf7_upload_tmp_dir(), WPCF7::wpcf7_maybe_add_random_dir()
		 *
		 * @return array
		 */
		function zw_cf7_upload_files( $attachment, $version ) {
			if( empty( $attachment ) )
			return;

			$new_attachment = $attachment;

			foreach ( $attachment as $key => $value ) {
				$tmp_name = $value;
				$uploads_dir = wpcf7_maybe_add_random_dir( $this->zw_wpcf7_upload_tmp_dir() );
				foreach ($tmp_name as $newkey => $file_path) {
					$get_file_name = explode( '/', $file_path );
					$new_uploaded_file = path_join( $uploads_dir, end( $get_file_name ) );
					if ( copy( $file_path, $new_uploaded_file ) ) {
						chmod( $new_uploaded_file, 0755 );
						if($version == 'old'){
							$new_attachment_file[$newkey] = $new_uploaded_file;
						}else{
							$new_attachment_file[$key] = $new_uploaded_file;
						}
					}
				}
			}
			return $new_attachment_file;
		}

		/**
		 * Place Order.
		 *
		 * @method placeOrder
		 *
		 * @param  string
		 *
		 * @uses action__wpcf7_before_send_mail
		 *
		 * @return array
		 */
		function placeOrder( $merchantCode, $key, $post_json_encode ) {

			$date = gmdate('Y-m-d H:i:s', time() );
			$hash = hash_hmac( 'md5', strlen( $merchantCode ) . $merchantCode . strlen( $date ) . $date, $key );

			$header = [
				'Content-Type'	=> 'application/json',
				'Accept'		=> 'application/json',
				'X-Avangate-Authentication'	=> 'code="' . $merchantCode . '" date="' . $date . '" hash="' . $hash . '"'
			];

			$response = wp_remote_post( 'https://api.avangate.com/rest/6.0/orders/', [
				'method'	=> 'POST',
				'timeout'	=> 120,
				'sslverify'	=> false,
				'headers'	=> $header,
				'body'		=> $post_json_encode,
			] );

			if ( is_wp_error( $response ) ) {

				$error_message = $response->get_error_message();

				return [ 'error' => __( 'Could not connect' ) . ' ' . $error_message ];

			} else {

				$foo = json_decode( $response['body'], true );

				return  $foo;
			}
		}

		/**
		 * Retrive Order Detail.
		 *
		 * @method getOrderInfo
		 *
		 * @param  string
		 *
		 * @return array
		 */
		function getOrderInfo( $refNo, $merchantCode, $key ) {

			$date = gmdate('Y-m-d H:i:s', time() );
			$hash = hash_hmac( 'md5', strlen( $merchantCode ) . $merchantCode . strlen( $date ) . $date, $key );

			$header = [
				'Content-Type'	=> 'application/json',
				'Accept'		=> 'application/json',
				'X-Avangate-Authentication'	=> 'code="' . $merchantCode . '" date="' . $date . '" hash="' . $hash . '"'
			];

			$response = wp_remote_post( 'https://api.avangate.com/rest/6.0/orders/' . $refNo . '/', [
				'method'	=> 'GET',
				'timeout'	=> 120,
				'sslverify'	=> true,
				'headers'	=> $header,
				'body'		=> ''
			] );

			if ( is_wp_error( $response ) ) {

				$error_message = $response->get_error_message();

				return [ 'error' => __( 'Could not connect' ) . ' ' . $error_message ];

			} else {

				$foo = json_decode( $response['body'], true );

				return  $foo;
			}			
		}

		/**
		 * Get current conatct from 7 version.
		 *
		 * @method wpcf7_version
		 *
		 * @return string
		 */			
		function wpcf7_version() {

			$wpcf7_path = plugin_dir_path( CF72CH_DIR ) . 'contact-form-7/wp-contact-form-7.php'; 

			if( ! function_exists('get_plugin_data') ){
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$plugin_data = get_plugin_data( $wpcf7_path );
					
			return $plugin_data['Version'];
		}
	}

	add_action( 'plugins_loaded', function() {
		CF72CH()->lib = new CF72CH_Lib;
	} );
}
