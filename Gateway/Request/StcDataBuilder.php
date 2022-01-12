<?php

namespace Paypal\BraintreeBrasil\Gateway\Request;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Paypal\BraintreeBrasil\Logger\Logger;
use Paypal\BraintreeBrasil\Model\Customer\FieldsMapper;
use Paypal\BraintreeBrasil\Observer\CreditCard\DataAssignObserver;
use Paypal\BraintreeBrasil\Traits\FormatFields;

/**
 * Class StcDataBuilder
 */
class StcDataBuilder implements BuilderInterface
{
    use FormatFields;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var FieldsMapper
     */
    protected $fieldsMapper;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @param Session $checkoutSession
     * @param FieldsMapper $fieldsMapper
     * @param Logger $logger
     */
    public function __construct(
        Session $checkoutSession,
        FieldsMapper $fieldsMapper,
        Logger $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->fieldsMapper = $fieldsMapper;
    }

    /**
     * Add shopper data into request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        /** @var PaymentDataObject $paymentDataObject */
        $paymentDataObject = SubjectReader::readPayment($buildSubject);

        $this->logger->info('STC Data Builder');

        $payment = $paymentDataObject->getPayment();
        /** @var Order $order */
        $order = $payment->getOrder();
        $quote = $this->checkoutSession->getQuote();

        $additionalData = $payment->getAdditionalInformation();
        $deviceData = $additionalData[DataAssignObserver::DEVICE_DATA];
        $deviceData = json_decode($deviceData, true);
        $correlationId = $deviceData['correlation_id'] ?? '';

        try {
            $request['stc'] = $this->buildStcData($correlationId, $order, $quote);
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        return $request;
    }

    /**
     * @param string $correlationId
     * @param Order $order
     * @param Quote $quote
     * @return array
     * @throws \Exception
     */
    protected function buildStcData($correlationId, $order, $quote)
    {
        $cnpj = $this->fieldsMapper->getCustomerCnpj($quote);
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        return [
            'correlation_id' => $correlationId,
            'tracking_id' => $correlationId,
            'additional_data' => [
                [
                    'key' => 'sender_account_id',
                    'value' => $cnpj ?: $this->fieldsMapper->getCustomerCpf($quote)
                ],
                [
                    'key' => 'sender_first_name',
                    'value' => $order->getCustomerFirstname()
                ],
                [
                    'key' => 'sender_last_name',
                    'value' => $order->getCustomerLastname()
                ],
                [
                    'key' => 'sender_email',
                    'value' => $order->getCustomerEmail()
                ],
                [
                    'key' => 'sender_phone',
                    'value' => $this->fieldsMapper->getCustomerTelephone($quote)
                ],
                [
                    'key' => 'sender_country_code',
                    'value' => 'BR'
                ],
                [
                    'key' => 'sender_create_date',
                    'value' => $now->format('Y-m-d\TH:i:s')
                ],
                [
                    'key' => 'br_cpf',
                    'value' => $cnpj ?: $this->fieldsMapper->getCustomerCpf($quote)
                ]
            ]
        ];
    }
}
