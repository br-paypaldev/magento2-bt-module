<?php
namespace Paypal\BraintreeBrasil\Model\Ui\CreditCard;

use Paypal\BraintreeBrasil\Gateway\Config\Config;
use Paypal\BraintreeBrasil\Gateway\Config\CreditCard\Config as CreditCardConfig;
use Paypal\BraintreeBrasil\Gateway\Http\Client;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'paypal_braintree_brasil_creditcard';

    /**
     * @var GatewayConfig
     */
    private $gatewayConfig;
    /**
     * @var Config
     */
    private $braintreeConfig;
    /**
     * @var CreditCardConfig
     */
    private $creditCardConfig;
    /**
     * @var Client
     */
    private $braintreeClient;

    /**
     * ConfigProvider constructor.
     *
     * @param Config $braintreeConfig
     * @param CreditCardConfig $creditCardConfig
     * @param Client $braintreeClient
     * @param GatewayConfig $gatewayConfig
     */
    public function __construct(
        Config $braintreeConfig,
        CreditCardConfig $creditCardConfig,
        Client $braintreeClient,
        GatewayConfig $gatewayConfig
    ) {
        $this->gatewayConfig = $gatewayConfig;
        $this->braintreeConfig = $braintreeConfig;
        $this->creditCardConfig = $creditCardConfig;
        $this->braintreeClient = $braintreeClient;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $active = (bool)$this->creditCardConfig->getActive();
        $title = $this->creditCardConfig->getTitle();
        $clientToken = $active ? $this->braintreeClient->getClientToken() : null;

        return [
            'payment' => [
                'paypal_braintree_brasil_creditcard' => [
                    'active' => $active,
                    'title' => $title,
                    'client_token' => $clientToken
                ]
            ]
        ];
    }
}
