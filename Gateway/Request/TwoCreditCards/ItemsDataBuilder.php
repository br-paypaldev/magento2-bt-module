<?php

namespace Paypal\BraintreeBrasil\Gateway\Request\TwoCreditCards;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order;
use Paypal\BraintreeBrasil\Gateway\Request\ItemsDataBuilder as DefaultItemsDataBuilder;

/**
 * Class ItemsDataBuilder
 */
class ItemsDataBuilder extends DefaultItemsDataBuilder
{

    /**
     * Add items data into request
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
        $request['card_1']['lineItems'] = [];
        $request['card_2']['lineItems'] = [];
        $quote = $this->cartRepository->get($order->getQuoteId());

        try {
            foreach ($quote->getAllVisibleItems() as $item) {
                $item = $this->buildLineItem($item);
                $request['card_1']['lineItems'][] = $item;
                $request['card_2']['lineItems'][] = $item;
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        return $request;
    }
}
