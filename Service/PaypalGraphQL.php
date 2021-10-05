<?php
declare(strict_types=1);

namespace Paypal\BraintreeBrasil\Service;

use Paypal\BraintreeBrasil\Gateway\Config\Config;
use Paypal\BraintreeBrasil\Logger\Logger;
use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use Magento\Framework\Webapi\Rest\Request;

class PaypalGraphQL
{
    const PAYPAL_GRAPHQL_ENDPOINT_SANDBOX = 'https://payments.sandbox.braintree-api.com/graphql';
    const PAYPAL_GRAPHQL_ENDPOINT_PRODUCTION = 'https://payments.braintree-api.com/graphql';

    const TRANSACTION_STATUS_SETTLED = 'SETTLED';
    const TRANSACTION_STATUS_SETTLEMENT_CONFIRMED = 'SETTLEMENT_CONFIRMED';
    const TRANSACTION_STATUS_SETTLEMENT_PENDING = 'SETTLEMENT_PENDING';
    const TRANSACTION_STATUS_SETTLING = 'SETTLING';
    const TRANSACTION_STATUS_SUBMITTED_FOR_SETTLEMENT = 'SUBMITTED_FOR_SETTLEMENT';

    /**
     * @var ClientFactory
     */
    private $clientFactory;
    /**
     * @var ResponseFactory
     */
    private $responseFactory;
    /**
     * @var Config
     */
    private $braintreeConfig;

    /**
     * PaypalGraphQL constructor.
     * @param Logger $logger
     * @param ClientFactory $clientFactory
     * @param Config $braintreeConfig
     * @param ResponseFactory $responseFactory
     */
    public function __construct(
        Logger $logger,
        ClientFactory $clientFactory,
        Config $braintreeConfig,
        ResponseFactory $responseFactory
    ) {

        $this->clientFactory = $clientFactory;
        $this->responseFactory = $responseFactory;
        $this->braintreeConfig = $braintreeConfig;
        $this->logger = $logger;
    }

    /**
     * Do GraphQL call
     *
     * @param $query
     * @param null $variables
     * @param bool $sandbox
     * @return Response|\Psr\Http\Message\ResponseInterface
     */
    public function execute($query, $variables = null, $sandbox = true)
    {
        /** @var Client $client */
        $client = $this->clientFactory->create();

        $graphQLEndpoint = $sandbox ? self::PAYPAL_GRAPHQL_ENDPOINT_SANDBOX : self::PAYPAL_GRAPHQL_ENDPOINT_PRODUCTION;
        $authToken = base64_encode(
            $this->braintreeConfig->getPublicKey() . ":" . $this->braintreeConfig->getPrivateKey()
        );

        $params = [
            'json' => [
                'query' => $query,
                'variables' => $variables
            ],
            'headers' => [
                'Authorization' => 'Bearer ' . $authToken,
                'Braintree-Version' => '2019-01-01',
                'Content-type' => 'application/json'
            ]
        ];

        $this->logger->info('PayPal GraphQL Query', [$params]);

        try {
            $response = $client->request(
                Request::HTTP_METHOD_POST,
                $graphQLEndpoint,
                $params
            );
        } catch (GuzzleException $exception) {
            /** @var Response $response */
            $response = $this->responseFactory->create([
                'status' => $exception->getCode(),
                'reason' => $exception->getMessage()
            ]);
        }

        return $response;
    }
}
