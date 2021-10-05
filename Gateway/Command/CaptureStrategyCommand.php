<?php
namespace Paypal\BraintreeBrasil\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;

class CaptureStrategyCommand implements CommandInterface
{
    const SALE = 'sale';
    const CAPTURE = 'settlement';

    private $subjectReader;
    private $commandPool;

    /**
     * CaptureStrategyCommand constructor.
     * @param SubjectReader $subjectReader
     * @param CommandPoolInterface $commandPool
     */
    public function __construct(
        SubjectReader $subjectReader,
        CommandPoolInterface $commandPool
    )
    {
        $this->commandPool = $commandPool;
        $this->subjectReader = $subjectReader;
    }

    public function execute(array $commandSubject)
    {
        /** @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface $paymentDO */
        $paymentDO = $this->subjectReader->readPayment($commandSubject);

        /** @var \Magento\Sales\Api\Data\OrderPaymentInterface $paymentInfo */
        $paymentInfo = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($paymentInfo);

        $command = $this->getCommand($paymentInfo);
        $this->commandPool->get($command)->execute($commandSubject);
    }

    private function getCommand(OrderPaymentInterface $payment)
    {
        if (!$payment->getAuthorizationTransaction()) {
            return self::SALE;
        } else {
            return self::CAPTURE;
        }
    }
}
