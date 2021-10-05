<?php
namespace Paypal\BraintreeBrasil\Api\Data;

interface PaymentTokenInterface
{
    const CARD_TYPE_CREDITCARD = 'creditcard';
    const CARD_TYPE_DEBITCARD = 'debitcard';

    /**
     * @return int
     */
    public function getEntityId();

    /**
     * @param int $entity_id
     * @return \Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterface
     */
    public function setEntityId($entity_id);

    /**
     * @return int
     */
    public function getCustomerId();

    /**
     * @param int $customer_id
     * @return \Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterface
     */
    public function setCustomerId($customer_id);

    /**
     * @return string
     */
    public function getToken();

    /**
     * @param string $token
     * @return \Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterface
     */
    public function setToken($token);

    /**
     * @return string
     */
    public function getType();

    /**
     * @param string $type
     * @return \Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterface
     */
    public function setType($type);

    /**
     * @return string
     */
    public function getCardBrand();

    /**
     * @param string $card_brand
     * @return \Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterface
     */
    public function setCardBrand($card_brand);

    /**
     * @return string
     */
    public function getCardExpMonth();

    /**
     * @param string $card_exp_month
     * @return \Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterface
     */
    public function setCardExpMonth($card_exp_month);

    /**
     * @return string
     */
    public function getCardExpYear();

    /**
     * @param string $card_exp_year
     * @return \Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterface
     */
    public function setCardExpYear($card_exp_year);

    /**
     * @return string
     */
    public function getCardLastFour();

    /**
     * @param string $card_last_four
     * @return \Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterface
     */
    public function setCardLastFour($card_last_four);

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param string $created_at
     * @return \Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterface
     */
    public function setCreatedAt($created_at);
}
