<?php
namespace Paypal\BraintreeBrasil\Gateway\Response\DebitCard;

use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Model\PaymentTokenRepository;
use Magento\Framework\Event\Manager;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;

class AuthorizationHandler implements HandlerInterface
{
    protected $logger;
    /**
     * @var PaymentTokenRepository
     */
    private $paymentTokenRepository;
    /**
     * @var Manager
     */
    private $eventManager;

    /**
     * AuthorizationHandler constructor.
     * @param PaymentTokenRepository $paymentTokenRepository
     * @param Manager $eventManager
     * @param Logger $logger
     */
    public function __construct(
        PaymentTokenRepository $paymentTokenRepository,
        Manager $eventManager,
        Logger $logger
    ) {
        $this->logger = $logger;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->eventManager = $eventManager;
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

        $paymentResult = $response['payment_result'];

        try {
            // add payment token data to additional information
            if($response['payment_token_id']){
                $paymentToken = $this->paymentTokenRepository->get($response['payment_token_id']);
                $payment->setAdditionalInformation('dc_type', $paymentToken->getCardBrand());
                $payment->setAdditionalInformation('dc_last', $paymentToken->getCardLastFour());
            }

            if($response['save_dc']){
                $this->eventManager->dispatch('braintree_brasil_debitcard_save_payment_token', [
                    'braintree_transaction' => $paymentResult->transaction
                ]);
            }

            $payment->setTransactionId($paymentResult->transaction->id);
            $payment->setCcTransId($paymentResult->transaction->id);
            $payment->setLastTransId($paymentResult->transaction->id);

            $payment->setIsTransactionPending(false);

            $payment->setIsTransactionClosed(false);
            $payment->setShouldCloseParentTransaction(false);

            $payment->getOrder()->setCanSendNewEmailFlag(true);
        } catch (\Exception $e) {
            $this->logger->info('AUTHORIZATION HANDLER ERROR', [$e->getMessage()]);
        }
    }
}
