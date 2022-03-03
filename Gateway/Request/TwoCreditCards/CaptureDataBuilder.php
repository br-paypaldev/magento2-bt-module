<?php

namespace Paypal\BraintreeBrasil\Gateway\Request\TwoCreditCards;

use Magento\Framework\Model\Context;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Observer\TwoCreditCards\DataAssignObserver;

class CaptureDataBuilder implements BuilderInterface
{
    private $logger;

    /**
     * CaptureDataBuilder constructor.
     *
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    public function build(array $buildSubject)
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $order = $payment->getOrder();
        $amount = SubjectReader::readAmount($buildSubject);
        $card1Additional = $payment->getAdditionalInformation('card_1');
        $card2Additional = $payment->getAdditionalInformation('card_2');

        $card1Amount = $card1Additional['can_capture_amount'];
        $card2Amount = $card2Additional['can_capture_amount'];

        $this->logger->info('Capture Data Builder');

        $request = [];

        $request['card_1'] = [
            'transaction_id' => $card1Additional['transaction_id'],
            'amount' => bcadd($card1Amount, $amount * -1) > 0 ? $amount : $card1Amount,
            'is_partial' => bcadd($card1Amount, $amount * -1) > 0,
            'order_increment_id' => $order->getIncrementId(),
        ];
        $amount -= $card1Amount;

        $request['card_2'] = [
            'transaction_id' => $card2Additional['transaction_id'],
            'amount' => bcadd($card2Amount, $amount * -1) > 0 ? $amount : $card2Amount,
            'is_partial' => bcadd($card2Amount, $amount * -1) > 0,
            'order_increment_id' => $order->getIncrementId()
        ];

        return $request;
    }
}
