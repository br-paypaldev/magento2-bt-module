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
        'Paypal_BraintreeBrasil/js/action/select-payment-method',
        'Magento_Checkout/js/action/set-payment-information-extended',
        'Magento_Checkout/js/action/get-totals',
        'braintreeJqueryMask'
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
        $,
        selectPaymentMethodAction,
        setPaymentInformationExtended,
        getTotals
    ) {
        'use strict';

        var MoneyOpts = {
            reverse: true,
            maxlength: false,
            placeholder: '0,00',
            eventNeedChanged: false,
            prefix: 'R$',
            onKeyPress: function (value, ev, curField, opts) {
                var mask = curField.data('mask').mask;
                var decimalSep = (/0(.)00/gi).exec(mask)[1] || ',';
                if (curField.data('mask-isZero') && curField.data('mask-keycode') === 8) {
                    $(curField).val('');
                } else if (value) {
                    value = value.replace(new RegExp('^0*\\' + decimalSep + '?0*'), '');
                    if (value.length === 0) {
                        value = '0' + decimalSep + '00';
                    } else if (value.length === 1) {
                        value = '0' + decimalSep + '0' + value;
                    } else if (value.length === 2) {
                        value = '0' + decimalSep + value;
                    }
                    $(curField).val(value).data('mask-isZero', (value == '0' + decimalSep + '00'));
                }
            }
        };

        return Component.extend({
            defaults: {
                template: 'Paypal_BraintreeBrasil/payment/two_creditcards',
                isLoggedIn: false,
                card1BraintreeTokenData: '',
                card2BraintreeTokenData: '',
                creditCard1Installments: 1,
                creditCard2Installments: 1,
                card1AvailableInstallments: [],
                card2AvailableInstallments: [],
                card1HostedFieldsInitialized: false,
                card2HostedFieldsInitialized: false,
                saveCc1: false,
                saveCc2: false,
                showForm1: false,
                showForm2: false,
                usePaymentToken1: '',
                usePaymentToken2: '',
                isPaymentValueValid: true
            },


            initialize: function () {
                var self = this;
                this._super();

                var grandTotal = this.calculateGrandTotal();

                totals.totals.subscribe(function () {
                    grandTotal = self.calculateGrandTotal();
                    var card1Total = this.toFloat(this.card1Total()),
                        card2Total = this.toFloat(this.card2Total()),
                        diff = card1Total + card2Total - grandTotal
                    if (diff !== 0) {
                        this.card1Total(this.formatCurrency(card1Total + diff))
                    }
                    this.loadInstallments(this.card1AvailableInstallments, this.card1Total);
                    this.loadInstallments(this.card2AvailableInstallments, this.card2Total);
                }.bind(this));

                $.when(availablePaymentTokensAction('creditcard')).then(function (result) {
                    self.availablePaymentTokens(result);
                });

                if (window.checkoutConfig.quoteData.customer_id) {
                    this.isLoggedIn(true);
                }

                // show form behaviour
                this.usePaymentToken1.subscribe(function (value) {
                    if (parseInt(value)) {
                        self.showForm1(false);
                    } else {
                        self.showForm1(true)
                    }
                })
                this.usePaymentToken2.subscribe(function (value) {
                    if (parseInt(value)) {
                        self.showForm2(false);
                    } else {
                        self.showForm2(true)
                    }
                })

                this.availablePaymentTokens.subscribe(function (value) {
                    if (!value.length) {
                        self.showForm1(true);
                        self.showForm2(true);
                    }
                })
                if (!this.isLoggedIn()) {
                    this.showForm1(true);
                    this.showForm2(true);
                }

                this.creditCard1Installments.subscribe(function (value) {
                    if (value && this.isActive()) {
                        this.updateCard1SelectedInstallments(true)
                    }
                }.bind(this));

                this.creditCard2Installments.subscribe(function (value) {
                    if (value && this.isActive()) {
                        this.updateCard2SelectedInstallments(true)
                    }
                }.bind(this))

                this.isChecked.subscribe(function () {
                    if (this.card1AvailableInstallments() && this.card2AvailableInstallments() && this.isActive()) {
                        this.updateCard1SelectedInstallments(false)
                        this.updateCard2SelectedInstallments(false)
                    }
                }.bind(this));

                this.card1Total.subscribe(function (value) {
                    value = this.toFloat(value);
                    var orderTotalAllowed = this.calculateGrandTotal() - 0.01;
                    if (value > orderTotalAllowed) {
                        this.isPaymentValueValid(false);
                        this.paymentValueMsg(
                            $t("Amount is higher than permitted: R$%1").replace('%1', this.formatCurrency(orderTotalAllowed))
                        );
                    } else if (value < 0.01) {
                        this.isPaymentValueValid(false);
                        this.paymentValueMsg(
                            $t("Amount is lower than permitted: R$%1").replace('%1', this.formatCurrency(0.01))
                        );
                    } else {
                        this.isPaymentValueValid(true);
                        this.paymentValueMsg('');
                        this.calculateCard2Value(value);
                        this.loadInstallments(this.card1AvailableInstallments, this.card1Total);
                        this.loadInstallments(this.card2AvailableInstallments, this.card2Total);
                    }
                }.bind(this));

                this.card1Total(this.formatCurrency(grandTotal / 2));
                this.card2Total(this.formatCurrency(grandTotal / 2));
                this.loadInstallments(this.card1AvailableInstallments, this.card1Total);
                this.loadInstallments(this.card2AvailableInstallments, this.card2Total);
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'isLoggedIn',
                        'card1BraintreeTokenData',
                        'card2BraintreeTokenData',
                        'creditCard1Installments',
                        'creditCard2Installments',
                        'maskedCreditCardNumber',
                        'card1HostedFieldsInstance',
                        'card2HostedFieldsInstance',
                        'card1HostedFieldsInitialized',
                        'card2HostedFieldsInitialized',
                        'card1DataCollectorInstance',
                        'card2DataCollectorInstance',
                        'saveCc1',
                        'saveCc2',
                        'usePaymentToken1',
                        'usePaymentToken2',
                        'showForm1',
                        'showForm2',
                        'card1Total',
                        'card2Total',
                        'paymentValueMsg',
                        'isPaymentValueValid'
                    ]);

                this.card1AvailableInstallments = ko.observableArray();
                this.card2AvailableInstallments = ko.observableArray();
                this.availablePaymentTokens = ko.observableArray();
                return this;
            },

            calculateGrandTotal: function () {
                var grandTotal = parseFloat(totals.getSegment('grand_total').value);

                // remove previous insterest rate applied
                if (totals.getSegment('installments_interest_rate')) {
                    grandTotal = grandTotal - parseFloat(totals.getSegment('installments_interest_rate').value);
                }

                return grandTotal;
            },

            getData: function () {
                // is not checked
                if (this.getCode() !== this.isChecked()) {
                    return {
                        method: this.getCode()
                    }
                }

                var card1Data = {
                        'amount': this.card1Total()
                    },
                    card2Data = {
                        'amount': this.card2Total()
                    };

                if (this.usePaymentToken1()) {
                    card1Data = {
                        'amount': this.card1Total(),
                        'installments': this.creditCard1Installments(),
                        'use_payment_token': this.usePaymentToken1(),
                        'device_data': this.card1DataCollectorInstance().deviceData,
                        'payment_nonce': null,
                        'cc_bin': null,
                        'cc_last': null,
                        'cc_type': null,
                        'cc_exp_month': null,
                        'cc_exp_year': null,
                        'cc_owner': null,
                        'save_cc': null,
                    }
                } else if (this.card1BraintreeTokenData()) {
                    card1Data = {
                        'amount': this.card1Total(),
                        'payment_nonce': this.card1BraintreeTokenData().nonce,
                        'cc_bin': this.card1BraintreeTokenData().details.bin,
                        'cc_last': this.card1BraintreeTokenData().details.lastFour,
                        'cc_type': this.card1BraintreeTokenData().details.cardType,
                        'cc_exp_month': this.card1BraintreeTokenData().details.expirationMonth,
                        'cc_exp_year': this.card1BraintreeTokenData().details.expirationYear,
                        'cc_owner': this.card1BraintreeTokenData().details.cardholderName,
                        'installments': this.creditCard1Installments(),
                        'use_payment_token': null,
                        'save_cc': this.saveCc1(),
                        'device_data': this.card1DataCollectorInstance().deviceData
                    }
                }
                if (this.usePaymentToken2()) {
                    card2Data = {
                        'amount': this.card2Total(),
                        'installments': this.creditCard2Installments(),
                        'use_payment_token': this.usePaymentToken2(),
                        'device_data': this.card2DataCollectorInstance().deviceData,
                        'payment_nonce': null,
                        'cc_bin': null,
                        'cc_last': null,
                        'cc_type': null,
                        'cc_exp_month': null,
                        'cc_exp_year': null,
                        'cc_owner': null,
                        'save_cc': null,
                    };
                } else if (this.card2BraintreeTokenData()) {
                    card2Data = {
                        'amount': this.card2Total(),
                        'payment_nonce': this.card2BraintreeTokenData().nonce,
                        'cc_bin': this.card2BraintreeTokenData().details.bin,
                        'cc_last': this.card2BraintreeTokenData().details.lastFour,
                        'cc_type': this.card2BraintreeTokenData().details.cardType,
                        'cc_exp_month': this.card2BraintreeTokenData().details.expirationMonth,
                        'cc_exp_year': this.card2BraintreeTokenData().details.expirationYear,
                        'cc_owner': this.card2BraintreeTokenData().details.cardholderName,
                        'installments': this.creditCard2Installments(),
                        'use_payment_token': null,
                        'save_cc': this.saveCc2(),
                        'device_data': this.card2DataCollectorInstance().deviceData
                    }
                }

                // have card data
                return {
                    method: this.getCode(),
                    additional_data: {
                        'card_1': JSON.stringify(card1Data),
                        'card_2': JSON.stringify(card2Data)
                    }
                };
            },

            updateCard1SelectedInstallments: function (selectPaymentMethod) {
                $.when(saveCreditCardSelectedInstallmentsAction(this.creditCard1Installments())).then(function () {
                    if (selectPaymentMethod) {
                        this.selectPaymentMethod();
                    }
                }.bind(this));
            },

            updateCard2SelectedInstallments: function (selectPaymentMethod) {
                $.when(saveCreditCardSelectedInstallmentsAction(this.creditCard2Installments(), 'second_creditcard_installments')).then(function () {
                    if (selectPaymentMethod) {
                        this.selectPaymentMethod();
                    }
                }.bind(this));
            },

            initCard1HostedFields: function () {
                this.initHostedFields(
                    '#dropin-cc-name-1',
                    '#dropin-cc-number-1',
                    '#dropin-cc-cvv-1',
                    '#dropin-cc-expiration-1',
                    this.card1HostedFieldsInitialized,
                    this.card1DataCollectorInstance,
                    this.card1HostedFieldsInstance
                );
            },

            initCard2HostedFields: function () {
                this.initHostedFields(
                    '#dropin-cc-name-2',
                    '#dropin-cc-number-2',
                    '#dropin-cc-cvv-2',
                    '#dropin-cc-expiration-2',
                    this.card2HostedFieldsInitialized,
                    this.card2DataCollectorInstance,
                    this.card2HostedFieldsInstance
                );
            },

            initHostedFields: function (ccName, ccNumber, ccCvv, ccExpiration, initObservable, cardDataCollector, hostedField) {
                var self = this

                if (initObservable()) {
                    return;
                }
                initObservable(true)

                braintreeBrasilClient.create({
                    authorization: this.getMethodConfig().client_token
                }, function (err, clientInstance) {
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
                        cardDataCollector(dataCollectorInstance);
                    });

                    // hosted fields
                    braintreeBrasilHostedFields.create({
                        client: clientInstance,
                        fields: {
                            cardholderName: {
                                selector: ccName,
                                placeholder: $t('Name as it appears on your card')
                            },
                            number: {
                                selector: ccNumber,
                                placeholder: '4111 1111 1111 1111'
                            },
                            cvv: {
                                selector: ccCvv,
                                placeholder: '123'
                            },
                            expirationDate: {
                                selector: ccExpiration,
                                placeholder: $t('MM / YY')
                            }
                        }
                    }, function (err, hostedFieldsInstance) {
                        if (err) {
                            console.error(err);
                            return;
                        }

                        hostedField(hostedFieldsInstance);
                    });
                });
            },

            isActive: function () {
                return true;
            },

            getAvailablePaymentTokens: function () {
                var options = [];

                _.each(this.availablePaymentTokens(), function (item) {
                    options.push({
                        label: item.card_brand + ' - XXXX-XXXX-XXXX-' + item.card_last_four,
                        value: item.entity_id
                    });
                });

                options.push({
                    label: $t('Use a different card'),
                    value: ''
                });

                this.usePaymentToken1(_.first(options).value);
                this.usePaymentToken2(_.first(options).value);

                return options;
            },

            getGlobalConfig: function () {
                return window.checkoutConfig.payment.paypal_braintree_brasil
            },

            getMethodConfig: function () {
                return window.checkoutConfig.payment.paypal_braintree_brasil_two_creditcards
            },

            _createToken: function (usePaymentToken, hostedField, callback) {
                if (usePaymentToken()) {
                    callback(true);
                } else {
                    hostedField().tokenize(function (err, payload) {
                        if (err) {
                            console.error(err);
                            callback(false);
                        } else {
                            callback(payload);
                        }
                    });
                }
            },

            validateCreditCardData: function (usePaymentToken, hostedFields, callback) {
                if (usePaymentToken()) {
                    callback(true);
                } else {
                    var has_errors = false;
                    var state = hostedFields().getState();

                    Object.keys(state.fields).forEach(function (field) {
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

            beforePlaceOrder: function () {
                var self = this;

                fullScreenLoader.startLoader();
                this.validateCreditCardData(self.usePaymentToken1, self.card1HostedFieldsInstance, function (isValid) {
                    if (isValid) {
                        self._createToken(self.usePaymentToken1, self.card1HostedFieldsInstance, function (token) {
                            if (typeof token === 'object') {
                                self.card1BraintreeTokenData(token);
                            } else if (!token) {
                                self.card1BraintreeTokenData(null);
                                self.messageContainer.addErrorMessage({
                                    message: $t('Token generation error. Please contact support.')
                                });
                                fullScreenLoader.stopLoader();
                                return;
                            }

                            self.validateCreditCardData(self.usePaymentToken2, self.card2HostedFieldsInstance, function (isValid) {
                                if (isValid) {
                                    self._createToken(self.usePaymentToken2, self.card2HostedFieldsInstance, function (token) {
                                        if (typeof token === 'object') {
                                            self.card2BraintreeTokenData(token);
                                        } else if (!token) {
                                            self.card2BraintreeTokenData(null);
                                            self.messageContainer.addErrorMessage({
                                                message: $t('Token generation error. Please contact support.')
                                            });
                                            fullScreenLoader.stopLoader();
                                            return;
                                        }
                                        self.placeOrder();
                                        fullScreenLoader.stopLoader();
                                    })
                                } else {
                                    self.messageContainer.addErrorMessage({message: $t('Invalid credit card')});
                                    fullScreenLoader.stopLoader();
                                }
                            });
                        });

                    } else {
                        self.messageContainer.addErrorMessage({message: $t('Invalid credit card')});
                        fullScreenLoader.stopLoader();
                    }
                });

            },

            setDecimalMask1: function () {
                $('#' + this.getCode() + '_cc_value_1').mask('#.##0,00', MoneyOpts);
            },

            setDecimalMask2: function () {
                $('#' + this.getCode() + '_cc_value_2').mask('#.##0,00', MoneyOpts);
            },

            formatCurrency: function (value) {
                if (!value && value !== 0) return;

                var isFloat = (Number(value) === value && value % 1 !== 0)
                if (isFloat) {
                    return parseFloat(value).toFixed(2).replace('.', ',');
                }
                return value + ',00'
            },

            toFloat: function (value) {
                var float = /^\s*(\+|-)?((\d+(\.\d+)?)|(\.\d+))\s*$/;
                if (value && !float.test(value)) {
                    value = parseFloat(value.replace('.', '').replace(',', '.'));
                }
                return value;
            },

            calculateCard2Value: function (card1Value) {
                var grandTotal = this.calculateGrandTotal();
                this.card2Total(this.formatCurrency(grandTotal - card1Value));
            },

            loadInstallments: function (cardInstallmentsObservable, cardTotal) {
                var total = this.toFloat(cardTotal())
                $.when(installmentsAction(total, 'twocreditcards')).then(function (result) {
                    cardInstallmentsObservable(result);
                });
            },

            /**
             * @return {Boolean}
             */
            selectPaymentMethod: function () {
                selectPaymentMethodAction(this.getData());
                checkoutData.setSelectedPaymentMethod(this.item.method);
                $.when(setPaymentInformationExtended(this.messageContainer, this.getData(), true)).done(function () {
                    getTotals([], null)
                });

                return true;
            }
        });
    }
)
;
