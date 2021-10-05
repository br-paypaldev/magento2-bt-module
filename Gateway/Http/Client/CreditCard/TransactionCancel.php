<?php
namespace Paypal\BraintreeBrasil\Gateway\Http\Client\CreditCard;

use Paypal\BraintreeBrasil\Gateway\Config\CreditCard\Config;
use Paypal\BraintreeBrasil\Gateway\Http\Client;
use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Model\Config\Source\PaymentAction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class TransactionCancel
 */
class TransactionCancel implements ClientInterface
{

    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var Client
     */
    private $braintreeClient;

    /**
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param Client $braintreeClient
     */
    public function __construct(
        Logger $logger,
        StoreManagerInterface $storeManager,
        Client $braintreeClient
    ) {
        $this->logger = $logger;
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

        $this->logger->info('Transaction Cancel', [$request]);

        $response = [];

        try {
            $transactionId = $request['transaction_id'];

            $braintreeTransaction = $this->braintreeClient->getBraintreeClient()->transaction()->find(
                $transactionId
            );

            if ($braintreeTransaction->__get('status') === \Braintree\Transaction::VOIDED) {
                $cancelResult = new \stdClass();
                $cancelResult->success = true;
            } else {
                $cancelResult = $this->braintreeClient->getBraintreeClient()->transaction()->void($transactionId);
            }


            $this->logger->info('Transaction Cancel RESULT', [$cancelResult]);

            $response['cancel_result'] = $cancelResult;
        } catch (\Exception $e) {
            $this->logger->info('Transaction Cancel ERROR', [$e->getMessage()]);
            $response['error'] = $e->getMessage();
        }

        return $response;
    }
}
