<?php
namespace Paypal\BraintreeBrasil\Gateway\Request\CreditCard;

use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Framework\Model\Context;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;

class CaptureDataBuilder implements BuilderInterface
{
    private $logger;
    private $appState;
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
        Context $context,
        Repository $transactionRepository,
        Logger $logger
    ) {
        $this->logger = $logger;
        $this->appState = $context->getAppState();
        $this->transactionRepository = $transactionRepository;
    }

    public function build(array $buildSubject)
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $order = $payment->getOrder();
        $amount =  SubjectReader::readAmount($buildSubject);

        $this->logger->info('Capture Data Builder');

        $request = [];

        $transactionAuthorization = $this->transactionRepository->getByTransactionType(
            TransactionInterface::TYPE_AUTH,
            $payment->getId()
        );

        $request['transaction_id'] = $transactionAuthorization->getTxnId();
        $request['amount'] = $amount;
        $request['is_partial'] = $amount < $order->getGrandTotal();
        $request['order_increment_id'] = $order->getIncrementId();

        return $request;
    }
}
