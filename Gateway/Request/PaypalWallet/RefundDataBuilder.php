<?php

namespace Paypal\BraintreeBrasil\Gateway\Request\PaypalWallet;

use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;

class RefundDataBuilder implements BuilderInterface
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Repository
     */
    private $transactionRepository;

    /**
     * @param Repository $transactionRepository
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

        // authorized only
        $transactionAuthorization = $this->transactionRepository->getByTransactionType(
            TransactionInterface::TYPE_AUTH,
            $payment->getId()
        );

        // captured automatically
        if (!$transactionAuthorization) {
            $transactionAuthorization = $this->transactionRepository->getByTransactionType(
                TransactionInterface::TYPE_CAPTURE,
                $payment->getId()
            );
        }

        $request['transaction_id'] = $transactionAuthorization->getTxnId();
        $request['amount'] = $amount;
        $request['partial_refund'] = $payment->getBaseAmountRefundedOnline();

        return $request;
    }
}
