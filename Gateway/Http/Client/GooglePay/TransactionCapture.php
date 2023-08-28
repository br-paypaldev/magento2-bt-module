<?php
namespace Paypal\BraintreeBrasil\Gateway\Http\Client\GooglePay;

use Paypal\BraintreeBrasil\Gateway\Config\GooglePay\Config;
use Paypal\BraintreeBrasil\Gateway\Http\Client;
use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Framework\Model\Context;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class TransactionCapture
 */
class TransactionCapture implements ClientInterface
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
     */
    public function __construct(
        Logger $logger,
        Client $braintreeClient
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

        $this->logger->info('Transaction Capture', [$request]);

        $response = [];

        try {
            $transactionId = $request['transaction_id'];

            $amount = $request['amount'];
            $isPartial = $request['is_partial'];

            if ($isPartial) {
                $captureResult = $this->braintreeClient->getBraintreeClient()->transaction(
                )->submitForPartialSettlement($transactionId, $amount, [
                    'orderId' => $request['order_increment_id']
                ]);
            } else {
                $captureResult = $this->braintreeClient->getBraintreeClient()->transaction()->submitForSettlement(
                    $transactionId
                );
            }

            $this->logger->info('Transaction Capture RESULT', [$captureResult]);

            $response['capture_result'] = $captureResult;
            $response['amount'] = $amount;
        } catch (\Braintree\Exception\Authorization $exception) {
            $this->logger->info('Transaction Capture ERROR. Method not allowed!');
            $response['error'] = __('Action not allowed!');
        } catch (\Exception $e) {
            $this->logger->info('Transaction Capture ERROR', [$e->getMessage()]);
            $response['error'] = $e->getMessage();
        }

        return $response;
    }
}
