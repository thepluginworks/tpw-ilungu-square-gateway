<?php
/**
 * Plugin Name: TPW Square Gateway
 * Plugin URI: https://thepluginworks.com/
 * Description: Square payment gateway add-on for TPW Core with direct HTTP payment processing and frontend SDK ownership.
 * Author: ThePluginWorks
 * Author URI: https://thepluginworks.com/
 * Version: 1.1.1
 * Text Domain: tpw-square-gateway
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'TPW_SQUARE_GATEWAY_PLUGIN_VERSION' ) ) {
    define( 'TPW_SQUARE_GATEWAY_PLUGIN_VERSION', '1.1.1' );
}

if ( ! defined( 'TPW_SQUARE_GATEWAY_VERSION' ) ) {
    define( 'TPW_SQUARE_GATEWAY_VERSION', TPW_SQUARE_GATEWAY_PLUGIN_VERSION );
}

if ( ! defined( 'TPW_SQUARE_GATEWAY_PATH' ) ) {
    define( 'TPW_SQUARE_GATEWAY_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'TPW_SQUARE_GATEWAY_URL' ) ) {
    define( 'TPW_SQUARE_GATEWAY_URL', plugin_dir_url( __FILE__ ) );
}

require_once TPW_SQUARE_GATEWAY_PATH . 'includes/class-tpw-square-gateway-loader.php';
require_once TPW_SQUARE_GATEWAY_PATH . 'includes/class-tpw-square-gateway-updater.php';
require_once TPW_SQUARE_GATEWAY_PATH . 'includes/class-tpw-square-gateway-core-integration.php';
require_once TPW_SQUARE_GATEWAY_PATH . 'includes/class-tpw-square-gateway-compatibility-loader.php';
require_once TPW_SQUARE_GATEWAY_PATH . 'includes/class-tpw-square-gateway-direct-http-client.php';
require_once TPW_SQUARE_GATEWAY_PATH . 'includes/class-tpw-square-gateway-legacy-bridge.php';
require_once TPW_SQUARE_GATEWAY_PATH . 'includes/class-tpw-square-gateway-admin.php';
require_once TPW_SQUARE_GATEWAY_PATH . 'includes/class-tpw-square-gateway-settings.php';
require_once TPW_SQUARE_GATEWAY_PATH . 'includes/class-tpw-square-gateway-webhook-controller.php';

register_activation_hook( __FILE__, 'tpw_square_gateway_activate' );
function tpw_square_gateway_activate() {
    TPW_Square_Gateway_Loader::activate();
}

register_deactivation_hook( __FILE__, 'tpw_square_gateway_deactivate' );
function tpw_square_gateway_deactivate() {
    TPW_Square_Gateway_Loader::deactivate();
}

add_action( 'init', 'tpw_square_gateway_load_textdomain' );
function tpw_square_gateway_load_textdomain() {
    load_plugin_textdomain( 'tpw-square-gateway', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_action( 'plugins_loaded', 'tpw_square_gateway_bootstrap', 20 );
function tpw_square_gateway_bootstrap() {
    TPW_Square_Gateway_Updater::init();
    TPW_Square_Gateway_Loader::bootstrap();
}