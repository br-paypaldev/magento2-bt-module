<?php
namespace Paypal\BraintreeBrasil\Model;

use Paypal\BraintreeBrasil\Api\Data\InstallmentInterfaceFactory;
use Paypal\BraintreeBrasil\Api\CreditCardManagementInterface;
use Paypal\BraintreeBrasil\Gateway\Config\CreditCard\Config;
use Magento\Checkout\Model\Session;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\QuoteRepository;

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
     * @var Config
     */
    private $creditCardConfig;
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * InstallmentsManagement constructor.
     * @param Session $checkoutSession
     * @param Config $creditCardConfig
     * @param PriceCurrencyInterface $priceCurrency
     * @param QuoteRepository $quoteRepository
     * @param InstallmentInterfaceFactory $installmentInterfaceFactory
     */
    public function __construct
    (
        Session $checkoutSession,
        Config $creditCardConfig,
        PriceCurrencyInterface $priceCurrency,
        QuoteRepository $quoteRepository,
        InstallmentInterfaceFactory $installmentInterfaceFactory
    )
    {
        $this->installmentInterfaceFactory = $installmentInterfaceFactory;
        $this->checkoutSession = $checkoutSession;
        $this->priceCurrency = $priceCurrency;
        $this->creditCardConfig = $creditCardConfig;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Return available installments for current quote
     *
     * @param float $total
     * @return \Paypal\BraintreeBrasil\Api\Data\InstallmentInterface[]
     */
    public function getAvailableInstallments($total)
    {
        $result = [];

        if($this->creditCardConfig->getEnableInstallments()){
            $installmentsConfiguration = $this->creditCardConfig->getInstallmentsConfiguration();

            if(!$installmentsConfiguration){
                return $result;
            }

            usort($installmentsConfiguration, function($a, $b){
                if((int)$a['installment'] > (int)$b['installment']) return 1;
                if((int)$a['installment'] < (int)$b['installment']) return -1;
                return 0;
            });

            foreach($installmentsConfiguration as $configItem){

                $interestRate = (float)$configItem['interest_rate'];
                $interestCost = ($interestRate / 100) * $total;
                $installmentNumber = (int)$configItem['installment'];
                $totalCost = $total + $interestCost;
                $installmentPrice = $totalCost / $installmentNumber;
                $minValue = (float)$configItem['min_value'];

                if($total >= $minValue){
                    $installment = $this->installmentInterfaceFactory->create();

                    if(!$interestRate){
                        $installment->setLabel(sprintf(
                            __('%sx of %s without interest'),
                            $installmentNumber,
                            $this->priceCurrency->format($installmentPrice, false)
                        ));
                    } else {
                        $installment->setLabel(sprintf(
                            __('%sx of %s with interest (total cost %s)'),
                            $installmentNumber,
                            $this->priceCurrency->format($installmentPrice, false),
                            $this->priceCurrency->format($totalCost, false)
                        ));
                    }

                    $installment->setValue($installmentNumber);
                    $installment->setInterestRate($interestCost);
                    $installment->setTotalCost($totalCost);

                    $result[] = $installment;
                }
            }
        }

        return $result;
    }

    /**
     * @param int $installments
     * @return bool
     */
    public function saveSelectedInstallments($installments)
    {
        $quote = $this->checkoutSession->getQuote();

        $quote->setCreditcardInstallments($installments);
        $this->quoteRepository->save($quote);

        $this->checkoutSession->setBraintreeCreditCardInstallments($installments);

        return true;
    }
}
