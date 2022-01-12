<?php

namespace Paypal\BraintreeBrasil\Block\Info;

use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Info;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;

class CreditCard extends Info
{

    /**
     * @var string
     */
    protected $_template = 'Paypal_BraintreeBrasil::info/creditcard.phtml';
    /**
     * @var Repository
     */
    private $transactionRepository;
    /**
     * @var Data
     */
    private $pricingHelper;

    /**
     * CreditCard constructor.
     * @param Context $context
     * @param Repository $transactionRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $pricingHelper,
        Repository $transactionRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->transactionRepository = $transactionRepository;
        $this->pricingHelper = $pricingHelper;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        $transactionAuthorization = $this->transactionRepository->getByTransactionType(
            TransactionInterface::TYPE_AUTH,
            $this->getInfo()->getId()
        );

        if (!$transactionAuthorization) {
            $transactionAuthorization = $this->transactionRepository->getByTransactionType(
                TransactionInterface::TYPE_CAPTURE,
                $this->getInfo()->getId()
            );
        }

        return $transactionAuthorization->getTxnId();
    }

    /**
     * @param $value
     */
    public function formatCurrency($value)
    {
        return $this->pricingHelper->currency($value, true, false);
    }
}
