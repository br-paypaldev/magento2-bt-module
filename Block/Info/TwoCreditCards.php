<?php

namespace Paypal\BraintreeBrasil\Block\Info;

use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Info;

class TwoCreditCards extends Info
{

    /**
     * @var string
     */
    protected $_template = 'Paypal_BraintreeBrasil::info/two_creditcards.phtml';

    /**
     * @var Data
     */
    private $pricingHelper;

    /**
     * @param Context $context
     * @param Data $pricingHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $pricingHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->pricingHelper = $pricingHelper;
    }


    /**
     * @param $value
     */
    public function formatCurrency($value)
    {
        return $this->pricingHelper->currency($value, true, false);
    }
}
