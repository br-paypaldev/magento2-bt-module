<?php

namespace Paypal\BraintreeBrasil\Gateway\Http\Client\TwoCreditCards;

use Braintree\Exception\Authorization;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Paypal\BraintreeBrasil\Gateway\Config\TwoCreditCards\Config;
use Paypal\BraintreeBrasil\Gateway\Helper\Stc;
use Paypal\BraintreeBrasil\Gateway\Http\Client;
use Paypal\BraintreeBrasil\Logger\Logger;

/**
 * Class TransactionAuthorization
 */
class TransactionAuthorization implements ClientInterface
{

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Client
     */
    private $braintreeClient;

    /**
     * @var Config
     */
    private $twoCreditCardsConfig;

    /**
     * @var Stc
     */
    private $stcHelper;

    /**
     * @param Logger $logger
     * @param Client $braintreeClient
     * @param Config $twoCreditCardsConfig
     * @param Stc $stcHelper
     * @param array $data
     */
    public function __construct(
        Logger $logger,
        Client $braintreeClient,
        Config $twoCreditCardsConfig,
        Stc $stcHelper,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->braintreeClient = $braintreeClient;
        $this->twoCreditCardsConfig = $twoCreditCardsConfig;
        $this->stcHelper = $stcHelper;
    }

    /**
     * @param TransferInterface $transferObject
     * @return array
     * @throws ClientException
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $request = $transferObject->getBody();

        $this->logger->info('Transaction Authorization', [$request]);

        $response = [];

        try {
            if (empty($request['customer']['braintree_customer_id'])) {
                throw new LocalizedException(__('Customer CPF/CNPJ is empty'));
            }

            $braintreeCustomer = $this->braintreeClient->createBraintreeCustomerIfNotExists(
                $request['customer']['braintree_customer_id'],
                $request['customer']['firstname'],
                $request['customer']['lastname'],
                $request['customer']['email'],
                $request['customer']['telephone'],
                $request['customer']['fax'],
                $request['customer']['company']
            );

            $request['card_1']['stc'] = $this->setStcSenderCreateDate(
                $request['card_1']['stc'],
                $braintreeCustomer->createdAt->format(
                    'Y-m-d\TH:i:s'
                )
            );
            $request['card_2']['stc'] = $this->setStcSenderCreateDate(
                $request['card_2']['stc'],
                $braintreeCustomer->createdAt->format(
                    'Y-m-d\TH:i:s'
                )
            );

            $paymentTokenIds = [
                'card_1' => $request['card_1']['payment_token_id'],
                'card_2' => $request['card_2']['payment_token_id']
            ];

            $braintreeCustomerId = $braintreeCustomer->jsonSerialize()['id'];

            $this->sendStc($request['card_1']['stc'], 'First Credit Card');
            $this->sendStc($request['card_2']['stc'], 'Second Credit Card');

            unset(
                $request['customer'],
                $request['card_1']['payment_token_id'],
                $request['card_2']['payment_token_id'],
                $request['card_1']['stc'],
                $request['card_2']['stc']
            );

            $request['card_1']['customerId'] = $braintreeCustomerId;
            $request['card_2']['customerId'] = $braintreeCustomerId;

            $request['card_1']['billing'] = $request['billing'];
            $request['card_2']['billing'] = $request['billing'];
            if (isset($request['shipping'])) {
                $request['card_1']['shipping'] = $request['shipping'];
                $request['card_2']['shipping'] = $request['shipping'];
            }

            $result['card_1'] = $this->braintreeClient->getBraintreeClient()->transaction()->sale($request['card_1']);
            $result['card_2'] = $this->braintreeClient->getBraintreeClient()->transaction()->sale($request['card_2']);

            $response['braintree_customer_id'] = $braintreeCustomerId;

            $this->logger->info('Transaction RESULT', [$result]);

            $response['payment_result'] = $result;
            $response['save_cc'] = [
                'card_1' => $request['card_1']['options']['storeInVaultOnSuccess'] ?? false,
                'card_2' => $request['card_2']['options']['storeInVaultOnSuccess'] ?? false
            ];
            $response['payment_token_ids'] = $paymentTokenIds;
        } catch (Authorization $e) {
            $this->logger->info('Braintree Authorization Exception', [$e->getMessage()]);
            $response['error'] = $e->getMessage();
        } catch (\Exception $e) {
            $this->logger->info('Transaction Authorization ERROR', [$e->getMessage()]);
            $response['error'] = $e->getMessage();
        }

        return $response;
    }

    private function setStcSenderCreateDate($stc, $date)
    {
        foreach ($stc['additional_data'] as &$data) {
            if ($data['key'] === 'sender_create_date') {
                $data['value'] = $date;
                break;
            }
        }

        return $stc;
    }

    /**
     * @param array $stcData
     * @throws \Exception
     */
    private function sendStc($stcData, $card)
    {
        if ($this->twoCreditCardsConfig->getEnableStc()) {
            try {
                $this->stcHelper->send($stcData);
            } catch (\Exception $exception) {
                $this->logger->error(__("Can't create STC for %1. %2", $card, $exception->getMessage()));
                throw $exception;
            }
        }
    }
}
