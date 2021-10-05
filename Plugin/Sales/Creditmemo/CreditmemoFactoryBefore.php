<?php

namespace Paypal\BraintreeBrasil\Plugin\Sales\Creditmemo;

use Paypal\BraintreeBrasil\Helper\CreditmemoData;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Invoice;

class CreditmemoFactoryBefore
{
    /**
     * @var CreditmemoData
     */
    private $creditmemoData;

    /**
     * @param CreditmemoData $creditmemoData
     */
    public function __construct(
        CreditmemoData $creditmemoData
    ) {
        $this->creditmemoData = $creditmemoData;
    }

    public function beforeCreateByInvoice(CreditmemoFactory $subject, Invoice $invoice, array $data = [])
    {
        $this->creditmemoData->setCreditmemoData($data);
        return [$invoice, $data];
    }
}
