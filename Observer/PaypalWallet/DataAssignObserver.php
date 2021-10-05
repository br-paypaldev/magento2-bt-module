<?php
namespace Paypal\BraintreeBrasil\Observer\PaypalWallet;

use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Class DataAssignObserver
 */
class DataAssignObserver extends AbstractDataAssignObserver
{
    const INSTALLMENTS = 'installments';
    const BILLING_AGREEMENT_DATA = 'billing_agreement_data';
    const DEVICE_DATA = 'device_data';

    /**
     * @var array
     */
    protected $additionalInformationList = [
        self::DEVICE_DATA,
        self::BILLING_AGREEMENT_DATA,
        self::INSTALLMENTS
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

        $this->logger->info('PAYPAL WALLET DATA ASSIGN');

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
