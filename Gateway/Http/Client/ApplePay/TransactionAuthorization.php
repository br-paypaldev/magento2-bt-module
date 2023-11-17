<?php

namespace Paypal\BraintreeBrasil\Gateway\Http\Client\ApplePay;

use Braintree\Exception\Authorization;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Paypal\BraintreeBrasil\Gateway\Config\DebitCard\Config;
use Paypal\BraintreeBrasil\Gateway\Http\Client;
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
     * @param Logger $logger
     * @param Client $braintreeClient
     * @param array $data
     */
    public function __construct(
        Logger $logger,
        Client $braintreeClient,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->braintreeClient = $braintreeClient;
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
            if (empty($request['customer']['braintree_customer_id'])) {
                throw new LocalizedException(__('Customer CPF/CNPJ is empty'));
            }

            $braintreeCustomer = $this->braintreeClient->createBraintreeCustomerIfNotExists(
                $request['customer']['braintree_customer_id'],
                $request['customer']['firstname'],
                $request['customer']['lastname'],
                $request['customer']['email'],
                $request['customer']['telephone'],
                $request['customer']['fax'],
                $request['customer']['company']
            );

            $braintreeCustomerId = $braintreeCustomer->jsonSerialize()['id'];

            unset($request['customer'], $request['payment_token_id'], $request['stc']);

            $request['customerId'] = $braintreeCustomerId;

            $result = $this->braintreeClient->getBraintreeClient()->transaction()->sale($request);

            $this->logger->info('Transaction RESULT', [$result]);

            $response['payment_result'] = $result;
            $response['save_dc'] = $request['options']['storeInVaultOnSuccess'] ?? false;
            $response['braintree_customer_id'] = $braintreeCustomerId;
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
