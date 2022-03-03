<?php

namespace Paypal\BraintreeBrasil\Gateway\Response\TwoCreditCards;

use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;

class RefundHandler implements HandlerInterface
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * RefundHandler constructor.
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
        $amountRefunded = 0.0;

        $this->logger->info('REFUND HANDLER', [$response]);

        try {
            foreach ($response['refund_result'] as $card => $data) {
                $amountRefunded += (float)$data->transaction->amount;
                $refund = [
                    'id' => $data->transaction->id,
                    'amount' => (float)$data->transaction->amount
                ];
                $additional = $payment->getAdditionalInformation($card);
                $additional['can_refund_amount'] -= $refund['amount'];
                $additional['refund'][] = $refund;
                $payment->setAdditionalInformation($card, $additional);
            }
            if ($payment->getAmountRefunded() + $amountRefunded >= $payment->getAmountPaid()) {
                $payment->setIsTransactionPending(false);
                $payment->setIsTransactionClosed(true);
                $payment->setShouldCloseParentTransaction(true);
            }
        } catch (\Exception $e) {
            $this->logger->info('REFUND HANDLER ERROR', [$e->getMessage()]);
        }
    }
}
