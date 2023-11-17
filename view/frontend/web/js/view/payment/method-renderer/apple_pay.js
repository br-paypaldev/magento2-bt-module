define(
    [
        'jquery',
        'ko',
        'underscore',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/totals',
        'braintreeBrasilClient',
        'braintreeBrasilDeviceDataCollector',
        'braintreeBrasilApplePay',
        'applePayLib'
    ],
    function (
        $,
        ko,
        _,
        Component,
        totals,
        braintreeBrasilClient,
        braintreeBrasilDeviceDataCollector,
        braintreeBrasilApplePay
        ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Paypal_BraintreeBrasil/payment/apple_pay',
                canPlaceOrder: false,
                applePayAccountAuthorized: false,
                appleInstance: null,
                applePayPaymentData: null,
                dataCollectorInstance: null
            },
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'isLoggedIn',
                        'dataCollectorInstance',
                        'canPlaceOrder',
                        'applePayAccountAuthorized',
                        'applePayPaymentData'
                    ]);
                return this;
            },

            getGlobalConfig: function() {
                return window.checkoutConfig.payment.paypal_braintree_brasil
            },

            getMethodConfig: function () {
                return window.checkoutConfig.payment.paypal_braintree_brasil_apple_pay;
            },

            initialize: function () {
                this._super();
            },

            calculateGrandTotal: function(){
                var grandTotal = parseFloat(totals.getSegment('grand_total').value);

                return grandTotal;
            },

            getData: function() {
                if(this.getCode() !== this.isChecked()){
                    return {
                        method: this.getCode()
                    }
                }

                return {
                    method: this.getCode(),
                    additional_data: {
                        details: this.applePayPaymentData().details
                            ? JSON.stringify(this.applePayPaymentData().details)
                            : null,
                        nonce: this.applePayPaymentData().nonce,
                        device_data: this.dataCollectorInstance().deviceData
                    }
                };
            },

            initApplePayButton: function () {
                let self = this;
                let pay = document.getElementById("pay");
                let appleInstance;

                braintreeBrasilClient.create({
                    authorization: this.getMethodConfig().client_token
                }, function(err, clientInstance) {
                    if (err) {
                        console.error(err);
                        return;
                    }

                    braintreeBrasilDeviceDataCollector.create({
                        client: clientInstance,
                        hostedFields: true
                    }, function (err, dataCollectorInstance) {
                        if (err) {
                            // Handle error in creation of data collector
                            return;
                        }
                        // At this point, you should access the dataCollectorInstance.deviceData value and provide it
                        // to your server, e.g. by injecting it into your form as a hidden input.
                        self.dataCollectorInstance(dataCollectorInstance);
                    });

                    braintreeBrasilApplePay.create({
                        client: clientInstance
                    }, function (applePayErr, instance) {
                        if (applePayErr) {
                            console.error(applePayErr);
                            return;
                        }
                        appleInstance = instance;
                    })
                })

                pay.addEventListener('click', function (event) {
                    console.log("event =>", event);
                    let paymentRequest = appleInstance.createPaymentRequest({
                        countryCode: "BR",
                        currencyCode: "BRL",
                        total: {
                            label: self.getMethodConfig().store,
                            amount: `${self.calculateGrandTotal()}`
                        },
                        requiredBillingContactFields: ["postalAddress"]
                    })

                    let session = new ApplePaySession(3, paymentRequest);

                    session.onvalidatemerchant = function (event) {
                        appleInstance.performValidation({
                            validationURL: event.validationURL,
                            displayName: self.getMethodConfig().store
                        }, function(err, merchantSession) {
                            if(err) {
                                console.log(err);
                                console.log("Apple Pay failed to load.")
                                return;
                            }

                            session.completeMerchantValidation(merchantSession)
                        });
                    };

                    session.onpaymentauthorized = function (event) {
                        appleInstance.tokenize({
                            token: event.payment.token
                        }, function (tokenizeErr, payload) {
                            if (tokenizeErr) {
                                console.error('Error tokenizing Apple Pay:', tokenizeErr);
                                session.completePayment(ApplePaySession.STATUS_FAILURE);
                                return;
                            }

                            console.log('nonce:', payload.nonce);

                            console.log('billingPostalCode:', event.payment.billingContact.postalCode);
                            session.completePayment(ApplePaySession.STATUS_SUCCESS);
                            self.applePayPaymentData(payload);
                            self.canPlaceOrder(true);
                            self.applePayAccountAuthorized(true);
                        })
                    }
                    session.begin();
                })
            },

            isAppleDevice: function () {
                return window.ApplePaySession
            }
        });
    }
);
