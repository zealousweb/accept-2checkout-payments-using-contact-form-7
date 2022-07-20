<?php
/**
 * CF72CH_Front_Action Class
 *
 * Handles the Frontend Actions.
 *
 * @package WordPress
 * @subpackage Accept 2Checkout Payments Using Contact Form 7
 * @since 1.2
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF72CH_Front_Action' ) ){

	/**
	 *  The CF72CH_Front_Action Class
	 */
	class CF72CH_Front_Action {

		function __construct()  {

			add_action( 'wp_enqueue_scripts', array( $this, 'action__wp_enqueue_scripts' ) );

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
		 * Action: wp_enqueue_scripts
		 *
		 * - enqueue script in front side
		 *
		 */
		function action__wp_enqueue_scripts() {
			wp_register_script( CF72CH_PREFIX . '_front_js', CF72CH_URL . 'assets/js/front.min.js', array( 'jquery-core' ), CF72CH_VERSION, 'true');
			wp_register_script( CF72CH_PREFIX . '_CardValidator_js', CF72CH_URL . 'assets/js/creditCardValidator.min.js', array( 'jquery-core' ), CF72CH_VERSION, '');
			wp_enqueue_script( CF72CH_PREFIX . '_order_retrive', CF72CH_URL . 'assets/js/order-retrive.min.js', array( 'jquery-core' ), CF72CH_VERSION, 'true');
			wp_localize_script( CF72CH_PREFIX . '_order_retrive', 'frontend_ajax_object',
				array( 
					'ajaxurl' => admin_url( 'admin-ajax.php' )
				)
			);

			wp_register_style( CF72CH_PREFIX . '_select2', CF72CH_URL . 'assets/css/select2.min.css', array(), '4.0.7' );
			wp_register_script( CF72CH_PREFIX . '_select2', CF72CH_URL . 'assets/js/select2.min.js', array( 'jquery-core' ), '4.0.7' );

			wp_register_style( CF72CH_PREFIX . '_front_css', CF72CH_URL . 'assets/css/front.min.css', array(), CF72CH_VERSION );
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


	}

	add_action( 'plugins_loaded' , function() {
		CF72CH()->front->action = new CF72CH_Front_Action;
	} );
}
