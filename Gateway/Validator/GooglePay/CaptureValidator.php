<?php

namespace Paypal\BraintreeBrasil\Gateway\Validator\GooglePay;

use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class CaptureValidator extends AbstractValidator
{
    protected $logger;
    protected $eventManager;

    protected $settleStatus = [
        \Braintree\Transaction::SUBMITTED_FOR_SETTLEMENT,
        \Braintree\Transaction::SETTLED,
        \Braintree\Transaction::SETTLING,
    ];

    /**
     * AuthorizationValidator constructor.
     *
     * @param ResultInterfaceFactory $resultFactory
     * @param Logger $logger
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

        $concatMsg = '';
        if (isset($response['error'])) {
            $concatMsg = $response['error'];
        }

        if (!isset($response['capture_result'])) {
            $errorMessage = __('Erro ao tentar capturar o pagamento');
        } else {
            $captureResult = $response['capture_result'];

            //don't throw error when transaction already is submitted
            if (!$captureResult->success) {
                $transaction = $this->getTransaction($captureResult);
                if ($transaction) {
                    $status = $transaction->__get('status');
                    $amount = (float)$transaction->__get('amount');
                    $amountRequested = (float)$transaction->__get('amountRequested');
                    if (in_array($status, $this->settleStatus)
                        && $amount >= $amountRequested
                    ) {
                        $this->logger->info("Transaction already submitted!");
                    } elseif ($amount < $amountRequested) {
                        $errorMessage = __("Transaction partially captured. Invoice not created!");
                    } else {
                        $errorMessage = __('Erro ao tentar capturar o pagamento');
                        $this->logger->info('Capture transaction error', [$captureResult->errors]);
                    }
                } else {
                    $errorMessage = __('Erro ao tentar capturar o pagamento');
                    $this->logger->info('Capture transaction error', [$captureResult->errors]);
                }
            }
        }

        if ($errorMessage) {
            $isValid = false;
            $errorMessages[] = $errorMessage;
            $errorMessages[] = $concatMsg;
        }

        return $this->createResult($isValid, $errorMessages);
    }

    private function getTransaction($captureResult)
    {
        try {
            $transaction = $captureResult->__get('transaction');
        } catch (\Exception $exception) {
            $transaction = null;
        }
        return $transaction;
    }
}
