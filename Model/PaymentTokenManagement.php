<?php
namespace Paypal\BraintreeBrasil\Model;

use Paypal\BraintreeBrasil\Api\PaymentTokenManagementInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;

class PaymentTokenManagement implements PaymentTokenManagementInterface
{
    /**
     * @var Session
     */
    private $customerSession;
    /**
     * @var PaymentTokenRepository
     */
    private $paymentTokenRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * PaymentTokenManagement constructor.
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param PaymentTokenRepository $paymentTokenRepository
     * @param Session $customerSession
     */
    public function __construct
    (
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        PaymentTokenRepository $paymentTokenRepository,
        Session $customerSession
    )
    {
        $this->customerSession = $customerSession;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
    }

    /**
     * @param string $type
     * @return \Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterface[]
     */
    public function getAvailablePaymentTokens($type)
    {
        $customerId = $this->customerSession->getCustomerId();

        $result = [];

        if((int)$customerId){
            $customerFilter = $this->filterBuilder
                ->setField('customer_id')
                ->setConditionType('eq')
                ->setValue($customerId)
                ->create();

            $paymentTokenTypeFilter = $this->filterBuilder
                ->setField('type')
                ->setConditionType('eq')
                ->setValue($type)
                ->create();

            $this->searchCriteriaBuilder->addFilter($customerFilter);
            $this->searchCriteriaBuilder->addFilter($paymentTokenTypeFilter);

            $searchCriteria = $this->searchCriteriaBuilder->create();
            $paymentTokenSearchResult = $this->paymentTokenRepository
                ->getList($searchCriteria);

            foreach($paymentTokenSearchResult->getItems() as $item){
                $item->setToken(null);
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @param int $customer_id
     * @return \Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterface[]
     */
    public function getCustomerPaymentTokens($customer_id)
    {
        $result = [];

        if((int)$customer_id){
            $customerFilter = $this->filterBuilder
                ->setField('customer_id')
                ->setConditionType('eq')
                ->setValue($customer_id)
                ->create();

            $this->searchCriteriaBuilder->addFilter($customerFilter);

            $searchCriteria = $this->searchCriteriaBuilder->create();
            $paymentTokenSearchResult = $this->paymentTokenRepository
                ->getList($searchCriteria);

            foreach($paymentTokenSearchResult->getItems() as $item){
                $result[] = $item;
            }
        }

        return $result;
    }
}
