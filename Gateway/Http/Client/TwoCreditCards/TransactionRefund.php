<?php

namespace Paypal\BraintreeBrasil\Gateway\Http\Client\TwoCreditCards;

use Paypal\BraintreeBrasil\Gateway\Http\Client;
use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

/**
 * Class TransactionRefund
 */
class TransactionRefund implements ClientInterface
{
    protected $statusToRefund = [
        \Braintree\Transaction::SETTLED,
        \Braintree\Transaction::SETTLING
    ];

    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var Client
     */
    private $braintreeClient;

    /**
     * PaymentRequest constructor.
     *
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

        $this->logger->info('Transaction Refund', [$request]);

        $response = [];

        try {
            foreach ($request as $card => $data) {
                $response['refund_result'][$card] = $this->refund($data);
            }

            $this->logger->info('Transaction Refund RESULT', [$response]);
        } catch (\Exception $e) {
            $this->logger->info('Transaction Refund ERROR', [$e->getMessage()]);
            $response['error'] = $e->getMessage();
        }

        return $response;
    }

    /**
     * @param array $request
     * @return \Braintree\Result\Error|\Braintree\Result\Successful
     * @throws LocalizedException
     * @throws \Braintree\Exception\NotFound
     */
    private function refund($request)
    {
        $transactionId = $request['transaction_id'];
        $amount = $request['refund_amount'];

        $braintreeTransaction = $this->braintreeClient->getBraintreeClient()->transaction()->find($transactionId);
        if (!in_array($braintreeTransaction->status, $this->statusToRefund)) {
            throw new LocalizedException(__('Transaction is not settled or settling. Refund is not available.'));
        }

        if (!$request['is_partial'] && $braintreeTransaction->refundId) {
            throw new LocalizedException(
                __("Transaction already refunded or partially refunded. Please refund offline")
            );
        }

        $refundResult = $this->braintreeClient
            ->getBraintreeClient()
            ->transaction()
            ->refund($transactionId, $request['is_partial'] ? $amount : null);

        return $refundResult;
    }
}
