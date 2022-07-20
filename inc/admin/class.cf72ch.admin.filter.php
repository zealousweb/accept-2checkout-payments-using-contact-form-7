<?php
/**
 * CF72CH_Admin_Filter Class
 *
 * Handles the admin functionality.
 *
 * @package WordPress
 * @subpackage Accept 2Checkout Payments Using Contact Form 7
 * @since 1.2
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF72CH_Admin_Filter' ) ) {

	/**
	 *  The CF72CH_Admin_Filter Class
	 */
	class CF72CH_Admin_Filter {

		function __construct() {
			// Adding Sagepay setting tab
			add_filter( 'wpcf7_editor_panels',							array( $this, 'filter__cf72ch_wpcf7_editor_panels' ), 10, 3 );
			add_filter( 'post_row_actions', 							array( $this, 'filter__cf72ch_post_row_actions' ), 10, 3 );
			add_filter( 'plugin_action_links_'.CF72CH_PLUGIN_BASENAME,	array( $this,'filter__cf72ch_admin_plugin_links'), 10, 2 );

			add_filter( 'manage_edit-'.CF72CH_POST_TYPE.'_sortable_columns',	array( $this, 'filter__manage_cf72ch_data_sortable_columns' ), 10, 3 );
			add_filter( 'manage_'.CF72CH_POST_TYPE.'_posts_columns',			array( $this, 'filter__cf72ch_manage_data_posts_columns' ), 10, 3 );
			add_filter( 'bulk_actions-edit-'.CF72CH_POST_TYPE.'',				array( $this, 'filter__cf72ch_bulk_actions_edit_data' ) );
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
		 * 2Checkout tab
		 * Adding tab in contact form 7
		 *
		 * @param $panels
		 *
		 * @return array
		 */
		public function filter__cf72ch_wpcf7_editor_panels( $panels ) {
			$panels[ 'two-checkout-add-on' ] = array(
				'title'		=> __( '2checkout', 'accept-2checkout-payments-using-contact-form-7' ),
				'callback'	=> array( $this, 'wpcf7_cf72ch_admin_after_additional_settings' )
			);		

			return $panels;
		}

		/**
		 * Filter: post_row_actions
		 *
		 * - Used to modify the post list action buttons.
		 *
		 * @method filter__cf72ch_post_row_actions
		 *
		 * @param  array $actions
		 *
		 * @return array
		 */
		function filter__cf72ch_post_row_actions( $actions ) {

			if ( get_post_type() === CF72CH_POST_TYPE ) {
				unset( $actions['view'] );				
				unset( $actions['inline hide-if-no-js'] );
			}

			return $actions;
		}

		/**
		* Filter: plugin_action_links
		*
		* - Used to add links on Plugins listing page.
		*
		* @method filter__cf72ch_admin_plugin_links
		*
		* @param  array $actions
		*	
		* @return string
		*/
		function filter__cf72ch_admin_plugin_links( $links, $file ) {
			if ( $file != CF72CH_PLUGIN_BASENAME ) {
				return $links;
			}
		
			if ( ! current_user_can( 'wpcf7_read_contact_forms' ) ) {
				return $links;
			}

			$documentLink = '<a target="_blank" href="'.CF72CH_DOCUMENT.'">' . __( 'Document Link', 'accept-2checkout-payments-using-contact-form-7' ) . '</a>';
			array_unshift( $links , $documentLink);
		
			return $links;
		}


		/**
		 * Filter: manage_edit-cf72ch_data_sortable_columns
		 *
		 * - Used to add the sortable fields into "cf72ch_data" CPT
		 *
		 * @method filter__manage_cf72ch_data_sortable_columns
		 *
		 * @param  array $columns
		 *
		 * @return array
		 */
		function filter__manage_cf72ch_data_sortable_columns( $columns ) {
			$columns['total'] = '_total';
			return $columns;
		}

		/**
		 * Filter: manage_cf72ch_data_posts_columns
		 *
		 * - Used to add new column fields for the "cf72ch_data" CPT
		 *
		 * @method filter__cfspzw_manage_cfspzw_data_posts_columns
		 *
		 * @param  array $columns
		 *
		 * @return array
		 */
		function filter__cf72ch_manage_data_posts_columns( $columns ) {
			unset( $columns['date'] );
			$columns['invoice_no'] 			= 	__( 'Invoice ID', 'accept-2checkout-payments-using-contact-form-7' );
			$columns['order_id'] 			= 	__( 'Order ID', 'accept-2checkout-payments-using-contact-form-7' );
			$columns['transaction_status'] 	=	__( 'Transaction Status', 'accept-2checkout-payments-using-contact-form-7' );
			$columns['total'] 				= 	__( 'Total Amount', 'accept-2checkout-payments-using-contact-form-7' );
			$columns['date'] 				= 	__( 'Submitted Date', 'accept-2checkout-payments-using-contact-form-7' );
			return $columns;
		}


		/**
		 * Filter: bulk_actions-edit-cf72ch_data
		 *
		 * - Add/Remove bulk actions for "cf72ch_data" CPT
		 *
		 * @method filter__cf72ch_bulk_actions_edit_data
		 *
		 * @param  array $actions
		 *
		 * @return array
		 */
		function filter__cf72ch_bulk_actions_edit_data( $actions ) {
			unset( $actions['edit'] );
			return $actions;
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
		 * Adding 2Checkout fields in Sagepay tab
		 *
		 * @param $cf7
		 */
		public function wpcf7_cf72ch_admin_after_additional_settings( $cf7 ) {
			
			require_once( CF72CH_DIR .  '/inc/admin/template/' . CF72CH_PREFIX . '.template.php' );

			wp_enqueue_script( CF72CH_PREFIX . '_admin_js' );

		}

	}

	add_action( 'plugins_loaded' , function() {
		CF72CH()->admin->filter = new CF72CH_Admin_Filter;
	} );
}
