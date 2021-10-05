<?php
namespace Paypal\BraintreeBrasil\Gateway\Validator\DebitCard;

use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class AuthorizationValidator extends AbstractValidator
{
    protected $logger;
    protected $eventManager;

    /**
     * AuthorizationValidator constructor.
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

        if (!isset($response['payment_result'])) {
            $errorMessage = __('Erro ao tentar autorizar o pagamento');
        } else {
            $result = $response['payment_result'];

            $transactionSuccessStatuses = [
                \Braintree\Transaction::AUTHORIZED,
                \Braintree\Transaction::AUTHORIZING,
                \Braintree\Transaction::SETTLED,
                \Braintree\Transaction::SETTLING,
                \Braintree\Transaction::SETTLEMENT_CONFIRMED,
                \Braintree\Transaction::SETTLEMENT_PENDING,
                \Braintree\Transaction::SUBMITTED_FOR_SETTLEMENT
            ];

            if(!$result->success && $result->transaction){
                if (!in_array($result->transaction->status, $transactionSuccessStatuses)) {
                    $errorMessage = __('Erro ao tentar autorizar o pagamento');

                    if($result->transaction->status === 'processor_declined') {
                        $errorMessage .= ': ' . $result->transaction->processorResponseText;
                    }
                }
            } else if(!$result->success && is_null($result->transaction)) {
                $errorMessage = __('Erro ao tentar autorizar o pagamento');
            }
        }

        if ($errorMessage) {
            $isValid = false;
            $errorMessages[] = $errorMessage;
        }

        return $this->createResult($isValid, $errorMessages);
    }
}
