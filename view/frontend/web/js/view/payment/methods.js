define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';
    rendererList.push(
        {
            type: 'paypal_braintree_brasil_creditcard',
            component: 'Paypal_BraintreeBrasil/js/view/payment/method-renderer/creditcard'
        },
        {
            type: 'paypal_braintree_brasil_debitcard',
            component: 'Paypal_BraintreeBrasil/js/view/payment/method-renderer/debitcard'
        },
        {
            type: 'paypal_braintree_brasil_paypal_wallet',
            component: 'Paypal_BraintreeBrasil/js/view/payment/method-renderer/paypal_wallet'
        },
        {
            type: 'paypal_braintree_brasil_two_creditcards',
            component: 'Paypal_BraintreeBrasil/js/view/payment/method-renderer/two_creditcards'
        },
        {
            type: 'paypal_braintree_brasil_google_pay',
            component: 'Paypal_BraintreeBrasil/js/view/payment/method-renderer/google_pay'
        },
        {
            type: 'paypal_braintree_brasil_apple_pay',
            component: 'Paypal_BraintreeBrasil/js/view/payment/method-renderer/apple_pay'
        }
    );
    return Component.extend({});
});
