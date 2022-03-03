<?php
namespace Paypal\BraintreeBrasil\Gateway\Validator\TwoCreditCards;

use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class RefundValidator extends AbstractValidator
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * RefundValidator constructor.
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
            $errorMessages[] = __('Erro ao tentar estornar o pagamento');
        } else {
            foreach ($response['refund_result'] as $refundResult) {
                if (!$refundResult->success) {
                    $errorMessage = __('Erro ao tentar estornar o pagamento');
                    $errorMessage .= ' ' . __($refundResult->message);
                    $this->logger->info('Refund transaction error', [$refundResult->errors]);
                }
                if ($errorMessage) {
                    $errorMessages[] = $errorMessage;
                }
            }
        }

        if (!empty($errorMessages)) {
            $isValid = false;
            $errorMessages[] = $errorMessage;
            $errorMessages[] = $concatMsg;
        }

        return $this->createResult($isValid, $errorMessages);
    }
}
