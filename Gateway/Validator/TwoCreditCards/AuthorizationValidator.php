<?php

namespace Paypal\BraintreeBrasil\Gateway\Validator\TwoCreditCards;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Gateway\Http\Client;

class AuthorizationValidator extends AbstractValidator
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Client
     */
    private $braintreeClient;

    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param Logger $logger
     * @param Client $client
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        Logger $logger,
        Client $client
    ) {
        $this->logger = $logger;
        $this->braintreeClient = $client;
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
            $errorMessages[] = __('Erro ao tentar autorizar o pagamento');
        } else {
            foreach ($response['payment_result'] as $result) {
                $transactionSuccessStatuses = [
                    \Braintree\Transaction::AUTHORIZED,
                    \Braintree\Transaction::AUTHORIZING,
                    \Braintree\Transaction::SETTLED,
                    \Braintree\Transaction::SETTLING,
                    \Braintree\Transaction::SETTLEMENT_CONFIRMED,
                    \Braintree\Transaction::SETTLEMENT_PENDING,
                    \Braintree\Transaction::SUBMITTED_FOR_SETTLEMENT
                ];

                if (!$result->success && $result->transaction) {
                    if (!in_array($result->transaction->status, $transactionSuccessStatuses)) {
                        $errorMessage = __('Erro ao tentar autorizar o pagamento');

                        if ($result->transaction->status === 'processor_declined') {
                            $errorMessage .= ': ' . $result->transaction->processorResponseText;
                        }
                    }
                } elseif (!$result->success && isset($result->_attributes['errors'])) {
                    $errorMessage = __('Erro ao tentar autorizar o pagamento');
                    $errorMessage .= ": {$result->_attributes['message']}";
                } elseif (!$result->success && is_null($result->transaction)) {
                    $errorMessage = $result->message ??  __('Erro ao tentar autorizar o pagamento');
                }
                if ($errorMessage) {
                    $errorMessages[] = $errorMessage;
                }
            }
        }

        if (!empty($errorMessages)) {
            $isValid = false;
            $errorMessages[] = $errorMessage;
            $this->cancelTransaction($response);
        }

        return $this->createResult($isValid, $errorMessages);
    }

    private function cancelTransaction($response)
    {
        foreach ($response['payment_result'] ?? [] as $result) {
            if (!isset($result->transaction->id)) {
                continue;
            }
            try {
                $cancelResponse = $this->braintreeClient->getBraintreeClient()->transaction()->void(
                    $result->transaction->id
                );
            } catch (\Exception $exception) {
                $this->logger->error(
                    __("Error on canceling transaction %1. %2", $result->transaction->id, $exception->getMessage())
                );
            }
        }
    }
}
