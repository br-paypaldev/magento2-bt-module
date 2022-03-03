<?php

namespace Paypal\BraintreeBrasil\Gateway\Request\TwoCreditCards;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order;
use Paypal\BraintreeBrasil\Gateway\Request\StcDataBuilder as BaseStcDataBuilder;
use Paypal\BraintreeBrasil\Observer\CreditCard\DataAssignObserver;

/**
 * Class StcDataBuilder
 */
class StcDataBuilder extends BaseStcDataBuilder
{

    /**
     * Add shopper data into request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        /** @var PaymentDataObject $paymentDataObject */
        $paymentDataObject = SubjectReader::readPayment($buildSubject);

        $this->logger->info('STC Data Builder');

        $payment = $paymentDataObject->getPayment();
        /** @var Order $order */
        $order = $payment->getOrder();
        $quote = $this->checkoutSession->getQuote();
        $additionalData = $payment->getAdditionalInformation();

        $card1CorrelationId = $this->getCorrelationId($additionalData['card_1']);
        $card2CorrelationId = $this->getCorrelationId($additionalData['card_2']);

        try {

            $request = [
                'card_1' => [
                    'stc' => $this->addStcField($this->buildStcData($card1CorrelationId, $order, $quote), '0')
                ],
                'card_2' => [
                    'stc' => $this->addStcField($this->buildStcData($card2CorrelationId, $order, $quote), '2')
                ]
            ];

        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        return $request;
    }

    /**
     * @param array $additionalData
     * @return string
     */
    private function getCorrelationId($additionalData)
    {
        $deviceData = $additionalData[DataAssignObserver::DEVICE_DATA];
        $deviceData = json_decode($deviceData, true);
        return $deviceData['correlation_id'] ?? '';
    }

    /**
     * @param array $stc
     * @param string $cardNumber
     * @return array
     */
    private function addStcField($stc, $cardNumber)
    {
        $stc['additional_data'][] = ['key' => 'multi_card_txn_indicator', 'value' => $cardNumber];
        return $stc;
    }
}
