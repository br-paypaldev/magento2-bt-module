<?php

namespace Paypal\BraintreeBrasil\Gateway\Response\TwoCreditCards;

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
     * CancelHandler constructor.
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

        $payment->setIsTransactionPending(false);
        $payment->setIsTransactionClosed(true);
        $payment->setShouldCloseParentTransaction(true);
    }
}
