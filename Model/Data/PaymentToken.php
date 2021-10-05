<?php
namespace Paypal\BraintreeBrasil\Model\Data;

use Paypal\BraintreeBrasil\Api\Data\PaymentTokenInterface;

class PaymentToken implements PaymentTokenInterface
{
    private $entity_id;
    private $customer_id;
    private $token;
    private $type;
    private $card_brand;
    private $card_exp_month;
    private $card_exp_year;
    private $card_last_four;
    private $created_at;

    /**
     * @return int
     */
    public function getEntityId()
    {
        return $this->entity_id;
    }

    /**
     * @param int $entity_id
     * @return $this|PaymentTokenInterface
     */
    public function setEntityId($entity_id)
    {
        $this->entity_id = $entity_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    /**
     * @param int $customer_id
     * @return $this|PaymentTokenInterface
     */
    public function setCustomerId($customer_id)
    {
        $this->customer_id = $customer_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return $this|PaymentTokenInterface
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this|PaymentTokenInterface
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardBrand()
    {
        return $this->card_brand;
    }

    /**
     * @param string $card_brand
     * @return $this|PaymentTokenInterface
     */
    public function setCardBrand($card_brand)
    {
        $this->card_brand = $card_brand;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardExpMonth()
    {
        return $this->card_exp_month;
    }

    /**
     * @param string $card_exp_month
     * @return $this|PaymentTokenInterface
     */
    public function setCardExpMonth($card_exp_month)
    {
        $this->card_exp_month = $card_exp_month;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardExpYear()
    {
        return $this->card_exp_year;
    }

    /**
     * @param string $card_exp_year
     * @return $this|PaymentTokenInterface
     */
    public function setCardExpYear($card_exp_year)
    {
        $this->card_exp_year = $card_exp_year;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardLastFour()
    {
        return $this->card_last_four;
    }

    /**
     * @param string $card_last_four
     * @return $this|PaymentTokenInterface
     */
    public function setCardLastFour($card_last_four)
    {
        $this->card_last_four = $card_last_four;
        return $this;
    }

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param string $created_at
     * @return $this|PaymentTokenInterface
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
        return $this;
    }
}
