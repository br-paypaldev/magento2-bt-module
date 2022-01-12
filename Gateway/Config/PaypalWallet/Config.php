<?php

namespace Paypal\BraintreeBrasil\Gateway\Config\PaypalWallet;

use Paypal\BraintreeBrasil\Gateway\Config\Config as GlobalModuleConfig;
use Paypal\BraintreeBrasil\Gateway\Http\Client;
use Paypal\BraintreeBrasil\Model\Ui\PaypalWallet\ConfigProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Store\Model\StoreResolver;

class Config extends GatewayConfig
{
    const KEY_ACTIVE = 'active';
    const KEY_TITLE = 'title';
    const KEY_SANDBOX_MERCHANT_ACCOUNT_ID = 'sandbox_merchant_account_id';
    const KEY_PRODUCTION_MERCHANT_ACCOUNT_ID = 'production_merchant_account_id';
    const KEY_SORT_ORDER = 'sort_order';
    const KEY_PAYMENT_ACTION = 'payment_action';
    const KEY_ENABLE_INSTALLMENTS = 'enable_installments';
    const KEY_MAX_INSTALLMENTS = 'max_installments';
    const KEY_CHANNEL = 'channel';
    const KEY_ENABLE_STC = 'enable_stc';

    /**
     * @var StoreResolver
     */
    private $storeResolver;
    /**
     * @var GlobalModuleConfig
     */
    private $globalModuleConfig;
    /**
     * @var Client
     */
    private $braintreeClient;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreResolver $storeResolver
     * @param GlobalModuleConfig $globalModuleConfig
     * @param Client $braintreeClient
     * @param string $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreResolver $storeResolver,
        GlobalModuleConfig $globalModuleConfig,
        Client $braintreeClient,
        $methodCode = ConfigProvider::CODE,
        $pathPattern = GatewayConfig::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->storeResolver = $storeResolver;
        $this->globalModuleConfig = $globalModuleConfig;
        $this->braintreeClient = $braintreeClient;
    }

    /**
     * @return string|null
     */
    public function getActive()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_ACTIVE, $storeId);
    }

    /**
     * @return string|null
     */
    public function getTitle()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_TITLE, $storeId);
    }

    /**
     * @return string|null
     */
    public function getMerchantAccountId()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        if ($this->globalModuleConfig->getIntegrationMode() === GlobalModuleConfig::SANDBOX_INTEGRATION_MODE) {
            return $this->getValue(self::KEY_SANDBOX_MERCHANT_ACCOUNT_ID, $storeId);
        }
        return $this->getValue(self::KEY_PRODUCTION_MERCHANT_ACCOUNT_ID, $storeId);
    }

    /**
     * @return string|null
     */
    public function getSortOrder()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_SORT_ORDER, $storeId);
    }

    /**
     * @return string|null
     */
    public function getPaymentAction()
    {
        if ($this->getEnableInstallments()) {
            return 'authorize_capture';
        }
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_PAYMENT_ACTION, $storeId);
    }

    /**
     * @return bool
     */
    public function getEnableInstallments()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return (bool)$this->getValue(self::KEY_ENABLE_INSTALLMENTS, $storeId);
    }

    /**
     * @return int
     */
    public function getMaxInstallments()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return (int)$this->getValue(self::KEY_MAX_INSTALLMENTS, $storeId);
    }

    public function getChannel()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_CHANNEL, $storeId);
    }

    /**
     * @return bool
     */
    public function getEnableStc()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return (bool)$this->getValue(self::KEY_ENABLE_STC, $storeId);
    }

    /**
     * @param string $field
     * @param null $storeId
     * @return false|mixed|null
     */
    public function getValue($field, $storeId = null)
    {
        if ($field === 'active') {
            if (!$this->globalModuleConfig->validateConfiguration()
                || !$this->braintreeClient->getClientToken()) {
                return false;
            }
        }
        return parent::getValue($field, $storeId);
    }
}
