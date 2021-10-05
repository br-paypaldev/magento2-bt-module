<?php

namespace Paypal\BraintreeBrasil\Gateway\Request;

use Paypal\BraintreeBrasil\Gateway\Config\CreditCard\Config;
use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Model\Config\Source\PaymentAction;
use Paypal\BraintreeBrasil\Model\PaymentTokenRepository;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Paypal\BraintreeBrasil\Observer\CreditCard\DataAssignObserver;

class PaymentDataBuilder implements BuilderInterface
{
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var Session
     */
    protected $customerSession;
    /**
     * @var PaymentTokenRepository
     */
    protected $paymentTokenRepository;
    /**
     * @var Config
     */
    protected $creditCardConfig;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param PaymentTokenRepository $paymentTokenRepository
     * @param Logger $logger
     * @param Config $creditCardConfig
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        PaymentTokenRepository $paymentTokenRepository,
        Logger $logger,
        Config $creditCardConfig
    ) {
        $this->logger = $logger;
        $this->appState = $context->getAppState();
        $this->customerSession = $customerSession;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->creditCardConfig = $creditCardConfig;
    }

    public function build(array $buildSubject)
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $order = $payment->getOrder();
        $additionalData = $payment->getAdditionalInformation();

        $paymentMethodNonce = $additionalData[DataAssignObserver::PAYMENT_NONCE];
        $device_data = $additionalData[DataAssignObserver::DEVICE_DATA];
        $installments = (int)$additionalData[DataAssignObserver::INSTALLMENTS];
        $saveCc = $additionalData[DataAssignObserver::SAVE_CC];
        $usePaymentToken = $additionalData[DataAssignObserver::USE_PAYMENT_TOKEN];
        $paymentToken = null;

        $this->logger->info('Payment Data Builder');

        if ($usePaymentToken) {
            $paymentToken = $this->paymentTokenRepository->get($usePaymentToken);
            if ($paymentToken->getCustomerId() != $this->customerSession->getCustomerId()) {
                throw new LocalizedException(__('Invalid card'));
            }
        }

        $capture = $this->creditCardConfig->getPaymentAction() === PaymentAction::PAYMENT_ACTION_AUTHORIZE_CAPTURE;

        $request = [
            'device_data' => $device_data,
            'installments' => $installments,
            'save_cc' => $saveCc,
            'options' => [
                'submitForSettlement' => $capture
            ]
        ];

        // payment token or payment method nonce
        if ($usePaymentToken) {
            $request['paymentMethodToken'] = $paymentToken ? $paymentToken->getToken() : null;
        } else {
            $request['paymentMethodNonce'] = $paymentMethodNonce;
        }

        if ($this->creditCardConfig->getMerchantAccountId()) {
            $request['merchantAccountId'] = $this->creditCardConfig->getMerchantAccountId();
        }

        // installments
        if ($this->creditCardConfig->getEnableInstallments()
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
