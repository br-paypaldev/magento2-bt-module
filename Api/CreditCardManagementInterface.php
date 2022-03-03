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
    public function getCreditcardInstallments($total);

    /**
     * Get available installments for current quote
     *
     * @param float $total
     * @return \Paypal\BraintreeBrasil\Api\Data\InstallmentInterface[]
     */
    public function getTwoCreditcardsInstallments($total);

    /**
     * Save selected installments for current quote
     *
     * @param int $installments
     * @param string $column
     * @return bool
     */
    public function saveSelectedInstallments($installments, $column = 'creditcard_installments');
}
