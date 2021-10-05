<?php
namespace Paypal\BraintreeBrasil\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Validator\Alnum;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Store\Model\StoreResolver;

class Config extends GatewayConfig
{
    const KEY_INTEGRATION_MODE = 'mode';
    const KEY_SANDBOX_MERCHANT_ID = 'sandbox_merchant_id';
    const KEY_SANDBOX_PUBLIC_KEY = 'sandbox_public_key';
    const KEY_SANDBOX_PRIVATE_KEY = 'sandbox_private_key';
    const KEY_PRODUCTION_MERCHANT_ID = 'production_merchant_id';
    const KEY_PRODUCTION_PUBLIC_KEY = 'production_public_key';
    const KEY_PRODUCTION_PRIVATE_KEY = 'production_private_key';
    const KEY_SANDBOX_MERCHANT_ACCOUNT_ID = 'sandbox_merchant_account_id';
    const KEY_PRODUCTION_MERCHANT_ACCOUNT_ID = 'production_merchant_account_id';
    const KEY_DEBUG = 'debug';

    const SANDBOX_INTEGRATION_MODE = 'sandbox';
    const PRODUCTION_INTEGRATION_MODE = 'production';

    private $storeResolver;
    /**
     * @var Alnum
     */
    private $alnumValidator;
    /**
     * @var Client
     */
    private $braintreeClient;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreResolver $storeResolver
     * @param Alnum $alnumValidator
     * @param string $methodCode
     * @param string $pathPattern
     */
    public function __construct
    (
        ScopeConfigInterface $scopeConfig,
        StoreResolver $storeResolver,
        Alnum $alnumValidator,
        $methodCode = 'paypal_braintree_brasil/integration',
        $pathPattern = '%s/%s'
    )
    {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->storeResolver = $storeResolver;
        $this->alnumValidator = $alnumValidator;
    }

    /**
     * @return string|null
     */
    public function getIntegrationMode()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_INTEGRATION_MODE, $storeId);
    }

    /**
     * @return string|null
     */
    public function getMerchantId()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        $field = self::KEY_PRODUCTION_MERCHANT_ID;

        if($this->getIntegrationMode() == self::SANDBOX_INTEGRATION_MODE){
            $field = self::KEY_SANDBOX_MERCHANT_ID;
        }

        return $this->getValue($field, $storeId);
    }

    /**
     * @return string|null
     */
    public function getPublicKey()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        $field = self::KEY_PRODUCTION_PUBLIC_KEY;

        if($this->getIntegrationMode() == self::SANDBOX_INTEGRATION_MODE){
            $field = self::KEY_SANDBOX_PUBLIC_KEY;
        }

        return $this->getValue($field, $storeId);
    }

    /**
     * @return string|null
     */
    public function getPrivateKey()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        $field = self::KEY_PRODUCTION_PRIVATE_KEY;

        if($this->getIntegrationMode() == self::SANDBOX_INTEGRATION_MODE){
            $field = self::KEY_SANDBOX_PRIVATE_KEY;
        }

        return $this->getValue($field, $storeId);
    }

    /**
     * @return string|null
     */
    public function getMerchantAccountId()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        $field = self::KEY_PRODUCTION_MERCHANT_ACCOUNT_ID;

        if($this->getIntegrationMode() == self::SANDBOX_INTEGRATION_MODE){
            $field = self::KEY_SANDBOX_MERCHANT_ACCOUNT_ID;
        }

        return $this->getValue($field, $storeId);
    }

    /**
     * @return string|null
     */
    public function isDebugEnabled()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_DEBUG, $storeId);
    }

    /**
     * Validate configured keys
     *
     * @return bool
     */
    public function validateConfiguration()
    {
        $isValid = true;

        if(!$this->getMerchantId()
            && !$this->alnumValidator->isValid($this->getMerchantId())){
            $isValid = false;
        }

        if(!$this->getPrivateKey()
            && !$this->alnumValidator->isValid($this->getPrivateKey())){
            $isValid = false;
        }

        if(!$this->getPublicKey()
            && !$this->alnumValidator->isValid($this->getPublicKey())){
            $isValid = false;
        }

        return $isValid;
    }
}
