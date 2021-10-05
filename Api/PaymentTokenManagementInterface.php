<?php
namespace Paypal\BraintreeBrasil\Api;

interface PaymentTokenManagementInterface
{
    /**
     * Get available payment tokens for current customer
     *
     * @param string $type
     * @return \Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterface[]
     */
    public function getAvailablePaymentTokens($type);
}
