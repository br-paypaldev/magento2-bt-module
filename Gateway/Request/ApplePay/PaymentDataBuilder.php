<?php

namespace Paypal\BraintreeBrasil\Gateway\Request\ApplePay;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Customer\Model\Session;
use Paypal\BraintreeBrasil\Model\PaymentTokenRepository;
use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Gateway\Config\ApplePay\Config;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Paypal\BraintreeBrasil\Model\Config\Source\PaymentAction;
use Paypal\BraintreeBrasil\Observer\ApplePay\DataAssignObserver;

class PaymentDataBuilder implements BuilderInterface
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var PaymentTokenRepository
     */
    private $paymentTokenRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Config
     */
    private $applePayConfig;

    /**
     * @param Session $customerSession
     * @param PaymentTokenRepository $paymentTokenRepository
     * @param Logger $logger
     * @param Config $applePayConfig
     */
    public function __construct(
        Session $customerSession,
        PaymentTokenRepository $paymentTokenRepository,
        Logger $logger,
        Config $applePayConfig
    ) {
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->applePayConfig = $applePayConfig;
    }

    public function build(array $buildSubject)
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $additionalData = $payment->getAdditionalInformation();

        $paymentMethodNonce = $additionalData[DataAssignObserver::PAYMENT_NONCE];
        $device_data = $additionalData[DataAssignObserver::DEVICE_DATA];

        $this->logger->info('Payment Data Builder');


        $capture = $this->applePayConfig->getPaymentAction() === PaymentAction::PAYMENT_ACTION_AUTHORIZE_CAPTURE;

        $request = [
            'paymentMethodNonce' => $paymentMethodNonce,
            'deviceData' => $device_data,
            'options' => [
                'submitForSettlement' => $capture
            ]
        ];

        if ($this->applePayConfig->getMerchantAccountId()) {
            $request['merchantAccountId'] = $this->applePayConfig->getMerchantAccountId();
        }

        return $request;
    }
}
