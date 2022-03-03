<?php

namespace Paypal\BraintreeBrasil\Gateway\Response\TwoCreditCards;

use Magento\Framework\Event\Manager;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Paypal\BraintreeBrasil\Api\CreditCardManagementInterface;
use Paypal\BraintreeBrasil\Api\Data\InstallmentInterface;
use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Model\PaymentTokenRepository;
use Paypal\BraintreeBrasil\Observer\TwoCreditCards\DataAssignObserver;

class AuthorizationHandler implements HandlerInterface
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Manager
     */
    private $eventManager;

    /**
     * @var PaymentTokenRepository
     */
    private $paymentTokenRepository;

    /**
     * @var CreditCardManagementInterface
     */
    private $creditCardManagement;

    /**
     * @param PaymentTokenRepository $paymentTokenRepository
     * @param Manager $eventManager
     * @param Logger $logger
     * @param CreditCardManagementInterface $creditCardManagement
     */
    public function __construct(
        PaymentTokenRepository $paymentTokenRepository,
        Manager $eventManager,
        Logger $logger,
        CreditCardManagementInterface $creditCardManagement
    ) {
        $this->logger = $logger;
        $this->eventManager = $eventManager;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->creditCardManagement = $creditCardManagement;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $payment = SubjectReader::readPayment($handlingSubject);
        $payment = $payment->getPayment();

        $this->logger->info('AUTHORIZATION HANDLER', [$response]);
        $card1Additional = $payment->getAdditionalInformation('card_1');
        $card2Additional = $payment->getAdditionalInformation('card_2');

        $card1Result = $response['payment_result']['card_1'];
        $card2Result = $response['payment_result']['card_2'];

        try {
            $card1Installment = $this->getCardInstallmentData(
                $card1Additional[DataAssignObserver::AMOUNT],
                $card1Additional[DataAssignObserver::INSTALLMENTS]
            );
            $card1 = [
                'installments_interest_rate' => $card1Installment ? $card1Installment->getInterestRate() : 0,
                'installments_value' => $card1Installment
                    ? round($card1Installment->getTotalCost() / $card1Installment->getValue(), 2)
                    : $card1Additional[DataAssignObserver::AMOUNT],
                'transaction_id' => $card1Result->transaction->id,
                'total_cost' => $card1Installment
                    ? round($card1Installment->getTotalCost(), 2)
                    : $card1Additional[DataAssignObserver::AMOUNT],
                'can_capture_amount' => $card1Installment
                    ? round($card1Installment->getTotalCost(), 2)
                    : $card1Additional[DataAssignObserver::AMOUNT],
                'can_refund_amount' => $card1Installment
                    ? round($card1Installment->getTotalCost(), 2)
                    : $card1Additional[DataAssignObserver::AMOUNT],
            ];
            $card2Installment = $this->getCardInstallmentData(
                $card2Additional[DataAssignObserver::AMOUNT],
                $card2Additional[DataAssignObserver::INSTALLMENTS]
            );
            $card2 = [
                'installments_interest_rate' => $card2Installment ? $card2Installment->getInterestRate() : 0,
                'installments_value' => $card2Installment
                    ? round($card2Installment->getTotalCost() / $card2Installment->getValue(), 2)
                    : $card2Additional[DataAssignObserver::AMOUNT],
                'transaction_id' => $card2Result->transaction->id,
                'total_cost' => $card2Installment
                    ? round($card2Installment->getTotalCost(), 2)
                    : $card2Additional[DataAssignObserver::AMOUNT],
                'can_capture_amount' => $card2Installment
                    ? round($card2Installment->getTotalCost(), 2)
                    : $card2Additional[DataAssignObserver::AMOUNT],
                'can_refund_amount' => $card2Installment
                    ? round($card2Installment->getTotalCost(), 2)
                    : $card2Additional[DataAssignObserver::AMOUNT],
            ];

            // add payment token data to additional information
            if ($response['payment_token_ids']['card_1']) {
                $paymentToken = $this->paymentTokenRepository->get($response['payment_token_ids']['card_1']);
                $card1['cc_type'] = $paymentToken->getCardBrand();
                $card1['cc_last'] = $paymentToken->getCardLastFour();
            }
            if ($response['payment_token_ids']['card_2']) {
                $paymentToken = $this->paymentTokenRepository->get($response['payment_token_ids']['card_2']);
                $card2['cc_type'] = $paymentToken->getCardBrand();
                $card2['cc_last'] = $paymentToken->getCardLastFour();
            }

            $card1 = array_merge($card1Additional, $card1);
            $card2 = array_merge($card2Additional, $card2);
            $payment->setAdditionalInformation(['card_1' => $card1, 'card_2' => $card2]);

            // other transaction informations
            $payment->setTransactionId("{$card1Result->transaction->id}-{$card2Result->transaction->id}");
            $payment->setCcTransId("{$card1Result->transaction->id}-{$card2Result->transaction->id}");
            $payment->setLastTransId("{$card1Result->transaction->id}-{$card2Result->transaction->id}");

            $payment->setIsTransactionPending(true);
            $payment->setIsTransactionClosed(false);
            $payment->setShouldCloseParentTransaction(false);

            $payment->getOrder()->setCanSendNewEmailFlag(true);

            if ($response['save_cc']['card_1']) {
                $this->eventManager->dispatch('braintree_brasil_two_creditcards_save_payment_token', [
                    'braintree_transaction' => $card1Result->transaction
                ]);
            }
            if ($response['save_cc']['card_2']) {
                $this->eventManager->dispatch('braintree_brasil_two_creditcards_save_payment_token', [
                    'braintree_transaction' => $card2Result->transaction
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->info('AUTHORIZATION HANDLER ERROR', [$e->getMessage()]);
        }
    }

    /**
     * @param float $total
     * @param int $installmentValue
     * @return InstallmentInterface|null
     */
    private function getCardInstallmentData($total, $installmentValue)
    {
        $availableInstallments = $this->creditCardManagement->getTwoCreditcardsInstallments($total);
        $installmentObj = null;

        foreach ($availableInstallments as $installment) {
            if ($installment->getValue() === $installmentValue) {
                $installmentObj = $installment;
                break;
            }
        }
        return $installmentObj;
    }
}
