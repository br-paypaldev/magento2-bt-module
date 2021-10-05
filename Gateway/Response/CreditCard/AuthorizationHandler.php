<?php
namespace Paypal\BraintreeBrasil\Gateway\Response\CreditCard;

use Paypal\BraintreeBrasil\Gateway\Config\CreditCard\Config;
use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Model\CreditCardManagement;
use Paypal\BraintreeBrasil\Model\PaymentTokenRepository;
use Paypal\BraintreeBrasil\Model\Ui\CreditCard\ConfigProvider;
use Paypal\BraintreeBrasil\Observer\CreditCard\DataAssignObserver;
use Magento\Framework\Event\Manager;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;

class AuthorizationHandler implements HandlerInterface
{
    protected $logger;
    /**
     * @var Config
     */
    private $creditCardConfig;
    /**
     * @var CreditCardManagement
     */
    private $installmentsManagement;
    /**
     * @var Manager
     */
    private $eventManager;
    /**
     * @var PaymentTokenRepository
     */
    private $paymentTokenRepository;

    /**
     * AuthorizationHandler constructor.
     * @param Config $creditCardConfig
     * @param CreditCardManagement $installmentsManagement
     * @param PaymentTokenRepository $paymentTokenRepository
     * @param Manager $eventManager
     * @param Logger $logger
     */
    public function __construct(
        Config $creditCardConfig,
        CreditCardManagement $installmentsManagement,
        PaymentTokenRepository $paymentTokenRepository,
        Manager $eventManager,
        Logger $logger
    ) {
        $this->logger = $logger;
        $this->creditCardConfig = $creditCardConfig;
        $this->installmentsManagement = $installmentsManagement;
        $this->eventManager = $eventManager;
        $this->paymentTokenRepository = $paymentTokenRepository;
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

            // add payment token data to additional information
            if($response['payment_token_id']){
                $paymentToken = $this->paymentTokenRepository->get($response['payment_token_id']);
                $payment->setAdditionalInformation('cc_type', $paymentToken->getCardBrand());
                $payment->setAdditionalInformation('cc_last', $paymentToken->getCardLastFour());
            }

            // other transaction informations
            $payment->setTransactionId($paymentResult->transaction->id);
            $payment->setCcTransId($paymentResult->transaction->id);
            $payment->setLastTransId($paymentResult->transaction->id);
            $payment->setIsTransactionPending(false);

            $payment->setIsTransactionClosed(false);
            $payment->setShouldCloseParentTransaction(false);

            $payment->getOrder()->setCanSendNewEmailFlag(true);

            if($response['save_cc']){
                $this->eventManager->dispatch('braintree_brasil_creditcard_save_payment_token', [
                    'braintree_transaction' => $paymentResult->transaction
                ]);
            }

        } catch (\Exception $e) {
            $this->logger->info('AUTHORIZATION HANDLER ERROR', [$e->getMessage()]);
        }
    }
}
