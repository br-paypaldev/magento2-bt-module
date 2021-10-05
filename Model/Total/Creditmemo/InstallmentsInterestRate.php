<?php

declare(strict_types=1);

namespace Paypal\BraintreeBrasil\Model\Total\Creditmemo;

use Magento\Directory\Model\PriceCurrency;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

class InstallmentsInterestRate extends AbstractTotal
{
    /**
     * @var PriceCurrency
     */
    private $priceCurrency;

    /**
     * @param PriceCurrency $priceCurrency
     * @param array $data
     */
    public function __construct(
        PriceCurrency $priceCurrency,
        array $data = []
    ) {
        $this->priceCurrency = $priceCurrency;
        parent::__construct($data);
    }

    /**
     * @param Creditmemo $creditmemo
     * @return $this
     */
    public function collect(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();

        // amounts without tax
        $orderInterestRate = $order->getInstallmentsInterestRate();
        $allowedAmount = $orderInterestRate - $order->getInstallmentsInterestRateRefunded();

        if ($creditmemo->hasInstallmentsInterestRate()) {
            $desiredAmount = $this->priceCurrency->roundPrice($creditmemo->getInstallmentsInterestRate());
            if ($desiredAmount > $this->priceCurrency->roundPrice($allowedAmount)) {
                $allowedAmount = $order->getBaseCurrency()->format($allowedAmount, null, false);
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Maximum installment interest rate amount allowed to refund is: %1', $allowedAmount)
                );
            } else {
                $allowedAmount = $desiredAmount;
            }
        }

        $creditmemo->setInstallmentsInterestRate($allowedAmount);

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $allowedAmount);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $allowedAmount);

        return $this;
    }
}
