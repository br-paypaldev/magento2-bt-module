<?php

declare(strict_types=1);

namespace Paypal\BraintreeBrasil\Gateway\Http\Client\PaypalWallet\Authorization;

use Paypal\BraintreeBrasil\Gateway\Config\Config;
use Paypal\BraintreeBrasil\Service\PaypalGraphQL;

class ChargePayPalWalletInstallments
{
    /**
     * @var PaypalGraphQL
     */
    private $paypalGraphQLClient;
    /**
     * @var Config
     */
    private $braintreeConfig;

    /**
     * @param Config $braintreeConfig
     * @param PaypalGraphQL $paypalGraphQLClient
     */
    public function __construct(
        Config $braintreeConfig,
        PaypalGraphQL $paypalGraphQLClient
    ) {
        $this->paypalGraphQLClient = $paypalGraphQLClient;
        $this->braintreeConfig = $braintreeConfig;
    }

    /**
     * Execute payment charge on PayPal Wallet account
     *
     * @param $paymentMethodId
     * @param $amount
     * @param $installments
     * @param $financingOptionMonthlyPayment
     * @param $lineItems
     * @param $shipping
     * @param $shippingAmount
     * @param $merchantAccountId
     * @param $descriptor
     * @return mixed
     */
    public function execute(
        $paymentMethodId,
        $amount,
        $installments,
        $financingOptionMonthlyPayment,
        $lineItems,
        $shipping,
        $shippingAmount,
        $merchantAccountId,
        $descriptor
    ) {
        $query = 'mutation ChargePayPalAccount($input: ChargePayPalAccountInput!) {
              chargePayPalAccount(input: $input) {
                transaction {
                  status
                  id
                  legacyId
                  paymentMethodSnapshot {
                    __typename
                    ... on PayPalTransactionDetails {
                      payer {
                        payerId
                        email
                      }
                      paymentId
                      selectedFinancingOption {
                        term
                        monthlyPayment {
                          value
                          currencyIsoCode
                          currencyCode
                        }
                        discountPercentage
                        discountAmount {
                          value
                          currencyIsoCode
                          currencyCode
                        }
                      }
                    }
                  }
                }
              }
            }';

        foreach ($lineItems as &$item) {
            $item['kind'] = strtoupper($item['kind']);
        }

        $shipping['countryCode'] = $shipping['countryCodeAlpha2'];
        unset(
            $shipping['countryCodeAlpha2'],
            $shipping['countryCodeAlpha3'],
            $shipping['countryCodeNumeric'],
            $shipping['countryName']
        );

        $variables = [
            'input' => [
                'paymentMethodId' => $paymentMethodId,
                'transaction' => [
                    'amount' => $amount,
                    'lineItems' => $lineItems,
                    'shipping' => [
                        'shippingAddress' => $shipping,
                        'shippingAmount' => $shippingAmount
                    ],
                    'merchantAccountId' => $merchantAccountId
                ],
                'options' => [
                    'selectedFinancingOption' => [
                        'term' => $installments,
                        'currencyCode' => 'BRL',
                        'monthlyPayment' => $financingOptionMonthlyPayment
                    ]
                ]
            ]
        ];

        if ($descriptor) {
            $variables['input']['transaction']['descriptor'] = $descriptor;
        }

        $isSandbox = $this->braintreeConfig->getIntegrationMode() === Config::SANDBOX_INTEGRATION_MODE;
        $response = $this->paypalGraphQLClient->execute($query, $variables, $isSandbox);

        return json_decode($response->getBody()->getContents());
    }
}
