<?php

namespace Paypal\BraintreeBrasil\Block\Sales\Order\Creditmemo;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Model\Order;
use Zend_Currency;

class InstallmentsInterestRate extends Template
{
    /**
     * Source object
     *
     * @var DataObject
     */
    protected $_source;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @param Context $context
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        Context $context,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context, $data);
    }

    /**
     * Initialize creditmemo adjustment totals
     *
     * @return $this
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->_source = $parent->getSource();
        $total = new DataObject(['code' => 'interest_rate', 'block_name' => $this->getNameInLayout()]);
        $parent->removeTotal('installments_interest_rate');
        $parent->addTotal($total);
        return $this;
    }

    /**
     * Format value based on order currency
     *
     * @param null|float $value
     *
     * @return string
     * @since 102.1.0
     */
    public function formatValue($value)
    {
        /** @var Order $order */
        $order = $this->getSource()->getOrder();

        return $order->getOrderCurrency()->formatPrecision(
            $value,
            2,
            ['display' => Zend_Currency::NO_SYMBOL],
            false,
            false
        );
    }

    /**
     * Get source object
     *
     * @return DataObject
     */
    public function getSource()
    {
        return $this->_source;
    }

    /**
     * Get credit memo shipping amount depend on configuration settings
     *
     * @return float
     */
    public function getInstallmentsInterestRateAmount()
    {
        $source = $this->getSource();
        $interestRate = $source->getInstallmentsInterestRate();
        return $this->priceCurrency->roundPrice($interestRate);
    }

    /**
     * Get label for shipping total based on configuration settings
     *
     * @return string
     */
    public function getInstallmentsInterestRateLabel()
    {
        return __('Installments Interest Rate');
    }
}
