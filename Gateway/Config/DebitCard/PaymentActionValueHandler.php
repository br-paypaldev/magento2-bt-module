<?php
namespace Paypal\BraintreeBrasil\Gateway\Config\DebitCard;

use Magento\Payment\Gateway\Config\ValueHandlerInterface;

class PaymentActionValueHandler implements ValueHandlerInterface
{
    public function handle(array $subject, $storeId = null)
    {
        return 'authorize_capture';
    }
}
