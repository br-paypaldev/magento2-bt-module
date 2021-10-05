<?php
namespace Paypal\BraintreeBrasil\Gateway\Http\Client\PaypalWallet;

use Paypal\BraintreeBrasil\Gateway\Config\PaypalWallet\Config;
use Paypal\BraintreeBrasil\Gateway\Http\Client;
use Paypal\BraintreeBrasil\Logger\Logger;
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
