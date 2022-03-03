<?php

namespace Paypal\BraintreeBrasil\Gateway\Request\TwoCreditCards;

use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Paypal\BraintreeBrasil\Gateway\Config\TwoCreditCards\Config;
use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Model\Config\Source\PaymentAction;
use Paypal\BraintreeBrasil\Model\PaymentTokenRepository;
use Paypal\BraintreeBrasil\Observer\CreditCard\DataAssignObserver;

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
    private $twoCreditCardsConfig;

    /**
     * @param Session $customerSession
     * @param PaymentTokenRepository $paymentTokenRepository
     * @param Logger $logger
     * @param Config $creditCardConfig
     */
    public function __construct(
        Session $customerSession,
        PaymentTokenRepository $paymentTokenRepository,
        Logger $logger,
        Config $twoCreditCardsConfig
    ) {
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->twoCreditCardsConfig = $twoCreditCardsConfig;
    }

    public function build(array $buildSubject)
    {
        $this->logger->info('Payment Data Builder');

        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $additionalData = $payment->getAdditionalInformation();

        $request['card_1'] = $this->buildPaymentData($additionalData['card_1']);
        $request['card_2'] = $this->buildPaymentData($additionalData['card_2']);

        return $request;
    }

    private function buildPaymentData($additionalData)
    {
        $paymentMethodNonce = $additionalData[DataAssignObserver::PAYMENT_NONCE];
        $device_data = $additionalData[DataAssignObserver::DEVICE_DATA];
        $installments = (int)$additionalData[DataAssignObserver::INSTALLMENTS];
        $saveCc = $additionalData[DataAssignObserver::SAVE_CC];
        $usePaymentToken = $additionalData[DataAssignObserver::USE_PAYMENT_TOKEN];
        $paymentToken = null;

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
                'submitForSettlement' => false
            ]
        ];

        // payment token or payment method nonce
        if ($usePaymentToken) {
            $request['paymentMethodToken'] = $paymentToken ? $paymentToken->getToken() : null;
        } else {
            $request['paymentMethodNonce'] = $paymentMethodNonce;
        }

        if ($this->twoCreditCardsConfig->getMerchantAccountId()) {
            $request['merchantAccountId'] = $this->twoCreditCardsConfig->getMerchantAccountId();
        }

        // installments
        if ($this->twoCreditCardsConfig->getEnableInstallments()
            && $installments > 1) {
            $request['installments'] = [
                'count' => $installments
            ];
        }

        // save card
        if ($saveCc && !$usePaymentToken) {
            $request['options']['storeInVaultOnSuccess'] = true;
        }

        return $request;
    }
}
