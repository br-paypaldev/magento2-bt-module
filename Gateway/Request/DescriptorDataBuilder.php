<?php

namespace Paypal\BraintreeBrasil\Gateway\Request;

use Magento\Framework\App\ObjectManager;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Paypal\BraintreeBrasil\Logger\Logger;

/**
 * Class DescriptorDataBuilder
 */
class DescriptorDataBuilder implements BuilderInterface
{

    /**
     * @var Logger
     */
    protected $logger;

    protected $config;

    /**
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger,
        $configClass = null
    ) {
        $this->logger = $logger;
        $this->config = $configClass ? ObjectManager::getInstance()->create($configClass) : null;
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

        return $this->buildDescriptor();
    }

    protected function buildDescriptor()
    {
        $descriptor['descriptor']['name'] = $this->config->getDescriptorName();
        if ($phone = $this->config->getDescriptorPhone()) {
            $descriptor['descriptor']['phone'] = $phone;
        }
        if ($url = $this->config->getDescriptorUrl()) {
            $descriptor['descriptor']['url'] = $url;
        }
        return $descriptor;
    }

}
