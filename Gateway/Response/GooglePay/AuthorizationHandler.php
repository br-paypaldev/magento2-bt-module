<?php

namespace Paypal\BraintreeBrasil\Gateway\Response\GooglePay;

use Exception;
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

        $this->logger->info('BRAINTREE AUTHORIZATION HANDLER', [$response]);

        $paymentResult = $response['payment_result'] ?? null;

        try {
            if ($paymentResult) {
                // other transaction informations
                $payment->setTransactionId($paymentResult->transaction->id);
                $payment->setCcTransId($paymentResult->transaction->id);
                $payment->setLastTransId($paymentResult->transaction->id);
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
