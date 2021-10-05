<?php

namespace Paypal\BraintreeBrasil\Plugin\Sales\Order;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\RefundOperation;
use Magento\Sales\Model\Order\Payment;

class RefundPlugin
{

    public function beforeExecute(
        RefundOperation $subject,
        CreditmemoInterface $creditmemo,
        OrderInterface $order,
        $online = false
    ) {
        if ($creditmemo->getState() == Creditmemo::STATE_REFUNDED
            && $creditmemo->getOrderId() == $order->getEntityId()
            && $this->isBraintreeMethod($order->getPayment())
        ) {
            $order->setInstallmentsInterestRateRefunded(
                $order->getInstallmentsInterestRateRefunded() + $creditmemo->getInstallmentsInterestRate()
            );
        }

        return [$creditmemo, $order, $online];
    }

    /**
     * @param Payment $payment
     */
    private function isBraintreeMethod($payment)
    {
        $methods = [
            'paypal_braintree_brasil_creditcard'
        ];

        return in_array($payment->getMethod(), $methods);
    }

    public function afterCreateByInvoice()
    {
    }

}
