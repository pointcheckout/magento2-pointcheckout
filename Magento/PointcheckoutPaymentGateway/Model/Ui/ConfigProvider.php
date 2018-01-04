<?php
/**
 * Copyright © 2017 PointCheckout. All rights reserved.
 */
namespace Magento\PointcheckoutPaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\PointcheckoutPaymentGateway\Gateway\Http\Client\ClientMock;
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
//         file_put_contents("/Applications/XAMPP/xamppfiles/htdocs/magento/var/log/yaser.log",'######  checkout key from session is  #####\r\n'.date("Y-m-d") ,FILE_APPEND);
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
