<?php
namespace Paypal\BraintreeBrasil\Gateway\Request\GooglePay;

use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Framework\Model\Context;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

class CancelDataBuilder implements BuilderInterface
{
    private $logger;
    private $appState;

    /**
     * PaymentDataBuilder constructor.
     * @param Context $context
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        Logger $logger
    ) {
        $this->logger = $logger;
        $this->appState = $context->getAppState();
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
