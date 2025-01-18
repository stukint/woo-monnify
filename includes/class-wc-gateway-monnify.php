<?php

use WpOrg\Requests\Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Gateway_Monnify extends WC_Payment_Gateway_CC {

    /**
	 * Is test mode active?
	 *
	 * @var bool
	 */
	public $testmode;

    /**
	 * Should orders be marked as complete after payment?
	 * 
	 * @var bool
	 */
	public $autocomplete_order;

    /**
	 * Monnify payment page type.
	 *
	 * @var string
	 */
	public $payment_page;

    /**
	 * Monniy test api key.
	 *
	 * @var string
	 */
	public $test_api_key;

    /**
	 * Monnify test secret key.
	 *
	 * @var string
	 */
	public $test_secret_key;

    /**
	 * Monnify test contract code.
	 *
	 * @var string
	 */
	public $test_contract_code;

	/**
	 * Monnify test API URL.
	 *
	 * @var string
	 */
	public $test_api_url;

    /**
	 * Monnify live api key.
	 *
	 * @var string
	 */
	public $live_api_key;

    /**
	 * Monnify live secret key.
	 *
	 * @var string
	 */
	public $live_secret_key;

    /**
	 * Monnify live contract code.
	 *
	 * @var string
	 */
	public $live_contract_code;

	/**
	 * Monnify test API URL.
	 *
	 * @var string
	 */
	public $live_api_url;

    /**
	 * Should we save customer cards?
	 *
	 * @var bool
	 */
	//Disable this for now
	//public $saved_cards;

    /**
	 * Should the cancel & remove order button be removed on the pay for order page.
	 *
	 * @var bool
	 */
	public $remove_cancel_order_button;

	/**
	 * API public key
	 *
	 * @var string
	 */
	public $api_key;

	/**
	 * API secret key
	 *
	 * @var string
	 */
	public $secret_key;

	/**
	 * Contract Code
	 *
	 * @var string
	 */
	public $contract_code;

	/**
	 * SDK URL
	 *
	 * @var string
	 */
	public $sdk_url;

	/**
	 * API URL
	 *
	 * @var string
	 */
	public $api_url;

	/**
	 * Gateway disabled message
	 *
	 * @var string
	 */
	public $msg;

	/**
	 * Payment channels.
	 *
	 * @var array
	 */
	public $payment_methods = array();

    /**
	 * Constructor
	 */
    public function __construct() {
        $this->id                 = 'monnify';
		$this->method_title       = __( 'Monnify', 'woo-monnify' );
		$this->method_description = sprintf( __( 'Monnify provide merchants with the tools and services needed to accept online payments from local and international customers using Mastercard, Visa, Verve Cards and Bank Accounts. <a href="%1$s" target="_blank">Sign up</a> for a Monnify account, and <a href="%2$s" target="_blank">get your API keys</a>.', 'woo-monnify' ), 'https://monnify.com', 'https://app.monnify.com/developer' );
		$this->has_fields         = true;

		$this->payment_page = $this->get_option( 'payment_page' );

        $this->supports = array(
			'products',
			'refunds',
			// 'tokenization',
			'subscriptions',
			'multiple_subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change',
			'subscription_payment_method_change_customer',
		);

        // Load the form fields
		$this->init_form_fields();

		// Load the settings
		$this->init_settings();

        // Get settings values
        $this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
		$this->enabled            = $this->get_option( 'enabled' );
		$this->testmode           = $this->get_option( 'testmode' ) === 'yes' ? true : false;
		$this->autocomplete_order = $this->get_option( 'autocomplete_order' ) === 'yes' ? true : false;

        $this->test_api_key = $this->get_option('test_api_key');
        $this->test_secret_key = $this->get_option('test_secret_key');
        $this->test_contract_code = $this->get_option('test_contract_code');

        $this->live_api_key = $this->get_option('live_api_key');
        $this->live_secret_key = $this->get_option('live_secret_key');
        $this->live_contract_code = $this->get_option('live_contract_code');

        //Card payments not working
		//$this->saved_cards = $this->get_option( 'saved_cards' ) === 'yes' ? true : false;

		$this->remove_cancel_order_button = $this->get_option( 'remove_cancel_order_button' ) === 'yes' ? true : false;

        $this->api_key = $this->testmode ? $this->test_api_key : $this->live_api_key;
        $this->secret_key = $this->testmode ? $this->test_secret_key : $this->live_secret_key;
		$this->contract_code = $this->testmode ? $this->test_contract_code : $this->live_contract_code;

		$this->sdk_url = 'https://sdk.monnify.com';
		$this->test_api_url = 'https://sandbox.monnify.com';
		$this->live_api_url = 'https://api.monnify.com';
		$this->api_url = $this->testmode ? $this->test_api_url : $this->live_api_url;

        // Hooks
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array(
				$this,
				'process_admin_options',
			)
		);

		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );

		// Payment listener/API hook.
		add_action( 'woocommerce_api_wc_gateway_monnify', array( $this, 'verify_monnify_transaction' ) );

		// Webhook listener/API hook.
		add_action( 'woocommerce_api_nts_wc_monnify_webhook', array( $this, 'process_webhooks' ) );

		// Check if the gateway can be used.
		if ( ! $this->is_valid_for_use() ) {
			$this->enabled = false;
		}
    }

	/**
	 * Check if this gateway is enabled and available in the user's country.
	 */
	public function is_valid_for_use() {
		if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_monnify_supported_currencies', array( 'NGN', 'GHS' ) ) ) ) {
			$this->msg = sprintf( __( 'Monnify does not support your store currency. Kindly set it to either NGN (&#8358) or GHS (&#x20b5;) <a href="%s">here</a>', 'woo-monnify' ), admin_url( 'admin.php?page=wc-settings&tab=general' ) );	

			return false;
		}

		return true;
	}

	/**
	 * Display monnify payment icon.
	 */
	public function get_icon() {
		$icon = '<img src="' . WC_HTTPS::force_https_url( plugins_url( 'assets/images/monnify.png', WC_MONNIFY_MAIN_FILE ) ) . '" alt="Monnify Payment Options" />';

		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	/**
	 * Check if Monnify merchant details is filled.
	 */
	public function admin_notices() {
		
		if ( $this->enabled == 'no' ) {
			return;
		}

		// Check required fields.
		if ( ! ( $this->api_key && $this->secret_key ) ) {
			echo '<div class="error"><p>' . sprintf( __( 'Please enter your Monnify merchant details <a href="%s">here</a> to be able to use the Monnify WooCommerce plugin.', 'woo-monnify' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=monnify' ) ) . '</p></div>';
			return;
		}
	}

	/**
	 * Check if Monnify gateway is enabled.
	 *
	 * @return bool
	 */
	public function is_available() {

		if ( 'yes' == $this->enabled ) {

			if ( ! ( $this->api_key && $this->secret_key ) ) {

				return false;

			}

			return true;

		}

		return false;

	}

	/**
	 * Admin Panel Options.
	 */
	public function admin_options() {

		?>

		<h2><?php _e('Monnify', 'woo-monnify'); ?>
		<?php 
			if ( function_exists( 'wc_back_link' ) ) {
				wc_back_link( __( 'Return to payments', 'woo-monnify' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
			}
		?>
		</h2>

		<h4>
			<strong><?php printf( __( 'Optional: To avoid situations where bad network makes it impossible to verify transactions, set your webhook URL <a href="%1$s" target="_blank" rel="noopener noreferrer">here</a> to the URL below<span style="color: red"><pre><code>%2$s</code></pre></span>', 'woo-monnify' ), 'https://app.monnify.com/developer#webhook-urls', WC()->api_request_url( 'Nts_WC_Monnify_Webhook' ) ); ?></strong>
		</h4>
		
		<?php

		if ( $this->is_valid_for_use() ) {

			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';

		} else { 
			?>
			<div class="inline error"><p><strong><?php _e( 'Monnify Payment Gateway Disabled', 'woo-monnify' ); ?></strong>: <?php echo $this->msg; ?></p></div>
			<?php
		}

	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {

		$form_fields = array(
			'enabled' => array(
				'title'       => __( 'Enable/Disable', 'woo-monnify' ),
				'label'       => __( 'Enable Monnify', 'woo-monnify' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable Monnify as a payment option on the checkout page.', 'woo-monnify' ),
				'default'     => 'no',
				'desc_tip'    => true
			),
			'title' => array(
				'title'       => __( 'Title', 'woo-monnify' ),
				'type'        => 'text',
				'description' => __( 'This controls the payment method title which the user sees during checkout.', 'woo-monnify' ),
				'default'     => __( 'Debit/Credit Cards', 'woo-monnify' ),
				'desc_tip'    => true
			),
			'description' => array(
				'title'       => __( 'Description', 'woo-monnify' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the payment method description which the user sees during checkout.', 'woo-monnify' ),
				'default'     => __( 'Make payment using your debit and credit cards', 'woo-monnify' ),
				'desc_tip'    => true
			),
			'testmode' => array(
				'title'       => __( 'Test mode', 'woo-monnify' ),
				'label'       => __( 'Enable Test Mode', 'woo-monnify' ),
				'type'        => 'checkbox',
				'description' => __( 'Test mode enables you to test payments before going live. <br />Once the LIVE MODE is enabled on your Monnify account uncheck this.', 'woo-monnify' ),
				'default'     => 'yes',
				'desc_tip'    => true
			),
			'payment_page' => array(
				'title'       => __( 'Payment Option', 'woo-monnify' ),
				'type'        => 'select',
				'description' => __( 'SDK shows the payment popup on the page while Redirect will redirect the customer to Monnify to make payment.', 'woo-monnify' ),
				'default'     => '',
				'desc_tip'    => false,
				'options'     => array(
					''          => __( 'Select One', 'woo-monnify' ),
					'inline'    => __( 'SDK', 'woo-monnify' ),
					'redirect'  => __( 'Redirect', 'woo-monnify' )
				)
			),
			'test_secret_key' => array(
				'title'       => __( 'Test Secret Key', 'woo-monnify' ),
				'type'        => 'password',
				'description' => __( 'Enter your Test Secret Key here', 'woo-monnify' ),
				'default'     => ''
			),
			'test_api_key' => array(
				'title'       => __( 'Test API Key', 'woo-monnify' ),
				'type'        => 'text',
				'description' => __( 'Enter your Test API Key here.', 'woo-monnify' ),
				'default'     => ''
			),
			'test_contract_code' => array(
				'title'       => __( 'Test Contract Code', 'woo-monnify' ),
				'type'        => 'text',
				'description' => __( 'Enter your Test Contract Code here.', 'woo-monnify' ),
				'default'     => ''
			),
			'live_secret_key' => array(
				'title'       => __( 'Live Secret Key', 'woo-monnify' ),
				'type'        => 'password',
				'description' => __( 'Enter your Live Secret Key here', 'woo-monnify' ),
				'default'     => ''
			),
			'live_api_key' => array(
				'title'       => __( 'Live API Key', 'woo-monnify' ),
				'type'        => 'text',
				'description' => __( 'Enter your Live API Key here.', 'woo-monnify' ),
				'default'     => ''
			),
			'live_contract_code' => array(
				'title'       => __( 'Live Contract Code', 'woo-monnify' ),
				'type'        => 'text',
				'description' => __( 'Enter your Live Contract Code here.', 'woo-monnify' ),
				'default'     => ''
			),
			'autocomplete_order' => array(
				'title'       => __( 'Autocomplete Order After Payment', 'woo-monnify' ),
				'label'       => __( 'Autocomplete Order', 'woo-monnify' ),
				'type'        => 'checkbox',
				'class'       => 'wc-monnify-autocomplete-order',
				'description' => __( 'If enabled, the order will be marked as complete after successful payment', 'woo-monnify' ),
				'default'     => 'no',
				'desc_tip'    => true
			),
			'remove_cancel_order_button' => array(
				'title'       => __( 'Remove Cancel Order & Restore Cart Button', 'woo-monnify' ),
				'label'       => __( 'Remove the cancel order & restore cart button on the pay for order page', 'woo-monnify' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			// 'custom_gateways' => array(
			// 	'title'       => __( 'Additional Monnify Gateways', 'woo-monnify' ),
			// 	'type'        => 'select',
			// 	'description' => __( 'Create additional custom Monnify based gateways. This allows you to create additional Monnify gateways for different payment methods.', 'woo-monnify' ),
			// 	'default'     => '',
			// 	'desc_tip'    => true,
			// 	'options'     => array(
			// 		''  => __( 'Select One', 'woo-monnify' ),
			// 		'1' => __( '1 gateway', 'woo-monnify' ),
			// 		'2' => __( '2 gateways', 'woo-monnify' ),
			// 		'3' => __( '3 gateways', 'woo-monnify' ),
			// 		'4' => __( '4 gateways', 'woo-monnify' ),
			// 		'5' => __( '5 gateways', 'woo-monnify' )
			// 	)
			// ),
			// 'saved_cards' => array(
			// 	'title'       => __( 'Saved Cards', 'woo-monnify' ),
			// 	'label'       => __( 'Enable Payment via Saved Cards', 'woo-monnify' ),
			// 	'type'        => 'checkbox',
			// 	'description' => __( 'If enabled, users will be able to pay with a saved card during checkout. Card details are saved on Monnify servers, not on your store.<br>Note that you need to have a valid SSL certificate installed.', 'woo-monnify' ),
			// 	'default'     => 'no',
			// 	'desc_tip'    => true
			// )
		);

		// if ( 'NGN' !== get_woocommerce_currency() ) {
		// 	unset( $form_fields['custom_gateways'] );
		// }

		$this->form_fields = $form_fields;
	}

	/**
	 * Payment form on checkout page
	 */
	public function payment_fields() {

		if ( $this->description ) {
			echo wpautop( wptexturize( $this->description ) );
		}

		if ( ! is_ssl() ) {
			return;
		}

		//Tokenization is pointless. Card payments not working
		// if ( $this->supports( 'tokenization' ) && is_checkout() && $this->saved_cards && is_user_logged_in() ) {
		// 	$this->tokenization_script();
		// 	$this->saved_payment_methods();
		// 	$this->save_payment_method_checkbox();
		// }

	}



	/**
	 * Outputs scripts used for monnify payment.
	 */
	public function payment_scripts() {
		
		if ( isset( $_GET['pay_for_order'] ) || ! is_checkout_pay_page() ) {
			return;
		}

		if ( $this->enabled === 'no' ) {
			return;
		}

		$order_key = urldecode( $_GET['key'] );
		$order_id  = absint( get_query_var( 'order-pay' ) );

		$order = wc_get_order( $order_id );

		if ( $this->id !== $order->get_payment_method() ) {
			return;
		}

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( 'jquery' );

		wp_enqueue_script('monnify', $this->sdk_url . '/plugin/monnify.js', array( 'jquery' ), WC_MONNIFY_VERSION, false );

		wp_enqueue_script( 'wc_monnify', plugins_url( 'assets/js/monnify' . $suffix . '.js', WC_MONNIFY_MAIN_FILE ), array( 'jquery', 'monnify' ), WC_MONNIFY_VERSION, false );

		$monnify_params = array(
			'apiKey' => $this->api_key,
			'contractCode' => $this->contract_code
		);

		if ( is_checkout_pay_page() && get_query_var( 'order-pay' ) ) {

			$email         = $order->get_billing_email();
			$first_name	   = $order->get_billing_first_name();
			$last_name	   = $order->get_billing_last_name();
			$customer_name = $first_name . ' ' . $last_name;
			$amount        = $order->get_total();
			$txnref        = 'MNFY_' . $order_id . '_' . time();
			$site_name     = get_option( 'blogname' );
			$payment_descr = 'Payment for ' . $site_name . ' #' . $order_id;
			$the_order_id  = $order->get_id();
			$the_order_key = $order->get_order_key();
			$currency      = $order->get_currency();

			if ( $the_order_id == $order_id && $the_order_key == $order_key ) {

				$monnify_params['amount'] = $amount;
				$monnify_params['currency'] = $currency;
				$monnify_params['reference'] = $txnref;
				$monnify_params['customerFullName'] = $customer_name;
				$monnify_params['customerEmail'] = $email;
				$monnify_params['paymentDescription'] = $payment_descr;

			}

			$order->update_meta_data('_monnify_txn_ref', $txnref);
			$order->save();
		}

		$payment_methods = $this->get_gateway_payment_methods( $order );

		if ( !empty( $payment_methods ) ){
			if ( in_array( 'CARD', $payment_methods, true ) ){
				$monnify_params['card_method'] = 'true';
			}

			if ( in_array( 'ACCOUNT_TRANSFER', $payment_methods, true ) ){
				$monnify_params['account_transfer_method'] = 'true';
			}

			if ( in_array( 'USSD', $payment_methods, true ) ){
				$monnify_params['ussd_method'] = 'true';
			}

			if ( in_array( 'PHONE_NUMBER', $payment_methods, true ) ){
				$monnify_params['phone_number_method'] = 'true';
			}
		}

		wp_localize_script( 'wc_monnify', 'wc_monnify_params', $monnify_params );

	}

	/**
	 * Process the payment.
	 *
	 * @param int $order_id
	 *
	 * @return array|void
	 */
	public function process_payment( $order_id ) {
		//Token payment logic not coded because card payments dont work
		$order = wc_get_order( $order_id );

		if ( 'redirect' === $this->payment_page ) {
			return $this->process_redirect_payment_option( $order_id );
		}

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);

	}
	
	/**
	 * Process a redirect payment option payment.
	 *
	 * @since 5.7
	 * @param int $order_id
	 * @return array|void
	 */
	public function process_redirect_payment_option( $order_id ) {
		//write the code
	}

	/**
	 * Load admin scripts.
	 */
	public function admin_scripts() {
		
		if ( 'woocommerce_page_wc-settings' !== get_current_screen()->id ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$monnify_admin_params = array(
			'plugin_url' => WC_MONNIFY_URL,
		);

		wp_enqueue_script( 'wc_monnify_admin', plugins_url('assets/js/monnify-admin' . $suffix . '.js', WC_MONNIFY_MAIN_FILE ), array(), WC_MONNIFY_VERSION, true);

		wp_localize_script( 'wc_monnify_admin', 'wc_monnify_admin_params', $monnify_admin_params );
		
	}

	/**
	 * Show new card can only be added when placing an order notice.
	 */
	public function add_payment_method() {

		wc_add_notice( __( 'You can only add a new card when placing an order.', 'woo-monnify' ), 'error' );

		return;

	}
	
	/**
	 * Displays the payment page.
	 *
	 * @param $order_id
	 */
	public function receipt_page( $order_id ) {
		$order = wc_get_order( $order_id );

		echo '<div id="wc-monnify-form">';

		echo '<p>' . __( 'Thank you for your order, please click the button below to pay with Monnify.', 'woo-monnify' ) . '</p>';

		echo '<div id="monnify_form"><form id="order_review" method="post" action="' . WC()->api_request_url( 'WC_Gateway_Monnify' ) . '"></form><button class="button" id="monnify-payment-button">' . __( 'Pay Now', 'woo-monnify' ) . '</button>';

		if ( ! $this->remove_cancel_order_button ) {
			echo '  <a class="button cancel" id="monnify-cancel-payment-button" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart', 'woo-monnify' ) . '</a></div>';
		}

		echo '</div>';
	}

	/**
	 * Verify Monnify payment.
	 */
	public function verify_monnify_transaction() {
		
		if ( isset( $_REQUEST['monnify_txnref'] ) ) {
			$monnify_txn_ref = sanitize_text_field( $_REQUEST['monnify_txnref'] );
		} elseif ( isset( $_REQUEST['transactionReference'] ) ) {
			$monnify_txn_ref = sanitize_text_field( $_REQUEST['transactionReference'] );
		} else {
			$monnify_txn_ref = false;
		}

		@ob_clean();

		if ( $monnify_txn_ref ) {

			$monnify_response = $this->get_monnify_transaction( $monnify_txn_ref );

			$monnify_response;

		}

		
		
	}

	/**
	 * Process Webhook.
	 */
	public function process_webhooks() {
		//write the code
	}

	/**
	 * Retrieve access token for monnify verify
	 */
	private function get_monnify_access_token( ){

		$token_url = $this->api_url . '/api/v1/auth/login';

		$token_string = $this->api_key . ':' . $this->secret_key;

		$token_encode = base64_encode($token_string);

		$headers = array(
			'Authorization' => 'Basic ' . $token_encode
		);

		$args = array(
			'headers' => $headers,
			'timeout' => 60
		);

		$request = wp_remote_post($token_url, $args);

		if( !is_wp_error($request) && 200 === wp_remote_retrieve_response_code($request) ){
			$response = json_decode( wp_remote_retrieve_body($request) );
			return $response['accessToken'];
		}

		return false;

	}


	/**
	 * Retrieve a transaction from Monnify.
	 *
	 * @since 5.7.5
	 * @param $monnify_txn_ref
	 * @return false|mixed
	 */
	private function get_monnify_transaction( $monnify_txn_ref ) {

		$access_token = $this->get_monnify_access_token();

		error_log(print_r($access_token, true));

		// if ( $access_token ){

		// 	$monnify_url = $this->api_url . '/api/v2/transactions/' . urlencode( $monnify_txn_ref );

		// 	$headers = array(
		// 		'Authorization' => 'Bearer ' . $access_token,
		// 	);

		// 	$args = array(
		// 		'headers' => $headers,
		// 		'timeout' => 60,
		// 	);

		// 	$request = wp_remote_get($monnify_url, $args);

		// 	error_log(print_r($request, true));

		// 	if( !is_wp_error($request) && 200 === wp_remote_retrieve_response_code($request) ){
		// 		return json_decode( wp_remote_retrieve_body($request) );
		// 	}

		// 	return false;

		// }

		// return false;

	}

	/**
	 * Retrieve the payment channels configured for the gateway
	 *
	 * @since 5.7
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	protected function get_gateway_payment_methods( $order ) {
		//Hard Set the payment method because card payments dont work
		//$payment_methods = $this->payment_methods;
		$payment_methods = array('ACCOUNT_TRANSFER');
		if ( empty( $payment_methods ) && ( 'monnify' !== $order->get_payment_method() )){
			$payment_methods = array('CARD');
		}

		/**
		 * Filter the list of payment methods.
		 *
		 * @param array $payment_methods A list of payment methods.
		 * @param string $id Payment method ID.
		 * @param WC_Order $order Order object.
		 * @since 5.8.2
		 */
		return apply_filters( 'wc_monnify_payment_methods', $payment_methods, $this->id, $order );

	}

	/**
	 * Get Monnify payment icon URL.
	 */
	public function get_logo_url() {
		
		$url = WC_HTTPS::force_https_url( plugins_url( 'assets/images/monnify.png', WC_MONNIFY_MAIN_FILE ) );

		return apply_filters( 'wc_monnify_gateway_icon_url', $url, $this->id );

	}

	
}