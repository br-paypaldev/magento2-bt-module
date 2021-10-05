<?php

namespace Paypal\BraintreeBrasil\Gateway\Http\Client\PaypalWallet;

use Braintree\Transaction;
use Paypal\BraintreeBrasil\Gateway\Config\PaypalWallet\Config;
use Paypal\BraintreeBrasil\Gateway\Http\Client;
use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class TransactionRefund
 */
class TransactionRefund implements ClientInterface
{

    protected $helper;
    protected $logger;
    protected $_appState;
    protected $_storeManager;
    /**
     * @var Client
     */
    private $braintreeClient;
    /**
     * @var Config
     */
    private $paypalWalletConfig;

    /**
     * PaymentRequest constructor.
     *
     * @param Context $context
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param Config $paypalWalletConfig
     * @param Client $braintreeClient
     * @param array $data
     */
    public function __construct(
        Context $context,
        Logger $logger,
        StoreManagerInterface $storeManager,
        Config $paypalWalletConfig,
        Client $braintreeClient,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->_appState = $context->getAppState();
        $this->_storeManager = $storeManager;
        $this->braintreeClient = $braintreeClient;
        $this->paypalWalletConfig = $paypalWalletConfig;
    }

    /**
     * @param TransferInterface $transferObject
     * @return array
     * @throws ClientException
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $request = $transferObject->getBody();

        $this->logger->info('Transaction Refund', [$request]);

        $statusToRefund = [
            \Braintree\Transaction::SETTLED,
            \Braintree\Transaction::SETTLING
        ];

        $response = [];

        try {
            $transactionId = $request['transaction_id'];
            $amount = $request['amount'];


            $braintreeTransaction = $this->braintreeClient->getBraintreeClient()->transaction()->find(
                $transactionId
            );
            if (!in_array($braintreeTransaction->status, $statusToRefund)) {
                throw new LocalizedException(
                    __('Transaction is not settled or settling. Refund is not available.')
                );
            }

            if (!$request['partial_refund'] && $braintreeTransaction->refundId) {
                throw new LocalizedException(
                    __("Transaction already refunded or partially refunded. Please refund offline")
                );
            }

            $refundResult = $this->braintreeClient->getBraintreeClient()->transaction()->refund(
                $transactionId,
                $amount
            );

            $this->logger->info('Transaction Refund RESULT', [$refundResult]);

            $response['refund_result'] = $refundResult;
        } catch (\Braintree\Exception\NotFound $exception) {
            $this->logger->info('Transaction Refund ERROR', [$exception->getMessage()]);
            $response['error'] = __("Transaction %1 not found!", $transactionId);
        } catch (\Exception $e) {
            $this->logger->info('Transaction Refund ERROR', [$e->getMessage()]);
            $response['error'] = $e->getMessage();
        }

        return $response;
    }
}
