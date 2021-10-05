define([
    'mage/storage',
    'Magento_Checkout/js/model/url-builder'
], function (storage, urlBuilder) {
    'use strict';

    return function (payment_method_nonce) {
        return storage.post(
            urlBuilder.createUrl(
                '/braintreebrasil/paypal-wallet/save-payment-method',
                {}
            ),
            JSON.stringify({
                payment_method_nonce: payment_method_nonce
            })
        );
    };
});
