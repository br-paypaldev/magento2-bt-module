<?php
namespace Paypal\BraintreeBrasil\Observer\DebitCard;

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
    const SAVE_DC = 'save_dc';
    const DC_BIN = 'dc_bin';
    const DC_LAST = 'dc_last';
    const DC_OWNER = 'dc_owner';
    const DC_EXP_MONTH = 'dc_exp_month';
    const DC_EXP_YEAR = 'dc_exp_year';
    const DC_TYPE = 'dc_type';
    const DEVICE_DATA = 'device_data';

    /**
     * @var array
     */
    protected $additionalInformationList = [
        self::PAYMENT_NONCE,
        self::USE_PAYMENT_TOKEN,
        self::SAVE_DC,
        self::DC_LAST,
        self::DC_BIN,
        self::DC_OWNER,
        self::DC_EXP_MONTH,
        self::DC_EXP_YEAR,
        self::DC_TYPE,
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

        $this->logger->info('DEBITCARD DATA ASSIGN');

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
