<?php
namespace Paypal\BraintreeBrasil\Model\Ui\GooglePay;

use Paypal\BraintreeBrasil\Gateway\Config\Config;
use Paypal\BraintreeBrasil\Gateway\Config\GooglePay\Config as GooglePayConfig;
use Paypal\BraintreeBrasil\Gateway\Http\Client;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'paypal_braintree_brasil_google_pay';

    /**
     * @var GatewayConfig
     */
    private $gatewayConfig;
    /**
     * @var Config
     */
    private $braintreeConfig;
    /**
     * @var GooglePayConfig
     */
    private $googlePayConfig;
    /**
     * @var Client
     */
    private $braintreeClient;

    /**
     * ConfigProvider constructor.
     *
     * @param Config $braintreeConfig
     * @param GooglePayConfig $googlePayConfig
     * @param Client $braintreeClient
     * @param GatewayConfig $gatewayConfig
     */
    public function __construct(
        Config $braintreeConfig,
        GooglePayConfig $googlePayConfig,
        Client $braintreeClient,
        GatewayConfig $gatewayConfig
    ) {
        $this->gatewayConfig = $gatewayConfig;
        $this->braintreeConfig = $braintreeConfig;
        $this->googlePayConfig = $googlePayConfig;
        $this->braintreeClient = $braintreeClient;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $active = (bool)$this->googlePayConfig->getActive();
        $title = $this->googlePayConfig->getTitle();
        $clientToken = $active ? $this->braintreeClient->getClientToken() : null;
        $isProduction = $this->braintreeConfig->getIntegrationMode();

        return [
            'payment' => [
                'paypal_braintree_brasil_google_pay' => [
                    'active' => $active,
                    'title' => $title,
                    'client_token' => $clientToken,
                    'google_merchant_id' => $isProduction ? $this->googlePayConfig->getGoogleMerchantId() : null,
                ]
            ]
        ];
    }
}
