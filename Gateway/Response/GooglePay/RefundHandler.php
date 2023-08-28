<?php

namespace Paypal\BraintreeBrasil\Gateway\Response\GooglePay;

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

        $this->logger->info('REFUND HANDLER', [$response]);

        try {
            if ($payment->getAmountRefunded() + $response['amount'] >= $payment->getAmountPaid()) {
                $payment->setIsTransactionPending(false);
                $payment->setIsTransactionClosed(true);
                $payment->setShouldCloseParentTransaction(true);
            }
        } catch (\Exception $e) {
            $this->logger->info('REFUND HANDLER ERROR', [$e->getMessage()]);
        }
    }
}
