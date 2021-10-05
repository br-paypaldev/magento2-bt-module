<?php
namespace Paypal\BraintreeBrasil\Model\Ui\PaypalWallet;

use Paypal\BraintreeBrasil\Gateway\Config\Config;
use Paypal\BraintreeBrasil\Gateway\Config\PaypalWallet\Config as PaypalWalletConfig;
use Paypal\BraintreeBrasil\Gateway\Http\Client;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'paypal_braintree_brasil_paypal_wallet';

    /**
     * @var GatewayConfig
     */
    private $gatewayConfig;
    /**
     * @var Config
     */
    private $braintreeConfig;
    /**
     * @var PaypalWalletConfig
     */
    private $paypalWalletConfig;
    /**
     * @var Client
     */
    private $braintreeClient;

    /**
     * ConfigProvider constructor.
     *
     * @param Config $braintreeConfig
     * @param PaypalWalletConfig $paypalWalletConfig
     * @param Client $braintreeClient
     * @param GatewayConfig $gatewayConfig
     */
    public function __construct(
        Config $braintreeConfig,
        PaypalWalletConfig $paypalWalletConfig,
        Client $braintreeClient,
        GatewayConfig $gatewayConfig
    ) {
        $this->gatewayConfig = $gatewayConfig;
        $this->braintreeConfig = $braintreeConfig;
        $this->paypalWalletConfig = $paypalWalletConfig;
        $this->braintreeClient = $braintreeClient;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $active = (bool)$this->paypalWalletConfig->getActive();
        $title = $this->paypalWalletConfig->getTitle();
        $enableInstallments = $this->paypalWalletConfig->getEnableInstallments();
        $clientToken = $active ? $this->braintreeClient->getClientToken() : null;

        return [
            'payment' => [
                'paypal_braintree_brasil_paypal_wallet' => [
                    'active' => $active,
                    'title' => $title,
                    'enable_installments' => (bool)$enableInstallments,
                    'client_token' => $clientToken,
                    'teste' => 123
                ]
            ]
        ];
    }
}
