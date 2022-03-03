<?php

namespace Paypal\BraintreeBrasil\Gateway\Helper;

use Paypal\BraintreeBrasil\Gateway\Config\Config;
use Paypal\BraintreeBrasil\Gateway\Http\Client;
use Paypal\BraintreeBrasil\Logger\Logger;

class Stc
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Client $client
     * @param Logger $logger
     * @param Config $config
     */
    public function __construct(
        Client $client,
        Logger $logger,
        Config $config
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function send($stcData)
    {
        try {
            $this->logger->info(__("STC request: %1", json_encode($stcData)));
            $stcResponse = $this->client->sendStc($stcData);
            if ($stcResponse->isSuccessful()) {
                $this->logger->info(__("STC sent successfully"));
                return;
            }
            $msg = __(
                "STC response error. Status: %1. Message: %2. Body: %3",
                $stcResponse->getStatus(),
                $stcResponse->getMessage(),
                $stcResponse->getBody()
            );
            $this->logger->info($msg);
            if ($this->config->getStcBlockTransaction()) {
                throw new \Exception($msg);
            }
        } catch (\Exception $exception) {
            $this->logger->error(__("Can't create STC. %1", $exception->getMessage()));
            throw $exception;
        }
    }
}
