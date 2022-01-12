define(
    [
        'ko',
        'underscore',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Paypal_BraintreeBrasil/js/action/get-paypal-wallet-installments',
        'Paypal_BraintreeBrasil/js/action/save-paypal-payment-method',
        'Paypal_BraintreeBrasil/js/action/save-paypal-wallet-selected-installments',
        'Magento_Checkout/js/action/select-payment-method',
        'braintreeBrasilClient',
        'braintreeBrasilDeviceDataCollector',
        'paypalCheckout',
        'braintreePaypalCheckout',
        'mage/translate',
        'jquery',
        'Magento_Checkout/js/model/totals',
    ],
    function (
        ko,
        _,
        Component,
        checkoutData,
        quote,
        priceUtils,
        fullScreenLoader,
        additionalValidators,
        getPaypalWalletInstallmentsAction,
        savePaypalPaymentMethodAction,
        savePaypalWalletSelectedInstallmentsAction,
        selectPaymentMethodAction,
        braintreeBrasilClient,
        braintreeBrasilDeviceDataCollector,
        paypalCheckout,
        braintreePaypalCheckout,
        $t,
        $,
        totals
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Paypal_BraintreeBrasil/payment/paypal_wallet',
                isLoggedIn: false,
                paypalBillingAgreementData: null,
                paypalButtonInitialized: false,
                paypalAccountAuthorized: false,
                canPlaceOrder: false,
                installments: 1
            },

            validate: function() {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'isLoggedIn',
                        'dataCollectorInstance',
                        'paypalBillingAgreementData',
                        'paypalButtonInitialized',
                        'paypalAccountAuthorized',
                        'canPlaceOrder',
                        'installments',
                        'dataCollectorInstance'
                    ]);

                this.availableInstallments = ko.observableArray();
                return this;
            },

            initialize: function() {
                var self = this;
                this._super();

                if(window.checkoutConfig.quoteData.customer_id){
                    this.isLoggedIn(true);
                }

                this.installments.subscribe(function(value){
                    if(value && this.isActive()){
                        this.updateSelectedInstallments(true)
                    }
                }.bind(this))

                totals.totals.subscribe(function(){
                    if(this.isActive()){
                        this.updateInstallments()
                    }
                }.bind(this))

                this.isChecked.subscribe(function(){
                    if(this.availableInstallments() && this.isActive()){
                        this.updateSelectedInstallments(false)
                    }
                }.bind(this))
            },

            getData: function() {
                return {
                    method: this.getCode(),
                    additional_data: {
                        billing_agreement_data: this.paypalBillingAgreementData()
                            ? JSON.stringify(this.paypalBillingAgreementData())
                            : null,
                        installments: this.installments(),
                        device_data: this.dataCollectorInstance().deviceData
                    }
                };
            },

            isActive: function () {
                return true;
            },

            getGlobalConfig: function() {
                return window.checkoutConfig.payment.paypal_braintree_brasil
            },

            getMethodConfig: function() {
                return window.checkoutConfig.payment.paypal_braintree_brasil_paypal_wallet
            },

            enableInstallments: function(){
                return this.getMethodConfig().enable_installments;
            },

            updateSelectedInstallments: function(selectPaymentMethod){
                $('body').trigger('processStart');

                $.when(savePaypalWalletSelectedInstallmentsAction(this.installments())).then(function(){
                    $('body').trigger('processStop');

                    if(selectPaymentMethod){
                        this.selectPaymentMethod();
                    }
                }.bind(this));
            },

            initPaypalButton: function(){
                var self = this;

                if(this.paypalButtonInitialized()){
                    return;
                }
                this.paypalButtonInitialized(true)

                braintreeBrasilClient.create({
                    authorization: this.getMethodConfig().client_token
                }, function(err, clientInstance) {
                    if (err) {
                        console.error(err);
                        return;
                    }

                    braintreePaypalCheckout.create({
                        client: clientInstance
                    }).then(function(paypalCheckoutInstance){

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

                        return paypal.Button.render({
                            env: self.getGlobalConfig().integration_mode,
                            locale: 'pt_BR',
                            commit: false,
                            style: {
                                size: 'medium',
                                color: 'blue',
                                shape: 'pill',
                                label: 'paypal'
                            },
                            payment: function(){

                                var paypalPaymentOptions = {
                                    locale: 'pt_BR',
                                    currency: 'BRL',
                                    enableShippingAddress: false,
                                    shippingAddressEditable: false,
                                    flow: 'vault',
                                    billingAgreementDescription: $t('Billing agreement') // TODO: add config
                                }

                                return paypalCheckoutInstance.createPayment(paypalPaymentOptions)
                            },
                            onAuthorize: function(data, actions){

                                return paypalCheckoutInstance.tokenizePayment(data)
                                    .then(function(payload){
                                        self.paypalBillingAgreementData(data)
                                        self.paypalAccountAuthorized(true)
                                        self.updateAuthorizedAccount(payload);
                                    })
                            },
                            onCancel: function(data){
                                console.log('Cancel', data)
                                self.paypalBillingAgreementData(null)
                                self.paypalAccountAuthorized(false)
                            },
                            onError: function(err){
                                console.log('Error', err)
                                self.paypalBillingAgreementData(null)
                                self.paypalAccountAuthorized(false)
                                self.messageContainer.addErrorMessage({
                                    message: $t('Ocorreu um erro ao tentar realizar a autorização do seu pagamento')
                                });
                            }
                        }, 'braintree-paypal-button').then(function(){
                            console.log('Button rendered',  paypalCheckoutInstance)
                        }).catch(function(err){
                            console.log('Button rendering error', err)
                            self.paypalBillingAgreementData(null)
                        })
                    })
                })
            },

            updateInstallments: function(){
                var self = this;
                var total = quote.getTotals()();

                $('body').trigger('processStart');

                $.when(getPaypalWalletInstallmentsAction(total.grand_total)).then(function(result){
                    self.availableInstallments(result);
                    $('body').trigger('processStop');

                    self.updateSelectedInstallments();
                })
            },

            updateAuthorizedAccount: function(tokenizationPayload){
                var self = this;

                $('body').trigger('processStart');

                $.when(savePaypalPaymentMethodAction(tokenizationPayload.nonce)).then(function(){
                    self.canPlaceOrder(true);
                    $('body').trigger('processStop');

                    self.updateInstallments();
                });
            }
        });
    }
);
