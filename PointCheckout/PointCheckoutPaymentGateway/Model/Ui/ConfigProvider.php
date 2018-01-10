<?php
/**
 * Copyright © 2017 PointCheckout. All rights reserved.
 */
namespace PointCheckout\PointCheckoutPaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use PointCheckout\PointCheckoutPaymentGateway\Gateway\Http\Client\ClientMock;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\AbstractAction;
    

/**
 * Class ConfigProvider
 */
 class ConfigProvider extends AbstractAction implements ConfigProviderInterface
{
    const CODE = 'pointcheckout_gateway';
    
    
    public function __construct(
        Context $context
        ) {
            parent::__construct($context);
            $this->_url = $context->getUrl();
    }
    

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'transactionResults' => [
                        ClientMock::SUCCESS => __('Success'),
                        ClientMock::FAILURE => __('Fraud')
                    ],
                    'redirectUrl' => $this->_url->getRouteUrl('redirecttogateway/payment/redirect')
                ]
            ]
        ];
    }
    public function execute()
    {}

    
    
}
