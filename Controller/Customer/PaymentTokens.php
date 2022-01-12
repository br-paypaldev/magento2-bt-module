<?php

namespace Paypal\BraintreeBrasil\Controller\Customer;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

class PaymentTokens implements HttpGetActionInterface
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
     * Constructor
     *
     * @param Session $customerSession
     * @param RedirectFactory $redirectFactory
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Session $customerSession,
        RedirectFactory $redirectFactory,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->redirectFactory = $redirectFactory;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            $redirect = $this->redirectFactory->create();
            $redirect->setPath('customer/account/login');
            return $redirect;
        }
        return $this->resultPageFactory->create();
    }
}
