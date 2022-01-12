<?php

namespace Paypal\BraintreeBrasil\Traits;

trait FormatFields
{
    public function onlyNumbers($string)
    {
        return preg_replace("/[^0-9]/", "", $string);
    }
}
