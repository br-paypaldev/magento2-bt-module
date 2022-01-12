<?php

namespace Paypal\BraintreeBrasil\Logger;

use Paypal\BraintreeBrasil\Gateway\Config\Config;
use Magento\Store\Model\StoreResolver;

class Logger extends \Monolog\Logger
{
    /**
     * @var Config
     */
    private $braintreeConfig;
    /**
     * @var StoreResolver
     */
    private $storeResolver;

    /**
     * Logger constructor.
     * @param string $name
     * @param array $handlers
     * @param array $processors
     * @param StoreResolver $storeResolver
     * @param Config $braintreeConfig
     */
    public function __construct(
        $name,
        StoreResolver $storeResolver,
        Config $braintreeConfig,
        array $handlers = array(),
        array $processors = array()
    ) {
        parent::__construct($name, $handlers, $processors);
        $this->braintreeConfig = $braintreeConfig;
        $this->storeResolver = $storeResolver;
    }

    /**
     * @param int $level
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function addRecord($level, $message, array $context = [])
    {
        if ($this->braintreeConfig->isDebugEnabled()) {
            $storeId = $this->storeResolver->getCurrentStoreId();
            $message = '[Store ID ' . $storeId . '] ' . $message;
            return parent::addRecord($level, $message, $context);
        }
        return true;
    }
}
