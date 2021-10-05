<?php
namespace Paypal\BraintreeBrasil\Gateway\Request;

use Paypal\BraintreeBrasil\Gateway\Config\Config;
use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class CustomerDataBuilder
 */
class OrderDataBuilder implements BuilderInterface
{
    private $logger;

    private $baseConfig;

    /**
     * CustomerDataBuilder constructor.
     *
     * @param Session $checkoutSession
     * @param Logger $logger
     */
    public function __construct(
        Session $checkoutSession,
        Logger $logger,
        Config $baseConfig
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->baseConfig = $baseConfig;
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
        $order = $payment->getOrder();

        try {
            $request = [
                'orderId' => $order->getIncrementId(),
                'merchantAccountId' => $this->baseConfig->getMerchantAccountId(),
                'amount' => round($order->getGrandTotal(), 2),
            ];

        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        return $request;
    }
}
