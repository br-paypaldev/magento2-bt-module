<?php
namespace Paypal\BraintreeBrasil\Observer\TwoCreditCards;

use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Paypal\BraintreeBrasil\Traits\FormatFields;

/**
 * Class DataAssignObserver
 */
class DataAssignObserver extends AbstractDataAssignObserver
{
    use FormatFields;

    const AMOUNT = 'amount';
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
        self::AMOUNT,
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

        $cardData = [
            'card_1' => [],
            'card_2' => []
        ];
        $card1 = json_decode($additionalData['card_1'] ?? '{}', true);
        $card2 = json_decode($additionalData['card_2'] ?? '{}', true);

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (array_key_exists($additionalInformationKey, $card1)) {
                if ($additionalInformationKey === self::AMOUNT) {
                    $card1[$additionalInformationKey] = $this->toFloat($card1[$additionalInformationKey]);
                }
                $cardData['card_1'][$additionalInformationKey] = $card1[$additionalInformationKey];
            }
            if (array_key_exists($additionalInformationKey, $card2)) {
                if ($additionalInformationKey === self::AMOUNT) {
                    $card2[$additionalInformationKey] = $this->toFloat($card2[$additionalInformationKey]);
                }
                $cardData['card_2'][$additionalInformationKey] = $card2[$additionalInformationKey];
            }
        }

        $paymentInfo->setAdditionalInformation($cardData);
    }
}
