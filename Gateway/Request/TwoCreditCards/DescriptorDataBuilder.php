<?php

namespace Paypal\BraintreeBrasil\Gateway\Request\TwoCreditCards;

use Paypal\BraintreeBrasil\Gateway\Config\TwoCreditCards\Config;
use Paypal\BraintreeBrasil\Logger\Logger;

/**
 * Class DescriptorDataBuilder
 */
class DescriptorDataBuilder extends \Paypal\BraintreeBrasil\Gateway\Request\DescriptorDataBuilder
{

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Logger $logger
     * @param Config $config
     * @param null $configClass
     */
    public function __construct(
        Logger $logger,
        Config $config,
        $configClass = null
    ) {
        parent::__construct($logger, $configClass);
        $this->config = $config;
    }

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!$this->config) {
            return [];
        }

        if (!$this->config->getDescriptorActive()) {
            return [];
        }

        $this->logger->info('Dynamic Descriptor Data Builder');

        return [
            'card_1' => $this->buildDescriptor(),
            'card_2' => $this->buildDescriptor()
        ];
    }

}
