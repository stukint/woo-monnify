<?php

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
	 * Should we save customer cards?
	 *
	 * @var bool
	 */
	public $saved_cards;

    /**
	 * Should Monnify split payment be enabled.
	 *
	 * @var bool
	 */
	public $split_payment;

    /**
	 * Should the cancel & remove order button be removed on the pay for order page.
	 *
	 * @var bool
	 */
	public $remove_cancel_order_button;

    /**
	 * Monnify sub account code.
	 *
	 * @var string
	 */
	public $subaccount_code;

    /**
	 * Who bears Monnify charges?
	 *
	 * @var string
	 */
	public $charges_account;

    /**
	 * A flat fee to charge the sub account for each transaction.
	 *
	 * @var string
	 */
	public $transaction_charges;

    /**
	 * Should custom metadata be enabled?
	 *
	 * @var bool
	 */
	public $custom_metadata;

    /**
	 * Should the order id be sent as a custom metadata to Monnify?
	 *
	 * @var bool
	 */
	public $meta_order_id;

    /**
	 * Should the customer name be sent as a custom metadata to Monnify?
	 *
	 * @var bool
	 */
	public $meta_name;

    /**
	 * Should the billing email be sent as a custom metadata to Monnify?
	 *
	 * @var bool
	 */
	public $meta_email;

    /**
	 * Should the billing phone be sent as a custom metadata to Monnify?
	 *
	 * @var bool
	 */
	public $meta_phone;

    /**
	 * Should the billing address be sent as a custom metadata to Monnify?
	 *
	 * @var bool
	 */
	public $meta_billing_address;

	/**
	 * Should the shipping address be sent as a custom metadata to Monnify?
	 *
	 * @var bool
	 */
	public $meta_shipping_address;

	/**
	 * Should the order items be sent as a custom metadata to Monnify?
	 *
	 * @var bool
	 */
	public $meta_products;

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
	public $payment_channels = array();

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
			'tokenization',
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

        // Get setting values
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

        $this->saved_cards = $this->get_option( 'saved_cards' ) === 'yes' ? true : false;

		$this->split_payment              = $this->get_option( 'split_payment' ) === 'yes' ? true : false;
		$this->remove_cancel_order_button = $this->get_option( 'remove_cancel_order_button' ) === 'yes' ? true : false;
		$this->subaccount_code            = $this->get_option( 'subaccount_code' );
		$this->charges_account            = $this->get_option( 'split_payment_charge_account' );
		$this->transaction_charges        = $this->get_option( 'split_payment_transaction_charge' );

		$this->custom_metadata = $this->get_option( 'custom_metadata' ) === 'yes' ? true : false;

		$this->meta_order_id         = $this->get_option( 'meta_order_id' ) === 'yes' ? true : false;
		$this->meta_name             = $this->get_option( 'meta_name' ) === 'yes' ? true : false;
		$this->meta_email            = $this->get_option( 'meta_email' ) === 'yes' ? true : false;
		$this->meta_phone            = $this->get_option( 'meta_phone' ) === 'yes' ? true : false;
		$this->meta_billing_address  = $this->get_option( 'meta_billing_address' ) === 'yes' ? true : false;
		$this->meta_shipping_address = $this->get_option( 'meta_shipping_address' ) === 'yes' ? true : false;
		$this->meta_products         = $this->get_option( 'meta_products' ) === 'yes' ? true : false;

        $this->api_key = $this->testmode ? $this->test_api_key : $this->live_api_key;
        $this->secret_key = $this->testmode ? $this->test_secret_key : $this->live_secret_key;

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
    }
}