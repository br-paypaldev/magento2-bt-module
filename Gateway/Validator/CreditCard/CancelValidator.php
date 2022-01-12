<?php

namespace Paypal\BraintreeBrasil\Gateway\Validator\CreditCard;

use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class CancelValidator extends AbstractValidator
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * CancelValidator constructor.
     *
     * @param ResultInterfaceFactory $resultFactory
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        Logger $logger
    ) {
        $this->logger = $logger;
        parent::__construct($resultFactory);
    }

    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $response = SubjectReader::readResponse($validationSubject);

        $errorMessages = [];
        $errorMessage = null;
        $isValid = true;

        if (!isset($response['cancel_result'])) {
            $errorMessage = __('Erro ao tentar cancelar o pagamento');
        } else {
            $cancelResult = $response['cancel_result'];

            if (!$cancelResult->success) {
                $errorMessage = __('Erro ao tentar cancelar o pagamento');

                $this->logger->info('Cancel transaction error', [$cancelResult->errors]);
            }
        }

        if ($errorMessage) {
            $isValid = false;
            $errorMessages[] = $errorMessage;
        }

        return $this->createResult($isValid, $errorMessages);
    }
}
