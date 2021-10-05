<?php
namespace Paypal\BraintreeBrasil\Gateway\Http;

use Paypal\BraintreeBrasil\Logger\Logger;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

class TransferFactory implements TransferFactoryInterface
{
    /**
     * @var TransferBuilder
     */
    private $transferBuilder;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param TransferBuilder $transferBuilder
     */
    public function __construct(
        Logger $logger,
        TransferBuilder $transferBuilder
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->logger = $logger;
    }

    /**
     * Builds gateway transfer object
     *
     * @param array $request
     * @return TransferInterface
     */
    public function create(array $request)
    {
        $this->logger->info('TRANSFER FACTORY', [$request]);

        return $this->transferBuilder
            ->setBody($request)
            ->build();
    }
}
