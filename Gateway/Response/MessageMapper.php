<?php

namespace Paypal\BraintreeBrasil\Gateway\Response;

use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;

class MessageMapper implements ErrorMessageMapperInterface
{

    /**
     * @inheritDoc
     */
    public function getMessage(string $code)
    {
        return $code;
    }
}
