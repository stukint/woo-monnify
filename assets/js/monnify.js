jQuery( function( $ ) {

    let monnify_submit = false;

    $( '#wc-monnify-form' ).hide();

    wcMonnifyFormHandler();

    jQuery( '#monnify-payment-button' ).click( function(){
        return wcMonnifyFormHandler();
    } );

    jQuery( '#monnify_form form#order_review' ).submit( function() {
        return wcMonnifyFormHandler();
    });

    function wcPaymentMethods() {

        let payment_methods = [];

        if( wc_monnify_params.card_method ){
            payment_methods.push( 'CARD' );
        }

        if( wc_monnify_params.account_transfer_method ){
            payment_methods.push( 'ACCOUNT_TRANSFER' );
        }

        if( wc_monnify_params.ussd_method ){
            payment_methods.push( 'USSD' );
        }

        if( wc_monnify_params.phone_number_method ){
            payment_methods.push( 'PHONE_NUMBER' );
        }

        return payment_methods;

    }

    function wcMonnifyFormHandler(){

        $( '#wc-monnify-form' ).hide();

        if ( monnify_submit ){
            monnify_submit = false;
            return true;
        }

        let $form = $( 'form#payment-form, form#order_review' );

        let monnify_txnref = $form.find( 'input.monnify_txnref' );

        monnify_txnref.val( '' );

        let amount = Number( wc_monnify_params.amount );

        let monnify_callback = function( transaction ) {
			$form.append( '<input type="hidden" class="monnify_txnref" name="monnify_txnref" value="' + transaction.transactionReference + '"/>' );
			monnify_submit = true;

			$form.submit();

			$( 'body' ).block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				},
				css: {
					cursor: "wait"
				}
			} );
		};

        let monnify_close_callback = function(){
            $form.append( '<input type="hidden" class="monnify_txnref" name="monnify_txnref" value="' + wc_monnify_params.reference + '"/>' );
			monnify_submit = true;

			$form.submit();

			$( 'body' ).block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				},
				css: {
					cursor: "wait"
				}
			} );
        };

        let paymentData = {
            amount: amount,
            currency: wc_monnify_params.currency,
            reference: wc_monnify_params.reference,
            customerFullName: wc_monnify_params.customerFullName,
            customerEmail: wc_monnify_params.customerEmail,
            apiKey: wc_monnify_params.apiKey,
            contractCode: wc_monnify_params.contractCode,
            paymentDescription: wc_monnify_params.paymentDescription,
            onLoadStart: ()=>{
            },
            onLoadComplete: ()=>{
            },
            onComplete: monnify_callback,
            onClose: monnify_close_callback
        }

        if ( Array.isArray( wcPaymentMethods() ) && wcPaymentMethods().length ) {
            paymentData['paymentMethods'] = wcPaymentMethods();
        }

        MonnifySDK.initialize(paymentData);

    }

});