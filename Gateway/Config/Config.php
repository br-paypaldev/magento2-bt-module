<?php

namespace Paypal\BraintreeBrasil\Gateway\Config;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Validator\Alnum;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Store\Model\StoreResolver;

class Config extends GatewayConfig
{
    const KEY_BASE = 'paypal_braintree_brasil/integration';
    const KEY_INTEGRATION_MODE = 'mode';
    const KEY_SANDBOX_MERCHANT_ID = 'sandbox_merchant_id';
    const KEY_SANDBOX_PUBLIC_KEY = 'sandbox_public_key';
    const KEY_SANDBOX_PRIVATE_KEY = 'sandbox_private_key';
    const KEY_PRODUCTION_MERCHANT_ID = 'production_merchant_id';
    const KEY_PRODUCTION_PUBLIC_KEY = 'production_public_key';
    const KEY_PRODUCTION_PRIVATE_KEY = 'production_private_key';
    const KEY_SANDBOX_MERCHANT_ACCOUNT_ID = 'sandbox_merchant_account_id';
    const KEY_PRODUCTION_MERCHANT_ACCOUNT_ID = 'production_merchant_account_id';
    const KEY_STC_MERCHANT_ID = 'stc_merchant_id';
    const KEY_STC_CLIENT_ID = 'stc_client_id';
    const KEY_STC_PRIVATE_KEY = 'stc_private_key';
    const KEY_STC_ACCESS_TOKEN = 'stc_access_token';
    const KEY_STC_ACCESS_TOKEN_EXPIRES = 'stc_access_token_expires';
    const KEY_STC_BLOCK_TRANSACTION = 'stc_block_transaction';

    const KEY_DEBUG = 'debug';

    const SANDBOX_INTEGRATION_MODE = 'sandbox';
    const PRODUCTION_INTEGRATION_MODE = 'production';

    /**
     * @var StoreResolver
     */
    private $storeResolver;

    /**
     * @var Alnum
     */
    private $alnumValidator;

    /**
     * @var WriterInterface
     */
    protected $writer;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreResolver $storeResolver
     * @param Alnum $alnumValidator
     * @param string $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreResolver $storeResolver,
        Alnum $alnumValidator,
        WriterInterface $writer,
        TypeListInterface $cacheTypeList,
        $methodCode = self::KEY_BASE,
        $pathPattern = '%s/%s'
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->storeResolver = $storeResolver;
        $this->alnumValidator = $alnumValidator;
        $this->writer = $writer;
        $this->cacheTypeList = $cacheTypeList;
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

        if ($this->getIntegrationMode() == self::SANDBOX_INTEGRATION_MODE) {
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

        if ($this->getIntegrationMode() == self::SANDBOX_INTEGRATION_MODE) {
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

        if ($this->getIntegrationMode() == self::SANDBOX_INTEGRATION_MODE) {
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

        if ($this->getIntegrationMode() == self::SANDBOX_INTEGRATION_MODE) {
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

        if (!$this->getMerchantId()
            && !$this->alnumValidator->isValid($this->getMerchantId())) {
            $isValid = false;
        }

        if (!$this->getPrivateKey()
            && !$this->alnumValidator->isValid($this->getPrivateKey())) {
            $isValid = false;
        }

        if (!$this->getPublicKey()
            && !$this->alnumValidator->isValid($this->getPublicKey())) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * @return string
     */
    public function getStcMerchantId()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_STC_MERCHANT_ID, $storeId);
    }

    /**
     * @return string
     */
    public function getStcClientId()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_STC_CLIENT_ID, $storeId);
    }

    /**
     * @return string
     */
    public function getStcPrivateKey()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_STC_PRIVATE_KEY, $storeId);
    }

    /**
     * @return false|string
     */
    public function getStcToken()
    {
        $token = $this->getValue(self::KEY_STC_ACCESS_TOKEN);
        if ($token) {
            $expiresin = $this->getValue(self::KEY_STC_ACCESS_TOKEN_EXPIRES);
            $now = new \DateTime();
            $nowTimestamp = $now->getTimestamp();
            if ($nowTimestamp > $expiresin) {
                $token = false;
            }
        }

        return $token;
    }

    /**
     * @param string $token
     * @param string $expiresin
     */
    public function setStcToken($token, $expiresin)
    {
        //- 5 minutos
        $expiresin -= 300;
        $now = new \DateTime();
        $now->add(new \DateInterval("PT{$expiresin}S"));
        $this->writer->save(self::KEY_BASE . '/' . self::KEY_STC_ACCESS_TOKEN, $token);
        $this->writer->save(self::KEY_BASE . '/' . self::KEY_STC_ACCESS_TOKEN_EXPIRES, $now->getTimestamp());
        //limpa cache após novos valores
        $this->cacheTypeList->cleanType('config');
    }

    /**
     * @return bool
     */
    public function getStcBlockTransaction()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return (bool)$this->getValue(self::KEY_STC_BLOCK_TRANSACTION, $storeId);
    }
}
