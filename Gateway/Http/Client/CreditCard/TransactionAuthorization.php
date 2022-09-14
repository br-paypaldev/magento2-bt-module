<?php

namespace Paypal\BraintreeBrasil\Gateway\Http\Client\CreditCard;

use Braintree\Exception\Authorization;
use Paypal\BraintreeBrasil\Gateway\Config\CreditCard\Config;
use Paypal\BraintreeBrasil\Gateway\Helper\Stc;
use Paypal\BraintreeBrasil\Gateway\Http\Client;
use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Store\Model\StoreManagerInterface;

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
    private $creditCardConfig;

    /**
     * @var Stc
     */
    private $stcHelper;

    /**
     * @param Logger $logger
     * @param Client $braintreeClient
     * @param Config $creditCardConfig
     * @param Stc $stcHelper
     * @param array $data
     */
    public function __construct(
        Logger $logger,
        Client $braintreeClient,
        Config $creditCardConfig,
        Stc $stcHelper,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->braintreeClient = $braintreeClient;
        $this->creditCardConfig = $creditCardConfig;
        $this->stcHelper = $stcHelper;
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

            foreach ($request['stc']['additional_data'] as &$data) {
                if ($data['key'] === 'sender_create_date') {
                    $data['value'] = $braintreeCustomer->createdAt->format('Y-m-d\TH:i:s');
                    break;
                }
            }

            $paymentTokenId = $request['payment_token_id'];
            $braintreeCustomerId = $braintreeCustomer->jsonSerialize()['id'];

            $this->sendStc($request['stc']);
            unset($request['customer'], $request['payment_token_id'], $request['stc']);

            $request['customerId'] = $braintreeCustomerId;

            $result = $this->braintreeClient->getBraintreeClient()->transaction()->sale($request);

            $this->logger->info('Transaction RESULT', [$result]);

            $response['payment_result'] = $result;
            $response['capture'] = $request['options']['submitForSettlement'];
            $response['save_cc'] = $request['options']['storeInVaultOnSuccess'] ?? false;
            $response['payment_token_id'] = $paymentTokenId;
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

    /**
     * @param array $stcData
     * @throws \Exception
     */
    private function sendStc($stcData)
    {
        if ($this->creditCardConfig->getEnableStc()) {
            $this->stcHelper->send($stcData);
        }
    }
}
