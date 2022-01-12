<?php

namespace Paypal\BraintreeBrasil\Gateway\Config\PaypalWallet;

use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Paypal\BraintreeBrasil\Gateway\Config\PaypalWallet\Config as PaypalWalletConfig;
use Paypal\BraintreeBrasil\Observer\PaypalWallet\DataAssignObserver;

class PaymentActionValueHandler implements ValueHandlerInterface
{
    /**
     * @var PaypalWalletConfig
     */
    private $paypalWalletConfig;

    /**
     * @param PaypalWalletConfig $paypalWalletConfig
     */
    public function __construct(
        PaypalWalletConfig $paypalWalletConfig
    ) {
        $this->paypalWalletConfig = $paypalWalletConfig;
    }

    public function handle(array $subject, $storeId = null)
    {
        $payment = SubjectReader::readPayment($subject);
        $additionalInformation = $payment->getPayment()->getAdditionalInformation();
        $installments = $additionalInformation[DataAssignObserver::INSTALLMENTS];

        if ((int)$installments > 1) {
            return 'authorize_capture';
        }

        return $this->paypalWalletConfig->getPaymentAction();
    }
}
