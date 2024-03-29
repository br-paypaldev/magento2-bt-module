<?php

namespace Paypal\BraintreeBrasil\Gateway\Config\GooglePay;

use Paypal\BraintreeBrasil\Gateway\Config\Config as GlobalModuleConfig;
use Paypal\BraintreeBrasil\Gateway\Http\Client;
use Paypal\BraintreeBrasil\Model\Ui\GooglePay\ConfigProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Store\Model\StoreResolver;

class Config extends GatewayConfig
{
    const KEY_ACTIVE = 'active';
    const KEY_TITLE = 'title';
    const KEY_GOOGLE_MERCHANT_ID = 'google_merchant_id';
    const KEY_PAYMENT_ACTION = 'payment_action';
    const KEY_SANDBOX_MERCHANT_ACCOUNT_ID = 'sandbox_merchant_account_id';
    const KEY_PRODUCTION_MERCHANT_ACCOUNT_ID = 'production_merchant_account_id';
    const KEY_CHANNEL = 'channel';
    const KEY_DESCRIPTOR_ACTIVE = 'descriptor_active';
    const KEY_DESCRIPTOR_NAME = 'descriptor_name';
    const KEY_DESCRIPTOR_PHONE = 'descriptor_phone';
    const KEY_DESCRIPTOR_URL = 'descriptor_url';

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
     * @return string
     */
    public function getChannel()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_CHANNEL, $storeId);
    }

    /**
     * @return bool
     */
    public function getDescriptorActive()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return (bool)$this->getValue(self::KEY_DESCRIPTOR_ACTIVE, $storeId);
    }

    /**
     * @return string
     */
    public function getDescriptorName()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_DESCRIPTOR_NAME, $storeId);
    }

    /**
     * @return string
     */
    public function getDescriptorPhone()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_DESCRIPTOR_PHONE, $storeId);
    }

    /**
     * @return string
     */
    public function getDescriptorUrl()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_DESCRIPTOR_URL, $storeId);
    }


    /**
     * @return string|null
     */
    public function getGoogleMerchantId()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_GOOGLE_MERCHANT_ID, $storeId);
    }

    /**
     * @return string|null
     */
    public function getPaymentAction()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_PAYMENT_ACTION, $storeId);
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
