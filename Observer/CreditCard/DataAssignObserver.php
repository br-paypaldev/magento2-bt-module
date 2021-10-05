<?php
namespace Paypal\BraintreeBrasil\Observer\CreditCard;

use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Class DataAssignObserver
 */
class DataAssignObserver extends AbstractDataAssignObserver
{
    const PAYMENT_NONCE = 'payment_nonce';
    const USE_PAYMENT_TOKEN = 'use_payment_token';
    const SAVE_CC = 'save_cc';
    const INSTALLMENTS = 'installments';
    const CC_BIN = 'cc_bin';
    const CC_LAST = 'cc_last';
    const CC_OWNER = 'cc_owner';
    const CC_EXP_MONTH = 'cc_exp_month';
    const CC_EXP_YEAR = 'cc_exp_year';
    const CC_TYPE = 'cc_type';
    const DEVICE_DATA = 'device_data';

    /**
     * @var array
     */
    protected $additionalInformationList = [
        self::PAYMENT_NONCE,
        self::USE_PAYMENT_TOKEN,
        self::SAVE_CC,
        self::INSTALLMENTS,
        self::CC_LAST,
        self::CC_BIN,
        self::CC_OWNER,
        self::CC_EXP_MONTH,
        self::CC_EXP_YEAR,
        self::CC_TYPE,
        self::DEVICE_DATA
    ];

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $this->logger->info('CREDITCARD DATA ASSIGN');

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $additionalInformationKey,
                    $additionalData[$additionalInformationKey]
                );
            }
        }
    }
}
