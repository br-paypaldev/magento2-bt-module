define([
    'mage/storage',
    'Magento_Checkout/js/model/url-builder'
], function (storage, urlBuilder) {
    'use strict';

    return function (installments) {
        return storage.post(
            urlBuilder.createUrl(
                '/braintreebrasil/creditcard/save-selected-installments',
                {}
            ),
            JSON.stringify({
                installments: installments
            })
        );
    };
});
