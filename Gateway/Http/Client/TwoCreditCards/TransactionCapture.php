<?php
namespace Paypal\BraintreeBrasil\Gateway\Http\Client\TwoCreditCards;

use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Paypal\BraintreeBrasil\Gateway\Http\Client;
use Paypal\BraintreeBrasil\Logger\Logger;

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
            foreach ($request as $card => $data) {
                $captureResult[$card] = $this->capture($data);
            }

            $this->logger->info('Transaction Capture RESULT', [$captureResult]);

            $response['capture_result'] = $captureResult;
            $response['amount']['card_1'] = $request['card_1']['amount'];
            $response['amount']['card_2'] = $request['card_2']['amount'];
        } catch (\Braintree\Exception\Authorization $exception) {
            $this->logger->info('Transaction Capture ERROR. Method not allowed!');
            $response['error'] = __('Action not allowed!');
        } catch (\Exception $e) {
            $this->logger->info('Transaction Capture ERROR', [$e->getMessage()]);
            $response['error'] = $e->getMessage();
        }

        return $response;
    }

    private function capture($request)
    {
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

        return $captureResult;
    }
}
