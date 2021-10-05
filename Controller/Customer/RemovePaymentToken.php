<?php
namespace Paypal\BraintreeBrasil\Controller\Customer;

use _HumbugBoxe8a38a0636f4\Nette\Neon\Exception;
use Paypal\BraintreeBrasil\Model\PaymentTokenRepository;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\Manager;

class RemovePaymentToken implements HttpGetActionInterface
{
    protected $resultPageFactory;
    /**
     * @var Session
     */
    private $customerSession;
    /**
     * @var RedirectFactory
     */
    private $redirectFactory;
    /**
     * @var PaymentTokenRepository
     */
    private $paymentTokenRepository;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var Manager
     */
    private $messageManager;

    /**
     * Constructor
     *
     * @param Session $customerSession
     * @param RequestInterface $request
     * @param PaymentTokenRepository $paymentTokenRepository
     * @param Manager $messageManager
     * @param RedirectFactory $redirectFactory
     */
    public function __construct(
        Session $customerSession,
        RequestInterface $request,
        PaymentTokenRepository $paymentTokenRepository,
        Manager $messageManager,
        RedirectFactory $redirectFactory
    ) {
        $this->customerSession = $customerSession;
        $this->redirectFactory = $redirectFactory;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->request = $request;
        $this->messageManager = $messageManager;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        if(!$this->customerSession->isLoggedIn()){
            $redirect = $this->redirectFactory->create();
            $redirect->setPath('customer/account/login');
            return $redirect;
        }

        try {
            $id = $this->request->getParam('id');
            $paymentToken = $this->paymentTokenRepository->get($id);

            if($paymentToken->getCustomerId() != $this->customerSession->getCustomerId()){
                throw new LocalizedException(__('Not authorized'));
            }

            $this->paymentTokenRepository->delete($paymentToken);

            $this->messageManager->addSuccess(__('The card has been removed'));

        } catch(\Exception $e){
            $this->messageManager->addError($e->getMessage());
        }

        $redirect = $this->redirectFactory->create();
        $redirect->setPath('braintree_brasil/customer/paymenttokens');
        return $redirect;
    }
}
