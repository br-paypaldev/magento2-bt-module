<?php

namespace Paypal\BraintreeBrasil\Gateway\Request\PaypalWallet;

use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Paypal\BraintreeBrasil\Observer\PaypalWallet\DataAssignObserver;

class PaymentWithInstallmentsDataBuilder implements BuilderInterface
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
     * @param Session $checkoutSession
     * @param Logger $logger
     */
    public function __construct(
        Session $checkoutSession,
        Logger $logger
    ) {
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
    }

    public function build(array $buildSubject)
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $additionalData = $payment->getAdditionalInformation();

        if (!$this->checkoutSession->getPaypalWalletPaymentMethod()) {
            throw new LocalizedException(__('Invalid PayPal account'));
        }

        $installments = (int)$additionalData[DataAssignObserver::INSTALLMENTS];
        $paymentMethodGraphQlId = $this->checkoutSession->getPaypalWalletPaymentMethod()->globalId;
        $financingOption = $this->checkoutSession->getPaypalWalletFinancingOption();

        $this->logger->info('Payment with installments Data Builder');

        $request = [];

        $request['with_installments'] = [
            'payment_method_graphql_id' => $paymentMethodGraphQlId,
            'installments' => $installments,
            'financing_option_monthly_payment' => $financingOption->monthlyPayment->value
        ];

        return $request;
    }
}
