<?php

namespace Paypal\BraintreeBrasil\Gateway\Response\GooglePay;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Paypal\BraintreeBrasil\Logger\Logger;

class CancelHandler implements HandlerInterface
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

        $this->logger->info('CANCEL HANDLER', [$response]);

        $cancelResult = $response['cancel_result'];

        try {
            if ($cancelResult->success) {
                $payment->setIsTransactionPending(false);
                $payment->setIsTransactionClosed(true);
                $payment->setShouldCloseParentTransaction(true);
            }
        } catch (\Exception $e) {
            $this->logger->info('CANCEL HANDLER ERROR', [$e->getMessage()]);
        }
    }
}
