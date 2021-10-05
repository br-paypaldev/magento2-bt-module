<?php
namespace Paypal\BraintreeBrasil\Model\Data;

use Paypal\BraintreeBrasil\Api\Data\InstallmentInterface;

class Installment implements InstallmentInterface
{
    /**
     * @var string $label
     */
    private $label;

    /**
     * @var int $value
     */
    private $value;

    /**
     * @var float $interest_rate
     */
    private $interest_rate;

    /**
     * @var float $total_cost
     */
    private $total_cost;

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return \Paypal\BraintreeBrasil\Api\Data\InstallmentInterface
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param int $value
     * @return \Paypal\BraintreeBrasil\Api\Data\InstallmentInterface
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return float
     */
    public function getInterestRate()
    {
        return $this->interest_rate;
    }

    /**
     * @param float $value
     * @return \Paypal\BraintreeBrasil\Api\Data\InstallmentInterface
     */
    public function setInterestRate($value)
    {
        $this->interest_rate = $value;
        return $this;
    }

    /**
     * @return float
     */
    public function getTotalCost()
    {
        return $this->total_cost;
    }

    /**
     * @param float $value
     * @return \Paypal\BraintreeBrasil\Api\Data\InstallmentInterface
     */
    public function setTotalCost($value)
    {
        $this->total_cost = $value;
        return $this;
    }
}
