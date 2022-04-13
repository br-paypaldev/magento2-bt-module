<?php

namespace Paypal\BraintreeBrasil\Gateway\Request;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\Order;
use Paypal\BraintreeBrasil\Logger\Logger;

/**
 * Class ItemsDataBuilder
 */
class ItemsDataBuilder implements BuilderInterface
{
    public const ITEM_NAME_LENGTH = 35;
    public const ITEM_DESCRIPTION_LENGTH = 127;

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
        $request['lineItems'] = [];
        $quote = $this->cartRepository->get($order->getQuoteId());

        try {
            foreach ($quote->getAllVisibleItems() as $item) {
                $request['lineItems'][] = [
                    'kind' => \Braintree\TransactionLineItem::DEBIT,
                    'name' => substr($item->getName(), 0, self::ITEM_NAME_LENGTH),
                    'description' => substr(
                        strip_tags($item->getProduct()->getShortDescription()),
                        0,
                        self::ITEM_DESCRIPTION_LENGTH
                    ),
                    'quantity' => $item->getQty(),
                    'totalAmount' => $item->getBasePrice() * $item->getQty() - $item->getDiscountAmount(),
                    'unitAmount' => $item->getBasePrice() - $item->getDiscountAmount() / $item->getQty(),
                ];
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        return $request;
    }
}
