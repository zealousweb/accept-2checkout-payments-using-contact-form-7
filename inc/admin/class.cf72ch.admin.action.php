<?php
/**
 * CF72CH_Admin_Action Class
 *
 * Handles the admin functionality.
 *
 * @package WordPress
 * @subpackage Accept 2Checkout Payments Using Contact Form 7
 * @since 1.2
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF72CH_Admin_Action' ) ){

	/**
	 *  The CF72CH_Admin_Action Class
	 */
	class CF72CH_Admin_Action {

		function __construct()  {

			add_action( 'init',				array( $this, 'action__cf72ch_init' ) );
			add_action( 'add_meta_boxes',	array( $this, 'action__cf72ch_add_meta_boxes' ) );

			// Save settings of contact form 7 admin
			add_action( 'wpcf7_save_contact_form', array( $this, 'action__cf72ch_wpcf7_save_contact_form' ), 20, 2 );

			add_action( 'manage_'.CF72CH_POST_TYPE.'_posts_custom_column',  array( $this, 'action__manage_cf72ch_data_posts_custom_column' ), 10, 2 );

			add_action( 'pre_get_posts',			array( $this, 'action__cf72ch_pre_get_posts' ) );
			add_action( 'restrict_manage_posts',	array( $this, 'action__cf72ch_restrict_manage_posts' ) );
			add_action( 'parse_query',				array( $this, 'action__cf72ch_parse_query' ) );

			add_action( CF72CH_PREFIX . '/postbox', array( $this, 'action__cf72ch_postbox' ) );

			add_action( 'wp_ajax_cf72ch_review_done',			array( $this, 'action__cf72ch_review_done'));
			add_action( 'wp_ajax_nopriv_cf72ch_review_done',	array( $this, 'action__cf72ch_review_done'));

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
		 * Action: init
		 *
		 * - Register neccessary assets for backend.
		 *
		 * @method action__cf72ch_init
		 */

		function action__cf72ch_init() {

			wp_register_script( CF72CH_PREFIX . '_modal_js', CF72CH_URL . 'assets/js/bootstrap.min.js', array(), CF72CH_VERSION );
			wp_register_script( CF72CH_PREFIX . '_cookie_js', CF72CH_URL . 'assets/js/cookie.min.js', array(), CF72CH_VERSION );

			wp_register_style( CF72CH_PREFIX . '_admin_css', CF72CH_URL . 'assets/css/admin.min.css', array(), CF72CH_VERSION );
			wp_register_script( CF72CH_PREFIX . '_admin_js', CF72CH_URL . 'assets/js/admin.min.js', array( 'jquery-core' ), CF72CH_VERSION );

			wp_register_style( 'select2', CF72CH_URL . 'assets/css/select2.min.css', array(), CF72CH_VERSION );
			wp_register_script( 'select2', CF72CH_URL . 'assets/js/select2.min.js', array( 'jquery-core' ), CF72CH_VERSION );

			wp_enqueue_script( CF72CH_PREFIX . '_order_retrive', CF72CH_URL . 'assets/js/order-retrive.js', array( 'jquery-core' ), CF72CH_VERSION, 'true');
			wp_localize_script( CF72CH_PREFIX . '_order_retrive', 'frontend_ajax_object',
				array( 
					'ajaxurl' => admin_url( 'admin-ajax.php' )
				)
			);
		}		

		/**
		 *
		 * Add message boxes for the CPT "cf72ch_data"
		 *
		 * @method action__cf72ch_add_meta_boxes
		 *
		 */
		function action__cf72ch_add_meta_boxes() {
			add_meta_box( 'cf72ch-data', __( 'From Data', 'accept-2checkout-payments-using-contact-form-7' ), array( $this, 'cf72ch_show_from_data' ), CF72CH_POST_TYPE, 'normal', 'high' );
			add_meta_box( 'cf72ch-help', __( 'Do you need help for configuration?', 'accept-2checkout-payments-using-contact-form-7' ), array( $this, 'cf72ch_show_help_data' ), CF72CH_POST_TYPE, 'side', 'high' );
		}	


		/**
		 * Action: cf72ch_wpcf7_save_contact_form
		 *
		 * - Save setting fields data.
		 *
		 * @param object $WPCF7_form
		 */
		public function action__cf72ch_wpcf7_save_contact_form( $WPCF7_form ) {

			$wpcf7 = WPCF7_ContactForm::get_current();

			if ( !empty( $wpcf7 ) ) {
				$post_id = $wpcf7->id();
			}

			$form_fields = array(
				CF72CH_META_PREFIX . 'use_2checkout',
				CF72CH_META_PREFIX . 'debug',
				CF72CH_META_PREFIX . 'payment_mode',
				CF72CH_META_PREFIX . 'merchant_code',
				CF72CH_META_PREFIX . 'secret_key',
				CF72CH_META_PREFIX . 'two_checkout_order_name',
				CF72CH_META_PREFIX . 'amount',
				CF72CH_META_PREFIX . 'customer_email',
				CF72CH_META_PREFIX . 'quantity',
				CF72CH_META_PREFIX . 'currency',
				CF72CH_META_PREFIX . 'success_returnurl',
				CF72CH_META_PREFIX . 'cancel_returnurl',

				CF72CH_META_PREFIX . 'billing_first_name',
				CF72CH_META_PREFIX . 'billing_last_name',
				CF72CH_META_PREFIX . 'billing_address',
				CF72CH_META_PREFIX . 'billing_city',
				CF72CH_META_PREFIX . 'billing_state',
				CF72CH_META_PREFIX . 'billing_country',
				CF72CH_META_PREFIX . 'billing_zipcode',
			);

			/**
			 * Save custom form setting fields
			 *
			 * @var array $form_fields
			 */

			$form_fields = apply_filters( CF72CH_PREFIX . 'save_fields', $form_fields );
			if(!get_option('_exceed_cf72ch_l')){
				add_option('_exceed_cf72ch_l', 'cf72ch10');
			}
			if ( !empty( $form_fields ) ) {
				foreach ( $form_fields as $key ) {
					if( isset( $_REQUEST[ $key ] ) ){
						$keyval = sanitize_text_field( $_REQUEST[ $key ] );
						trim( sanitize_text_field( update_post_meta( $post_id, $key, $keyval ) ) );
					}else{
						trim( sanitize_text_field( update_post_meta( $post_id, $key, '' ) ) );
					}
				}
			}
		}

		/**
		 * Action: manage_data_posts_custom_column
		 *
		 * @method manage_cf72ch_data_posts_custom_column
		 *
		 * @param  string  $column
		 * @param  int     $post_id
		 *
		 * @return string
		 */
		function action__manage_cf72ch_data_posts_custom_column( $column, $post_id ) {

			$data_ct = $this->cf72ch_check_data_ct( sanitize_text_field( $post_id ) );

			switch ( $column ) {

				case 'invoice_no' :
					if( $data_ct ){
						echo '<a href='.CF72CH_PRODUCT_LINK.' target="_blank">To unlock more features consider upgrading to PRO</a>';
					}else{
						echo (
							!empty( get_post_meta( $post_id , '_invoice_no', true ) )
							? (
								(
									!empty( CF72CH()->lib->response_status )
									&& array_key_exists( get_post_meta( $post_id , '_invoice_no', true ), CF72CH()->lib->response_status)
								)
								? CF72CH()->lib->response_status[get_post_meta( $post_id , '_invoice_no', true )]
								: trim( sanitize_text_field( get_post_meta( $post_id , '_invoice_no', true ) ) )
							)
							: ''
						);
					}
				break;

				case 'order_id' :
					if( $data_ct ){
						echo '<a href='.CF72CH_PRODUCT_LINK.' target="_blank">To unlock more features consider upgrading to PRO</a>';
					}else{
						echo (
							!empty( get_post_meta( $post_id , '_order_id', true ) )
							? (
								(
									!empty( CF72CH()->lib->response_status )
									&& array_key_exists( get_post_meta( $post_id , '_order_id', true ), CF72CH()->lib->response_status)
								)
								? CF72CH()->lib->response_status[get_post_meta( $post_id , '_order_id', true )]
								: trim( sanitize_text_field( get_post_meta( $post_id , '_order_id', true ) ) )
							)
							: ''
						);
					}
				break;

				case 'transaction_status' :
					if( $data_ct ){
						echo '<a href='.CF72CH_PRODUCT_LINK.' target="_blank">To unlock more features consider upgrading to PRO</a>';
					}else{
						echo (
							!empty( get_post_meta( $post_id , '_transaction_status', true ) )
							? (
								(
									!empty( CF72CH()->lib->response_status )
									&& array_key_exists( get_post_meta( $post_id , '_transaction_status', true ), CF72CH()->lib->response_status)
								)
								? CF72CH()->lib->response_status[get_post_meta( $post_id , '_transaction_status', true )]
								: trim( sanitize_text_field( get_post_meta( $post_id , '_transaction_status', true ) ) )
							)
							: ''
						);
					}
				break;

				case 'total' :
					if( $data_ct ){
						echo '<a href='.CF72CH_PRODUCT_LINK.' target="_blank">To unlock more features consider upgrading to PRO</a>';
					}else{
						echo ( !empty( get_post_meta( $post_id , '_total', true ) ) ? trim( sanitize_text_field( get_post_meta( $post_id , '_total', true ) ) ) : '' );
					}
				break;

			}
		}


		/**
		 * Action: pre_get_posts
		 *
		 * - Used to perform order by into CPT List.
		 *
		 * @method action__cf72ch_pre_get_posts
		 *
		 * @param  object $query WP_Query
		 */
		function action__cf72ch_pre_get_posts( $query ) {

			if (
				! is_admin()
				|| !in_array ( $query->get( 'post_type' ), array( CF72CH_POST_TYPE ) )
			)
				return;

			$orderby = $query->get( 'orderby' );

			if ( '_total' == $orderby ) {
				$query->set( 'meta_key', '_total' );
				$query->set( 'orderby', 'meta_value_num' );
			}
		}

		/**
		 * Action: restrict_manage_posts
		 *
		 * - Used to creat filter by form and export functionality.
		 *
		 * @method action__cf72ch_restrict_manage_posts
		 *
		 * @param  string $post_type
		 */
		function action__cf72ch_restrict_manage_posts( $post_type ) {

			if ( CF72CH_POST_TYPE != $post_type ) {
				return;
			}

			$posts = get_posts(
				array(
					'post_type'			=> 'wpcf7_contact_form',
					'post_status'		=> 'publish',
					'suppress_filters'	=> false,
					'posts_per_page'	=> -1,
					'meta_key'			=> 'cf72ch_use_2checkout',
					'meta_value'		=> 1,
				)
			);

			if ( empty( $posts ) ) {
				return;
			}

			$selected = ( isset( $_REQUEST['form-id'] ) ? sanitize_text_field( $_REQUEST['form-id'] ) : '' );

			echo '<select name="form-id" id="form-id">';
			echo '<option value="all">' . __( 'Select Forms', 'accept-2checkout-payments-using-contact-form-7' ) . '</option>';
			foreach ( $posts as $post ) {
				echo '<option value="' . $post->ID . '" ' . selected( $selected, $post->ID, false ) . '>' . $post->post_title  . '</option>';
			}
			echo '</select>';
		}

		/**
		 * Action: parse_query
		 *
		 * - Filter data by form id.
		 *
		 * @method action__cf72ch_parse_query
		 *
		 * @param  object $query WP_Query
		 */
		function action__cf72ch_parse_query( $query ) {
			if (
				! is_admin()
				|| !in_array ( $query->get( 'post_type' ), array( CF72CH_POST_TYPE ) )
			)
				return;

			if (
				is_admin()
				&& isset( $_REQUEST['form-id'] )
				&& 'all' != $_REQUEST['form-id']
			) {
				$query->query_vars['meta_value']	= sanitize_text_field( $_REQUEST['form-id'] );
				$query->query_vars['meta_compare']	= '=';
			}

		}

		/**
		 * Action: CF72CH_PREFIX /postbox
		 *
		 * - Added metabox for the setting fields in backend.
		 *
		 * @method action__cf72ch_postbox
		 */
		function action__cf72ch_postbox() {

			echo '<div id="configuration-help" class="postbox">' .
				apply_filters(
					CF72CH_PREFIX . '/help/postbox',
					'<h3>' . __( 'Do you need help for configuration?', 'accept-2checkout-payments-using-contact-form-7' ) . '</h3>' .
					'<p></p>' .
					'<ol>' .
						'<li><a href="'.CF72CH_DOCUMENT.'" target="_blank">Refer the document.</a></li>' .
						'<li><a href="'.CF72CH_SUPPORT.'" target="_blank">Contact Us</a></li>' .
					'</ol>'
				) .
			'</div>';
		}



		/**
		 * Action: review done
		 *
		 * - Review done.
		 *
		 * @method action__cf72ch_review_done
		 */
		function action__cf72ch_review_done(){
			if( isset( $_POST['value'] ) && $_POST['value'] == 1 ){
				add_option( 'cf72ch_review', "1" );
			}
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
		 * - Used to display the form data in CPT detail page.
		 *
		 * @method cf72ch_show_from_data
		 *
		 * @param  object $post WP_Post
		 */

		function cf72ch_show_from_data( $post ) {

			$fields = CF72CH()->lib->data_fields;
			$form_id = get_post_meta( $post->ID, '_form_id', true );
			$post_type = $post->post_type;

			$data_ct = $this->cf72ch_check_data_ct( $post->ID );

			if( $data_ct ) {

				echo '<table><tbody>'.
				'<style>.inside-field th{ text-align: left; }</style>';
					echo'<tr class="inside-field"><th scope="row">You are using Free Accept 2Checkout Payments Using Contact Form 7 - no license needed. Enjoy! 🙂‚</th></tr>';
					echo'<tr class="inside-field"><th scope="row"><a href='.CF72CH_PRODUCT_LINK.' target="_blank">To unlock more features consider upgrading to PRO.</a></th></tr>';
				echo '</tbody></table>';

			}else{
				echo '<table class="cf72ch-box-data form-table">' .
					'<style>.inside-field td, .inside-field th{ padding-top: 5px; padding-bottom: 5px;}</style>';

					if ( !empty( $fields ) ) {

						if ( array_key_exists( '_transaction_response', $fields ) && empty( get_post_meta( $form_id, CF72CH_META_PREFIX . 'debug', true )  ) ) {
							unset( $fields['_transaction_response'] );
						}

						$attachment = ( !empty( get_post_meta( $post->ID, '_attachment', true ) ) ? unserialize( get_post_meta( $post->ID, '_attachment', true ) ) : '' );
						$root_path = get_home_path();

						foreach ( $fields as $key => $value ) {

							if (
								!empty( get_post_meta( $post->ID, $key, true ) )
								&& $key != '_form_data'
								&& $key != '_transaction_response'
								&& $key != '_transaction_status'
							) {

								$val = trim( sanitize_text_field( get_post_meta( $post->ID, $key, true ) ) );

								echo '<tr class="form-field">' .
									'<th scope="row">' .
										'<label for="hcf_author">' . __( sprintf( '%s', $value ), 'accept-2checkout-payments-using-contact-form-7' ) . '</label>' .
									'</th>' .
									'<td>' .
										(
											(
												'_form_id' == $key
												&& !empty( get_the_title( get_post_meta( $post->ID, $key, true ) ) )
											)
											? get_the_title( get_post_meta( $post->ID, $key, true ) )
											: trim( sanitize_text_field(  get_post_meta( $post->ID, $key, true ) ) )
										) .
									'</td>' .
								'</tr>';

							} else if(
								!empty( get_post_meta( $post->ID, $key, true ) )
								&& $key == '_transaction_status'
							){
								echo '<tr class="form-field">' .
									'<th scope="row">' .
										'<label for="hcf_author">' . __( sprintf( '%s', $value ), 'accept-2checkout-payments-using-contact-form-7' ) . '</label>' .
									'</th>' .
									'<td>' .
										(
											(
												!empty( CF72CH()->lib->response_status )
												&& array_key_exists( get_post_meta( $post->ID , $key, true ), CF72CH()->lib->response_status )
											)
											? CF72CH()->lib->response_status[get_post_meta( $post->ID , $key, true )]
											: trim( sanitize_text_field(  get_post_meta( $post->ID , $key, true ) ) )
										) .
									'</td>' .
								'</tr>';
							} else if (
								!empty( get_post_meta( $post->ID, $key, true ) )
								&& $key == '_transaction_status'
							) {

								echo '<tr class="form-field">' .
									'<th scope="row">' .
										'<label for="hcf_author">' . __( sprintf( '%s', $value ), 'accept-2checkout-payments-using-contact-form-7' ) . '</label>' .
									'</th>' .
									'<td>' .
										(
											(
												!empty( CF72CH()->lib->response_status )
												&& array_key_exists( get_post_meta( $post->ID , $key, true ), CF72CH()->lib->response_status )
											)
											? CF72CH()->lib->response_status[get_post_meta( $post->ID , $key, true )]
											: trim( sanitize_text_field(  get_post_meta( $post->ID , $key, true ) ) )
										) .
									'</td>' .
								'</tr>';

							} else if (
								!empty( get_post_meta( $post->ID, $key, true ) )
								&& $key == '_form_data'
							) {

								echo '<tr class="form-field">' .
									'<th scope="row">' .
										'<label for="hcf_author">' . __( sprintf( '%s', $value ), 'accept-2checkout-payments-using-contact-form-7' ) . '</label>' .
									'</th>' .
									'<td>' .
										'<table>';

											$data = unserialize( get_post_meta( $post->ID, $key, true ) );

											$hide_data = apply_filters( CF72CH_PREFIX . '/hide-display', array( '_wpcf7', '_wpcf7_version', '_wpcf7_locale', '_wpcf7_unit_tag', '_wpcf7_container_post' ) );
											foreach ( $hide_data as $key => $value ) {
												if ( array_key_exists( $value, $data ) ) {
													unset( $data[$value] );
												}
											}

											if ( !empty( $data ) ) {
												foreach ( $data as $key => $value ) {
													if ( strpos( $key, 'two_checkout-' ) === false ) {
														echo '<tr class="inside-field">' .
															'<th scope="row">' .
																__( sprintf( '%s', $key ), 'accept-2checkout-payments-using-contact-form-7' ) .
															'</th>' .
															'<td>' .
																(
																	(
																		!empty( $attachment )
																		&& array_key_exists( $key, $attachment )
																	)
																	? '<a href="' . esc_url( home_url( str_replace( $root_path, '/', $attachment[$key] ) ) ) . '" target="_blank" download>' . __( sprintf( '%s', substr($attachment[$key], strrpos($attachment[$key], '/') + 1) ), 'accept-2checkout-payments-using-contact-form-7' ) . '</a>'
																	: __( sprintf( '%s', ( is_array($value) ? implode( ', ', $value ) :  $value ) ), 'accept-2checkout-payments-using-contact-form-7' )
																) .
															'</td>' .
														'</tr>';
													}
												}
											}

										echo '</table>' .
									'</td>
								</tr>';

							} else if (
								!empty( get_post_meta( $post->ID, $key, true ) )
								&& $key == '_transaction_response'
							) {

								echo '<tr class="form-field">' .
									'<th scope="row">' .
										'<label for="hcf_author">' . __( sprintf( '%s', $value ), 'accept-2checkout-payments-using-contact-form-7' ) . '</label>' .
									'</th>' .
									'<td>' .
										'<code style="word-break: break-all;">' .
											(
												trim( sanitize_text_field(  get_post_meta( $post->ID , $key, true ) ) )
											) .
										'</code>' .
									'</td>' .
								'</tr>';
							}
						}
					}

				echo '</table>';
			}
		}

		/**
		 * - Used to add meta box in CPT detail page.
		 */
		function cf72ch_show_help_data() {
			echo '<div id="cf72ch-data-help">' .
				apply_filters(
					CF72CH_PREFIX . '/help/'.CF72CH_POST_TYPE.'/postbox',
					'<ol>' .
						'<li><a href="'.CF72CH_DOCUMENT.'" target="_blank">Refer the document.</a></li>' .
						'<li><a href="'.CF72CH_SUPPORT.'" target="_blank">Contact Us</a></li>' .
					'</ol>'
				) .
			'</div>';
		}
		
		/**
		 * Check CT
		 */
		function cf72ch_check_data_ct( $post_id ){
			$data = unserialize( get_post_meta( $post_id, '_form_data', true ) );
			if( !empty( get_post_meta( $post_id, '_form_data', true ) ) && isset( $data['_exceed_num_cf72ch'] ) && !empty( $data['_exceed_num_cf72ch'] ) ){
				return $data['_exceed_num_cf72ch'];
			}else{
				return '';
			}
		}
	}

	add_action( 'plugins_loaded' , function() {
		CF72CH()->admin->action = new CF72CH_Admin_Action;
	} );
}
