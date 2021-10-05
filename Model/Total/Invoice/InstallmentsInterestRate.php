<?php
declare(strict_types=1);

namespace Paypal\BraintreeBrasil\Model\Total\Invoice;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

class InstallmentsInterestRate extends AbstractTotal
{
    /**
     * @param Invoice $invoice
     * @return $this
     */
    public function collect(Invoice $invoice)
    {
        $interestRate = $invoice->getOrder()->getInstallmentsInterestRate()
            - $invoice->getOrder()->getInstallmentsInterestRateRefunded();
        $invoice->setData('installments_interest_rate', $interestRate);

        $invoice->setGrandTotal($invoice->getGrandTotal() + $interestRate);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $interestRate);

        return $this;
    }
}
