<?php

namespace Paypal\BraintreeBrasil\Model\Total;

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Paypal\BraintreeBrasil\Api\CreditCardManagementInterface;
use Paypal\BraintreeBrasil\Api\PaypalWalletManagementInterface;
use Paypal\BraintreeBrasil\Model\Ui\CreditCard\ConfigProvider as ConfigProviderCreditCard;
use Paypal\BraintreeBrasil\Model\Ui\TwoCreditCards\ConfigProvider as ConfigProviderTwoCreditCards;
use Paypal\BraintreeBrasil\Model\Ui\PaypalWallet\ConfigProvider as ConfigProviderPaypalWallet;
use Paypal\BraintreeBrasil\Observer\TwoCreditCards\DataAssignObserver;

class InstallmentsInterestRate extends AbstractTotal
{
    /**
     * @var PaypalWalletManagementInterface
     */
    private $paypalWalletManagement;
    /**
     * @var CreditCardManagementInterface
     */
    private $creditCardManagement;

    /**
     * Custom constructor.
     * @param CreditCardManagementInterface $creditCardManagement
     * @param PaypalWalletManagementInterface $paypalWalletManagement
     */
    public function __construct(
        CreditCardManagementInterface $creditCardManagement,
        PaypalWalletManagementInterface $paypalWalletManagement
    ) {
        $this->setCode('installments_interest_rate');
        $this->paypalWalletManagement = $paypalWalletManagement;
        $this->creditCardManagement = $creditCardManagement;
    }

    /**
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        $items = $shippingAssignment->getItems();
        if (!count($items)) {
            return $this;
        }

        if ($quote->getPayment()->getMethod() === ConfigProviderCreditCard::CODE
            || $quote->getPayment()->getMethod() === ConfigProviderPaypalWallet::CODE
            || $quote->getPayment()->getMethod() === ConfigProviderTwoCreditCards::CODE) {
            $installments = $quote->getCreditcardInstallments();

            if ($quote->getPayment()->getMethod() === ConfigProviderPaypalWallet::CODE) {
                $installments = $quote->getPaypalwalletInstallments();
            }

            if ($quote->getPayment()->getMethod() === ConfigProviderTwoCreditCards::CODE) {
                $installments = max($quote->getCreditcardInstallments(), $quote->getSecondCreditcardInstallments());
            }

            if ($installments > 1) {
                $grandTotal = $this->calculateGrandTotalWithoutInterestRate($total);

                // Total installments interest rate cost
                $amount = $this->getInstallmentInterestRate($quote, $grandTotal);

                $total->setTotalAmount($this->getCode(), $amount);
                $total->setBaseTotalAmount($this->getCode(), $amount);
                $total->setInstallmentsInterestRate($amount);
                $quote->setData('installments_interest_rate', $amount);
            } else {
                $total->setInstallmentsInterestRate(0);
                $quote->setData('installments_interest_rate', 0);
            }
        } else {
            $total->setInstallmentsInterestRate(0);
            $quote->setData('installments_interest_rate', 0);
        }

        return $this;
    }

    /**
     * @param Total $total
     */
    protected function clearValues(Total $total)
    {
        $total->setTotalAmount('subtotal', 0);
        $total->setBaseTotalAmount('subtotal', 0);
        $total->setTotalAmount('tax', 0);
        $total->setBaseTotalAmount('tax', 0);
        $total->setTotalAmount('discount_tax_compensation', 0);
        $total->setBaseTotalAmount('discount_tax_compensation', 0);
        $total->setTotalAmount('shipping_discount_tax_compensation', 0);
        $total->setBaseTotalAmount('shipping_discount_tax_compensation', 0);
        $total->setInstallmentsInterestRate(0);
        $total->setSubtotalInclTax(0);
        $total->setBaseSubtotalInclTax(0);
    }

    /**
     * @param Quote $quote
     * @param Total $total
     * @return array
     */
    public function fetch(Quote $quote, Total $total)
    {
        return [
            'code' => $this->getCode(),
            'title' => __('Installments interest rate'),
            'value' => (float)$quote->getInstallmentsInterestRate()
        ];
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getLabel()
    {
        return __('Installments interest rate');
    }

    /**
     * Calculate total interest rate cost
     * @param Quote $quote
     * @param float $total
     * @return float
     */
    private function getInstallmentInterestRate($quote, $total)
    {
        $amount = 0;

        if ($quote->getPayment()->getMethod() === ConfigProviderPaypalWallet::CODE) {
            $amount = $this->getInterestRateAmount(
                $this->paypalWalletManagement->getAvailableInstallments($total),
                (int)$quote->getPaypalwalletInstallments()
            );
        } else {
            if ($quote->getPayment()->getMethod() === ConfigProviderCreditCard::CODE) {
                $amount = $this->getInterestRateAmount(
                    $this->creditCardManagement->getCreditcardInstallments($total),
                    (int)$quote->getCreditcardInstallments()
                );
            } elseif ($quote->getPayment()->getMethod() === ConfigProviderTwoCreditCards::CODE) {
                $card1Total = $quote->getPayment()->getAdditionalInformation(
                        'card_1'
                    )[DataAssignObserver::AMOUNT] ?? 0.0;
                $card2Total = $quote->getPayment()->getAdditionalInformation(
                        'card_2'
                    )[DataAssignObserver::AMOUNT] ?? 0.0;
                $card1AvailableInstallments = $this->creditCardManagement->getTwoCreditcardsInstallments($card1Total);
                $card2AvailableInstallments = $this->creditCardManagement->getTwoCreditcardsInstallments($card2Total);

                $amount += $this->getInterestRateAmount(
                    $card1AvailableInstallments,
                    (int)$quote->getCreditcardInstallments()
                );
                $amount += $this->getInterestRateAmount(
                    $card2AvailableInstallments,
                    (int)$quote->getSecondCreditcardInstallments()
                );
            } else {
                return $amount;
            }
        }


        return $amount;
    }

    /**
     * Calculate grand total without interest rate
     *
     * @param Total $total
     */
    private function calculateGrandTotalWithoutInterestRate(Total $total)
    {
        $grandTotal = 0;
        foreach ($total->getAllTotalAmounts() as $key => $amount) {
            $grandTotal = $grandTotal + (float)$amount;
        }
        return $grandTotal;
    }

    private function getInterestRateAmount($availableInstallments, $installmentNumber)
    {
        $amount = 0.0;

        foreach ($availableInstallments as $installment) {
            if ($installment->getValue() == $installmentNumber) {
                $amount = $installment->getInterestRate();
                break;
            }
        }

        return $amount;
    }
}
