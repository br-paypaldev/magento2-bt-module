<?php

namespace Paypal\BraintreeBrasil\Traits;

trait FormatFields
{
    public function onlyNumbers($string)
    {
        return preg_replace("/[^0-9]/", "", $string);
    }

    /**
     * @param string $value
     * @return float
     */
    public function toFloat($value)
    {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        return (float)$value;
    }
}
