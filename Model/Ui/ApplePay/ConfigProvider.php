<?php
namespace Paypal\BraintreeBrasil\Model\Ui\ApplePay;

use Paypal\BraintreeBrasil\Gateway\Config\Config;
use Paypal\BraintreeBrasil\Gateway\Config\ApplePay\Config as ApplePayConfig;
use Paypal\BraintreeBrasil\Gateway\Http\Client;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'paypal_braintree_brasil_apple_pay';

    /**
     * @var GatewayConfig
     */
    private $gatewayConfig;
    /**
     * @var Config
     */
    private $braintreeConfig;
    /**
     * @var ApplePayConfig
     */
    private $applePayConfig;
    /**
     * @var Client
     */
    private $braintreeClient;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * ConfigProvider constructor.
     *
     * @param Config $braintreeConfig
     * @param ApplePayConfig $applePayConfig
     * @param Client $braintreeClient
     * @param GatewayConfig $gatewayConfig
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Config $braintreeConfig,
        ApplePayConfig $applePayConfig,
        Client $braintreeClient,
        GatewayConfig $gatewayConfig,
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->gatewayConfig = $gatewayConfig;
        $this->braintreeConfig = $braintreeConfig;
        $this->applePayConfig = $applePayConfig;
        $this->braintreeClient = $braintreeClient;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $active = (bool)$this->applePayConfig->getActive();
        $title = $this->applePayConfig->getTitle();
        $clientToken = $active ? $this->braintreeClient->getClientToken() : null;
        $storeLabel = $this->applePayConfig->getStoreLabel()
            ? $this->applePayConfig->getStoreLabel()
            : $this->scopeConfig->getValue('general/store_information/name');

        return [
            'payment' => [
                'paypal_braintree_brasil_apple_pay' => [
                    'active' => $active,
                    'title' => $title,
                    'client_token' => $clientToken,
                    'store' => $storeLabel
                ]
            ]
        ];
    }
}
