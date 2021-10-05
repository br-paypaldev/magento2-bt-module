define([
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/totals'
    ], function (AbstractTotalComponent, quote, priceUtils, totals) {
        "use strict";

        return AbstractTotalComponent.extend({
            defaults: {
                isFullTaxSummaryDisplayed: window.checkoutConfig.isFullTaxSummaryDisplayed || false,
                template: 'Paypal_BraintreeBrasil/checkout/summary/installments-interest-rate'
            },
            totals: quote.getTotals(),
            isTaxDisplayedInGrandTotal: window.checkoutConfig.includeTaxInGrandTotal || false,

            isDisplayed: function() {
                return this.isFullMode() && this.getPureValue() !== 0;
            },

            getValue: function() {
                var price = 0;
                if (this.totals()) {
                    price = totals.getSegment('installments_interest_rate').value;
                }
                return this.getFormattedPrice(price);
            },
            getPureValue: function() {
                var price = 0;
                if (this.totals()) {
                    price = totals.getSegment('installments_interest_rate').value;
                }
                return price;
            }
        });
    }
);
