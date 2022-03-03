<?php

namespace Paypal\BraintreeBrasil\Gateway\Request\TwoCreditCards;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\Order;
use Paypal\BraintreeBrasil\Logger\Logger;

/**
 * Class CustomerDataBuilder
 */
class ItemsDataBuilder implements BuilderInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @param Logger $logger
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        Logger $logger,
        CartRepositoryInterface $cartRepository
    ) {
        $this->logger = $logger;
        $this->cartRepository = $cartRepository;
    }

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
                $item = [
                    'kind' => \Braintree\TransactionLineItem::DEBIT,
                    'name' => $item->getName(),
                    'quantity' => $item->getQty(),
                    'totalAmount' => $item->getBasePrice() * $item->getQty() - $item->getDiscountAmount(),
                    'unitAmount' => $item->getBasePrice() - $item->getDiscountAmount() / $item->getQty(),
                ];
                $request['card_1']['lineItems'][] = $item;
                $request['card_2']['lineItems'][] = $item;
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        return $request;
    }
}
