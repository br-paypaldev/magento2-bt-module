define([
    'mage/storage',
    'Magento_Checkout/js/model/url-builder'
], function (storage, urlBuilder) {
    'use strict';

    return function (total, type) {
        if (type === 'creditcard') {
            return storage.post(
                urlBuilder.createUrl(
                    '/braintreebrasil/creditcard/available-installments',
                    {}
                ),
                JSON.stringify({
                    total: total
                })
            )
        }
        if (type === 'twocreditcards') {
            return storage.post(
                urlBuilder.createUrl(
                    '/braintreebrasil/twocreditcards/available-installments',
                    {}
                ),
                JSON.stringify({
                    total: total
                })
            )
        }
    };
});
