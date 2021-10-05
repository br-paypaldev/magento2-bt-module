<?php

namespace Paypal\BraintreeBrasil\Gateway\Request\PaypalWallet;

use Paypal\BraintreeBrasil\Gateway\Config\PaypalWallet\Config;
use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Model\Config\Source\PaymentAction;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Paypal\BraintreeBrasil\Observer\PaypalWallet\DataAssignObserver;

class PaymentSingleDataBuilder implements BuilderInterface
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Config
     */
    private $paypalConfig;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param Logger $logger
     * @param Config $paypalConfig
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Logger $logger,
        Config $paypalConfig
    ) {
        $this->logger = $logger;
        $this->appState = $context->getAppState();
        $this->checkoutSession = $checkoutSession;
        $this->paypalConfig = $paypalConfig;
    }

    public function build(array $buildSubject)
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $additionalData = $payment->getAdditionalInformation();

        if (!$this->checkoutSession->getPaypalWalletPaymentMethod()) {
            throw new LocalizedException(__('Invalid PayPal account'));
        }

        $device_data = $additionalData[DataAssignObserver::DEVICE_DATA];
        $paymentMethodToken = $this->checkoutSession->getPaypalWalletPaymentMethod()->token;

        $this->logger->info('Payment Data Builder');

        $capture = $this->paypalConfig->getPaymentAction() === PaymentAction::PAYMENT_ACTION_AUTHORIZE_CAPTURE;

        $request['without_installments'] = [
            'deviceData' => $device_data,
            'paymentMethodToken' => $paymentMethodToken,
            'options' => [
                'submitForSettlement' => $capture
            ]
        ];

        return $request;
    }
}
