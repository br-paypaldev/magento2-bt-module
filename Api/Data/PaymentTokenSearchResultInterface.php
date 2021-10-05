<?php
namespace Paypal\BraintreeBrasil\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface PaymentTokenSearchResultInterface extends SearchResultsInterface
{

    /**
     * Get token result list
     * @return \Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterface[]
     */
    public function getItems();

    /**
     * Set token result list
     * @param \Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
