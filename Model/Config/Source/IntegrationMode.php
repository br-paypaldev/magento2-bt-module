<?php
declare(strict_types=1);

namespace Paypal\BraintreeBrasil\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class IntegrationMode implements OptionSourceInterface
{
    const MODE_SANDBOX = "sandbox";
    const MODE_PRODUCTION = "production";

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::MODE_SANDBOX, 'label' => __('Tests')],
            ['value' => self::MODE_PRODUCTION, 'label' => __('Production')]
        ];
    }
}
