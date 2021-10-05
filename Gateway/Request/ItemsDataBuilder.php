<?php

namespace Paypal\BraintreeBrasil\Gateway\Request;

use Paypal\BraintreeBrasil\Gateway\Config\Config;
use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;

/**
 * Class CustomerDataBuilder
 */
class ItemsDataBuilder implements BuilderInterface
{
    private $logger;

    /**
     * CustomerDataBuilder constructor.
     *
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
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

        $this->logger->info('Items Data Builder');

        $payment = $paymentDataObject->getPayment();
        /** @var Order $order */
        $order = $payment->getOrder();
        $request['lineItems'] = [];

        try {
            foreach ($order->getAllItems() as $item) {
                $request['lineItems'][] = [
                    'kind' => \Braintree\TransactionLineItem::DEBIT,
                    'name' => $item->getName(),
                    'quantity' => $item->getQtyOrdered(),
                    'totalAmount' => ($item->getBasePrice() - $item->getDiscountAmount()) * $item->getQtyOrdered(),
                    'unitAmount' => $item->getBasePrice() - $item->getDiscountAmount(),
                    'unitOfMeasure' => 'unit',
                    'discountAmount' => $item->getDiscountAmount(),
                    'productCode' => $item->getSku(),
                ];
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        return $request;
    }
}
