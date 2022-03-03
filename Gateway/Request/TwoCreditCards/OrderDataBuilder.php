<?php
namespace Paypal\BraintreeBrasil\Gateway\Request\TwoCreditCards;

use Magento\Sales\Model\Order;
use Paypal\BraintreeBrasil\Api\CreditCardManagementInterface;
use Paypal\BraintreeBrasil\Gateway\Config\Config;
use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Paypal\BraintreeBrasil\Observer\TwoCreditCards\DataAssignObserver;

/**
 * Class OrderDataBuilder
 */
class OrderDataBuilder implements BuilderInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Config
     */
    private $baseConfig;

    private $creditCardManagement;

    /**
     * OrderDataBuilder constructor.
     *
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger,
        Config $baseConfig,
        CreditCardManagementInterface $creditCardManagement
    ) {
        $this->logger = $logger;
        $this->baseConfig = $baseConfig;
        $this->creditCardManagement = $creditCardManagement;
    }

    /**
     * Add shopper data into request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        /** @var PaymentDataObject $paymentDataObject */
        $paymentDataObject = SubjectReader::readPayment($buildSubject);

        $this->logger->info('Order Data Builder');

        $payment = $paymentDataObject->getPayment();
        $additionalData = $payment->getAdditionalInformation();

        /** @var Order $order */
        $order = $payment->getOrder();

        try {
            $request = [
                'card_1' => [
                    'orderId' => $order->getIncrementId(),
                    'merchantAccountId' => $this->baseConfig->getMerchantAccountId(),
                    'amount' => $this->calculateCardTotal(
                        $additionalData['card_1'][DataAssignObserver::AMOUNT],
                        $additionalData['card_1'][DataAssignObserver::INSTALLMENTS]
                    )
                ],
                'card_2' => [
                    'orderId' => $order->getIncrementId(),
                    'merchantAccountId' => $this->baseConfig->getMerchantAccountId(),
                    'amount' => $this->calculateCardTotal(
                        $additionalData['card_2'][DataAssignObserver::AMOUNT],
                        $additionalData['card_2'][DataAssignObserver::INSTALLMENTS]
                    )
                ]
            ];

            if (!$order->getIsVirtual()) {
                $request['card_1']['shippingAmount'] = $order->getShippingAmount();
                $request['card_2']['shippingAmount'] = $order->getShippingAmount();
            }

        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        return $request;
    }

    /**
     * @param float $total
     * @param int $installmentValue
     */
    private function calculateCardTotal($total, $installmentValue)
    {
        $availableInstallments = $this->creditCardManagement->getTwoCreditcardsInstallments($total);

        foreach ($availableInstallments as $installment) {
            if ($installment->getValue() === $installmentValue) {
                $total = $installment->getTotalCost();
                break;
            }
        }
        return round($total, 2);
    }
}
