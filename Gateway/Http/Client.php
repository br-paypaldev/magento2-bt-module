<?php

namespace Paypal\BraintreeBrasil\Gateway\Http;

use Braintree\Exception\NotFound as BraintreeExceptionNotFound;
use Braintree\Gateway as BraintreeGateway;
use Paypal\BraintreeBrasil\Gateway\Config\Config;
use Paypal\BraintreeBrasil\Logger\Logger;

class Client
{
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

    /**
     * Client constructor.
     * @param Logger $logger
     * @param Config $braintreeConfig
     */
    public function __construct(
        Logger $logger,
        Config $braintreeConfig
    ) {
        $this->braintreeConfig = $braintreeConfig;
        $this->logger = $logger;
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
}
