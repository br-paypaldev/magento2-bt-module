<?php

namespace Paypal\BraintreeBrasil\Gateway\Http\Client\DebitCard;

use Braintree\Exception\Authentication;
use Braintree\Exception\Authorization;
use Paypal\BraintreeBrasil\Gateway\Config\Config as GatewayModuleConfig;
use Paypal\BraintreeBrasil\Gateway\Config\DebitCard\Config;
use Paypal\BraintreeBrasil\Gateway\Http\Client;
use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class TransactionSale
 */
class TransactionAuthorization implements ClientInterface
{

    protected $helper;
    protected $logger;
    protected $_appState;
    protected $_storeManager;
    /**
     * @var Client
     */
    private $braintreeClient;
    /**
     * @var Config
     */
    private $debitCardConfig;
    /**
     * @var GatewayModuleConfig
     */
    private $gatewayModuleConfig;

    /**
     * PaymentRequest constructor.
     *
     * @param Context $context
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param GatewayModuleConfig $gatewayModuleConfig
     * @param Config $debitCardConfig
     * @param Client $braintreeClient
     * @param array $data
     */
    public function __construct(
        Context $context,
        Logger $logger,
        StoreManagerInterface $storeManager,
        GatewayModuleConfig $gatewayModuleConfig,
        Config $debitCardConfig,
        Client $braintreeClient,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->_appState = $context->getAppState();
        $this->_storeManager = $storeManager;
        $this->braintreeClient = $braintreeClient;
        $this->debitCardConfig = $debitCardConfig;
        $this->gatewayModuleConfig = $gatewayModuleConfig;
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

            $this->braintreeClient->createBraintreeCustomerIfNotExists(
                $request['customer']['braintree_customer_id'],
                $request['customer']['firstname'],
                $request['customer']['lastname'],
                $request['customer']['email'],
                $request['customer']['telephone'],
                $request['customer']['fax'],
                $request['customer']['company']
            );

            $paymentTokenId = $request['payment_token_id'];
            $braintreeCustomerId = $request['customer']['braintree_customer_id'];
            unset($request['customer'], $request['payment_token_id']);

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
}
