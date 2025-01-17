jQuery( function( $ ) {
    'use strict';

	/**
	 * Object to handle Monnify admin functions.
	 */
	var wc_monnify_admin = {
        /**
		 * Initialize.
		 */
		init: function() {

            //Toggle api key setting.
            $( document.body ).on('change', '#woocommerce_monnify_testmode', function() {
                var test_secret_key = $( '#woocommerce_monnify_test_secret_key' ).parents( 'tr' ).eq( 0 ),
                    test_api_key = $( '#woocommerce_monnify_test_api_key' ).parents( 'tr' ).eq( 0 ),
                    test_contract_code = $( '#woocommerce_monnify_test_contract_code' ).parents( 'tr' ).eq( 0 ),
                    live_secret_key = $( '#woocommerce_monnify_live_secret_key' ).parents( 'tr' ).eq( 0 ),
                    live_api_key = $( '#woocommerce_monnify_live_api_key' ).parents( 'tr' ).eq( 0 ),
                    live_contract_code = $( '#woocommerce_monnify_live_contract_code' ).parents( 'tr' ).eq( 0 );

                    if ( $( this ).is( ':checked' ) ) {
                        test_secret_key.show();
                        test_api_key.show();
                        test_contract_code.show();
                        live_secret_key.hide();
                        live_api_key.hide();
                        live_contract_code.hide();
                    } else {
                        test_secret_key.hide();
                        test_api_key.hide();
                        test_contract_code.hide();
                        live_secret_key.show();
                        live_api_key.show();
                        live_contract_code.show();
                    }
            });

            $( '#woocommerce_monnify_testmode' ).change();

            //Toogle bank filter setting.

            //Toogle secret
            
        }
    }

    wc_monnify_admin.init();

});