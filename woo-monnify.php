<?php 
/**
 * Plugin Name: Monnify WooCommerce Payment Gateway
 * Plugin URI: https://monnify.com
 * Description: WooCommerce payment gateway for Monnify
 * Version: 1.0.0
 * Author: Netsave Technologies
 * Author URI: https://www.netsavetech.com.ng
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires Plugins: woocommerce
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 9.1
 * Text Domain: woo-monnify
 * Domain Path: /languages
 */

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WC_MONNIFY_MAIN_FILE', __FILE__ );
define( 'WC_MONNIFY_URL', untrailingslashit( plugins_url( '/', __FILE__ ) ) );

define( 'WC_MONNIFY_VERSION', '1.0.0' );

/**
 * Initialize Monnify WooCommerce payment gateway.
 */
function nts_wc_monnify_init() {
	load_plugin_textdomain( 'woo-monnify', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		add_action( 'admin_notices', 'nts_wc_monnify_wc_missing_notice' );
		return;
	}

	add_action( 'admin_init', 'nts_wc_monnify_testmode_notice' );

	require_once __DIR__ . '/includes/class-wc-gateway-monnify.php';

	require_once __DIR__ . '/includes/class-wc-gateway-monnify-subscriptions.php';

	require_once __DIR__ . '/includes/custom-gateways/class-wc-gateway-custom-monnify.php';

	require_once __DIR__ . '/includes/custom-gateways/gateway-one/class-wc-gateway-monnify-one.php';
	require_once __DIR__ . '/includes/custom-gateways/gateway-two/class-wc-gateway-monnify-two.php';
	require_once __DIR__ . '/includes/custom-gateways/gateway-three/class-wc-gateway-monnify-three.php';
	require_once __DIR__ . '/includes/custom-gateways/gateway-four/class-wc-gateway-monnify-four.php';
	require_once __DIR__ . '/includes/custom-gateways/gateway-five/class-wc-gateway-monnify-five.php';

	add_filter( 'woocommerce_payment_gateways', 'nts_wc_add_monnify_gateway', 99 );

	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'nts_woo_monnify_plugin_action_links' );
}
add_action( 'plugins_loaded', 'nts_wc_monnify_init', 99 );

/**
 * Add Settings link to the plugin entry in the plugins menu.
 *
 * @param array $links Plugin action links.
 *
 * @return array
 **/
function nts_woo_monnify_plugin_action_links( $links ) {
	$settings_link = array(
		'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=monnify' ) . '" title="' . __( 'View Monnify WooCommerce Settings', 'woo-monnify' ) . '">' . __( 'Settings', 'woo-monnify' ) . '</a>',
	);

	return array_merge( $settings_link, $links );
}

/**
 * Add Monnify Gateway to WooCommerce.
 *
 * @param array $methods WooCommerce payment gateways methods.
 *
 * @return array
 */
function nts_wc_add_monnify_gateway( $methods ) {
	if ( class_exists( 'WC_Subscriptions_Order' ) && class_exists( 'WC_Payment_Gateway_CC' ) ) {
		$methods[] = 'WC_Gateway_Monnify_Subscriptions';
	} else {
		$methods[] = 'WC_Gateway_Monnify';
	}

	if ( 'NGN' === get_woocommerce_currency() ) {

		$settings        = get_option( 'woocommerce_monnify_settings', '' );
		$custom_gateways = isset( $settings['custom_gateways'] ) ? $settings['custom_gateways'] : '';

		switch ( $custom_gateways ) {
			case '5':
				$methods[] = 'WC_Gateway_Monnify_One';
				$methods[] = 'WC_Gateway_Monnify_Two';
				$methods[] = 'WC_Gateway_Monnify_Three';
				$methods[] = 'WC_Gateway_Monnify_Four';
				$methods[] = 'WC_Gateway_Monnify_Five';
				break;
			
			case '4':
				$methods[] = 'WC_Gateway_Monnify_One';
				$methods[] = 'WC_Gateway_Monnify_Two';
				$methods[] = 'WC_Gateway_Monnify_Three';
				$methods[] = 'WC_Gateway_Monnify_Four';
				break;
			
			case '3':
				$methods[] = 'WC_Gateway_Monnify_One';
				$methods[] = 'WC_Gateway_Monnify_Two';
				$methods[] = 'WC_Gateway_Monnify_Three';
				break;

			case '2':
				$methods[] = 'WC_Gateway_Monnify_One';
				$methods[] = 'WC_Gateway_Monnify_Two';
				break;
			
			case '1':
				$methods[] = 'WC_Gateway_Monnify_One';
				break;
			
			default:
				break;
		}
	}

	return $methods;
}

/**
 * Display a notice if WooCommerce is not installed
 */
function nts_wc_monnify_wc_missing_notice() {
	echo '<div class="error"><p><strong>' . sprintf( __( 'Monnify requires WooCommerce to be installed and active. Click %s to install WooCommerce.', 'woo-monnify' ), '<a href="' . admin_url( 'plugin-install.php?tab=plugin-information&plugin=woocommerce&TB_iframe=true&width=772&height=539' ) . '" class="thickbox open-plugin-details-modal">here</a>' ) . '</strong></p></div>';
}

/**
 * Display the test mode notice.
 **/
function nts_wc_monnify_testmode_notice() {
	
	if ( ! class_exists( Notes::class ) ) {
		return;
	}

	if ( ! class_exists( WC_Data_Store::class ) ) {
		return;
	}

	if ( ! method_exists( Notes::class, 'get_note_by_name' ) ) {
		return;
	}

	$test_mode_note = Notes::get_note_by_name( 'monnify-test-mode' );

	if ( false !== $test_mode_note ) {
		return;
	}

	$monnify_settings = get_option( 'woocommerce_monnify_settings' );
	$test_mode         = $monnify_settings['testmode'] ?? '';

	if ( 'yes' !== $test_mode ) {
		Notes::delete_notes_with_name( 'monnify-test-mode' );

		return;
	}

	$note = new Note();
	$note->set_title( __( 'Monnify test mode enabled', 'woo-monnify' ) );
	$note->set_content( __( 'Monnify test mode is currently enabled. Remember to disable it when you want to start accepting live payment on your site.', 'woo-monnify' ) );
	$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
	$note->set_layout( 'plain' );
	$note->set_is_snoozable( false );
	$note->set_name( 'monnify-test-mode' );
	$note->set_source( 'woo-monnify' );
	$note->add_action( 'disable-monnify-test-mode', __( 'Disable Monnify test mode', 'woo-monnify' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=monnify' ) );
	$note->save();
}

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * Registers WooCommerce Blocks integration.
 */
function nts_wc_gateway_monnify_woocommerce_block_support() {
	if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		require_once __DIR__ . '/includes/class-wc-gateway-monnify-blocks-support.php';
		// require_once __DIR__ . '/includes/custom-gateways/class-wc-gateway-custom-monnify-blocks-support.php';
		// require_once __DIR__ . '/includes/custom-gateways/gateway-one/class-wc-gateway-monnify-one-blocks-support.php';
		// require_once __DIR__ . '/includes/custom-gateways/gateway-two/class-wc-gateway-monnify-two-blocks-support.php';
		// require_once __DIR__ . '/includes/custom-gateways/gateway-three/class-wc-gateway-monnify-three-blocks-support.php';
		// require_once __DIR__ . '/includes/custom-gateways/gateway-four/class-wc-gateway-monnify-four-blocks-support.php';
		// require_once __DIR__ . '/includes/custom-gateways/gateway-five/class-wc-gateway-monnify-five-blocks-support.php';
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			static function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ){
				$payment_method_registry->register( new WC_Gateway_Monnify_Blocks_Support() );
				// $payment_method_registry->register( new WC_Gateway_Monnify_One_Blocks_Support() );
				// $payment_method_registry->register( new WC_Gateway_Monnify_Two_Blocks_Support() );
				// $payment_method_registry->register( new WC_Gateway_Monnify_Three_Blocks_Support() );
				// $payment_method_registry->register( new WC_Gateway_Monnify_Four_Blocks_Support() );
				// $payment_method_registry->register( new WC_Gateway_Monnify_Five_Blocks_Support() );
			}
		);
	}
}
add_action('woocommerce_blocks_loaded', 'nts_wc_gateway_monnify_woocommerce_block_support');


