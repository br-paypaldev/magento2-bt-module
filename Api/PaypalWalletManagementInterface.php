<?php
declare(strict_types=1);

namespace Paypal\BraintreeBrasil\Api;

interface PaypalWalletManagementInterface
{
    /**
     * Save a authorized Paypal payment method to Braintree Customer Account and returns payment token
     *
     * @param string $payment_method_nonce
     * @return bool
     */
    public function savePaypalPaymentMethod($payment_method_nonce);

    /**
     * Save selected instalments value on checkout
     *
     * @param int $installments
     * @return bool
     */
    public function saveSelectedInstallments($installments);

    /**
     * Query for Paypal Account installments
     *
     * @param float $total
     * @return \Paypal\BraintreeBrasil\Api\Data\InstallmentInterface[]
     */
    public function getAvailableInstallments($total);
}
