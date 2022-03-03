<?php

namespace Paypal\BraintreeBrasil\Model\Ui\TwoCreditCards;

use Magento\Checkout\Model\ConfigProviderInterface;
use Paypal\BraintreeBrasil\Gateway\Config\TwoCreditCards\Config as TwoCreditCardsConfig;
use Paypal\BraintreeBrasil\Gateway\Http\Client;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'paypal_braintree_brasil_two_creditcards';

    /**
     * @var TwoCreditCardsConfig
     */
    private $creditCardConfig;
    /**
     * @var Client
     */
    private $braintreeClient;

    /**
     * ConfigProvider constructor.
     *
     * @param TwoCreditCardsConfig $creditCardConfig
     * @param Client $braintreeClient
     */
    public function __construct(
        TwoCreditCardsConfig $creditCardConfig,
        Client $braintreeClient
    ) {
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
                self::CODE => [
                    'active' => $active,
                    'title' => $title,
                    'client_token' => $clientToken
                ]
            ]
        ];
    }
}
