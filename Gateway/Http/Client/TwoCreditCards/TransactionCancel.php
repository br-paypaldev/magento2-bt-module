<?php
namespace Paypal\BraintreeBrasil\Gateway\Http\Client\TwoCreditCards;

use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Paypal\BraintreeBrasil\Gateway\Http\Client;
use Paypal\BraintreeBrasil\Logger\Logger;

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

        $this->logger->info('Transaction Cancel', [$request]);

        $response = [];

        try {
            $response['cancel_result']['card_1'] = $this->cancel($request['card_1']['transaction_id']);
            $response['cancel_result']['card_2'] = $this->cancel($request['card_2']['transaction_id']);
            $this->logger->info('Transaction Cancel RESULT', [$response]);
        } catch (\Exception $e) {
            $this->logger->info('Transaction Cancel ERROR', [$e->getMessage()]);
            $response['error'] = $e->getMessage();
        }

        return $response;
    }

    private function cancel($transactionId)
    {
        $braintreeTransaction = $this->braintreeClient->getBraintreeClient()->transaction()->find(
            $transactionId
        );

        if ($braintreeTransaction->__get('status') === \Braintree\Transaction::VOIDED) {
            $cancelResult = new \stdClass();
            $cancelResult->success = true;
        } else {
            $cancelResult = $this->braintreeClient->getBraintreeClient()->transaction()->void($transactionId);
        }

        return $cancelResult;
    }
}
