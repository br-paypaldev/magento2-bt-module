<?php
namespace Paypal\BraintreeBrasil\Block\Customer;

use Paypal\BraintreeBrasil\Model\PaymentTokenManagement;
use Paypal\BraintreeBrasil\Model\ResourceModel\PaymentToken\Collection;
use Paypal\BraintreeBrasil\Model\ResourceModel\PaymentToken\CollectionFactory as PaymentTokenCollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;

class PaymentTokens extends Template
{
    /** @var Collection */
    private $_paymentTokenCollection;
    /**
     * @var PaymentTokenManagement
     */
    private $paymentTokenManagement;
    /**
     * @var Session
     */
    private $customerSession;
    /**
     * @var PaymentTokenCollectionFactory
     */
    private $paymentTokenCollectionFactory;

    /**
     * Saved constructor.
     * @param PaymentTokenManagement $paymentTokenManagement
     * @param PaymentTokenCollectionFactory $paymentTokenCollectionFactory
     * @param Session $customerSession
     * @param Context $context
     * @param array $data
     */
    public function __construct
    (
        PaymentTokenManagement $paymentTokenManagement,
        PaymentTokenCollectionFactory $paymentTokenCollectionFactory,
        Session $customerSession,
        Context $context,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->customerSession = $customerSession;
        $this->paymentTokenCollectionFactory = $paymentTokenCollectionFactory;
    }

    /**
     * Return a list of stored payment tokens
     * @return array
     */
    public function getPaymentTokens()
    {
        $customerId = $this->customerSession->getCustomerId();
        return $this->paymentTokenManagement->getCustomerPaymentTokens($customerId);
    }

    /**
     * @param $type
     * @return string
     */
    public function getTypeLabel($type)
    {
        if($type == 'debitcard'){
            return __('Debit card');
        }

        if($type == 'creditcard'){
            return __('Credit card');
        }

        return $type;
    }

    /**
     * @return $this|PaymentTokens
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if ($this->getPaymentTokenCollection()) {
            $pager = $this->getLayout()->createBlock(
                'Magento\Theme\Block\Html\Pager',
                'braintree_brasil.paymenttokens.pager'
            )->setAvailableLimit([20 => 20])
                ->setShowPerPage(true)
                ->setCollection($this->getPaymentTokenCollection());
            $this->setChild('pager', $pager);
            $this->getPaymentTokenCollection()->load();
        }

        return $this;
    }

    /**
     * @return \Paypal\BraintreeBrasil\Model\ResourceModel\PaymentToken\Collection
     */
    public function getPaymentTokenCollection()
    {
        if(!$this->_paymentTokenCollection){
            $page = $this->getRequest()->getParam('p', 1);

            $collection = $this->paymentTokenCollectionFactory->create();
            $collection->addFilter('customer_id', $this->customerSession->getCustomerId());
            $collection->setCurPage($page);
            $collection->setPageSize(20);

            $this->_paymentTokenCollection = $collection;
        }

        return $this->_paymentTokenCollection;
    }

    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }
}
