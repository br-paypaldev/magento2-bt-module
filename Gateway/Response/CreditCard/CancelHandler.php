<?php
namespace Paypal\BraintreeBrasil\Gateway\Response\CreditCard;

use Paypal\BraintreeBrasil\Gateway\Config\CreditCard\Config;
use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Model\CreditCardManagement;
use Paypal\BraintreeBrasil\Observer\CreditCard\DataAssignObserver;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;

class CancelHandler implements HandlerInterface
{
    protected $logger;
    /**
     * @var Config
     */
    private $creditCardConfig;

    /**
     * AuthorizationHandler constructor.
     * @param Config $creditCardConfig
     * @param Logger $logger
     */
    public function __construct(
        Config $creditCardConfig,
        Logger $logger
    ) {
        $this->logger = $logger;
        $this->creditCardConfig = $creditCardConfig;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $payment = SubjectReader::readPayment($handlingSubject);
        $payment = $payment->getPayment();

        $this->logger->info('CANCEL HANDLER', [$response]);

        $cancelResult = $response['cancel_result'];

        try {
            if($cancelResult->success){
                $payment->setIsTransactionPending(false);
                $payment->setIsTransactionClosed(true);
                $payment->setShouldCloseParentTransaction(true);
            }
        } catch (\Exception $e) {
            $this->logger->info('CANCEL HANDLER ERROR', [$e->getMessage()]);
        }
    }
}
