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
    public function __construct
    (
        Config $braintreeConfig,
        PaypalGraphQL $paypalGraphQLClient
    )
    {
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
     * @return mixed
     */
    public function execute($paymentMethodId, $amount, $installments, $financingOptionMonthlyPayment)
    {
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

        $variables = [
            'input' => [
                'paymentMethodId' => $paymentMethodId,
                'transaction' => [
                    'amount' => $amount
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

        $isSandbox = $this->braintreeConfig->getIntegrationMode() === Config::SANDBOX_INTEGRATION_MODE;
        $response = $this->paypalGraphQLClient->execute($query, $variables, $isSandbox);

        return json_decode($response->getBody()->getContents());
    }
}
