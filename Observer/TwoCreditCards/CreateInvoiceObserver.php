<?php

namespace Paypal\BraintreeBrasil\Observer\TwoCreditCards;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Paypal\BraintreeBrasil\Gateway\Config\TwoCreditCards\Config as TwoCreditCardsConfig;
use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Model\Config\Source\PaymentAction;
use Paypal\BraintreeBrasil\Service\Invoice;
use Paypal\BraintreeBrasil\Model\Ui\TwoCreditCards\ConfigProvider;

class CreateInvoiceObserver implements ObserverInterface
{
    /**
     * @var Invoice
     */
    private $invoice;

    /**
     * @var TwoCreditCardsConfig
     */
    private $twoCreditCardsConfig;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Invoice $invoice
     * @param TwoCreditCardsConfig $config
     * @param Logger $logger
     */
    public function __construct(
        Invoice $invoice,
        TwoCreditCardsConfig $twoCreditCardsConfig,
        Logger $logger
    ) {
        $this->invoice = $invoice;
        $this->twoCreditCardsConfig = $twoCreditCardsConfig;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getData('order');
        $payment = $order->getPayment();
        try {
            if ($payment->getMethod() === ConfigProvider::CODE
                && $this->twoCreditCardsConfig->getCaptureAfterAuthorize()
                === PaymentAction::PAYMENT_ACTION_AUTHORIZE_CAPTURE) {
                $this->invoice->handle($order);
            }
        } catch (\Exception $exception) {
            $this->logger->error(__("Cannot create invoice for Order %1 on observer", $order->getIncrementId()));
        }
    }
}
