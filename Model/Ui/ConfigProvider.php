<?php
namespace Paypal\BraintreeBrasil\Model\Ui;

use Paypal\BraintreeBrasil\Gateway\Config\Config;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'paypal_braintree_brasil';

    /**
     * @var GatewayConfig
     */
    private $gatewayConfig;
    /**
     * @var Config
     */
    private $braintreeConfig;

    /**
     * ConfigProvider constructor.
     *
     * @param GatewayConfig $gatewayConfig
     */
    public function __construct(
        Config $braintreeConfig,
        GatewayConfig $gatewayConfig
    ) {
        $this->gatewayConfig = $gatewayConfig;
        $this->braintreeConfig = $braintreeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
            'payment' => [
                'paypal_braintree_brasil' => [
                    'integration_mode' => $this->braintreeConfig->getIntegrationMode(),
                    'debug' => $this->braintreeConfig->isDebugEnabled()
                ]
            ]
        ];
    }
}
