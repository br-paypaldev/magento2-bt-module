<?php
namespace Paypal\BraintreeBrasil\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Store\Model\StoreResolver;

class CustomerAttributes extends GatewayConfig
{
    const KEY_CUSTOMER_COMPANY_LOGGEDIN = 'customer_company_loggedin';
    const KEY_CUSTOMER_CPF_LOGGEDIN = 'customer_cpf_loggedin';
    const KEY_CUSTOMER_CNPJ_LOGGEDIN = 'customer_cnpj_loggedin';
    const KEY_CUSTOMER_WEBSITE_LOGGEDIN = 'customer_website_loggedin';
    const KEY_CUSTOMER_TELEPHONE_LOGGEDIN = 'customer_telephone_loggedin';
    const KEY_CUSTOMER_FAX_LOGGEDIN = 'customer_fax_loggedin';
    const KEY_CUSTOMER_COMPANY_GUEST = 'customer_company_guest';
    const KEY_CUSTOMER_CPF_GUEST = 'customer_cpf_guest';
    const KEY_CUSTOMER_CNPJ_GUEST = 'customer_cnpj_guest';
    const KEY_CUSTOMER_WEBSITE_GUEST = 'customer_website_guest';
    const KEY_CUSTOMER_TELEPHONE_GUEST = 'customer_telephone_guest';
    const KEY_CUSTOMER_FAX_GUEST = 'customer_fax_guest';
    const KEY_ADDRESS_STREET = 'address_street';
    const KEY_ADDRESS_STREET_NUMBER = 'address_street_number';
    const KEY_ADDRESS_COMPLEMENTARY = 'address_complementary';
    const KEY_ADDRESS_NEIGHBORHOOD = 'address_neighborhood';

    private $storeResolver;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param string $methodCode
     * @param string $pathPattern
     */
    public function __construct
    (
        ScopeConfigInterface $scopeConfig,
        StoreResolver $storeResolver,
        $methodCode = 'paypal_braintree_brasil/attributes_mapping',
        $pathPattern = '%s/%s'
    )
    {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->storeResolver = $storeResolver;
    }

    /**
     * @return string
     */
    public function getCustomerCompanyLoggedin()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_CUSTOMER_COMPANY_LOGGEDIN, $storeId);
    }

    /**
     * @return string
     */
    public function getCustomerCompanyGuest()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_CUSTOMER_COMPANY_GUEST, $storeId);
    }

    /**
     * @return string
     */
    public function getCustomerCpfLoggedin()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_CUSTOMER_CPF_LOGGEDIN, $storeId);
    }

    /**
     * @return string
     */
    public function getCustomerCpfGuest()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_CUSTOMER_CPF_GUEST, $storeId);
    }

    /**
     * @return string
     */
    public function getCustomerCnpjLoggedin()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_CUSTOMER_CNPJ_LOGGEDIN, $storeId);
    }

    /**
     * @return string
     */
    public function getCustomerCnpjGuest()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_CUSTOMER_CNPJ_GUEST, $storeId);
    }

    /**
     * @return string
     */
    public function getCustomerWebsiteLoggedin()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_CUSTOMER_WEBSITE_LOGGEDIN, $storeId);
    }

    /**
     * @return string
     */
    public function getCustomerWebsiteGuest()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_CUSTOMER_WEBSITE_GUEST, $storeId);
    }

    /**
     * @return string
     */
    public function getCustomerTelephoneLoggedin()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_CUSTOMER_TELEPHONE_LOGGEDIN, $storeId);
    }

    /**
     * @return string
     */
    public function getCustomerTelephoneGuest()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_CUSTOMER_TELEPHONE_GUEST, $storeId);
    }

    /**
     * @return string
     */
    public function getCustomerFaxLoggedin()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_CUSTOMER_FAX_LOGGEDIN, $storeId);
    }

    /**
     * @return string
     */
    public function getCustomerFaxGuest()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_CUSTOMER_FAX_GUEST, $storeId);
    }

    /**
     * @return string
     */
    public function getAddressStreet()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_ADDRESS_STREET, $storeId);
    }

    /**
     * @return string
     */
    public function getAddressStreetNumber()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_ADDRESS_STREET_NUMBER, $storeId);
    }

    /**
     * @return string
     */
    public function getAddressComplementary()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_ADDRESS_COMPLEMENTARY, $storeId);
    }

    /**
     * @return string
     */
    public function getAddressNeighbordhood()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        return $this->getValue(self::KEY_ADDRESS_NEIGHBORHOOD, $storeId);
    }
}
