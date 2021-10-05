define([
    'mage/storage',
    'Magento_Checkout/js/model/url-builder'
], function (storage, urlBuilder) {
    'use strict';

    return function (total) {
        return storage.post(
            urlBuilder.createUrl(
                '/braintreebrasil/creditcard/available-installments',
                {}
            ),
            JSON.stringify({
                total: total
            })
        )
    };
});
