<?php
namespace Paypal\BraintreeBrasil\Block\Info;

use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Info;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;

class DebitCard extends Info
{

    /**
     * @var string
     */
    protected $_template = 'Paypal_BraintreeBrasil::info/debitcard.phtml';
    /**
     * @var Repository
     */
    private $transactionRepository;

    /**
     * DebitCard constructor.
     * @param Context $context
     * @param Repository $transactionRepository
     * @param array $data
     */
    public function __construct
    (
        Context $context,
        Repository $transactionRepository,
        array $data = [])
    {
        parent::__construct($context, $data);
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        $transactionAuthorization = $this->transactionRepository->getByTransactionType(
            TransactionInterface::TYPE_CAPTURE,
            $this->getInfo()->getId()
        );

        return $transactionAuthorization->getTxnId();
    }
}
