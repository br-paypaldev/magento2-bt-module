<?php
namespace Paypal\BraintreeBrasil\Plugin\Sales\Model\Order\Payment\State;

use Paypal\BraintreeBrasil\Gateway\Config\CreditCard\Config as CreditCardConfig;
use Paypal\BraintreeBrasil\Gateway\Config\DebitCard\Config as DebitCardConfig;
use Paypal\BraintreeBrasil\Gateway\Config\PaypalWallet\Config as PaypalWalletConfig;
use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Model\Config\Source\PaymentAction;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\State\CommandInterface as BaseCommandInterface;

class AuthorizeCommandPlugin
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var CreditCardConfig
     */
    private $creditCardConfig;
    /**
     * @var DebitCardConfig
     */
    private $debitCardConfig;
    /**
     * @var PaypalWalletConfig
     */
    private $paypalWalletConfig;

    /**
     * AuthorizeCommandPlugin constructor.
     * @param CreditCardConfig $creditCardConfig
     * @param Logger $logger
     */
    public function __construct(
        CreditCardConfig $creditCardConfig,
        DebitCardConfig $debitCardConfig,
        PaypalWalletConfig $paypalWalletConfig,
        Logger $logger
    ) {
        $this->logger = $logger;
        $this->creditCardConfig = $creditCardConfig;
        $this->debitCardConfig = $debitCardConfig;
        $this->paypalWalletConfig = $paypalWalletConfig;
    }

    /**
     * Set pending order status on order place
     * see https://github.com/magento/magento2/issues/5860
     *
     * @param BaseCommandInterface $subject
     * @param \Closure $proceed
     * @param OrderPaymentInterface $payment
     * @param $amount
     * @param OrderInterface $order
     * @return mixed
     */
    public function aroundExecute(
        BaseCommandInterface $subject,
        \Closure $proceed,
        OrderPaymentInterface $payment,
        $amount,
        OrderInterface $order
    ) {
        $result = $proceed($payment, $amount, $order);

        if ($payment->getMethod() === 'paypal_braintree_brasil_creditcard') {
            if ($this->creditCardConfig->getPaymentAction() === PaymentAction::PAYMENT_ACTION_AUTHORIZE) {
                $orderState = Order::STATE_NEW;

                if ($orderState && $order->getState() == Order::STATE_PROCESSING) {
                    $order->setState($orderState)
                        ->setStatus($order->getConfig()->getStateDefaultStatus($orderState));
                }
            }
        }

        if ($payment->getMethod() === 'paypal_braintree_brasil_debitcard') {
            if ($this->creditCardConfig->getPaymentAction() === PaymentAction::PAYMENT_ACTION_AUTHORIZE) {
                $orderState = Order::STATE_NEW;

                if ($orderState && $order->getState() == Order::STATE_PROCESSING) {
                    $order->setState($orderState)
                        ->setStatus($order->getConfig()->getStateDefaultStatus($orderState));
                }
            }
        }

        if ($payment->getMethod() === 'paypal_braintree_brasil_paypal_wallet') {
            if ($this->paypalWalletConfig->getPaymentAction() === PaymentAction::PAYMENT_ACTION_AUTHORIZE) {
                $orderState = Order::STATE_NEW;

                if ($orderState && $order->getState() == Order::STATE_PROCESSING) {
                    $order->setState($orderState)
                        ->setStatus($order->getConfig()->getStateDefaultStatus($orderState));
                }
            }
        }

        return $result;
    }
}
