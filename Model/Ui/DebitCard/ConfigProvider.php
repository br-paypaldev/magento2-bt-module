<?php
namespace Paypal\BraintreeBrasil\Model\Ui\DebitCard;

use Paypal\BraintreeBrasil\Gateway\Config\Config;
use Paypal\BraintreeBrasil\Gateway\Config\DebitCard\Config as DebitCardConfig;
use Paypal\BraintreeBrasil\Gateway\Http\Client;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'paypal_braintree_brasil_debitcard';

    /**
     * @var GatewayConfig
     */
    private $gatewayConfig;
    /**
     * @var Config
     */
    private $braintreeConfig;
    /**
     * @var DebitCardConfig
     */
    private $debitCardConfig;
    /**
     * @var Client
     */
    private $braintreeClient;

    /**
     * ConfigProvider constructor.
     *
     * @param Config $braintreeConfig
     * @param DebitCardConfig $debitCardConfig
     * @param Client $braintreeClient
     * @param GatewayConfig $gatewayConfig
     */
    public function __construct(
        Config $braintreeConfig,
        DebitCardConfig $debitCardConfig,
        Client $braintreeClient,
        GatewayConfig $gatewayConfig
    ) {
        $this->gatewayConfig = $gatewayConfig;
        $this->braintreeConfig = $braintreeConfig;
        $this->debitCardConfig = $debitCardConfig;
        $this->braintreeClient = $braintreeClient;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $active = (bool)$this->debitCardConfig->getActive();
        $title = $this->debitCardConfig->getTitle();
        $clientToken = $active ? $this->braintreeClient->getClientToken() : null;

        return [
            'payment' => [
                'paypal_braintree_brasil_debitcard' => [
                    'active' => $active,
                    'title' => $title,
                    'client_token' => $clientToken
                ]
            ]
        ];
    }
}
