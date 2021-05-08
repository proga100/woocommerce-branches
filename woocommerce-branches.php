<?php
/**
Plugin Name: Woocommerce Branches relations functions
Plugin URI: flance.info
Description: 1. Statement of account for parent account (age trial balance) 2. Invoice for parent account 3. Order status 4. Product price by customer 5. Packing slip split based on backend users selection
Author: flance.info
Author URI: flance.info
Text Domain: woocommerce-branches
Version: 1.0
*/

if( !defined( 'ABSPATH' ) ) exit; //Exit if accessed directly
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'woo-includes/woo-functions.php' );
}
define( 'FLANCE_BRANCHES', '1.0.0' );
define( 'FLANCE_BRANCHES_DB_VERSION', '1.0.0' );
define( 'FLANCE_BRANCHES_FILE', __FILE__ );
define( 'FLANCE_BRANCHES_PATH', dirname( FLANCE_BRANCHES_FILE ) );
define( 'FLANCE_BRANCHES_URL', plugin_dir_url( FLANCE_BRANCHES_FILE ) );

if ( ! is_textdomain_loaded( 'woocommerce-branches' ) ) {
    load_plugin_textdomain(
        'woocommerce-branches',
        false,
        'woocommerce-branches/languages'
    );
}
if ( ! is_woocommerce_active() ) {
	return;
}

require_once FLANCE_BRANCHES_FILE . '/inc/autoload.php';


