<?php

namespace Paypal\BraintreeBrasil\Gateway\Request\CreditCard;

use Paypal\BraintreeBrasil\Gateway\Config\CreditCard\Config;
use Magento\Payment\Gateway\Request\BuilderInterface;

class ChannelDataBuilder implements BuilderInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {
        return ['channel' => $this->config->getChannel()];
    }
}
