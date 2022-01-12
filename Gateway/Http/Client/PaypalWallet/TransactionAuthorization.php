<?php

namespace Paypal\BraintreeBrasil\Gateway\Http\Client\PaypalWallet;

use Braintree\Exception\Authorization;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Paypal\BraintreeBrasil\Gateway\Config\PaypalWallet\Config;
use Paypal\BraintreeBrasil\Gateway\Http\Client;
use Paypal\BraintreeBrasil\Gateway\Http\Client\PaypalWallet\Authorization\ChargePayPalWalletInstallments;
use Paypal\BraintreeBrasil\Logger\Logger;

/**
 * Class TransactionAuthorization
 */
class TransactionAuthorization implements ClientInterface
{

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Client
     */
    private $braintreeClient;

    /**
     * @var Config
     */
    private $paypalWalletConfig;

    /**
     * @var Authorization\ChargePayPalWalletInstallments
     */
    private $chargePayPalWalletInstallments;

    /**
     * @param Logger $logger
     * @param Config $paypalWalletConfig
     * @param Client $braintreeClient
     * @param ChargePayPalWalletInstallments $chargePayPalWalletInstallments
     * @param array $data
     */
    public function __construct(
        Logger $logger,
        Config $paypalWalletConfig,
        Client $braintreeClient,
        ChargePayPalWalletInstallments $chargePayPalWalletInstallments
    ) {
        $this->logger = $logger;
        $this->braintreeClient = $braintreeClient;
        $this->paypalWalletConfig = $paypalWalletConfig;
        $this->chargePayPalWalletInstallments = $chargePayPalWalletInstallments;
    }

    /**
     * @param TransferInterface $transferObject
     * @return array
     * @throws ClientException
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $request = $transferObject->getBody();

        $this->logger->info('Transaction Authorization', [$request]);

        $response = [];

        try {
            // Have installments?
            if ($this->paypalWalletConfig->getEnableInstallments()
                && isset($request['with_installments']['installments'])
                && (int)$request['with_installments']['installments'] > 1) {
                // Charge on PayPal GraphQl API

                if ($request['with_installments']['installments'] > $this->paypalWalletConfig->getMaxInstallments()) {
                    throw new LocalizedException(__('Invalid installment'));
                }

                $paymentMethodId = $request['with_installments']['payment_method_graphql_id'];
                $financingOptionMonthlyPayment = $request['with_installments']['financing_option_monthly_payment'];

                $result = $this->chargePayPalWalletInstallments->execute(
                    $paymentMethodId,
                    $request['amount'],
                    (int)$request['with_installments']['installments'],
                    $financingOptionMonthlyPayment,
                    $request['lineItems'],
                    $request['shipping'],
                    $request['shippingAmount'],
                    $request['merchantAccountId']
                );
                $response['paypal_charge_result'] = $result;

                $this->logger->info('Transaction RESULT', [$response['paypal_charge_result']]);
            } else {
                // Charge on Braintree API
                if (empty($request['customer']['braintree_customer_id'])) {
                    throw new LocalizedException(__('Customer CPF/CNPJ is empty'));
                }

                $request = array_merge($request, $request['without_installments']);
                unset(
                    $request['without_installments'],
                    $request['with_installments'],
                    $request['customer']
                );

                $result = $this->braintreeClient->getBraintreeClient()->transaction()->sale($request);
                $response['braintree_result'] = $result;

                $this->logger->info('Transaction RESULT', [$response['braintree_result']]);
            }
        } catch (Authorization $e) {
            $this->logger->info('Braintree Authorization Exception', [$e->getMessage()]);
            $response['error'] = $e->getMessage();
        } catch (\Exception $e) {
            $this->logger->info('Transaction Authorization ERROR', [$e->getMessage()]);
            $response['error'] = $e->getMessage();
        }

        return $response;
    }
}
