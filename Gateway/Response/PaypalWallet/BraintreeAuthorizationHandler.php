<?php

namespace Paypal\BraintreeBrasil\Gateway\Response\PaypalWallet;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Paypal\BraintreeBrasil\Logger\Logger;

class BraintreeAuthorizationHandler implements HandlerInterface
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * BraintreeAuthorizationHandler constructor.
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

        $this->logger->info('BRAINTREE AUTHORIZATION HANDLER', [$response]);

        $braintreePaymentResult = $response['braintree_result'] ?? null;

        try {
            if ($braintreePaymentResult) {
                $payment->setAdditionalInformation(
                    'paypalPayerId',
                    $braintreePaymentResult->transaction->paypal['payerId']
                );
                $payment->setAdditionalInformation(
                    'paypalPayerEmail',
                    $braintreePaymentResult->transaction->paypal['payerEmail']
                );

                $payment->setTransactionId($braintreePaymentResult->transaction->id);
                $payment->setCcTransId($braintreePaymentResult->transaction->id);
                $payment->setLastTransId($braintreePaymentResult->transaction->id);

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
