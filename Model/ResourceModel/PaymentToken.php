<?php
namespace Paypal\BraintreeBrasil\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class PaymentToken extends AbstractDb
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('braintree_brasil_payment_token', 'entity_id');
    }
}
