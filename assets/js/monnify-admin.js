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

            //Toogle secret
            $( '#woocommerce_monnify_test_secret_key, #woocommerce_monnify_live_secret_key' ).after(
				'<button class="wc-monnify-toggle-secret" style="height: 30px; margin-left: 2px; cursor: pointer"><span class="dashicons dashicons-visibility"></span></button>'
			);

            $( '.wc-monnify-toggle-secret' ).on( 'click', function( event ) {
				event.preventDefault();

				let $dashicon = $( this ).closest( 'button' ).find( '.dashicons' );
				let $input = $( this ).closest( 'tr' ).find( '.input-text' );
				let inputType = $input.attr( 'type' );

				if ( 'text' == inputType ) {
					$input.attr( 'type', 'password' );
					$dashicon.removeClass( 'dashicons-hidden' );
					$dashicon.addClass( 'dashicons-visibility' );
				} else {
					$input.attr( 'type', 'text' );
					$dashicon.removeClass( 'dashicons-visibility' );
					$dashicon.addClass( 'dashicons-hidden' );
				}
			} );
            
        }
    }

    wc_monnify_admin.init();

});