<?php
declare(strict_types=1);

namespace Paypal\BraintreeBrasil\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class PaymentAction implements OptionSourceInterface
{
    const PAYMENT_ACTION_AUTHORIZE = "authorize";
    const PAYMENT_ACTION_AUTHORIZE_CAPTURE = "authorize_capture";

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::PAYMENT_ACTION_AUTHORIZE, 'label' => __('Authorize (capture later)')],
            ['value' => self::PAYMENT_ACTION_AUTHORIZE_CAPTURE, 'label' => __('Authorization and Capture (capture automatically)')]
        ];
    }
}
