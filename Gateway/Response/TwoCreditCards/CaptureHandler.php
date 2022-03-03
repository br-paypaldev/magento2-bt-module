<?php

namespace Paypal\BraintreeBrasil\Gateway\Response\TwoCreditCards;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Paypal\BraintreeBrasil\Logger\Logger;

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

        $card1CaptureResult = $response['capture_result']['card_1'];
        $card2CaptureResult = $response['capture_result']['card_2'];
        $card1CloseTransaction = false;
        $card2CloseTransaction = false;
        $card1Amount = $response['amount']['card_1'];
        $card2Amount = $response['amount']['card_2'];

        if (!$card1CaptureResult->success) {
            $card1CloseTransaction = $this->checkUnsuccessStatus($card1CaptureResult);
        }

        if (!$card2CaptureResult->success) {
            $card2CloseTransaction = $this->checkUnsuccessStatus($card2CaptureResult);
        }

        if ($card1CloseTransaction && $card2CloseTransaction) {
            $this->logger->info("Transaction already submitted!");
            $payment->setIsTransactionPending(false);
            $payment->setIsTransactionClosed(true);
            $payment->setShouldCloseParentTransaction(true);
        } else {
            if ($card1Amount + $card2Amount + $payment->getAmountPaid() == $payment->getAmountOrdered()) {
                $payment->setIsTransactionPending(false);
                $payment->setIsTransactionClosed(true);
                $payment->setShouldCloseParentTransaction(true);
            }
        }

        $card1Additional = $payment->getAdditionalInformation('card_1');
        $card1Additional['can_capture_amount'] -= $card1Amount;
        $payment->setAdditionalInformation('card_1', $card1Additional);
        $card2Additional = $payment->getAdditionalInformation('card_2');
        $card2Additional['can_capture_amount'] -= $card2Amount;
        $payment->setAdditionalInformation('card_2', $card2Additional);
    }

    private function checkUnsuccessStatus($captureResult)
    {
        $transaction = $captureResult->__get('transaction');
        $status = $transaction->__get('status');
        //transaction already submitted
        //close magento payment
        return in_array($status, $this->settleStatus);
    }
}
