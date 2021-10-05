<?php
namespace Paypal\BraintreeBrasil\Model;

use Magento\Framework\Api\DataObjectHelper;
use Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterface;
use Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterfaceFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;

class PaymentToken extends \Magento\Framework\Model\AbstractModel
{

    protected $tokenDataFactory;

    protected $dataObjectHelper;

    protected $_eventPrefix = 'namespace_vendor_token';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param PaymentTokenInterfaceFactory $tokenDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Paypal\BraintreeBrasil\Model\ResourceModel\PaymentToken $resource
     * @param \Paypal\BraintreeBrasil\Model\ResourceModel\PaymentToken\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        PaymentTokenInterfaceFactory $tokenDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Paypal\BraintreeBrasil\Model\ResourceModel\PaymentToken $resource,
        \Paypal\BraintreeBrasil\Model\ResourceModel\PaymentToken\Collection $resourceCollection,
        array $data = []
    ) {
        $this->tokenDataFactory = $tokenDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve token model with token data
     * @return PaymentTokenInterface
     */
    public function getDataModel()
    {
        $tokenData = $this->getData();

        $tokenDataObject = $this->tokenDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $tokenDataObject,
            $tokenData,
            PaymentTokenInterface::class
        );

        return $tokenDataObject;
    }
}
