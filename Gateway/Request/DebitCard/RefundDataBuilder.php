<?php

namespace Paypal\BraintreeBrasil\Gateway\Request\DebitCard;

use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Framework\Model\Context;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;

class RefundDataBuilder implements BuilderInterface
{
    private $logger;
    /**
     * @var Repository
     */
    private $transactionRepository;

    /**
     * PaymentDataBuilder constructor.
     * @param Context $context
     * @param Logger $logger
     */
    public function __construct(
        Repository $transactionRepository,
        Logger $logger
    ) {
        $this->logger = $logger;
        $this->transactionRepository = $transactionRepository;
    }

    public function build(array $buildSubject)
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $amount = SubjectReader::readAmount($buildSubject);

        $this->logger->info('Refund Data Builder');

        $request = [];

        $transactionAuthorization = $this->transactionRepository->getByTransactionType(
            TransactionInterface::TYPE_CAPTURE,
            $payment->getId()
        );

        $request['transaction_id'] = $transactionAuthorization->getTxnId();
        $request['amount'] = $amount;
        $request['partial_refund'] = $payment->getBaseAmountRefundedOnline();

        return $request;
    }
}
