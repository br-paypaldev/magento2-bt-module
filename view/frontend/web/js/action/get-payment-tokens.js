define([
    'mage/storage',
    'Magento_Checkout/js/model/url-builder'
], function (storage, urlBuilder) {
    'use strict';

    return function (type) {
        return storage.get(urlBuilder.createUrl('/braintreebrasil/available-payment-tokens/' + type, {}))
    };
});
