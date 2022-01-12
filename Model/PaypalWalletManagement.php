<?php

declare(strict_types=1);

namespace Paypal\BraintreeBrasil\Model;

use Paypal\BraintreeBrasil\Api\Data\InstallmentInterfaceFactory;
use Paypal\BraintreeBrasil\Api\PaypalWalletManagementInterface;
use Paypal\BraintreeBrasil\Gateway\Config\Config;
use Paypal\BraintreeBrasil\Gateway\Config\PaypalWallet\Config as PaypalWalletConfig;
use Paypal\BraintreeBrasil\Gateway\Http\Client;
use Paypal\BraintreeBrasil\Model\Customer\FieldsMapper;
use Paypal\BraintreeBrasil\Service\GetCurrentAvailableInstallments;
use Paypal\BraintreeBrasil\Service\GetInstallments;
use Paypal\BraintreeBrasil\Service\PaypalGraphQL;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\QuoteRepository;

class PaypalWalletManagement implements PaypalWalletManagementInterface
{
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var FieldsMapper
     */
    private $fieldsMapper;
    /**
     * @var Client
     */
    private $braintreeClient;
    /**
     * @var PaypalGraphQL
     */
    private $paypalGraphQL;
    /**
     * @var Config
     */
    private $braintreeConfig;
    /**
     * @var InstallmentInterfaceFactory
     */
    private $installmentFactory;
    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;
    /**
     * @var PaypalWalletConfig
     */
    private $paypalWalletConfig;
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;
    /**
     * @var GetCurrentAvailableInstallments
     */
    private $getCurrentAvailableInstallments;
    /**
     * @var GetInstallments
     */
    private $getInstallments;

    /**
     * PaypalWalletManagement constructor.
     * @param Config $braintreeConfig
     * @param Session $checkoutSession
     * @param FieldsMapper $fieldsMapper
     * @param PaypalGraphQL $paypalGraphQL
     * @param GetInstallments $getInstallments
     * @param PriceCurrencyInterface $priceCurrency
     * @param PaypalWalletConfig $paypalWalletConfig
     * @param InstallmentInterfaceFactory $installmentFactory
     * @param QuoteRepository $quoteRepository
     * @param GetCurrentAvailableInstallments $getCurrentAvailableInstallments
     * @param Client $braintreeClient
     */
    public function __construct(
        Config $braintreeConfig,
        Session $checkoutSession,
        FieldsMapper $fieldsMapper,
        PaypalGraphQL $paypalGraphQL,
        GetInstallments $getInstallments,
        PriceCurrencyInterface $priceCurrency,
        PaypalWalletConfig $paypalWalletConfig,
        InstallmentInterfaceFactory $installmentFactory,
        QuoteRepository $quoteRepository,
        GetCurrentAvailableInstallments $getCurrentAvailableInstallments,
        Client $braintreeClient
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->fieldsMapper = $fieldsMapper;
        $this->braintreeClient = $braintreeClient;
        $this->paypalGraphQL = $paypalGraphQL;
        $this->braintreeConfig = $braintreeConfig;
        $this->installmentFactory = $installmentFactory;
        $this->priceCurrency = $priceCurrency;
        $this->paypalWalletConfig = $paypalWalletConfig;
        $this->quoteRepository = $quoteRepository;
        $this->getCurrentAvailableInstallments = $getCurrentAvailableInstallments;
        $this->getInstallments = $getInstallments;
    }

    /**
     * @param string $payment_method_nonce
     * @return bool
     */
    public function savePaypalPaymentMethod($payment_method_nonce)
    {
        $quote = $this->checkoutSession->getQuote();

        $cnpj = $this->fieldsMapper->getCustomerCnpj($quote);
        $braintree_customer_id = $cnpj ?: $this->fieldsMapper->getCustomerCpf($quote);
        $firstname = $quote->getBillingAddress()->getFirstname();
        $lastname = $quote->getBillingAddress()->getLastname();
        $company = $this->fieldsMapper->getCustomerCompany($quote);
        $email = $quote->getBillingAddress()->getEmail();
        $telephone = $this->fieldsMapper->getCustomerTelephone($quote);
        $fax = $this->fieldsMapper->getCustomerFax($quote);

        $this->braintreeClient->createBraintreeCustomerIfNotExists(
            $braintree_customer_id,
            $firstname,
            $lastname,
            $email,
            $telephone,
            $fax,
            $company
        );

        // create payment method from agreement
        $paymentMethodResult = $this->braintreeClient
            ->getBraintreeClient()
            ->paymentMethod()
            ->create(['customerId' => $braintree_customer_id, 'paymentMethodNonce' => $payment_method_nonce]);

        // save for later use, on authorization transaction and installments query
        $this->checkoutSession->setPaypalWalletPaymentMethod($paymentMethodResult->paymentMethod);

        return true;
    }

    /**
     * @param int $installments
     * @return bool
     */
    public function saveSelectedInstallments($installments)
    {
        $quote = $this->checkoutSession->getQuote();

        $quote->setPaypalwalletInstallments($installments);
        $this->quoteRepository->save($quote);

        $this->checkoutSession->setPaypalWalletInstallments($installments);

        // call paypal graphql service
        $total = $quote->getGrandTotal() - $quote->getInstallmentsInterestRate();
        $paymentMethod = $this->checkoutSession->getPaypalWalletPaymentMethod();

        if (!$paymentMethod) {
            return false;
        }

        $paymentMethodId = $paymentMethod->globalId;
        $isSandbox = $this->braintreeConfig->getIntegrationMode() === Config::SANDBOX_INTEGRATION_MODE;
        $content = $this->getInstallments->execute($paymentMethodId, $total, $isSandbox);

        if (!isset($content->data->paypalFinancingOptions->financingOptions)) {
            $this->checkoutSession->setPaypalWalletFinancingOption(null);
            throw new LocalizedException(__('Invalid installments query result'));
        }

        // send to checkout session
        $installmentOptions = $content->data->paypalFinancingOptions->financingOptions[0]->qualifyingFinancingOptions;
        foreach ($installmentOptions as $option) {
            if ($option->term == $installments) {
                $this->checkoutSession->setPaypalWalletFinancingOption($option);
                break;
            }
        }

        return true;
    }

    /**
     * @param float $total
     * @return \Paypal\BraintreeBrasil\Api\Data\InstallmentInterface[]
     */
    public function getAvailableInstallments($total)
    {
        if ($this->getCurrentAvailableInstallments->getAvailableInstallments()) {
            return $this->getCurrentAvailableInstallments->getAvailableInstallments();
        }

        $enableInstallments = $this->paypalWalletConfig->getEnableInstallments();
        $maxInstallments = $this->paypalWalletConfig->getMaxInstallments();

        if (!$enableInstallments) {
            return [];
        }

        $paymentMethod = $this->checkoutSession->getPaypalWalletPaymentMethod();

        if (!$paymentMethod) {
            return [];
        }

        // call paypal graphql service
        $paymentMethodId = $paymentMethod->globalId;
        $isSandbox = $this->braintreeConfig->getIntegrationMode() === Config::SANDBOX_INTEGRATION_MODE;
        $content = $this->getInstallments->execute($paymentMethodId, $total, $isSandbox);

        $result = [];

        if (!isset($content->data->paypalFinancingOptions->financingOptions)) {
            return $result;
        }

        $installmentOptions = $content->data->paypalFinancingOptions->financingOptions[0]->qualifyingFinancingOptions;
        foreach ($installmentOptions as $option) {
            if ($option->term > $maxInstallments) {
                break;
            }

            $installment = $this->installmentFactory->create();

            $installmentPrice = $this->priceCurrency->format($option->monthlyPayment->value, false);
            $totalCost = $this->priceCurrency->format($option->totalCost->value, false);
            $label = sprintf((string)__("%sx of %s without interest"), $option->term, $installmentPrice);

            if ($option->monthlyInterestRate) {
                $label = sprintf(
                    (string)__("%sx of %s with interest (total cost %s)"),
                    $option->term,
                    $installmentPrice,
                    $totalCost
                );
            }

            $installment->setLabel($label);
            $installment->setValue($option->term);
            $installment->setInterestRate($option->totalInterest->value);
            $installment->setTotalCost($option->totalCost->value);

            $result[] = $installment;
        }

        $this->getCurrentAvailableInstallments->setAvailableInstallments($result);

        return $result;
    }
}
