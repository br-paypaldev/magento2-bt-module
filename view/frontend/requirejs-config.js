var config = {
    map: {
        '*': {
            'braintreeBrasilClient': 'https://js.braintreegateway.com/web/3.76.4/js/client.min.js',
            'braintreeBrasilHostedFields': 'https://js.braintreegateway.com/web/3.76.4/js/hosted-fields.min.js',
            'braintreeBrasilDeviceDataCollector': 'https://js.braintreegateway.com/web/3.76.4/js/data-collector.min.js',
            'paypalCheckout': 'https://www.paypalobjects.com/api/checkout.js',
            'braintreePaypalCheckout': 'https://js.braintreegateway.com/web/3.76.4/js/paypal-checkout.min.js',
            'braintreeJqueryMask': 'Paypal_BraintreeBrasil/js/plugins/jquery.mask.min',
            'braintreeBrasilGooglePay': 'https://js.braintreegateway.com/web/3.76.4/js/google-payment.min.js',
            'braintreeBrasilApplePay': 'https://js.braintreegateway.com/web/3.76.4/js/apple-pay.min.js'
        }
    },
    paths: {
        'googlePayLib': 'https://pay.google.com/gp/p/js/pay',
        'applePayLib': 'https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk'
    },
    shim: {
        'braintreeJqueryMask': ['jquery']
    }
};
