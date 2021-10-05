<?php
namespace Paypal\BraintreeBrasil\Gateway\Response\CreditCard;

use Paypal\BraintreeBrasil\Gateway\Config\CreditCard\Config;
use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Model\CreditCardManagement;
use Paypal\BraintreeBrasil\Observer\CreditCard\DataAssignObserver;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;

class CaptureHandler implements HandlerInterface
{
    protected $logger;

    protected $settleStatus = [
        \Braintree\Transaction::SUBMITTED_FOR_SETTLEMENT,
        \Braintree\Transaction::SETTLED,
        \Braintree\Transaction::SETTLING,
    ];

    /**
     * AuthorizationHandler constructor.
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $payment = SubjectReader::readPayment($handlingSubject);
        $payment = $payment->getPayment();

        $this->logger->info('CAPTURE HANDLER', [$response]);

        $captureResult = $response['capture_result'];

        if (!$captureResult->success) {
            $transaction = $captureResult->__get('transaction');
            $status = $transaction->__get('status');
            //transaction already submitted
            //close magento payment
            if (in_array($status, $this->settleStatus)) {
                $this->logger->info("Transaction already submitted!");
                $payment->setIsTransactionPending(false);
                $payment->setIsTransactionClosed(true);
                $payment->setShouldCloseParentTransaction(true);
            }
        } else {
            $shouldCloseTransaction = false;
            if (($response['amount'] + $payment->getAmountPaid()) == $payment->getAmountOrdered()) {
                $shouldCloseTransaction = true;
            }

            try {
                if ($shouldCloseTransaction && $captureResult->success) {
                    $payment->setIsTransactionPending(false);
                    $payment->setIsTransactionClosed(true);
                    $payment->setShouldCloseParentTransaction(true);
                }
            } catch (\Exception $e) {
                $this->logger->info('CAPTURE HANDLER ERROR', [$e->getMessage()]);
            }
        }
    }
}
