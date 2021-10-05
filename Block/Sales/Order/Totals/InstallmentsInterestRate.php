<?php

declare(strict_types=1);

namespace Paypal\BraintreeBrasil\Block\Sales\Order\Totals;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Api\Data\StoreInterface;

class InstallmentsInterestRate extends Template
{
    protected $_order;
    protected $_source;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    public function __construct(
        Template\Context $context,
        DataObjectFactory $dataObjectFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->dataObjectFactory = $dataObjectFactory;
    }

    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->_order = $parent->getOrder();
        $this->_source = $parent->getSource();

        $totalData = $this->dataObjectFactory->create();
        $totalData->setData([
            'code' => $this->getNameInLayout(),
            'block_name' => $this->getNameInLayout(),
            'area' => $this->getArea(),
            'value' => $this->getInstallmentInterestValue()
        ]);

        $parent->addTotal($totalData, 'installments_interest_rate');

        return $this;
    }

    /**
     * Get order store object
     *
     * @return StoreInterface
     */
    public function getStore()
    {
        return $this->_order->getStore();
    }

    /**
     * @return OrderInterface
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * @return OrderInterface
     */
    public function getSource()
    {
        return $this->_source;
    }

    /**
     * @return array
     */
    public function getLabelProperties()
    {
        return $this->getParentBlock()->getLabelProperties();
    }

    /**
     * @return array
     */
    public function getValueProperties()
    {
        return $this->getParentBlock()->getValueProperties();
    }

    private function getInstallmentInterestValue()
    {
        $order = $this->getOrder();
        $installmentsValue = $order->getData('installments_interest_rate');
        $installmentsValueRefunded = $order->getData('installments_interest_rate_refunded');

        return $installmentsValue - $installmentsValueRefunded;
    }
}
