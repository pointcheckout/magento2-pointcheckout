<?php
/**
 * Copyright © 2017 PointCheckout. All rights reserved.
 */
namespace Magento\PointcheckoutPaymentGateway\Controller\Payment;

class Redirect extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory)
    {
        $this->_pageFactory = $pageFactory;
        return parent::__construct($context);
    }
    
    public function execute()
    {
        return $this->_pageFactory->create();
    }
}
