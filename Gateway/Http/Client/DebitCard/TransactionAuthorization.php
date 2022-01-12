<?php

namespace Paypal\BraintreeBrasil\Gateway\Http\Client\DebitCard;

use Braintree\Exception\Authorization;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Store\Model\StoreManagerInterface;
use Paypal\BraintreeBrasil\Gateway\Config\Config as GatewayModuleConfig;
use Paypal\BraintreeBrasil\Gateway\Config\DebitCard\Config;
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
    private $debitCardConfig;

    /**
     * @var Stc
     */
    private $stcHelper;

    /**
     * @param Logger $logger
     * @param Config $debitCardConfig
     * @param Client $braintreeClient
     * @param Stc $stcHelper
     * @param array $data
     */
    public function __construct(
        Logger $logger,
        Config $debitCardConfig,
        Client $braintreeClient,
        Stc $stcHelper,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->braintreeClient = $braintreeClient;
        $this->debitCardConfig = $debitCardConfig;
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

            foreach ($request['stc']['additional_data'] as &$data) {
                if ($data['key'] === 'sender_create_date') {
                    $data['value'] = $braintreeCustomer->createdAt->format('Y-m-d\TH:i:s');
                    break;
                }
            }

            $paymentTokenId = $request['payment_token_id'];
            $braintreeCustomerId = $request['customer']['braintree_customer_id'];

            $this->sendStc($request['stc']);
            unset($request['customer'], $request['payment_token_id'], $request['stc']);

            $result = $this->braintreeClient->getBraintreeClient()->transaction()->sale($request);

            $this->logger->info('Transaction RESULT', [$result]);

            $response['payment_result'] = $result;
            $response['save_dc'] = $request['options']['storeInVaultOnSuccess'] ?? false;
            $response['payment_token_id'] = $paymentTokenId;
            $response['braintree_customer_id'] = $braintreeCustomerId;
        } catch (Authorization $e) {
            $this->logger->info('Braintree Authorization Exception', [$e->getMessage()]);
            $response['error'] = $e->getMessage();
        } catch (\Exception $e) {
            $this->logger->info('Transaction Authorization ERROR', [$e->getMessage()]);
            $response['error'] = $e->getMessage();
        }

        return $response;
    }

    /**
     * @param array $stcData
     * @throws \Exception
     */
    private function sendStc($stcData)
    {
        if ($this->debitCardConfig->getEnableStc()) {
            $this->stcHelper->send($stcData);
        }
    }
}
