<?php

namespace Paypal\BraintreeBrasil\Gateway\Validator\TwoCreditCards;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Paypal\BraintreeBrasil\Logger\Logger;

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

        if (!isset($response['cancel_result'])) {
            $errorMessages[] = __('Erro ao tentar cancelar o pagamento');
        } else {
            foreach ($response['cancel_result'] as $cancelResult) {
                if (!$cancelResult->success) {
                    $errorMessages = __('Erro ao tentar cancelar o pagamento');
                    $this->logger->info('Cancel transaction error', [$cancelResult->errors]);
                }
            }
        }

        return $this->createResult(empty($errorMessages), $errorMessages);
    }
}
