<?php
/**
* Displays content for plugin page
*
* @package WordPress
* @subpackage Accept 2Checkout Payments Using Contact Form 7
* @since 1.2
* @version 1.2
*/

$new_post = ( isset( $_REQUEST[ 'post' ] ) ? sanitize_text_field( $_REQUEST[ 'post' ] ) : '' );

if ( empty( $new_post ) ) {
	$wpcf7 = WPCF7_ContactForm::get_current();
	$new_post = $wpcf7->id();
}

wp_enqueue_script( 'wp-pointer' );
wp_enqueue_style( 'wp-pointer' );

wp_enqueue_style( 'select2' );
wp_enqueue_script( 'select2' );

wp_enqueue_style( CF72CH_PREFIX . '_admin_css' );


$use_2checkout					= intval( get_post_meta( $new_post, CF72CH_META_PREFIX . 'use_2checkout', true ) );
$debug_2checkout				= intval( get_post_meta( $new_post, CF72CH_META_PREFIX . 'debug', true ) );
$payment_mode_val				= sanitize_text_field( get_post_meta( $new_post, CF72CH_META_PREFIX . 'payment_mode', true ) );
$merchant_code					= sanitize_text_field( get_post_meta( $new_post, CF72CH_META_PREFIX . 'merchant_code', true ) );
$secret_key						= sanitize_text_field( get_post_meta( $new_post, CF72CH_META_PREFIX . 'secret_key', true ) );
$two_checkout_order_name		= sanitize_text_field( get_post_meta( $new_post, CF72CH_META_PREFIX . 'two_checkout_order_name', true ) );
$amount							= sanitize_text_field( get_post_meta( $new_post, CF72CH_META_PREFIX . 'amount', true ) );
$quantity						= sanitize_text_field( get_post_meta( $new_post, CF72CH_META_PREFIX . 'quantity', true ) );
$success_returnurl				= sanitize_text_field( get_post_meta( $new_post, CF72CH_META_PREFIX . 'success_returnurl', true ) );
$cancel_returnurl				= sanitize_text_field( get_post_meta( $new_post, CF72CH_META_PREFIX . 'cancel_returnurl', true ) );
$currency						= sanitize_text_field( get_post_meta( $new_post, CF72CH_META_PREFIX . 'currency', true ) );
$customer_email					= sanitize_text_field( get_post_meta( $new_post, CF72CH_META_PREFIX . 'customer_email', true ) );

$billing_first_name				= sanitize_text_field( get_post_meta( $new_post, CF72CH_META_PREFIX . 'billing_first_name', true ) );
$billing_last_name				= sanitize_text_field( get_post_meta( $new_post, CF72CH_META_PREFIX . 'billing_last_name', true ) );
$billing_address				= sanitize_text_field( get_post_meta( $new_post, CF72CH_META_PREFIX . 'billing_address', true ) );
$billing_city					= sanitize_text_field( get_post_meta( $new_post, CF72CH_META_PREFIX . 'billing_city', true ) );
$billing_state					= sanitize_text_field( get_post_meta( $new_post, CF72CH_META_PREFIX . 'billing_state', true ) );
$billing_country				= sanitize_text_field( get_post_meta( $new_post, CF72CH_META_PREFIX . 'billing_country', true ) );
$billing_zipcode				= sanitize_text_field( get_post_meta( $new_post, CF72CH_META_PREFIX . 'billing_zipcode', true ) );
$cf72ch_review					= get_option( 'cf72ch_review' );

$currency_code = array(
	'USD' => 'United States Dollar',
	'AUD' => 'Australian Dollar',
	'BRL' => 'Brazilian Real',
	'CAD' => 'Canadian Dollar',
	'CZK' => 'Czech Koruna',
	'DKK' => 'Danish Krone',
	'EUR' => 'Euro',
	'HKD' => 'Hong Kong Dollar',
	'HUF' => 'Hungarian Forint',
	'INR' => 'Indian Rupee',
	'ILS' => 'Israeli New Shekel',
	'JPY' => 'Japanese Yen',
	'MYR' => 'Malaysian Ringgit',
	'MXN' => 'Mexican Peso',
	'TWD' => 'New Taiwan Dollar',
	'NZD' => 'New Zealand Dollar',
	'NOK' => 'Norwegian Krone',
	'PHP' => 'Philippine Peso',
	'PLN' => 'Polish Zloty',
	'GBP' => 'Pound Sterling',
	'RUB' => 'Russian Ruble',
	'SGD' => 'Singapore Dollar',
	'SEK' => 'Swedish Krona',
	'CHF' => 'Swiss Franc',
	'THB' => 'Thai Baht',	
);

$currency_code = apply_filters( CF72CH_META_PREFIX .'currency_code', $currency_code );

$payment_mode = array(
	'sandbox'		=> __( 'Sandbox', 'accept-2checkout-payments-using-contact-form-7'),
	'production'	=> __( 'Live', 'accept-2checkout-payments-using-contact-form-7')
);

$selected = '';

$args = array(
	'post_type'			=> array( 'page' ),
	'orderby'			=> 'title',
	'posts_per_page'	=> -1
);

$new_page = get_posts( $args );
$all_pages = array();
if ( !empty( $new_page ) ) {
	foreach ( $new_page as $new_pages ) {
		$all_pages[$new_pages->ID] = $new_pages->post_title;
	}
}

if ( !empty( $new_post ) ) {
	$cf7 = WPCF7_ContactForm::get_instance( sanitize_text_field( $_REQUEST['post'] ) );
	$tags = $cf7->collect_mail_tags();
}

echo '<div class="cf72ch-settings">' .
	'<div class="modal fade" id="myModal" role="dialog">
		<div class="modal-dialog">

			<!-- Modal content-->
			<div class="modal-content">
				<div class="inner-modal">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal">&times;</button>
						<h4 class="modal-title">'. esc_html__('Support us!', 'accept-2checkout-payments-using-contact-form-7').'</h4>
					</div>
					<div class="modal-body">
						<p>' . esc_html__('If you like this plugin please spare some time to review us.', 'accept-2checkout-payments-using-contact-form-7').'</p>
					</div>
					<div class="modal-footer">
						<a href="https://wordpress.org/support/plugin/accept-2checkout-payments-using-contact-form-7/reviews/" class="button primary-button review-cf72ch" target="_blank">' . esc_html__('Review us', 'accept-2checkout-payments-using-contact-form-7'). '</a>
						<button type="button" class="btn btn-default remind-cf72ch" data-dismiss="modal">'. esc_html__('Remind Me Later', 'accept-2checkout-payments-using-contact-form-7').'</button>
					</div>
					<div class="bird-icon">
						<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="71.366" height="49.822" viewBox="0 0 71.366 49.822"><defs><linearGradient id="a" x1="0.121" y1="0.5" x2="1.122" y2="0.5" gradientUnits="objectBoundingBox"><stop offset="0" stop-color="#3daeb5"/><stop offset="0.23" stop-color="#56c5d0"/><stop offset="0.505" stop-color="#56c5d0"/><stop offset="0.887" stop-color="#0074a2"/></linearGradient><linearGradient id="b" x1="0.142" y1="-0.312" x2="1.28" y2="1.261" gradientUnits="objectBoundingBox"><stop offset="0" stop-color="#56c5d0"/><stop offset="0.5" stop-color="#0074a2"/><stop offset="1" stop-color="#22566e"/></linearGradient><linearGradient id="c" x1="0.001" y1="0.5" x2="0.996" y2="0.5" gradientUnits="objectBoundingBox"><stop offset="0" stop-color="#c81f66"/><stop offset="0.446" stop-color="#f05b89"/><stop offset="1" stop-color="#c81f66"/></linearGradient><linearGradient id="d" x1="0.023" y1="0.477" x2="0.997" y2="0.477" gradientUnits="objectBoundingBox"><stop offset="0" stop-color="#ffc93e"/><stop offset="1" stop-color="#f69047"/></linearGradient><linearGradient id="e" x1="-0.009" y1="0.5" x2="1.091" y2="0.5" gradientUnits="objectBoundingBox"><stop offset="0" stop-color="#ed1651"/><stop offset="1" stop-color="#f05b7d"/></linearGradient><linearGradient id="f" y1="0.5" x2="1" y2="0.5" gradientUnits="objectBoundingBox"><stop offset="0" stop-color="#22566e"/><stop offset="0.992" stop-color="#3daeb5"/></linearGradient><linearGradient id="g" y1="0.5" x2="1" y2="0.5" gradientUnits="objectBoundingBox"><stop offset="0" stop-color="#0074a2"/><stop offset="1" stop-color="#56c5d0"/></linearGradient></defs><g transform="translate(-6211.895 1682)"><path d="M657.551,270.4H653.7v.708a2.425,2.425,0,0,0,2.417,2.418h3.851v-.708A2.424,2.424,0,0,0,657.551,270.4Z" transform="translate(5609.742 -1905.704)" fill="#0074a2"/><path d="M615.251,270.4H611.4v.708a2.424,2.424,0,0,0,2.418,2.418h3.851v-.708a2.424,2.424,0,0,0-2.418-2.418Z" transform="translate(5644.736 -1905.704)" fill="#f05b89"/><path d="M572.951,270.4H569.1v.708a2.424,2.424,0,0,0,2.418,2.418h3.851v-.708A2.425,2.425,0,0,0,572.951,270.4Z" transform="translate(5679.73 -1905.704)" fill="#f79548"/><g transform="translate(6211.895 -1682)"><path d="M396.147,61.5c-5.544-1.9-8.065-4.127-11.1-6.735s-4.093-4.491-5.838-6.649c-1.76-2.158-3.727-6.269-6.77-9.309s-8.635-3.416-12.676-1.261a9.288,9.288,0,0,0-4.555,6.6c5.406-.828,7.634,3.368,9.239,6.649s1.83,6.839,2.158,9.171c1.122,7.91,6.217,12.521,12.123,13.919,2.021.553,7.53,2.07,9,2.642,0,0-3.178-1.623-4.006-2.21H383.7c.553-.034,1.623-.138,2.174-.225a14.6,14.6,0,0,1,3.61,1.174s-2.124-1.537-2.158-1.537a17.242,17.242,0,0,0,7.081-3.886c.1-.1.207-.19.311-.294a19.955,19.955,0,0,1,4.577-3.16,29.966,29.966,0,0,1,11.122-3.039C403.5,63.039,398.254,62.228,396.147,61.5Z" transform="translate(-355.204 -29.935)" fill="url(#a)"/><g transform="translate(39.216 33.296)"><path d="M582.3,199.425a12.265,12.265,0,0,1,8.1-1.486c4.006.656,8.342,4.438,14.04,5.561.294.069.587.138.881.19,4.244.708,9.394-1.088,9.118-4.093C613.667,191.152,591.885,190.029,582.3,199.425Z" transform="translate(-582.3 -192.812)" fill="url(#b)"/></g><path d="M390.757,59.183a.558.558,0,0,1-.587.57.579.579,0,1,1,.587-.57Z" transform="translate(-383.663 -48.478)" fill="#fff"/><path d="M483.85,49.384c-4.179.587-8,1.261-11.485,1.987-37.735,7.944-36.2,24.126-31.38,29.135,13.332,13.9,40.188-25.8,43.227-30.706C484.351,49.591,484.127,49.35,483.85,49.384Z" transform="translate(-424.118 -40.855)" fill="url(#c)"/><path d="M471.249.044c-3.9,1.71-7.409,3.385-10.587,5.026-34.384,17.788-28.5,33.055-21.76,36.146,18.841,8.635,31.345-35.4,32.83-40.878C471.8.1,471.508-.06,471.249.044Z" transform="translate(-420.186 -0.011)" fill="url(#d)"/><path d="M433.693,84.508c.138-6.562,6.632-15.491,32.485-17.46.38-.881.794-2.07,1.242-3.209a5.587,5.587,0,0,1,.225-.57c.346-.9.639-1.727.863-2.366C440.636,66.616,433.572,77.548,433.693,84.508Z" transform="translate(-420.14 -50.384)" fill="url(#e)"/><path d="M489.539,94.923c-2.953-.1-6.51-.155-9.136-.138-7.478,9.36-20.793,25.9-31.414,26.319a9.764,9.764,0,0,1-1.606-.121h-.069a10.178,10.178,0,0,1-5.492-2.8c-1.382-1.313-2.021-2.522-1.191-1.33,11.226,15.854,45.161-17.391,49.185-21.467C490.006,95.2,489.833,94.939,489.539,94.923Z" transform="translate(-425.575 -78.415)" fill="url(#f)"/><path d="M475.131,94.8c-1.157,0-2.315.018-3.416.052-30.1.76-37.873,10.448-38.012,17.253a8.765,8.765,0,0,0,2.522,6.1,10.111,10.111,0,0,0,5.422,2.8h.069a15.594,15.594,0,0,0,1.589.121C454.355,121.119,468.172,104.367,475.131,94.8Z" transform="translate(-420.147 -78.43)" fill="url(#g)"/><path d="M466.105,96.6c-25.456,1.882-32.364,10.707-32.505,16.994a8.592,8.592,0,0,0,2.588,6.1,11.153,11.153,0,0,0,2.21,1.623,11.852,11.852,0,0,0,3.9,1.226c.38.034.76.052,1.139.069a13.52,13.52,0,0,0,2.608-.276C454.773,120.225,461.768,107.325,466.105,96.6Z" transform="translate(-420.065 -79.919)" fill="url(#g)"/></g></g></svg>
					</div>
				</div>
			</div>

		</div>
	</div>
	<div class="left-box postbox">' .
		'<table class="form-table">' .
			'<tbody>';

				if( empty( $tags ) ) {

					echo '<tr class="form-field">' .
						'<td>' .
						esc_html__( 'To use 2Checkout option, first you need to create and save form tags.', 'accept-2checkout-payments-using-contact-form-7' ).
							' <a href="'.esc_url(CF72CH_DOCUMENT).'" target="_blank">' . esc_html__( 'Document Link', 'accept-2checkout-payments-using-contact-form-7' ) . '</a>'.
						'</td>' .
					'</tr>';

				} else {

					echo '<tr class="form-field">' .
						'<th scope="row">' .
							'<label for="' .esc_attr(CF72CH_META_PREFIX ). 'use_2checkout">' .
							esc_html__( 'Enable/Disable', 'accept-2checkout-payments-using-contact-form-7' ) .
							'</label>' .
						'</th>' .
						'<td>' .
							'<input id="' . esc_attr(CF72CH_META_PREFIX) . 'use_2checkout" name="' . esc_attr(CF72CH_META_PREFIX) . 'use_2checkout" type="checkbox" class="enable_required" value="1" ' . checked( $use_2checkout, 1, false ) . '/>' .
						'</td>' .
					'</tr>' .
					'<tr class="form-field">' .
						'<th scope="row">' .
							'<label for="' . esc_attr(CF72CH_META_PREFIX) . 'debug">' .
							esc_html__( 'Enable Debug Mode', 'accept-2checkout-payments-using-contact-form-7' ) .
							'</label>' .
						'</th>' .
						'<td>' .
							'<input id="' . esc_attr(CF72CH_META_PREFIX) . 'debug" name="' . esc_attr(CF72CH_META_PREFIX) . 'debug" type="checkbox" value="1" ' . checked( $debug_2checkout, 1, false ) . '/>' .
						'</td>' .
					'</tr>' .
					'<tr class="form-field">' .
						'<th scope="row">' .
							'<label for="' . esc_attr(CF72CH_META_PREFIX) . 'payment_mode">' .
							esc_html__( 'Payment Mode ', 'accept-2checkout-payments-using-contact-form-7' ) .
							'*</label>' .
						'</th>' .
						'<td>' .
							'<select id="' . esc_attr(CF72CH_META_PREFIX) . 'payment_mode" name="' . esc_attr(CF72CH_META_PREFIX) . 'payment_mode" ' . ( !empty( $use_2checkout ) ? 'required' : '' ) . '>';
								if ( !empty( $payment_mode ) ) {
									foreach ( $payment_mode as $key => $value ) {
										echo '<option value="' . esc_attr( $key ) . '" ' . selected( $payment_mode_val, $key, false ) . '>' . esc_html( $value ) . '</option>';
									}
								}

							echo '</select>' .
						'</td>' .
					'</tr>' .
					'<tr class="form-field">' .
						'<th>' .
							'<label for="' . esc_attr(CF72CH_META_PREFIX) . 'two_checkout_order_name">' .
							esc_html__( '2Checkout Order Item Name *', 'accept-2checkout-payments-using-contact-form-7' ) .
							'</label>' .
							'<span class="cf72ch-tooltip hide-if-no-js" id="cf72ch-order-name"></span>' .
						'</th>' .
						'<td>' .
							'<input id="' . esc_attr(CF72CH_META_PREFIX) . 'two_checkout_order_name" name="' . esc_attr(CF72CH_META_PREFIX) . 'two_checkout_order_name" type="text" class="large-text required-fields" value="' . esc_attr( $two_checkout_order_name ) . '" />' .
						'</td>' .
					'</tr>' .
					'<tr class="form-field">' .
						'<th>' .
							'<label for="' . esc_attr(CF72CH_META_PREFIX) . 'merchant_code">' .
							esc_html__( 'Merchant Code ', 'accept-2checkout-payments-using-contact-form-7' ) .
							'*</label>' .	
							'<span class="cf72ch-tooltip hide-if-no-js" id="cf72ch-merchant-code"></span>' .
						'</th>' .
						'<td>' .
							'<input id="' . esc_attr(CF72CH_META_PREFIX) . 'merchant_code" name="' . esc_attr(CF72CH_META_PREFIX) . 'merchant_code" type="text" class="large-text required-fields" value="' . esc_attr( $merchant_code ) . '" ' . ( !empty( $use_2checkout ) ? 'required' : '' ) . ' />' .
						'</td>' .
					'</tr>' .
					'<tr class="form-field">' .
						'<th>' .
							'<label for="' . esc_attr(CF72CH_META_PREFIX) . 'secret_key">' .
							esc_html__( 'Secret Key ', 'accept-2checkout-payments-using-contact-form-7' ) .
							'*</label>' .
							'<span class="cf72ch-tooltip hide-if-no-js" id="cf72ch-secret-key"></span>' .
						'</th>' .
						'<td>' .
							'<input id="' . esc_attr(CF72CH_META_PREFIX) . 'secret_key" name="' . esc_attr(CF72CH_META_PREFIX) . 'secret_key" type="text" class="large-text required-fields" value="' . esc_attr( $secret_key ) . '" ' . ( !empty( $use_2checkout ) ? 'required' : '' ) . ' />' .
						'</td>' .
					'</tr>' .
					'<tr class="form-field">' .
						'<th>' .
							'<label for="' . esc_attr(CF72CH_META_PREFIX) . 'amount">' .
							esc_html__( 'Amount Field Name ', 'accept-2checkout-payments-using-contact-form-7' ) .
							'*</label>' .
							'<span class="cf72ch-tooltip hide-if-no-js" id="cf72ch-amount"></span>' .
						'</th>'.
						'<td>' .
							'<select id="' . esc_attr(CF72CH_META_PREFIX) . 'amount" class="cf72ch-required-fields" name="' . esc_attr(CF72CH_META_PREFIX) . 'amount">' .
								'<option value="">' . esc_html__( 'Select field name for amount', 'accept-2checkout-payments-using-contact-form-7' ) . '</option>';
								if( !empty( $tags ) ) {
									foreach ( $tags as $key => $tag ) { //phpcs:ignore
										echo '<option value="' . esc_attr( $tag ) . '" ' . selected( $amount, $tag, false )  . '>' . esc_html( $tag ) . '</option>';
									}
								}

							echo '</select>' .
						'</td>' .
					'</tr>'.
					'<tr class="form-field">' .
						'<th>' .
							'<label for="' . esc_attr(CF72CH_META_PREFIX) . 'quantity">' .
							esc_html__( 'Quantity Field Name (Optional)', 'accept-2checkout-payments-using-contact-form-7' ) .
							'</label>' .
							'<span class="cf72ch-tooltip hide-if-no-js" id="cf72ch-quantity"></span>' .
						'</th>'.
						'<td>' .
							'<select id="' . esc_attr(CF72CH_META_PREFIX) . 'quantity" class="cf72ch-required-fields" name="' . esc_attr(CF72CH_META_PREFIX) . 'quantity">' .
								'<option>' . esc_html__( 'Select field name for quantity', 'accept-2checkout-payments-using-contact-form-7' ) . '</option>';
								if( !empty( $tags ) ) {
									foreach ( $tags as $key => $tag ) {//phpcs:ignore
										echo '<option value="' . esc_attr( $tag ) . '" ' . selected( $quantity, $tag, false )  . '>' . esc_html( $tag ) . '</option>';
									}
								}

							echo '</select>' .
						'</td>' .
					'</tr>'.
					'<tr class="form-field">' .
						'<th>' .
							'<label for="' . esc_attr(CF72CH_META_PREFIX) . 'customer_email">' .
							esc_html__( 'Customer Email ', 'accept-2checkout-payments-using-contact-form-7' ) .
							'*</label>' .
							'<span class="cf72ch-tooltip hide-if-no-js" id="cf72ch-customer-email"></span>' .
						'</th>'.
						'<td>' .
							'<select id="' . esc_attr(CF72CH_META_PREFIX) . 'customer_email" class="cf72ch-required-fields" name="' . esc_attr(CF72CH_META_PREFIX) . 'customer_email" ' . ( !empty( $use_2checkout ) ? 'required' : '' ) . '>' .
								'<option value="">' . esc_html__( 'Select field name for customer email', 'accept-2checkout-payments-using-contact-form-7' ) . '</option>';
								if( !empty( $tags ) ) {
									foreach ( $tags as $key => $tag ) {//phpcs:ignore
										echo '<option value="' . esc_attr( $tag ) . '" ' . selected( $customer_email, $tag, false )  . '>' . esc_html( $tag ) . '</option>';
									}
								}

							echo '</select>' .
						'</td>' .
					'</tr>'.

					'<tr class="form-field">' .
						'<th>' .
							'<label for="' . esc_attr(CF72CH_META_PREFIX) . 'currency">' .
							esc_html__( 'Select Currency', 'accept-2checkout-payments-using-contact-form-7' ) .
							' *</label>' .
							'<span class="cf72ch-tooltip hide-if-no-js" id="cf72ch-currency"></span>' .
						'</th>' .
						'<td>' .
							'<select id="' . esc_attr(CF72CH_META_PREFIX) . 'currency" name="' . esc_attr(CF72CH_META_PREFIX) . 'currency">';

								if ( !empty( $currency_code ) ) {
									foreach ( $currency_code as $key => $value ) { //phpcs:ignore
										echo '<option value="' . esc_attr( $key ) . '" ' . selected( $currency, $key, false ) . '>' . esc_html( $value ) . '</option>';
									}
								}

							echo '</select>' .
						'</td>' .
					'</tr/>' .
					'<tr class="form-field">' .
						'<th>' .
							'<label for="' . esc_attr(CF72CH_META_PREFIX) . 'success_returnurl">' .
							esc_html__( 'Return Success URL (Optional)', 'accept-2checkout-payments-using-contact-form-7' ) .
							'</label>' .
							'<span class="cf72ch-tooltip hide-if-no-js" id="cf72ch-success-returnurl"></span>' .
						'</th>'.
						'<td>' .
							'<select id="' . esc_attr(CF72CH_META_PREFIX) . 'success_returnurl" name="' . esc_attr(CF72CH_META_PREFIX) . 'success_returnurl">' .
								'<option>' . esc_html__( 'Select page', 'accept-2checkout-payments-using-contact-form-7' ) . '</option>';

								if( !empty( $all_pages ) ) {
									foreach ( $all_pages as $new_post => $title ) { //phpcs:ignore
										echo '<option value="' . esc_attr( $new_post ) . '" ' . selected( $success_returnurl, $new_post, false )  . '>' .esc_html($title). '</option>';
									}
								}

							echo '</select>' .
						'</td>' .
					'</tr>'.
					'<tr class="form-field">' .
						'<th>' .
							'<label for="' . esc_attr(CF72CH_META_PREFIX) . 'cancel_returnurl">' .
							esc_html__( 'Return Cancel URL (Optional)', 'accept-2checkout-payments-using-contact-form-7' ) .
							'</label>' .
							'<span class="cf72ch-tooltip hide-if-no-js" id="cf72ch-cancel-returnurl"></span>' .
						'</th>'.
						'<td>' .
							'<select id="' . esc_attr(CF72CH_META_PREFIX) . 'cancel_returnurl" name="' . esc_attr(CF72CH_META_PREFIX) . 'cancel_returnurl">' .
								'<option>' . esc_html__( 'Select page', 'accept-2checkout-payments-using-contact-form-7' ) . '</option>';

								if( !empty( $all_pages ) ) {
									foreach ( $all_pages as $new_post => $title ) { //phpcs:ignore
										echo '<option value="' . esc_attr( $new_post ) . '" ' . selected( $cancel_returnurl, $new_post, false )  . '>' .esc_html($title). '</option>';
									}
								}

							echo '</select>' .
						'</td>' .
					'</tr>';


					// Billing Fields
					echo '<tr class="form-field">' .
						'<th colspan="2">' .
							'<label for="' . esc_attr(CF72CH_META_PREFIX) . 'customer_billing_details">' .
								'<h3 style="margin: 0;">' .
								esc_html__( 'Customer Billing Details', 'accept-2checkout-payments-using-contact-form-7' ) .
									'<span class="arrow-switch"></span>' .
								'</h3>' .
							'</label>' .
						'</th>' .
					'</tr>'.
					'<tr class="form-field">' .
						'<th>' .
							'<label for="' . esc_attr(CF72CH_META_PREFIX) . 'billing_first_name">' .
							esc_html__( 'Billing First Name Field ', 'accept-2checkout-payments-using-contact-form-7' ) .
							'*</label>' .
							'<span class="cf72ch-tooltip hide-if-no-js" id="cf72ch-billing-first-name"></span>' .
						'</th>'.
						'<td>' .
							'<select id="' . esc_attr(CF72CH_META_PREFIX) . 'billing_first_name" class="cf72ch-required-fields" name="' . esc_attr(CF72CH_META_PREFIX) . 'billing_first_name" ' . ( !empty( $use_2checkout ) ? 'required' : '' ) . '>' .
								'<option value="">' . esc_html__( 'Select field name for billing first name', 'accept-2checkout-payments-using-contact-form-7' ) . '</option>';
								if( !empty( $tags ) ) {
									foreach ( $tags as $key => $tag ) { //phpcs:ignore
										echo '<option value="' . esc_attr( $tag ) . '" ' . selected( $billing_first_name, $tag, false )  . '>' . esc_html( $tag ) . '</option>';
									}
								}

							echo '</select>' .
						'</td>' .
					'</tr>'.
					'<tr class="form-field">' .
						'<th>' .
							'<label for="' . esc_attr(CF72CH_META_PREFIX) . 'billing_last_name">' .
							esc_html__( 'Billing Last Name Field ', 'accept-2checkout-payments-using-contact-form-7' ) .
							'*</label>' .
							'<span class="cf72ch-tooltip hide-if-no-js" id="cf72ch-billing-last-name"></span>' .
						'</th>'.
						'<td>' .
							'<select id="' . esc_attr(CF72CH_META_PREFIX) . 'billing_last_name" class="cf72ch-required-fields" name="' . esc_attr(CF72CH_META_PREFIX) . 'billing_last_name" ' . ( !empty( $use_2checkout ) ? 'required' : '' ) . '>' .
								'<option value="">' . esc_html__( 'Select field name for billing last name', 'accept-2checkout-payments-using-contact-form-7' ) . '</option>';
								if( !empty( $tags ) ) {
									foreach ( $tags as $key => $tag ) { //phpcs:ignore
										echo '<option value="' . esc_attr( $tag ) . '" ' . selected( $billing_last_name, $tag, false )  . '>' . esc_html( $tag ) . '</option>';
									}
								}

							echo '</select>' .
						'</td>' .
					'</tr>'.
					'<tr class="form-field">' .
						'<th>' .
							'<label for="' . esc_attr(CF72CH_META_PREFIX) . 'billing_address">' .
							esc_html__( 'Billing Address Field ', 'accept-2checkout-payments-using-contact-form-7' ) .
							'*</label>' .
							'<span class="cf72ch-tooltip hide-if-no-js" id="cf72ch-billing-address"></span>' .
						'</th>'.
						'<td>' .
							'<select id="' . esc_attr(CF72CH_META_PREFIX) . 'billing_address" class="cf72ch-required-fields" name="' . esc_attr(CF72CH_META_PREFIX) . 'billing_address" ' . ( !empty( $use_2checkout ) ? 'required' : '' ) . '>' .
								'<option value="">' . esc_html__( 'Select field name for billing address', 'accept-2checkout-payments-using-contact-form-7' ) . '</option>';
								if( !empty( $tags ) ) {
									foreach ( $tags as $key => $tag ) { //phpcs:ignore
										echo '<option value="' . esc_attr( $tag ) . '" ' . selected( $billing_address, $tag, false )  . '>' . esc_html( $tag ) . '</option>';
									}
								}

							echo '</select>' .
						'</td>' .
					'</tr>'.
					'<tr class="form-field">' .
						'<th>' .
							'<label for="' . esc_attr(CF72CH_META_PREFIX) . 'billing_city">' .
							esc_html__( 'Billing City Field ', 'accept-2checkout-payments-using-contact-form-7' ) .
							'*</label>' .
							'<span class="cf72ch-tooltip hide-if-no-js" id="cf72ch-billing-city"></span>' .
						'</th>'.
						'<td>' .
							'<select id="' . esc_attr(CF72CH_META_PREFIX) . 'billing_city" class="cf72ch-required-fields" name="' . esc_attr(CF72CH_META_PREFIX) . 'billing_city" ' . ( !empty( $use_2checkout ) ? 'required' : '' ) . '>' .
								'<option value="">' . esc_html__( 'Select field name for billing city name', 'accept-2checkout-payments-using-contact-form-7' ) . '</option>';
								if( !empty( $tags ) ) {
									foreach ( $tags as $key => $tag ) { //phpcs:ignore
										echo '<option value="' . esc_attr( $tag ) . '" ' . selected( $billing_city, $tag, false )  . '>' . esc_html( $tag ) . '</option>';
									}
								}

							echo '</select>' .
						'</td>' .
					'</tr>'.
					'<tr class="form-field">' .
						'<th>' .
							'<label for="' . esc_attr(CF72CH_META_PREFIX) . 'billing_state">' .
							esc_html__( 'Billing State Field ', 'accept-2checkout-payments-using-contact-form-7' ) .
							'*</label>' .
							'<span class="cf72ch-tooltip hide-if-no-js" id="cf72ch-billing-state"></span>' .
						'</th>'.
						'<td>' .
							'<select id="' . esc_attr(CF72CH_META_PREFIX) . 'billing_state" class="cf72ch-required-fields" name="' . esc_attr(CF72CH_META_PREFIX) . 'billing_state" ' . ( !empty( $use_2checkout ) ? 'required' : '' ) . '>' .
								'<option value="">' . esc_html__( 'Select field name for billing state name', 'accept-2checkout-payments-using-contact-form-7' ) . '</option>';
								if( !empty( $tags ) ) {
									foreach ( $tags as $key => $tag ) { //phpcs:ignore
										echo '<option value="' . esc_attr( $tag ) . '" ' . selected( $billing_state, $tag, false )  . '>' . esc_html( $tag ) . '</option>';
									}
								}

							echo '</select>' .
						'</td>' .
					'</tr>'.
					'<tr class="form-field">' .
						'<th>' .
							'<label for="' . esc_attr(CF72CH_META_PREFIX) . 'billing_zipcode">' .
							esc_html__( 'Billing Zipcode Field ', 'accept-2checkout-payments-using-contact-form-7' ) .
							'*</label>' .
							'<span class="cf72ch-tooltip hide-if-no-js" id="cf72ch-billing-zipcode"></span>' .
						'</th>'.
						'<td>' .
							'<select id="' . esc_attr(CF72CH_META_PREFIX) . 'billing_zipcode" class="cf72ch-required-fields" name="' . esc_attr(CF72CH_META_PREFIX) . 'billing_zipcode" ' . ( !empty( $use_2checkout ) ? 'required' : '' ) . '>' .
								'<option value="">' . esc_html__( 'Select field name for billing Zipcode name', 'accept-2checkout-payments-using-contact-form-7' ) . '</option>';
								if( !empty( $tags ) ) {
									foreach ( $tags as $key => $tag ) { //phpcs:ignore
										echo '<option value="' . esc_attr( $tag ) . '" ' . selected( $billing_zipcode, $tag, false )  . '>' . esc_html( $tag ) . '</option>';
									}
								}

							echo '</select>' .
						'</td>' .
					'</tr>'.

					'<tr class="form-field">' .
						'<th>' .
							'<label for="' . esc_attr(CF72CH_META_PREFIX) . 'billing_country">' .
							esc_html__( 'Billing Country Field ', 'accept-2checkout-payments-using-contact-form-7' ) .
							'*</label>' .
							'<span class="cf72ch-tooltip hide-if-no-js" id="cf72ch-billing-country"></span>' .
						'</th>'.
						'<td>' .
							'<select id="' . esc_attr(CF72CH_META_PREFIX) . 'billing_country" class="cf72ch-required-fields" name="' . esc_attr(CF72CH_META_PREFIX) . 'billing_country" ' . ( !empty( $use_2checkout ) ? 'required' : '' ) . '>' .
								'<option value="">' . esc_html__( 'Select field name for billing country name', 'accept-2checkout-payments-using-contact-form-7' ) . '</option>';
								if( !empty( $tags ) ) {
									foreach ( $tags as $key => $tag ) { //phpcs:ignore
										echo '<option value="' . esc_attr( $tag ) . '" ' . selected( $billing_country, $tag, false )  . '>' . esc_html( $tag ) . '</option>';
									}
								}

							echo '</select>' .
						'</td>' .
					'</tr>'.

					/**
					 * - Add new field at the end.
					 *
					 * @var int $new_post
					 */
					do_action( CF72CH_PREFIX . 'add_fields', $new_post ); //phpcs:ignore

					echo '<input type="hidden" name="post" value="' . esc_attr($new_post) . '">';
				}
			echo '</tbody>' .
		'</table>' .
	'</div>' .
	'<div class="right-box">';
	/**
	 * Add new post box to display the information.
	 */
	do_action( CF72CH_PREFIX . '/postbox' );

	echo '</div>' .
'</div>';

$translation_array = array(
	

	'merchant_code'	=> __( '<h3>Merchant Code</h3>' .
						'<p>Get Merchant Code from <a href="#" target="_blank">here</a></p>',
						'accept-2checkout-payments-using-contact-form-7' ),

	'order_name'	=> __( '<h3>Order Item Name</h3>' .
						'<p>Set Order Item Name</p>',
						'accept-2checkout-payments-using-contact-form-7' ),

	'secret_key'	=> __( '<h3>Secret Key</h3>' .
						'<p>Get Secret Key from <a href="#" target="_blank">here</a></p>',
						'accept-2checkout-payments-using-contact-form-7' ),

	'currency'	=> __( '<h3>Select Currency</h3>' .
						'<p>Select the currency.</p>',
						'accept-2checkout-payments-using-contact-form-7' ),

	'amount'	=> __( '<h3>Amount Field</h3>' .
						'<p>Select field from where amount value needs to be retrieved.</p><p><b>Note: </b> Save the FORM details to view the list of fields.</p>',
						'accept-2checkout-payments-using-contact-form-7' ),

	'quantity'	=> __( '<h3>Quantity Field</h3>' .
						'<p>Select field from where quantity value needs to be retrieved.</p><p><b>Note: </b> Save the FORM details to view the list of fields.</p>',
						'accept-2checkout-payments-using-contact-form-7' ),

	'email'	=> __( '<h3>Customer Email Field</h3>' .
						'<p>Select field from where customer email value needs to be retrieved.</p><p><b>Note: </b> Save the FORM details to view the list of fields.</p>',
						'accept-2checkout-payments-using-contact-form-7' ),

	'success_returnurl'	=> __( '<h3>Success Return URL Field </h3>' .
						'<p>Select page and redirect customer after succesfully payment done.</p>',
						'accept-2checkout-payments-using-contact-form-7' ),

	'cancel_returnurl'	=> __( '<h3>Cancel Return URL Field  </h3>' .
						'<p>Select page and redirect customer after cancel payment process or payment not done.</p>',
						'accept-2checkout-payments-using-contact-form-7' ),	

	'billing_first_name'	=> __( '<h3>Billing First Name Field</h3>' .
						'<p>Select field from where billing first name value needs to be retrieved.</p><p><b>Note: </b> Save the FORM details to view the list of fields.</p>',
						'accept-2checkout-payments-using-contact-form-7' ),

	'billing_last_name' => __( '<h3>Billing Last Name Field</h3>' .
						'<p>Select field from where billing last name value needs to be retrieved.</p><p><b>Note: </b> Save the FORM details to view the list of fields.</p>',
						'accept-2checkout-payments-using-contact-form-7' ),
	
	'billing_address'	=> __( '<h3>Billing Address Field</h3>' .
						'<p>Select field from where billing address value needs to be retrieved.</p><p><b>Note: </b> Save the FORM details to view the list of fields.</p>',
						'accept-2checkout-payments-using-contact-form-7' ),

	'billing_city'	=> __( '<h3>Billing City Field</h3>' .
						'<p>Select field from where billing city value needs to be retrieved.</p><p><b>Note: </b> Save the FORM details to view the list of fields.</p>',
						'accept-2checkout-payments-using-contact-form-7' ),

	'billing_state'	=> __( '<h3>Billing State Field</h3>' .
						'<p>Select field from where billing state value needs to be retrieved.</p><p><b>Note: </b> Save the FORM details to view the list of fields.</p>',
						'accept-2checkout-payments-using-contact-form-7' ),

	'billing_zipcode'	=> __( '<h3>Billing ZipCode Field</h3>' .
						'<p>Select field from where billing zipcode value needs to be retrieved.</p><p><b>Note: </b> Save the FORM details to view the list of fields.</p>',
						'accept-2checkout-payments-using-contact-form-7' ),

	'billing_country'	=> __( '<h3>Billing Country Field</h3>' .
						'<p>Select field from where billing country value needs to be retrieved.</p><p><b>Note: </b> Save the FORM details to view the list of fields.</p>',
						'accept-2checkout-payments-using-contact-form-7' ),
	'cf72ch_review'		=> $cf72ch_review,

);

wp_enqueue_script( CF72CH_PREFIX . '_modal_js' );
wp_enqueue_script( CF72CH_PREFIX . '_cookie_js' );
wp_localize_script( CF72CH_PREFIX . '_admin_js', 'cf72ch_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'translate_string_cf72ch' => $translation_array ) );