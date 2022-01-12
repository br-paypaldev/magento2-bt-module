<?php

namespace Paypal\BraintreeBrasil\Model\Config\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\OptionSourceInterface;

class MerchantAccountId implements OptionSourceInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function toOptionArray()
    {
        $ids = $this->scopeConfig->getValue('paypal_braintree_brasil/merchant_account_id/ids');
        $ids = $ids ? json_decode($ids, true) : [];
        $options[] = ['label' => __("None"), 'value' => ''];

        foreach ($ids as $id) {
            $options[] = [
                'label' => $id['merchant_account_id'],
                'value' => $id['merchant_account_id']
            ];
        }

        return $options;
    }
}
