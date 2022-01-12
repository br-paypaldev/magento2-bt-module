<?php

declare(strict_types=1);

namespace Paypal\BraintreeBrasil\Observer\Totals;

use Magento\Framework\DataObject\Copy;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CopyFromQuoteToOrder implements ObserverInterface
{
    /**
     * @var Copy
     */
    private $copyService;

    /**
     * CopyFromQuoteToOrder constructor.
     * @param Copy $copyService
     */
    public function __construct(
        Copy $copyService
    ) {
        $this->copyService = $copyService;
    }

    public function execute(Observer $observer)
    {
        /* @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getData('order');

        /* @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getData('quote');

        if ($order && $quote) {
            $this->copyService->copyFieldsetToTarget('sales_convert_quote', 'to_order', $quote, $order);
        }
    }
}
