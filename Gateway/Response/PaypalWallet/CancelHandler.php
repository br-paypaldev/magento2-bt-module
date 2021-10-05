<?php
namespace Paypal\BraintreeBrasil\Gateway\Response\PaypalWallet;

use Paypal\BraintreeBrasil\Gateway\Config\PaypalWallet\Config;
use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;

class CancelHandler implements HandlerInterface
{
    protected $logger;
    /**
     * @var Config
     */
    private $paypalWalletConfig;

    /**
     * AuthorizationHandler constructor.
     * @param Config $paypalWalletConfig
     * @param Logger $logger
     */
    public function __construct(
        Config $paypalWalletConfig,
        Logger $logger
    ) {
        $this->logger = $logger;
        $this->paypalWalletConfig = $paypalWalletConfig;
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
