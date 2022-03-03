<?php

namespace Paypal\BraintreeBrasil\Observer\TwoCreditCards;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterface;
use Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterfaceFactory;
use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Model\PaymentTokenRepository;

class SavePaymentTokenObserver implements ObserverInterface
{
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var PaymentTokenRepository
     */
    private $paymentTokenRepository;
    /**
     * @var HydratorInterface
     */
    private $hydrator;
    /**
     * @var PaymentTokenInterfaceFactory
     */
    private $paymentTokenFactory;

    /**
     * SavePaymentMethodObserver constructor.
     * @param Logger $logger
     * @param HydratorInterface $hydrator
     * @param PaymentTokenRepository $paymentTokenRepository
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Logger $logger,
        HydratorInterface $hydrator,
        PaymentTokenInterfaceFactory $paymentTokenFactory,
        PaymentTokenRepository $paymentTokenRepository,
        CustomerSession $customerSession
    ) {
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->hydrator = $hydrator;
        $this->paymentTokenFactory = $paymentTokenFactory;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            $braintreeTransaction = $observer->getData('braintree_transaction');

            $customerId = $this->customerSession->getCustomerId();
            if ($customerId) {
                $data = [
                    'customer_id' => $customerId,
                    'token' => $braintreeTransaction->creditCard['token'],
                    'type' => PaymentTokenInterface::CARD_TYPE_CREDITCARD,
                    'card_brand' => $braintreeTransaction->creditCard['cardType'],
                    'card_exp_month' => $braintreeTransaction->creditCard['expirationMonth'],
                    'card_exp_year' => $braintreeTransaction->creditCard['expirationYear'],
                    'card_last_four' => $braintreeTransaction->creditCard['last4'],
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $paymentToken = $this->paymentTokenFactory->create();
                $this->hydrator->hydrate($paymentToken, $data);

                $this->paymentTokenRepository->save($paymentToken);
            }
        } catch (\Exception $e) {
            $this->logger->error('Unable to save payment method token: ' . $e->getMessage());
        }
    }
}
