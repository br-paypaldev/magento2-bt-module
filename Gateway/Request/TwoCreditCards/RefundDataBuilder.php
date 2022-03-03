<?php

namespace Paypal\BraintreeBrasil\Gateway\Request\TwoCreditCards;

use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Framework\Model\Context;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;

class RefundDataBuilder implements BuilderInterface
{
    /**
     * @var Repository
     */
    private $transactionRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Repository $transactionRepository
     * @param Logger $logger
     */
    public function __construct(
        Repository $transactionRepository,
        Logger $logger
    ) {
        $this->logger = $logger;
        $this->transactionRepository = $transactionRepository;
    }

    public function build(array $buildSubject)
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $order = $payment->getOrder();
        $amount = (float)SubjectReader::readAmount($buildSubject);

        $this->logger->info('Refund Data Builder');

        $card1Additional = $payment->getAdditionalInformation('card_1');
        if (isset($card1Additional['refund'])) {
            foreach ($card1Additional['refund'] as $refund) {
                $card1Additional['can_refund_amount'] -= $refund['amount'];
            }
        }
        $card2Additional = $payment->getAdditionalInformation('card_2');
        if (isset($card2Additional['refund'])) {
            foreach ($card2Additional['refund'] as $refund) {
                $card2Additional['can_refund_amount'] -= $refund['amount'];
            }
        }

        $request = [];

        if ($card1Additional['can_refund_amount'] > 0) {
            $request['card_1'] = [
                'transaction_id' => $card1Additional['transaction_id'],
                'amount' => $card1Additional['can_refund_amount'],
                'refund_amount' => $card1Additional['can_refund_amount'],
                'is_partial' => isset($card1Additional['refund'])
            ];
        }

        if ($card2Additional['can_refund_amount'] > 0) {
            $request['card_2'] = [
                'transaction_id' => $card2Additional['transaction_id'],
                'amount' => $card2Additional['can_refund_amount'],
                'refund_amount' => $card2Additional['can_refund_amount'],
                'is_partial' => isset($card2Additional['refund'])
            ];
        }

        if ($amount < $order->getGrandTotal()) {
            $request = $this->calculateRefund($amount, $request);
        }

        return $request;
    }

    /**
     * @param float $refundAmount
     * @param array $transactionsAmounts
     * @return mixed
     */
    private function calculateRefund($refundAmount, $transactionsAmounts)
    {
        foreach ($transactionsAmounts as $method => &$data) {
            if ($refundAmount <= 0.0) {
                unset($transactionsAmounts[$method]);
                break;
            }
            if ($refundAmount >= $data['amount']) {
                $refundAmount -= $data['amount'];
            } else {
                $data['is_partial'] = true;
                $data['refund_amount'] = $refundAmount;
                $refundAmount = 0.0;
            }
        }

        return $transactionsAmounts;
    }
}
