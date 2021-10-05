<?php
namespace Paypal\BraintreeBrasil\Api\Data;

interface InstallmentInterface
{
    /**
     * @return string
     */
    public function getLabel();

    /**
     * @param string $label
     * @return \Paypal\BraintreeBrasil\Api\Data\InstallmentInterface
     */
    public function setLabel($label);

    /**
     * @return int
     */
    public function getValue();

    /**
     * @param int $value
     * @return \Paypal\BraintreeBrasil\Api\Data\InstallmentInterface
     */
    public function setValue($value);

    /**
     * @return float
     */
    public function getInterestRate();

    /**
     * @param float $value
     * @return \Paypal\BraintreeBrasil\Api\Data\InstallmentInterface
     */
    public function setInterestRate($value);

    /**
     * @return float
     */
    public function getTotalCost();

    /**
     * @param float $value
     * @return \Paypal\BraintreeBrasil\Api\Data\InstallmentInterface
     */
    public function setTotalCost($value);
}
