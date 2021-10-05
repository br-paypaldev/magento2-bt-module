<?php

namespace Paypal\BraintreeBrasil\Helper;

use Magento\Framework\DataObject;

class CreditmemoData extends DataObject
{
    const CREDITMEMO_DATA = 'creditmemo_data';

    public function getCreditmemoData()
    {
        return $this->getData(self::CREDITMEMO_DATA);
    }

    public function setCreditmemoData($data)
    {
        $this->setData(self::CREDITMEMO_DATA, $data);
        return $this;
    }

}
