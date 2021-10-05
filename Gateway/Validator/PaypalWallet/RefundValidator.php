<?php
namespace Paypal\BraintreeBrasil\Gateway\Validator\PaypalWallet;

use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class RefundValidator extends AbstractValidator
{
    protected $logger;
    protected $eventManager;

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

        if (!isset($response['refund_result'])) {
            $errorMessage = __('Erro ao tentar estornar o pagamento');
        } else {
            $refundResult = $response['refund_result'];

            if (!$refundResult->success) {
                $errorMessage = __('Erro ao tentar estornar o pagamento');

                $this->logger->info('Refund transaction error', [$refundResult->errors]);
            }
        }

        if ($errorMessage) {
            $isValid = false;
            $errorMessages[] = $errorMessage;
            $errorMessages[] = $concatMsg;
        }

        return $this->createResult($isValid, $errorMessages);
    }
}
