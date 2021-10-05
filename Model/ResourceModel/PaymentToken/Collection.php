<?php
namespace Paypal\BraintreeBrasil\Model\ResourceModel\PaymentToken;

use Paypal\BraintreeBrasil\Model\PaymentToken as PaymentTokenModel;
use Paypal\BraintreeBrasil\Model\ResourceModel\PaymentToken as PaymentTokenResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            PaymentTokenModel::class,
            PaymentTokenResourceModel::class
        );
    }
}
