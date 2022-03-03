<?php

namespace Paypal\BraintreeBrasil\Gateway\Request\TwoCreditCards;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Paypal\BraintreeBrasil\Logger\Logger;

class CancelDataBuilder implements BuilderInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    public function build(array $buildSubject)
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $card1TransId = $payment->getAdditionalInformation('card_1')['transaction_id'] ?? '';
        $card2TransId = $payment->getAdditionalInformation('card_2')['transaction_id'] ?? '';

        $this->logger->info('Cancel Data Builder');

        $request = [
            'card_1' => [
                'transaction_id' => $card1TransId
            ],
            'card_2' => [
                'transaction_id' => $card2TransId
            ]
        ];

        return $request;
    }
}
