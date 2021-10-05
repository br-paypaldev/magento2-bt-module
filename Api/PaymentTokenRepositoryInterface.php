<?php
namespace Paypal\BraintreeBrasil\Api;

use Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterface;
use Paypal\BraintreeBrasil\Api\Data\TokenSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface PaymentTokenRepositoryInterface
{
    /**
     * Save token
     * @param PaymentTokenInterface $token
     * @return PaymentTokenInterface
     * @throws LocalizedException
     */
    public function save(
        PaymentTokenInterface $token
    );

    /**
     * Retrieve token
     * @param string $tokenId
     * @return PaymentTokenInterface
     * @throws LocalizedException
     */
    public function get($tokenId);

    /**
     * Retrieve token by criteria.
     * @param SearchCriteriaInterface $searchCriteria
     * @return TokenSearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete token
     * @param string $tokenId
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($tokenId);
}
