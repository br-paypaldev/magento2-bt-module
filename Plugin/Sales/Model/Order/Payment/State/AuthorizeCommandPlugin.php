<?php
namespace Paypal\BraintreeBrasil\Plugin\Sales\Model\Order\Payment\State;

use Paypal\BraintreeBrasil\Gateway\Config\CreditCard\Config as CreditCardConfig;
use Paypal\BraintreeBrasil\Gateway\Config\DebitCard\Config as DebitCardConfig;
use Paypal\BraintreeBrasil\Gateway\Config\PaypalWallet\Config as PaypalWalletConfig;
use Paypal\BraintreeBrasil\Gateway\Config\TwoCreditCards\Config as TwoCreditCardsConfig;
use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Model\Config\Source\PaymentAction;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\State\CommandInterface as BaseCommandInterface;
use Paypal\BraintreeBrasil\Model\Ui\CreditCard\ConfigProvider as CreditCardConfigProvider;
use Paypal\BraintreeBrasil\Model\Ui\DebitCard\ConfigProvider as DebitCardConfigProvider;
use Paypal\BraintreeBrasil\Model\Ui\PaypalWallet\ConfigProvider as PaypalWalletConfigProvider;
use Paypal\BraintreeBrasil\Model\Ui\TwoCreditCards\ConfigProvider as TwoCreditCardsConfigProvider;

class AuthorizeCommandPlugin
{
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
     * @var TwoCreditCardsConfig
     */
    private $twoCreditCardsConfig;

    /**
     * @param CreditCardConfig $creditCardConfig
     * @param DebitCardConfig $debitCardConfig
     * @param PaypalWalletConfig $paypalWalletConfig
     * @param TwoCreditCardsConfig $twoCreditCardsConfig
     */
    public function __construct(
        CreditCardConfig $creditCardConfig,
        DebitCardConfig $debitCardConfig,
        PaypalWalletConfig $paypalWalletConfig,
        TwoCreditCardsConfig $twoCreditCardsConfig
    ) {
        $this->creditCardConfig = $creditCardConfig;
        $this->debitCardConfig = $debitCardConfig;
        $this->paypalWalletConfig = $paypalWalletConfig;
        $this->twoCreditCardsConfig = $twoCreditCardsConfig;
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

        if ($payment->getMethod() === CreditCardConfigProvider::CODE) {
            if ($this->creditCardConfig->getPaymentAction() === PaymentAction::PAYMENT_ACTION_AUTHORIZE) {
                $orderState = Order::STATE_NEW;

                if ($orderState && $order->getState() == Order::STATE_PROCESSING) {
                    $order->setState($orderState)
                        ->setStatus($order->getConfig()->getStateDefaultStatus($orderState));
                }
            }
        }

        if ($payment->getMethod() === DebitCardConfigProvider::CODE) {
            if ($this->debitCardConfig->getPaymentAction() === PaymentAction::PAYMENT_ACTION_AUTHORIZE) {
                $orderState = Order::STATE_NEW;

                if ($orderState && $order->getState() == Order::STATE_PROCESSING) {
                    $order->setState($orderState)
                        ->setStatus($order->getConfig()->getStateDefaultStatus($orderState));
                }
            }
        }

        if ($payment->getMethod() === PaypalWalletConfigProvider::CODE) {
            if ($this->paypalWalletConfig->getPaymentAction() === PaymentAction::PAYMENT_ACTION_AUTHORIZE) {
                $orderState = Order::STATE_NEW;

                if ($orderState && $order->getState() == Order::STATE_PROCESSING) {
                    $order->setState($orderState)
                        ->setStatus($order->getConfig()->getStateDefaultStatus($orderState));
                }
            }
        }

        if ($payment->getMethod() === TwoCreditCardsConfigProvider::CODE) {
            if ($this->twoCreditCardsConfig->getCaptureAfterAuthorize() === PaymentAction::PAYMENT_ACTION_AUTHORIZE) {
                $orderState = Order::STATE_NEW;
                $order->setState($orderState)->setStatus($order->getConfig()->getStateDefaultStatus($orderState));
            }
        }

        return $result;
    }
}
