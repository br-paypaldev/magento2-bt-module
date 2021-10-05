<?php
namespace Paypal\BraintreeBrasil\Gateway\Request\PaypalWallet;

use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

class CancelDataBuilder implements BuilderInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * PaymentDataBuilder constructor.
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    public function build(array $buildSubject)
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();

        $this->logger->info('Cancel Data Builder');

        $request = [];

        $request['transaction_id'] = $payment->getLastTransId();

        return $request;
    }
}
