<?php

declare(strict_types=1);

namespace Paypal\BraintreeBrasil\Service;

class GetInstallments
{
    /**
     * @var PaypalGraphQL
     */
    private $paypalGraphQLClient;

    /**
     * @param PaypalGraphQL $paypalGraphQLClient
     */
    public function __construct(
        PaypalGraphQL $paypalGraphQLClient
    ) {
        $this->paypalGraphQLClient = $paypalGraphQLClient;
    }

    public function execute($paymentMethodId, $total, $isSandbox)
    {
        $query = 'query PayPalFinancingOptions($input: PayPalFinancingOptionsInput!) {
          paypalFinancingOptions(input: $input) {
            financingOptions {
              creditProductIdentifier
              qualifyingFinancingOptions {
                apr
                nominalRate
                term
                intervalDuration
                countryCode
                creditType
                minimumAmount {
                  value
                  currencyIsoCode
                  currencyCode
                }
                monthlyInterestRate
                monthlyPayment {
                  value
                  currencyIsoCode
                  currencyCode
                }
                totalInterest {
                  value
                  currencyIsoCode
                  currencyCode
                }
                totalCost {
                  value
                  currencyIsoCode
                  currencyCode
                }
                paypalSubsidized
              }
            }
          }
        }';

        $variables = [
            'input' => [
                'paymentMethodId' => $paymentMethodId,
                'amount' => [
                    'value' => $total,
                    'currencyCode' => 'BRL'
                ],
                'countryCode' => 'BR'
            ]
        ];

        $response = $this->paypalGraphQLClient->execute($query, $variables, $isSandbox);
        return json_decode($response->getBody()->getContents());
    }
}
