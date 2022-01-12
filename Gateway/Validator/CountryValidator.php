<?php

namespace Paypal\BraintreeBrasil\Gateway\Validator;

use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Paypal\BraintreeBrasil\Logger\Logger;

class CountryValidator extends AbstractValidator
{

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger $logger
     * @param ResultInterfaceFactory $resultFactory
     */
    public function __construct(
        Logger $logger,
        ResultInterfaceFactory $resultFactory
    ) {
        parent::__construct($resultFactory);
        $this->logger = $logger;
    }

    /**
     * @param array $validationSubject
     * @return \Magento\Payment\Gateway\Validator\ResultInterface
     * @throws NotFoundException
     * @throws \Exception
     */
    public function validate(array $validationSubject)
    {
        $this->logger->info('Country Validator', $validationSubject);

        $isValid = false;
        $country = $validationSubject['country'];

        if ($country == 'BR') {
            $isValid = true;
        }

        return $this->createResult($isValid);
    }
}
