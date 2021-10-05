<?php
namespace Paypal\BraintreeBrasil\Gateway\Response\PaypalWallet;

use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Observer\PaypalWallet\DataAssignObserver;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;

class PaypalChargeAuthorizationHandler implements HandlerInterface
{
    protected $logger;

    /**
     * PaypalChargeAuthorizationHandler constructor.
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $payment = SubjectReader::readPayment($handlingSubject);
        $payment = $payment->getPayment();

        $this->logger->info('PAYPAL CHARGE AUTHORIZATION HANDLER', [$response]);

        $paypalChargePaymentResult = $response['paypal_charge_result'] ?? null;

        try {
            if ($paypalChargePaymentResult) {
                $payment->setAdditionalInformation(
                    'paypalPayerId',
                    $paypalChargePaymentResult->data
                        ->chargePayPalAccount
                        ->transaction
                        ->paymentMethodSnapshot
                        ->payer->payerId
                );
                $payment->setAdditionalInformation(
                    'paypalPayerEmail',
                    $paypalChargePaymentResult->data
                        ->chargePayPalAccount
                        ->transaction
                        ->paymentMethodSnapshot
                        ->payer->email
                );
                $payment->setAdditionalInformation(
                    'transactionId',
                    $paypalChargePaymentResult->data
                        ->chargePayPalAccount
                        ->transaction
                        ->id
                );
                $payment->setAdditionalInformation(
                    'legacyId',
                    $paypalChargePaymentResult->data
                        ->chargePayPalAccount
                        ->transaction
                        ->legacyId
                );

                $paymentId = $paypalChargePaymentResult->data
                    ->chargePayPalAccount
                    ->transaction
                    ->legacyId;
                $payment->setTransactionId($paymentId);
                $payment->setCcTransId($paymentId);
                $payment->setLastTransId($paymentId);

                // save used installments information
                $installments = (int)$payment->getAdditionalInformation(DataAssignObserver::INSTALLMENTS);
                $total = $payment->getOrder()->getGrandTotal();

                $payment->setAdditionalInformation(
                    'installments_interest_rate',
                    $payment->getOrder()->getInstallmentsInterestRate()
                );
                $payment->setAdditionalInformation(
                    'installments_value',
                    ($total / $installments)
                );

                $payment->setIsTransactionPending(false);
                $payment->setIsTransactionClosed(false);
                $payment->setShouldCloseParentTransaction(false);

                $payment->getOrder()->setCanSendNewEmailFlag(true);
            }
        } catch (\Exception $e) {
            $this->logger->info('AUTHORIZATION HANDLER ERROR', [$e->getMessage()]);
        }
    }
}
