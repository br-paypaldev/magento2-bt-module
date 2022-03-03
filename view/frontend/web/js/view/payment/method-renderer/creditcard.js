define(
    [
        'ko',
        'underscore',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/totals',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Payment/js/model/credit-card-validation/credit-card-data',
        'Paypal_BraintreeBrasil/js/action/get-creditcard-installments',
        'Paypal_BraintreeBrasil/js/action/get-payment-tokens',
        'Paypal_BraintreeBrasil/js/action/save-creditcard-selected-installments',
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
        totals,
        priceUtils,
        fullScreenLoader,
        additionalValidators,
        creditCardData,
        installmentsAction,
        availablePaymentTokensAction,
        saveCreditCardSelectedInstallmentsAction,
        braintreeBrasilClient,
        braintreeBrasilHostedFields,
        braintreeBrasilDeviceDataCollector,
        $t,
        $
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Paypal_BraintreeBrasil/payment/creditcard',
                isLoggedIn: false,
                braintreeTokenData: '',
                creditCardInstallments: 1,
                availableInstallments: [],
                hostedFieldsInitialized: false,
                saveCc: false,
                showForm: false,
                usePaymentToken: ''
            },

            validate: function() {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },

            initialize: function() {
                var self = this;
                this._super();

                var grandTotal = this.calculateGrandTotal();

                $.when(installmentsAction(grandTotal, 'creditcard')).then(function(result){
                    self.availableInstallments(result);
                });

                totals.totals.subscribe(function(){
                    grandTotal = self.calculateGrandTotal();
                    $.when(installmentsAction(grandTotal, 'creditcard')).then(function(result){
                        self.availableInstallments(result);
                    });
                })

                $.when(availablePaymentTokensAction('creditcard')).then(function(result){
                    self.availablePaymentTokens(result);
                });

                if(window.checkoutConfig.quoteData.customer_id){
                    this.isLoggedIn(true);
                }

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

                this.creditCardInstallments.subscribe(function(value){
                    if(value && this.isActive()){
                        this.updateSelectedInstallments(true)
                    }
                }.bind(this))

                this.isChecked.subscribe(function(){
                    if(this.availableInstallments() && this.isActive()){
                        this.updateSelectedInstallments(false)
                    }
                }.bind(this))
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'isLoggedIn',
                        'braintreeTokenData',
                        'creditCardInstallments',
                        'maskedCreditCardNumber',
                        'hostedFieldsInstance',
                        'hostedFieldsInitialized',
                        'dataCollectorInstance',
                        'saveCc',
                        'usePaymentToken',
                        'showForm'
                    ]);

                this.availableInstallments = ko.observableArray();
                this.availablePaymentTokens = ko.observableArray();
                return this;
            },

            calculateGrandTotal: function(){
                var grandTotal = parseFloat(totals.getSegment('grand_total').value);

                // remove previous insterest rate applied
                if(totals.getSegment('installments_interest_rate')){
                    grandTotal = grandTotal - parseFloat(totals.getSegment('installments_interest_rate').value);
                }

                return grandTotal;
            },

            getData: function() {
                // is not checked
                if(this.getCode() !== this.isChecked()){
                    return {
                        method: this.getCode()
                    }
                }

                // is checked, and use token
                if(this.getCode() === this.isChecked() && this.usePaymentToken()){
                    return {
                        method: this.getCode(),
                        additional_data: {
                            'installments': this.creditCardInstallments(),
                            'use_payment_token': this.usePaymentToken(),
                            'device_data': this.dataCollectorInstance().deviceData,
                            'payment_nonce': null,
                            'cc_bin': null,
                            'cc_last': null,
                            'cc_type': null,
                            'cc_exp_month': null,
                            'cc_exp_year': null,
                            'cc_owner': null,
                            'save_cc': null,
                        }
                    };
                }

                // is checked, but not have card data
                if(this.getCode() === this.isChecked() && !this.braintreeTokenData()){
                    return {
                        method: this.getCode()
                    }
                }

                // have card data
                return {
                    method: this.getCode(),
                    additional_data: {
                        'payment_nonce': this.braintreeTokenData().nonce,
                        'cc_bin': this.braintreeTokenData().details.bin,
                        'cc_last': this.braintreeTokenData().details.lastFour,
                        'cc_type': this.braintreeTokenData().details.cardType,
                        'cc_exp_month': this.braintreeTokenData().details.expirationMonth,
                        'cc_exp_year': this.braintreeTokenData().details.expirationYear,
                        'cc_owner': this.braintreeTokenData().details.cardholderName,
                        'installments': this.creditCardInstallments(),
                        'use_payment_token': null,
                        'save_cc': this.saveCc(),
                        'device_data': this.dataCollectorInstance().deviceData
                    }
                };
            },

            updateSelectedInstallments: function(selectPaymentMethod){
                $('body').trigger('processStart');

                $.when(saveCreditCardSelectedInstallmentsAction(this.creditCardInstallments())).then(function(){
                    $('body').trigger('processStop');

                    if(selectPaymentMethod){
                        this.selectPaymentMethod();
                    }
                }.bind(this));
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

                    braintreeBrasilDeviceDataCollector.create({
                        client: clientInstance,
                        hostedFields: true
                    }, function (err, dataCollectorInstance) {
                        if (err) {
                            // Handle error in creation of data collector
                            return;
                        }
                        self.dataCollectorInstance(dataCollectorInstance);
                    });

                    // hosted fields
                    braintreeBrasilHostedFields.create({
                        client: clientInstance,
                        fields: {
                            cardholderName: {
                                selector: '#dropin-cc-name',
                                placeholder: $t('Name as it appears on your card')
                            },
                            number: {
                                selector: '#dropin-cc-number',
                                placeholder: '4111 1111 1111 1111'
                            },
                            cvv: {
                                selector: '#dropin-cc-cvv',
                                placeholder: '123'
                            },
                            expirationDate: {
                                selector: '#dropin-cc-expiration',
                                placeholder: $t('MM / YY')
                            }
                        }
                    }, function(err, hostedFieldsInstance) {
                        if (err) {
                            console.error(err);
                            return;
                        }

                        self.hostedFieldsInstance(hostedFieldsInstance);
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
                return window.checkoutConfig.payment.paypal_braintree_brasil_creditcard
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

            validateCreditCardData: function(callback){
                if(this.usePaymentToken()){
                    callback(true);
                } else {
                    var has_errors = false;
                    var state = this.hostedFieldsInstance().getState();

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

                if(this.validate()){
                    if(self.usePaymentToken()){
                        // use payment token
                        self.placeOrder();
                    } else {
                        // use new card
                        this.validateCreditCardData(function(isValid){
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
                                self.messageContainer.addErrorMessage({message: $t('Invalid credit card')});
                            }
                        });
                    }
                }
            }
        });
    }
);
