<?php

namespace Paypal\BraintreeBrasil\Gateway\Http\Client\CreditCard;

use Braintree\Exception\Authorization;
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
     * @var \Magento\Framework\App\State
     */
    protected $_appState;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var Client
     */
    private $braintreeClient;

    /**
     * @param Context $context
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param Client $braintreeClient
     * @param array $data
     */
    public function __construct(
        Context $context,
        Logger $logger,
        StoreManagerInterface $storeManager,
        Client $braintreeClient,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->_appState = $context->getAppState();
        $this->_storeManager = $storeManager;
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

            $this->braintreeClient->createBraintreeCustomerIfNotExists(
                $request['customer']['braintree_customer_id'],
                $request['customer']['firstname'],
                $request['customer']['lastname'],
                $request['customer']['email'],
                $request['customer']['telephone'],
                $request['customer']['fax'],
                $request['customer']['company']
            );

            $paymentTokenId = $request['payment_token_id'];
            $braintreeCustomerId = $request['customer']['braintree_customer_id'];
            unset($request['customer'], $request['payment_token_id']);
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
}
