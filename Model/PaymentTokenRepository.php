<?php
namespace Paypal\BraintreeBrasil\Model;

use Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;
use Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterfaceFactory;
use Paypal\BraintreeBrasil\Api\Data\PaymentTokenSearchResultInterfaceFactory;
use Paypal\BraintreeBrasil\Api\PaymentTokenRepositoryInterface;
use Paypal\BraintreeBrasil\Model\ResourceModel\PaymentToken as ResourceToken;
use Paypal\BraintreeBrasil\Model\ResourceModel\PaymentToken\CollectionFactory as PaymentTokenCollectionFactory;

class PaymentTokenRepository implements PaymentTokenRepositoryInterface
{

    protected $resource;

    protected $paymentTokenFactory;

    protected $paymentTokenCollectionFactory;

    protected $searchResultsFactory;

    protected $dataObjectHelper;

    protected $dataObjectProcessor;

    protected $dataTokenFactory;

    protected $extensionAttributesJoinProcessor;

    private $storeManager;

    private $collectionProcessor;

    protected $extensibleDataObjectConverter;
    /**
     * @var HydratorInterface
     */
    private $hydrator;

    /**
     * @param ResourceToken $resource
     * @param PaymentTokenFactory $paymentTokenFactory
     * @param PaymentTokenInterfaceFactory $dataTokenFactory
     * @param PaymentTokenCollectionFactory $paymentTokenCollectionFactory
     * @param PaymentTokenSearchResultInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        HydratorInterface $hydrator,
        ResourceToken $resource,
        PaymentTokenFactory $paymentTokenFactory,
        PaymentTokenInterfaceFactory $dataTokenFactory,
        PaymentTokenCollectionFactory $paymentTokenCollectionFactory,
        PaymentTokenSearchResultInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentTokenCollectionFactory = $paymentTokenCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataTokenFactory = $dataTokenFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->hydrator = $hydrator;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        PaymentTokenInterface $paymentToken
    ) {
        $paymentTokenData = $this->hydrator->extract($paymentToken);
        $paymentTokenModel = $this->paymentTokenFactory->create()->setData($paymentTokenData);

        try {
            $this->resource->save($paymentTokenModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the token: %1',
                $exception->getMessage()
            ));
        }
        return $paymentTokenModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get($paymentTokenId)
    {
        $paymentToken = $this->paymentTokenFactory->create();
        $this->resource->load($paymentToken, $paymentTokenId);
        if (!$paymentToken->getEntityId()) {
            throw new NoSuchEntityException(__('Token with id "%1" does not exist.', $paymentTokenId));
        }
        return $paymentToken->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        SearchCriteriaInterface $criteria
    ) {
        $collection = $this->paymentTokenCollectionFactory->create();
        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        PaymentTokenInterface $paymentToken
    ) {
        try {
            $paymentTokenModel = $this->paymentTokenFactory->create();
            $this->resource->load($paymentTokenModel, $paymentToken->getEntityId());
            $this->resource->delete($paymentTokenModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the PaymentToken: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($paymentTokenId)
    {
        return $this->delete($this->get($paymentTokenId));
    }
}
