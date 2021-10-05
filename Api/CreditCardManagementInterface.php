<?php
namespace Paypal\BraintreeBrasil\Api;

interface CreditCardManagementInterface
{
    /**
     * Get available installments for current quote
     *
     * @param float $total
     * @return \Paypal\BraintreeBrasil\Api\Data\InstallmentInterface[]
     */
    public function getAvailableInstallments($total);

    /**
     * Save selected installments for current quote
     *
     * @param int $installments
     * @return bool
     */
    public function saveSelectedInstallments($installments);
}
