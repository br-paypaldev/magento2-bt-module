<?php

namespace Paypal\BraintreeBrasil\Gateway\Validator\PaypalWallet;

use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Service\PaypalGraphQL;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Braintree\Transaction;

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

        $braintreePaymentResult = $response['braintree_result'] ?? null;
        $paypalChargePaymentResult = $response['paypal_charge_result'] ?? null;

        if (!$braintreePaymentResult && !$paypalChargePaymentResult) {
            $errorMessage = __('Erro ao tentar autorizar o pagamento');
        } else {
            // validate Braintree Transaction (SDK)
            if ($braintreePaymentResult) {
                $braintreeTransactionSuccessStatuses = [
                    Transaction::AUTHORIZED,
                    Transaction::AUTHORIZING,
                    Transaction::SETTLED,
                    Transaction::SETTLING,
                    Transaction::SETTLEMENT_CONFIRMED,
                    Transaction::SETTLEMENT_PENDING,
                    Transaction::SUBMITTED_FOR_SETTLEMENT
                ];

                if (!$braintreePaymentResult->success && $braintreePaymentResult->transaction) {
                    if (!in_array($braintreePaymentResult->transaction->status, $braintreeTransactionSuccessStatuses)) {
                        $errorMessage = __('Erro ao tentar autorizar o pagamento');

                        if ($braintreePaymentResult->transaction->status === 'processor_declined') {
                            $errorMessage .= ': ' . $braintreePaymentResult->transaction->processorResponseText;
                        }
                    }
                } elseif (!$braintreePaymentResult->success && is_null($braintreePaymentResult->transaction)) {
                    $errorMessage = __('Erro ao tentar autorizar o pagamento');
                    try {
                        $addMsg = $braintreePaymentResult->__get('message');
                        $errorMessage .= ': ' . $addMsg;
                    } catch (\Exception $exception) {
                        //do nothing
                    }
                }
            } else {
                // validate PayPal Charge Transaction (GraphQL)
                $transaction = $paypalChargePaymentResult->data->chargePayPalAccount->transaction ?? null;
                if (!$transaction) {
                    $errorMessage = __('Erro ao tentar autorizar o pagamento');
                } else {
                    $statuses = [
                        PaypalGraphQL::TRANSACTION_STATUS_SETTLED,
                        PaypalGraphQL::TRANSACTION_STATUS_SETTLEMENT_CONFIRMED,
                        PaypalGraphQL::TRANSACTION_STATUS_SETTLEMENT_PENDING,
                        PaypalGraphQL::TRANSACTION_STATUS_SETTLING,
                        PaypalGraphQL::TRANSACTION_STATUS_SUBMITTED_FOR_SETTLEMENT
                    ];

                    if ($transaction->status && in_array($transaction->status, $statuses)) {
                        $isValid = true;
                    } else {
                        $errorMessage = __('Transaction error');
                    }
                }
            }
        }

        if ($errorMessage) {
            $isValid = false;
            $errorMessages[] = $errorMessage;
        }

        return $this->createResult($isValid, $errorMessages);
    }
}
