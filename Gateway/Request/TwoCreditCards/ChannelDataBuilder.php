<?php

namespace Paypal\BraintreeBrasil\Gateway\Request\TwoCreditCards;

use Paypal\BraintreeBrasil\Gateway\Config\TwoCreditCards\Config;
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
        return [
            'card_1' => ['channel' => $this->config->getChannel()],
            'card_2' => ['channel' => $this->config->getChannel()]
        ];
    }
}
