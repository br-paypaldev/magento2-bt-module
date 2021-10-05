<?php

namespace Paypal\BraintreeBrasil\Plugin\Sales\Creditmemo;

use Paypal\BraintreeBrasil\Helper\CreditmemoData;
use Paypal\BraintreeBrasil\Model\Total\Creditmemo\InstallmentsInterestRate;
use Magento\Framework\Locale\FormatInterface;
use Magento\Sales\Model\Order\Creditmemo;

class CreditmemoCollectTotalsBefore
{
    /**
     * @var CreditmemoData
     */
    private $creditmemoData;
    /**
     * @var FormatInterface
     */
    private $localeFormat;

    /**
     * @param CreditmemoData $creditmemoData
     * @param FormatInterface $localeFormat
     */
    public function __construct(
        CreditmemoData $creditmemoData,
        FormatInterface $localeFormat
    ) {
        $this->creditmemoData = $creditmemoData;
        $this->localeFormat = $localeFormat;
    }

    public function beforeCollect(InstallmentsInterestRate $subject, Creditmemo $creditmemo)
    {
        $data = $this->creditmemoData->getCreditmemoData();

        if (isset($data['installments_interest_rate'])) {
            $interestRate = $this->parseNumber($data['installments_interest_rate']);
            $creditmemo->setInstallmentsInterestRate($interestRate);
        } else {
            $order = $creditmemo->getOrder();
            $baseAllowedAmount = $order->getInstallmentsInterestRate() - $order->getInstallmentsInterestRateRefunded();
            $creditmemo->setInstallmentsInterestRate($baseAllowedAmount);
        }

        return [$creditmemo];
    }

    private function parseNumber($value)
    {
        return $this->localeFormat->getNumber($value);
    }
}
