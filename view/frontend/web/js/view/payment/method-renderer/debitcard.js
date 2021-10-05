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
        'Paypal_BraintreeBrasil/js/action/get-payment-tokens',
        'braintreeBrasilClient',
        'braintreeBrasilHostedFields',
        'braintreeBrasilDeviceDataCollector',
        'mage/translate',
        'jquery',
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
        availablePaymentTokensAction,
        braintreeBrasilClient,
        braintreeBrasilHostedFields,
        braintreeBrasilDeviceDataCollector,
        $t,
        $
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Paypal_BraintreeBrasil/payment/debitcard',
                isLoggedIn: false,
                braintreeTokenData: '',
                hostedFieldsInitialized: false,
                saveDc: false,
                showForm: false,
                usePaymentToken: ''
            },

            validate: function() {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'isLoggedIn',
                        'braintreeTokenData',
                        'maskedDebitCardNumber',
                        'hostedFieldsInstance',
                        'hostedFieldsInitialized',
                        'dataCollectorInstance',
                        'saveDc',
                        'usePaymentToken',
                        'showForm'
                    ]);

                this.availablePaymentTokens = ko.observableArray();
                this.savedCards = ko.observableArray();
                return this;
            },

            initialize: function() {
                var self = this;
                this._super();

                if(window.checkoutConfig.quoteData.customer_id){
                    this.isLoggedIn(true);
                }

                $.when(availablePaymentTokensAction('debitcard')).then(function(result){
                    self.availablePaymentTokens(result);
                });

                // show form behaviour
                this.usePaymentToken.subscribe(function(value){
                    if(parseInt(value)){
                        self.showForm(false);
                    } else {
                        self.showForm(true)
                    }
                })
                this.availablePaymentTokens.subscribe(function(value){
                    if(!value.length){
                        self.showForm(true);
                    }
                })
                if(!this.isLoggedIn()){
                    this.showForm(true);
                }
            },

            getData: function() {
                if(this.getCode() !== this.isChecked()){
                    return {
                        method: this.getCode()
                    }
                }

                if(this.usePaymentToken()){
                    return {
                        method: this.getCode(),
                        additional_data: {
                            'use_payment_token': this.usePaymentToken(),
                            'device_data': this.dataCollectorInstance().deviceData,
                            'payment_nonce': null,
                            'dc_bin': null,
                            'dc_last': null,
                            'dc_type': null,
                            'dc_exp_month': null,
                            'dc_exp_year': null,
                            'dc_owner': null,
                            'save_dc': null,
                        }
                    };
                }

                return {
                    method: this.getCode(),
                    additional_data: {
                        'payment_nonce': this.braintreeTokenData().nonce,
                        'dc_bin': this.braintreeTokenData().details.bin,
                        'dc_last': this.braintreeTokenData().details.lastFour,
                        'dc_type': this.braintreeTokenData().details.cardType,
                        'dc_exp_month': this.braintreeTokenData().details.expirationMonth,
                        'dc_exp_year': this.braintreeTokenData().details.expirationYear,
                        'dc_owner': this.braintreeTokenData().details.cardholderName,
                        'save_dc': this.saveDc(),
                        'use_payment_token': null,
                        'device_data': this.dataCollectorInstance().deviceData
                    }
                };
            },

            initHostedFields: function(){
                var self = this

                if(this.hostedFieldsInitialized()){
                    return;
                }
                this.hostedFieldsInitialized(true)

                braintreeBrasilClient.create({
                    authorization: this.getMethodConfig().client_token
                }, function(err, clientInstance) {
                    if (err) {
                        console.error(err);
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

                    // hosted fields
                    braintreeBrasilHostedFields.create({
                        client: clientInstance,
                        fields: {
                            cardholderName: {
                                selector: '#dropin-dc-name',
                                placeholder: $t('Name as it appears on your card')
                            },
                            number: {
                                selector: '#dropin-dc-number',
                                placeholder: '4111 1111 1111 1111'
                            },
                            cvv: {
                                selector: '#dropin-dc-cvv',
                                placeholder: '123'
                            },
                            expirationDate: {
                                selector: '#dropin-dc-expiration',
                                placeholder: $t('MM / YY')
                            }
                        }
                    }, function(err, hostedFieldsInstance) {
                        if (err) {
                            console.error(err);
                            return;
                        }

                        self.hostedFieldsInstance(hostedFieldsInstance)
                    });
                });
            },

            isActive: function () {
                return true;
            },

            getInstallments: function(){
                return this.availableInstallments()
            },

            getAvailablePaymentTokens: function(){
                var options = [];

                _.each(this.availablePaymentTokens(), function(item){
                    options.push({
                        label: item.card_brand + ' - XXXX-XXXX-XXXX-' + item.card_last_four,
                        value: item.entity_id
                    });
                });

                options.push({
                    label: $t('Use a different card'),
                    value: ''
                });

                this.usePaymentToken(_.first(options).value);

                return options;
            },

            getGlobalConfig: function() {
                return window.checkoutConfig.payment.paypal_braintree_brasil
            },

            getMethodConfig: function() {
                return window.checkoutConfig.payment.paypal_braintree_brasil_debitcard
            },

            _createToken: function(callback){
                this.hostedFieldsInstance().tokenize(function(err, payload) {
                    if (err) {
                        console.error(err);
                        callback(null);
                    } else {
                        callback(payload);
                    }
                });
            },

            validateDebitCardData: function(callback){
                if(this.usePaymentToken()){
                    callback(true);
                } else {
                    var has_errors = false;
                    var state = this.hostedFieldsInstance().getState();

                    console.log('state', state)

                    Object.keys(state.fields).forEach(function(field) {
                        if (!state.fields[field].isValid) {
                            $(state.fields[field].container).addClass('is-invalid');
                            has_errors = true;
                        } else {
                            $(state.fields[field].container).removeClass('is-invalid');
                        }
                    });

                    callback(!has_errors);
                }
            },

            beforePlaceOrder: function(){
                var self = this;

                // validate default form
                if(this.validate()){
                    if(self.usePaymentToken()){
                        // use payment token
                        self.placeOrder();
                    } else {
                        this.validateDebitCardData(function(isValid){
                            if(isValid){
                                fullScreenLoader.startLoader();

                                self._createToken(function(token){

                                    if (token) {
                                        self.braintreeTokenData(token);
                                        self.placeOrder();
                                    } else {
                                        self.braintreeTokenData(null);
                                        self.messageContainer.addErrorMessage({
                                            message: $t('Token generation error. Please contact support.')
                                        });
                                    }

                                    fullScreenLoader.stopLoader();
                                })
                            } else {
                                self.messageContainer.addErrorMessage({message: $t('Invalid debit card')});
                            }
                        });
                    }
                }
            }
        });
    }
);
