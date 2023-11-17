define(
    [
        'jquery',
        'ko',
        'underscore',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/totals',
        'braintreeBrasilClient',
        'braintreeBrasilGooglePay',
        'braintreeBrasilDeviceDataCollector',
        'googlePayLib'
    ],
    function (
        $,
        ko,
        _,
        Component,
        totals,
        braintreeBrasilClient,
        braintreeBrasilGooglePay,
        braintreeBrasilDeviceDataCollector
        ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Paypal_BraintreeBrasil/payment/google_pay',
                canPlaceOrder: false,
                googlePayPaymentData: null,
                googlePayAccountAuthorized: false,
                allowedPaymentMethods: ['VISA', 'MASTERCARD', 'AMEX', 'MAESTRO', 'ELECTRON', 'ELO', 'ELO_DEBIT']
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
                        'googlePayAccountAuthorized',
                        'googlePayPaymentData'
                    ]);
                return this;
            },

            initialize: function () {
                this._super();
            },

            calculateGrandTotal: function(){
                var grandTotal = parseFloat(totals.getSegment('grand_total').value);

                return grandTotal;
            },

            getGlobalConfig: function() {
                return window.checkoutConfig.payment.paypal_braintree_brasil
            },

            getMethodConfig: function () {
                return window.checkoutConfig.payment.paypal_braintree_brasil_google_pay;
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
                        details: this.googlePayPaymentData().details
                            ? JSON.stringify(this.googlePayPaymentData().details)
                            : null,
                        nonce: this.googlePayPaymentData().nonce,
                        device_data: this.dataCollectorInstance().deviceData
                    }
                };
            },

            initGooglePayButton: function () {
                var self = this;

                var paymentsClient = new google.payments.api.PaymentsClient({
                    environment: this.getGlobalConfig().integration_mode === 'production' ? 'PRODUCTION' : 'TEST'
                });

                braintreeBrasilClient.create({
                    authorization: this.getMethodConfig().client_token
                }, function(err, clientInstance) {
                    if (err) {
                        console.error('Error creating client:', err);
                        return;
                    }

                    // device data collect
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

                    braintreeBrasilGooglePay.create({
                        client: clientInstance,
                        googlePayVersion: 2,
                    }, function(err, googlePayInstance) {
                        if (err) {
                            console.error('Error creating google payment:', err);
                            return;
                        }

                        paymentsClient.isReadyToPay({
                            apiVersion: 2,
                            apiVersionMinor: 0,
                            allowedPaymentMethods: googlePayInstance.createPaymentDataRequest().allowedPaymentMethods,
                            existingPaymentMethodRequired: true
                        }).then(isReadyToPay => {
                            if (isReadyToPay.result) {

                                const container = document.getElementById('braintree-google-pay-button');
                                const button = paymentsClient.createButton({
                                    buttonColor: 'default',
                                    buttonType: 'checkout',
                                    onClick: () => {

                                        let overrides = {
                                            transactionInfo: {
                                                currencyCode: 'BRL',
                                                countryCode: 'BR',
                                                totalPriceStatus: 'FINAL',
                                                totalPrice: `${self.calculateGrandTotal()}`,
                                            }
                                        };
                                        let paymentDataRequest = googlePayInstance.createPaymentDataRequest(overrides);
                                        paymentDataRequest.allowedPaymentMethods[0].parameters.billingAddressRequired = true;
                                        paymentDataRequest.allowedPaymentMethods[0].parameters.billingAddressParameters = {
                                            format: 'FULL',
                                            phoneNumberRequired: true
                                        };
                                        paymentDataRequest.allowedPaymentMethods[0].parameters.allowedCardNetworks = self.allowedPaymentMethods;
                                        paymentsClient.loadPaymentData(paymentDataRequest).then(paymentData => {
                                            return googlePayInstance.parseResponse(paymentData);
                                        }).then(result => {
                                            self.googlePayPaymentData(result);
                                            self.canPlaceOrder(true);
                                            self.googlePayAccountAuthorized(true);
                                        }).catch(err => {
                                            console.log("Tokenization failed");
                                            console.error(err);
                                        })
                                    },
                                    allowedPaymentMethods: self.allowedPaymentMethods
                                });
                                container.appendChild(button);
                            }
                        }).catch(err => {
                            console.log(err);
                        })
                    })

                })


            },
        });
    }
);
