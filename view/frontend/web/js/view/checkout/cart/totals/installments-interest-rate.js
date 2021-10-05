define([
        'Paypal_BraintreeBrasil/js/view/checkout/summary/installments-interest-rate'
    ], function (InstallmentsInterestRate) {
        'use strict';

        return InstallmentsInterestRate.extend({

            /**
             * @override
             */
            isDisplayed: function () {
                return this.getPureValue() !== 0;
            }
        });
    }
);
