<?php

namespace Paypal\BraintreeBrasil\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\QuoteRepository;
use Paypal\BraintreeBrasil\Api\CreditCardManagementInterface;
use Paypal\BraintreeBrasil\Api\Data\InstallmentInterfaceFactory;
use Paypal\BraintreeBrasil\Gateway\Config\CreditCard\Config as CreditCardConfig;
use Paypal\BraintreeBrasil\Gateway\Config\TwoCreditCards\Config as TwoCreditCardsConfig;

class CreditCardManagement implements CreditCardManagementInterface
{
    /**
     * @var InstallmentInterfaceFactory
     */
    private $installmentInterfaceFactory;
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;
    /**
     * @var CreditCardConfig
     */
    private $creditCardConfig;
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var TwoCreditCardsConfig
     */
    private $twoCreditCardsConfig;

    /**
     * @param Session $checkoutSession
     * @param TwoCreditCardsConfig $creditCardConfig
     * @param PriceCurrencyInterface $priceCurrency
     * @param QuoteRepository $quoteRepository
     * @param InstallmentInterfaceFactory $installmentInterfaceFactory
     * @param TwoCreditCardsConfig $twoCreditCardsConfig
     */
    public function __construct(
        Session $checkoutSession,
        CreditCardConfig $creditCardConfig,
        PriceCurrencyInterface $priceCurrency,
        QuoteRepository $quoteRepository,
        InstallmentInterfaceFactory $installmentInterfaceFactory,
        TwoCreditCardsConfig $twoCreditCardsConfig
    ) {
        $this->installmentInterfaceFactory = $installmentInterfaceFactory;
        $this->checkoutSession = $checkoutSession;
        $this->priceCurrency = $priceCurrency;
        $this->creditCardConfig = $creditCardConfig;
        $this->quoteRepository = $quoteRepository;
        $this->twoCreditCardsConfig = $twoCreditCardsConfig;
    }

    /**
     * @inheritDoc
     */
    public function getCreditcardInstallments($total)
    {
        if ($this->creditCardConfig->getEnableInstallments()) {
            return $this->getAvailableInstallments($total, $this->creditCardConfig->getInstallmentsConfiguration());
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function getTwoCreditcardsInstallments($total)
    {
        if ($this->twoCreditCardsConfig->getEnableInstallments()) {
            return $this->getAvailableInstallments($total, $this->twoCreditCardsConfig->getInstallmentsConfiguration());
        }

        return [];
    }

    /**
     * Return available installments for current quote
     *
     * @param float $total
     * @param array $installmentsConfiguration
     * @return \Paypal\BraintreeBrasil\Api\Data\InstallmentInterface[]
     */
    public function getAvailableInstallments($total, $installmentsConfiguration)
    {
        $result = [];

        usort($installmentsConfiguration, function ($a, $b) {
            if ((int)$a['installment'] > (int)$b['installment']) {
                return 1;
            }
            if ((int)$a['installment'] < (int)$b['installment']) {
                return -1;
            }
            return 0;
        });

        foreach ($installmentsConfiguration as $configItem) {
            $interestRate = (float)$configItem['interest_rate'];
            $interestCost = ($interestRate / 100) * $total;
            $installmentNumber = (int)$configItem['installment'];
            $totalCost = $total + $interestCost;
            $installmentPrice = $totalCost / $installmentNumber;
            $minValue = (float)$configItem['min_value'];

            if ($installmentPrice >= $minValue) {
                $installment = $this->installmentInterfaceFactory->create();

                if (!$interestRate) {
                    $installment->setLabel(
                        sprintf(
                            __('%sx of %s without interest'),
                            $installmentNumber,
                            $this->priceCurrency->format($installmentPrice, false)
                        )
                    );
                } else {
                    $installment->setLabel(
                        sprintf(
                            __('%sx of %s with interest (total cost %s)'),
                            $installmentNumber,
                            $this->priceCurrency->format($installmentPrice, false),
                            $this->priceCurrency->format($totalCost, false)
                        )
                    );
                }

                $installment->setValue($installmentNumber);
                $installment->setInterestRate($interestCost);
                $installment->setTotalCost($totalCost);

                $result[] = $installment;
            }
        }

        return $result;
    }

    /**
     * @param int $installments
     * @param string $column
     * @return bool
     */
    public function saveSelectedInstallments($installments, $column = 'creditcard_installments')
    {
        $quote = $this->checkoutSession->getQuote();

        //prevent update other columns
        if ($column === 'creditcard_installments' || $column === 'second_creditcard_installments') {
            $method = 'set' . str_replace('_', '', ucwords($column, '_'));
            $quote->$method($installments);
            $this->quoteRepository->save($quote);
        }

        return true;
    }
}
