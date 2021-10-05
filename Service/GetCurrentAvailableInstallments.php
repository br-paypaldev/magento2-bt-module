<?php
declare(strict_types=1);

namespace Paypal\BraintreeBrasil\Service;

class GetCurrentAvailableInstallments
{
    private $availableInstallments = null;

    /**
     * @param array $value
     */
    public function setAvailableInstallments($value)
    {
        $this->availableInstallments = $value;
        return $this;
    }

    public function getAvailableInstallments()
    {
        return $this->availableInstallments;
    }
}
