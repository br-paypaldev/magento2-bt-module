<?php

namespace Paypal\BraintreeBrasil\Gateway\Request;

use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Model\Customer\FieldsMapper;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use Paypal\BraintreeBrasil\Traits\FormatFields;

/**
 * Class CustomerDataBuilder
 */
class CustomerDataBuilder implements BuilderInterface
{
    use FormatFields;

    private $logger;
    /**
     * @var FieldsMapper
     */
    private $fieldsMapper;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * CustomerDataBuilder constructor.
     *
     * @param Session $checkoutSession
     * @param Logger $logger
     */
    public function __construct(
        Session $checkoutSession,
        FieldsMapper $fieldsMapper,
        Logger $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->fieldsMapper = $fieldsMapper;
    }

    /**
     * Add shopper data into request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        /** @var PaymentDataObject $paymentDataObject */
        $paymentDataObject = SubjectReader::readPayment($buildSubject);

        $this->logger->info('Customer Data Builder');

        $payment = $paymentDataObject->getPayment();
        /** @var Order $order */
        $order = $payment->getOrder();
        $quote = $this->checkoutSession->getQuote();
        $billingAddress = $order->getBillingAddress();

        try {
            $cnpj = $this->fieldsMapper->getCustomerCnpj($quote);

            $request = [
                'customer' => [
                    'braintree_customer_id' => $cnpj ?: $this->fieldsMapper->getCustomerCpf($quote),
                    'firstname' => $order->getCustomerFirstname(),
                    'lastname' => $order->getCustomerLastname(),
                    'company' => $this->fieldsMapper->getCustomerCompany($quote),
                    'email' => $order->getCustomerEmail(),
                    'telephone' => $this->fieldsMapper->getCustomerTelephone($quote),
                    'fax' => $this->fieldsMapper->getCustomerFax($quote),
                    'website' => $this->fieldsMapper->getCustomerWebsite($quote)
                ],
                'billing' => [
                    'countryCodeAlpha2' => 'BR',
                    'countryCodeAlpha3' => 'BRA',
                    'countryCodeNumeric' => 76,
                    'countryName' => 'Brazil',
                    'firstName' => $billingAddress->getFirstname(),
                    'lastName' => $billingAddress->getLastname(),
                    'streetAddress' => $this->fieldsMapper->getAddressStreet($quote),
                    'extendedAddress' => implode(', ', [
                        $this->fieldsMapper->getAddressStreetNumber($quote),
                        $this->fieldsMapper->getAddressComplementary($quote),
                        $this->fieldsMapper->getAddressNeighbordhood($quote)
                    ]),
                    'locality' => $billingAddress->getCity(),
                    'postalCode' => $this->onlyNumbers($billingAddress->getPostcode()),
                    'region' => $billingAddress->getRegion()
                ]
            ];

            if (!$order->getIsVirtual()) {
                $shippingAddress = $order->getShippingAddress();
                $request['shipping'] = [
                    'countryCodeAlpha2' => 'BR',
                    'countryCodeAlpha3' => 'BRA',
                    'countryCodeNumeric' => 76,
                    'countryName' => 'Brazil',
                    'firstName' => $shippingAddress->getFirstname(),
                    'lastName' => $shippingAddress->getLastname(),
                    'streetAddress' => $this->fieldsMapper->getAddressStreet($quote, 'shipping'),
                    'extendedAddress' => implode(', ', [
                        $this->fieldsMapper->getAddressStreetNumber($quote, 'shipping'),
                        $this->fieldsMapper->getAddressComplementary($quote, 'shipping'),
                        $this->fieldsMapper->getAddressNeighbordhood($quote, 'shipping')
                    ]),
                    'locality' => $shippingAddress->getCity(),
                    'postalCode' => $this->onlyNumbers($shippingAddress->getPostcode()),
                    'region' => $shippingAddress->getRegion()
                ];
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        return $request;
    }
}
