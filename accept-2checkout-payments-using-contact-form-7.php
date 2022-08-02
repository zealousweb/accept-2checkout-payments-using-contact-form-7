<?php
/**
* Plugin Name: Accept 2Checkout Payments Using Contact Form 7
* Plugin URL: https://wordpress.org/plugins/accept-2checkout-payments-using-contact-form-7/
* Description: This plugin will integrate 2checkout payment gateway for making your payments through Contact Form 7.
* Version: 1.2
* Author: ZealousWeb
* Author URI: https://www.zealousweb.com
* Developer: The Zealousweb Team
* Developer E-Mail: opensource@zealousweb.com
* Text Domain: accept-2checkout-payments-using-contact-form-7
* Domain Path: /languages
*
* Copyright: © 2009-2020 ZealousWeb.
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
* Basic plugin definitions
*
* @package Accept 2Checkout Payments Using Contact Form 7
* @since 1.2
*/

if ( !defined( 'CF72CH_VERSION' ) ) {
	define( 'CF72CH_VERSION', '1.2' ); // Version of plugin
}

if ( !defined( 'CF72CH_FILE' ) ) {
	define( 'CF72CH_FILE', __FILE__ ); // Plugin File
}

if ( !defined( 'CF72CH_DIR' ) ) {
	define( 'CF72CH_DIR', dirname( __FILE__ ) ); // Plugin dir
}

if ( !defined( 'CF72CH_URL' ) ) {
	define( 'CF72CH_URL', plugin_dir_url( __FILE__ ) ); // Plugin url
}

if ( !defined( 'CF72CH_PLUGIN_BASENAME' ) ) {
	define( 'CF72CH_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); // Plugin base name
}

if ( !defined( 'CF72CH_META_PREFIX' ) ) {
	define( 'CF72CH_META_PREFIX', 'cf72ch_' ); // Plugin metabox prefix
}

if ( !defined( 'CF72CH_PREFIX' ) ) {
	define( 'CF72CH_PREFIX', 'cf72ch' ); // Plugin prefix
}

if ( !defined( 'CF72CH_POST_TYPE' ) ) {
	define( 'CF72CH_POST_TYPE', 'cf72ch_data' ); // Plugin post type
}

if ( !defined( 'CF72CH_SUPPORT' ) ) {
	define( 'CF72CH_SUPPORT', 'https://zealousweb.com/support/' ); // Plugin Support Link
}

if ( !defined( 'CF72CH_DOCUMENT' ) ) {
	define( 'CF72CH_DOCUMENT', 'https://www.zealousweb.com/documentation/wordpress-plugins/accept-2checkout-payments-using-contact-form7/' ); // Plugin Document Link
}

if ( !defined( 'CF72CH_PRODUCT_LINK' ) ) {
	define( 'CF72CH_PRODUCT_LINK', 'https://www.zealousweb.com/wordpress-plugins/accept-2checkout-payments-using-contact-form-7/' ); // Plugin Product Link
}

/**
* Initialize the main class
*/
if ( !function_exists( 'CF72CH' ) ) {

	if ( is_admin() ) {
		require_once( CF72CH_DIR . '/inc/admin/class.' . CF72CH_PREFIX . '.admin.php' );
		require_once( CF72CH_DIR . '/inc/admin/class.' . CF72CH_PREFIX . '.admin.action.php' );
		require_once( CF72CH_DIR . '/inc/admin/class.' . CF72CH_PREFIX . '.admin.filter.php' );
	} else {
		require_once( CF72CH_DIR . '/inc/front/class.' . CF72CH_PREFIX . '.front.php' );
		require_once( CF72CH_DIR . '/inc/front/class.' . CF72CH_PREFIX . '.front.action.php' );
		require_once( CF72CH_DIR . '/inc/front/class.' . CF72CH_PREFIX . '.front.filter.php' );
	}

	require_once( CF72CH_DIR . '/inc/lib/class.' . CF72CH_PREFIX . '.lib.php' );

	//Initialize all the things.
	require_once( CF72CH_DIR . '/inc/class.' . CF72CH_PREFIX . '.php' );
}