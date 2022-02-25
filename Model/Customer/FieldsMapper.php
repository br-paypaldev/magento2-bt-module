<?php

namespace Paypal\BraintreeBrasil\Model\Customer;

use Magento\Customer\Model\CustomerFactory;
use Magento\Quote\Api\Data\CartInterface;
use Paypal\BraintreeBrasil\Gateway\Config\CustomerAttributes;

class FieldsMapper
{
    /**
     * @var CustomerAttributes
     */
    private $customerAttributesConfig;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * FieldsMapper constructor.
     * @param CustomerAttributes $customerAttributesConfig
     */
    public function __construct(
        CustomerFactory $customerFactory,
        CustomerAttributes $customerAttributesConfig
    ) {
        $this->customerAttributesConfig = $customerAttributesConfig;
        $this->customerFactory = $customerFactory;
    }

    /**
     * @param CartInterface $quote
     * @return string
     */
    public function getCustomerCpf($quote)
    {
        $value = null;

        if (!$quote->getCustomerId()) {
            $field = $this->customerAttributesConfig->getCustomerCpfGuest();
            $field = $this->cleanField($field);
            $value = $quote->getBillingAddress()->getData($field);
        } else {
            $field = $this->customerAttributesConfig->getCustomerCpfLoggedin();
            if ($this->_getFieldType($field) === 'customer') {
                $field = $this->cleanField($field);
                $customer = $quote->getCustomer();
                $value = $this->extractCustomerFieldValue($customer, $field);
            } else {
                if ($this->_getFieldType($field) === 'address') {
                    $field = $this->cleanField($field);
                    $value = $quote->getBillingAddress()->getData($field);
                }
            }
        }

        return is_array($value) ? '' : preg_replace('/\D+/', '', $value);
    }

    /**
     * @param CartInterface $quote
     * @return string
     */
    public function getCustomerCnpj($quote)
    {
        $value = null;

        if (!$quote->getCustomerId()) {
            $field = $this->customerAttributesConfig->getCustomerCnpjGuest();
            $field = $this->cleanField($field);
            $value = $quote->getBillingAddress()->getData($field);
        } else {
            $field = $this->customerAttributesConfig->getCustomerCnpjLoggedin();
            if ($this->_getFieldType($field) === 'customer') {
                $field = $this->cleanField($field);
                $customer = $quote->getCustomer();
                $value = $this->extractCustomerFieldValue($customer, $field);
            } else {
                if ($this->_getFieldType($field) === 'address') {
                    $field = $this->cleanField($field);
                    $value = $quote->getBillingAddress()->getData($field);
                }
            }
        }

        return is_array($value) ? '' : preg_replace('/\D+/', '', $value);
    }

    /**
     * @param CartInterface $quote
     * @param string $addressType
     * @return string
     */
    public function getCustomerCompany($quote, $addressType = 'billing')
    {
        $value = null;
        $addressType = ucfirst($addressType);
        $addressMethod = "get{$addressType}Address";

        if (!$quote->getCustomerId()) {
            $field = $this->customerAttributesConfig->getCustomerCompanyGuest();
            $field = $this->cleanField($field);
            $value = $quote->$addressMethod()->getData($field);
        } else {
            $field = $this->customerAttributesConfig->getCustomerCompanyLoggedin();
            if ($this->_getFieldType($field) === 'customer') {
                $field = $this->cleanField($field);
                $customer = $quote->getCustomer();
                $value = $this->extractCustomerFieldValue($customer, $field);
            } else {
                if ($this->_getFieldType($field) === 'address') {
                    $field = $this->cleanField($field);
                    $value = $quote->$addressMethod()->getData($field);
                }
            }
        }

        return is_array($value) ? '' : $value;
    }

    /**
     * @param CartInterface $quote
     * @return string
     */
    public function getCustomerWebsite($quote)
    {
        $value = null;

        if (!$quote->getCustomerId()) {
            $field = $this->customerAttributesConfig->getCustomerWebsiteGuest();
            $field = $this->cleanField($field);
            $value = $quote->getBillingAddress()->getData($field);
        } else {
            $field = $this->customerAttributesConfig->getCustomerWebsiteLoggedin();
            if ($this->_getFieldType($field) === 'customer') {
                $field = $this->cleanField($field);
                $customer = $quote->getCustomer();
                $value = $this->extractCustomerFieldValue($customer, $field);
            } else {
                if ($this->_getFieldType($field) === 'address') {
                    $field = $this->cleanField($field);
                    $value = $quote->getBillingAddress()->getData($field);
                }
            }
        }

        return is_array($value) ? '' : $value;
    }

    /**
     * @param CartInterface $quote
     * @return string
     */
    public function getCustomerTelephone($quote, $addressType = 'billing')
    {
        $value = null;
        $addressType = ucfirst($addressType);
        $addressMethod = "get{$addressType}Address";

        if (!$quote->getCustomerId()) {
            $field = $this->customerAttributesConfig->getCustomerTelephoneGuest();
            $field = $this->cleanField($field);
            $value = $quote->$addressMethod()->getData($field);
        } else {
            $field = $this->customerAttributesConfig->getCustomerTelephoneLoggedin();
            if ($this->_getFieldType($field) === 'customer') {
                $field = $this->cleanField($field);
                $customer = $quote->getCustomer();
                $value = $this->extractCustomerFieldValue($customer, $field);
            } else {
                if ($this->_getFieldType($field) === 'address') {
                    $field = $this->cleanField($field);
                    $value = $quote->$addressMethod()->getData($field);
                }
            }
        }

        return is_array($value) ? '' : $value;
    }

    /**
     * @param CartInterface $quote
     * @param string $addressType
     * @return string
     */
    public function getCustomerFax($quote, $addressType = 'billing')
    {
        $value = null;
        $addressType = ucfirst($addressType);
        $addressMethod = "get{$addressType}Address";

        if (!$quote->getCustomerId()) {
            $field = $this->customerAttributesConfig->getCustomerFaxGuest();
            $field = $this->cleanField($field);
            $value = $quote->$addressMethod()->getData($field);
        } else {
            $field = $this->customerAttributesConfig->getCustomerFaxLoggedin();
            if ($this->_getFieldType($field) === 'customer') {
                $field = $this->cleanField($field);
                $customer = $quote->getCustomer();
                $value = $this->extractCustomerFieldValue($customer, $field);
            } else {
                if ($this->_getFieldType($field) === 'address') {
                    $field = $this->cleanField($field);
                    $value = $quote->$addressMethod()->getData($field);
                }
            }
        }

        return is_array($value) ? '' : $value;
    }

    /**
     * @param CartInterface $quote
     * @param string $addressType
     * @return string
     */
    public function getAddressStreet($quote, $addressType = 'billing')
    {
        $field = $this->customerAttributesConfig->getAddressStreet();
        $field = $this->cleanField($field);
        $addressType = ucfirst($addressType);
        $addressMethod = "get{$addressType}Address";

        if (strpos($field, 'street_') !== false) {
            $street = $quote->$addressMethod()->getStreet();
            $line = (int)str_replace('street_', '', $field) - 1;
            $value = $street[$line] ?? null;
        } else {
            $value = $quote->$addressMethod()->getData($field);
        }

        return is_array($value) ? '' : $value;
    }

    /**
     * @param CartInterface $quote
     * @param string $addressType
     * @return string
     */
    public function getAddressStreetNumber($quote, $addressType = 'billing')
    {
        $field = $this->customerAttributesConfig->getAddressStreetNumber();
        $field = $this->cleanField($field);
        $addressType = ucfirst($addressType);
        $addressMethod = "get{$addressType}Address";

        if (strpos($field, 'street_') !== false) {
            $street = $quote->$addressMethod()->getStreet();
            $line = (int)str_replace('street_', '', $field) - 1;
            $value = $street[$line] ?? null;
        } else {
            $value = $quote->$addressMethod()->getData($field);
        }

        return is_array($value) ? '' : $value;
    }

    /**
     * @param CartInterface $quote
     * @param string $addressType
     * @return string
     */
    public function getAddressComplementary($quote, $addressType = 'billing')
    {
        $field = $this->customerAttributesConfig->getAddressComplementary();
        $field = $this->cleanField($field);
        $addressType = ucfirst($addressType);
        $addressMethod = "get{$addressType}Address";

        if (strpos($field, 'street_') !== false) {
            $street = $quote->$addressMethod()->getStreet();
            $line = (int)str_replace('street_', '', $field) - 1;
            $value = $street[$line] ?? null;
        } else {
            $value = $quote->$addressMethod()->getData($field);
        }

        return is_array($value) ? '' : $value;
    }

    /**
     * @param CartInterface $quote
     * @param string $addressType
     * @return string
     */
    public function getAddressNeighbordhood($quote, $addressType = 'billing')
    {
        $field = $this->customerAttributesConfig->getAddressNeighbordhood();
        $field = $this->cleanField($field);
        $addressType = ucfirst($addressType);
        $addressMethod = "get{$addressType}Address";

        if (strpos($field, 'street_') !== false) {
            $street = $quote->$addressMethod()->getStreet();
            $line = (int)str_replace('street_', '', $field) - 1;
            $value = $street[$line] ?? null;
        } else {
            $value = $quote->$addressMethod()->getData($field);
        }

        return is_array($value) ? '' : $value;
    }

    /**
     * @param $field
     * @return string
     */
    private function _getFieldType($field)
    {
        $type = 'customer';
        if (strpos($field, 'address_') === 0) {
            $type = 'address';
        }
        return $type;
    }

    /**
     * @param $field
     * @return array|string|string[]|null
     */
    private function cleanField($field)
    {
        $field = preg_replace('/^customer_/', '', $field);
        $field = preg_replace('/^address_/', '', $field);
        return $field;
    }

    /**
     * @param $customer
     * @param $billingAddress
     * @param $field
     */
    private function extractCustomerFieldValue($customer, $field)
    {
        $value = null;

        $customer = $this->customerFactory->create()->load($customer->getId());

        if ($customer->getData($field)) {
            $value = $customer->getData($field);
        }

        if ($customer->getCustomAttribute($field) && $customer->getCustomAttribute($field)->getValue()) {
            $value = $customer->getCustomAttribute($field)->getValue();
        }

        return $value;
    }
}
