<?php

namespace Paypal\BraintreeBrasil\Gateway\Http;

use Braintree\Exception\NotFound as BraintreeExceptionNotFound;
use Braintree\Gateway as BraintreeGateway;
use Laminas\Json\Json;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Paypal\BraintreeBrasil\Gateway\Config\Config;
use Paypal\BraintreeBrasil\Logger\Logger;

class Client
{
    const STC_SANDBOX_URL_BASE = 'https://api-m.sandbox.paypal.com';
    const STC_SANDBOX_URL_STC_PATH = '/v1/risk/transaction-contexts/[merchant_id]/[correlation_id]';
    const STC_PRODUCTION_URL_BASE = 'https://api-m.paypal.com';
    const STC_PRODUCTION_URL_STC_PATH = '/v1/risk/transaction-contexts/[merchant_id]/[correlation_id]';

    const STC_OAUTH_SANDBOX = 'https://api-m.sandbox.paypal.com/v1/oauth2/token';
    const STC_OAUTH_PRODUCTION = 'https://api-m.paypal.com/v1/oauth2/token';

    /** @var BraintreeGateway */
    private $braintree_client;

    /** @var string */
    private $client_token;

    /**
     * @var Config
     */
    private $braintreeConfig;
    /**
     * @var Logger
     */
    private $logger;

    private $clientFactory;

    /**
     * Client constructor.
     * @param Logger $logger
     * @param Config $braintreeConfig
     */
    public function __construct(
        Logger $logger,
        Config $braintreeConfig,
        ZendClientFactory $clientFactory
    ) {
        $this->braintreeConfig = $braintreeConfig;
        $this->logger = $logger;
        $this->clientFactory = $clientFactory;
    }

    /**
     * @return BraintreeGateway
     */
    public function getBraintreeClient()
    {
        if (!$this->braintree_client) {
            $this->braintree_client = new BraintreeGateway(
                [
                    'environment' => $this->braintreeConfig->getIntegrationMode(),
                    'merchantId' => $this->braintreeConfig->getMerchantId(),
                    'publicKey' => $this->braintreeConfig->getPublicKey(),
                    'privateKey' => $this->braintreeConfig->getPrivateKey()
                ]
            );
        }

        return $this->braintree_client;
    }

    /**
     * @return string
     */
    public function getClientToken()
    {
        if (!$this->client_token) {
            if (!$this->braintreeConfig->validateConfiguration()) {
                return '';
            }

            try {
                $this->client_token = $this->getBraintreeClient()->clientToken()->generate();
            } catch (\Exception $e) {
                $this->logger->critical('Generate client token error', [$e->getMessage()]);
            }
        }
        return $this->client_token;
    }

    /**
     * Create a Braintree customer on merchant account
     *
     * @param string $id
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @param string $telephone
     * @param string|null $fax
     * @param string|null $company
     */
    public function createBraintreeCustomerIfNotExists(
        $braintree_customer_id,
        $firstname,
        $lastname,
        $email,
        $telephone,
        $fax = null,
        $company = null
    ) {
        try {
            // find fo customer
            $customer = $this->getBraintreeClient()
                ->customer()
                ->find($braintree_customer_id);
        } catch (BraintreeExceptionNotFound $e) {
            // create when not found
            $this->getBraintreeClient()
                ->customer()
                ->create(
                    [
                        'id' => $braintree_customer_id,
                        'firstName' => $firstname,
                        'lastName' => $lastname,
                        'email' => $email,
                        'phone' => $telephone,
                        'fax' => $fax,
                        'company' => $company
                    ]
                );

            $customer = $this->getBraintreeClient()
                ->customer()
                ->find($braintree_customer_id);
        }

        return $customer;
    }

    /**
     * Get Braintree customer data
     * @param string $braintree_customer_id
     * @return bool|\Braintree\Customer
     * @throws BraintreeExceptionNotFound
     */
    public function getBraintreeCustomer($braintree_customer_id)
    {
        return $this->getBraintreeClient()
            ->customer()
            ->find($braintree_customer_id);
    }

    public function sendStc($stcData)
    {
        if ($this->braintreeConfig->getIntegrationMode() === 'sandbox') {
            $url = self::STC_SANDBOX_URL_BASE;
            $path = self::STC_SANDBOX_URL_STC_PATH;
        } else {
            $url = self::STC_PRODUCTION_URL_BASE;
            $path = self::STC_PRODUCTION_URL_STC_PATH;
        }

        /** @var ZendClient $client */
        $client = $this->clientFactory->create();


        $path = str_replace(
            ['[merchant_id]', '[correlation_id]'],
            [$this->braintreeConfig->getStcMerchantId(), $stcData['correlation_id']],
            $path
        );
        $bearer = $this->getStcToken();

        $client->setHeaders(
            [
                'Authorization' => $bearer,
                'Content-Type' => 'application/json',
                'PayPal-Partner-Attribution-Id' => 'DigitalHub_Ecom',
                'Paypal-Client-Metadata-Id' => $stcData['correlation_id']
            ]
        );
        $client->setUri($url . $path);
        unset($stcData['correlation_id']);
        $client->setRawData(json_encode($stcData));

        return $client->request('PUT');
    }

    /**
     * @return string
     * @throws \Zend_Http_Client_Exception
     */
    private function getStcToken()
    {
        $token = $this->braintreeConfig->getStcToken();
        if ($token) {
            return $token;
        }
        $clientId = $this->braintreeConfig->getStcClientId();
        $secret = $this->braintreeConfig->getStcPrivateKey();
        $base64 = base64_encode(sprintf("%s:%s", $clientId, $secret));
        /** @var ZendClient $client */
        $client = $this->clientFactory->create();

        if ($this->braintreeConfig->getIntegrationMode() === 'sandbox') {
            $url = self::STC_OAUTH_SANDBOX;
        } else {
            $url = self::STC_OAUTH_PRODUCTION;
        }

        $client->setUri($url);
        $client->setMethod(ZendClient::POST);
        $client->setHeaders(
            [
                'Authorization' => "Basic $base64",
                'Content-Type' => 'application/json',
            ]
        );
        $client->setParameterPost(
            [
                'grant_type' => 'client_credentials',
                'response_type' => 'token'
            ]
        );
        try {
            $response = $client->request();
            if ($response->isSuccessful()) {
                $response = Json::decode($response->getBody(), true);
                $token = $response['token_type'] . ' ' . $response['access_token'];
                $this->braintreeConfig->setStcToken($token, $response['expires_in']);
            } else {
                throw new \Exception($response->getMessage());
            }
        } catch (\Exception $exception) {
            $this->logger->error(__("Error on generate access token"));
            $this->logger->error($exception->getMessage());
            throw $exception;
        }

        return $token;
    }
}
