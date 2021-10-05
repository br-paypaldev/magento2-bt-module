<?php

namespace Paypal\BraintreeBrasil\Gateway\Request\DebitCard;

use Paypal\BraintreeBrasil\Gateway\Config\DebitCard\Config;
use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Model\PaymentTokenRepository;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Paypal\BraintreeBrasil\Observer\DebitCard\DataAssignObserver;

class PaymentDataBuilder implements BuilderInterface
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var PaymentTokenRepository
     */
    private $paymentTokenRepository;
    /**
     * @var Session
     */
    private $customerSession;
    /**
     * @var Config
     */
    private $debitCardConfig;

    /**
     * @param Session $customerSession
     * @param PaymentTokenRepository $paymentTokenRepository
     * @param Context $context
     * @param Logger $logger
     * @param Config $debitCardConfig
     */
    public function __construct(
        Session $customerSession,
        PaymentTokenRepository $paymentTokenRepository,
        Context $context,
        Logger $logger,
        Config $debitCardConfig
    ) {
        $this->logger = $logger;
        $this->appState = $context->getAppState();
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->customerSession = $customerSession;
        $this->debitCardConfig = $debitCardConfig;
    }

    public function build(array $buildSubject)
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $additionalData = $payment->getAdditionalInformation();

        $paymentMethodNonce = $additionalData[DataAssignObserver::PAYMENT_NONCE];
        $device_data = $additionalData[DataAssignObserver::DEVICE_DATA];
        $saveDc = $additionalData[DataAssignObserver::SAVE_DC];

        $usePaymentToken = $additionalData[DataAssignObserver::USE_PAYMENT_TOKEN];
        $paymentToken = null;

        $this->logger->info('Payment Data Builder');

        if ($usePaymentToken) {
            $paymentToken = $this->paymentTokenRepository->get($usePaymentToken);
            if ($paymentToken->getCustomerId() != $this->customerSession->getCustomerId()) {
                throw new LocalizedException(__('Invalid card'));
            }
        }

        $request = [
            'payment_token_id' => $paymentToken ? $paymentToken->getEntityId() : null,
            'deviceData' => $device_data,
            'options' => [
                'submitForSettlement' => true,
                'creditCard' => [
                    'accountType' => 'debit'
                ]
            ]
        ];

        // payment token or payment method nonce
        if ($usePaymentToken) {
            $request['paymentMethodToken'] = $paymentToken ? $paymentToken->getToken() : null;
        } else {
            $request['paymentMethodNonce'] = $paymentMethodNonce;
        }

        if ($this->debitCardConfig->getMerchantAccountId()) {
            $request['merchantAccountId'] = $this->debitCardConfig->getMerchantAccountId();
        }

        // save card
        if ($saveDc && !$usePaymentToken) {
            $request['options']['storeInVaultOnSuccess'] = true;
        }

        return $request;
    }
}
