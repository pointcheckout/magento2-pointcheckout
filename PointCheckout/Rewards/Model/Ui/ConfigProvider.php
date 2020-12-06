<?php

namespace PointCheckout\Rewards\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use PointCheckout\Rewards\Gateway\Http\Client\ClientMock;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\AbstractAction;
    

/**
 * Class ConfigProvider
 */
 class ConfigProvider extends AbstractAction implements ConfigProviderInterface
{
    const CODE = 'pointcheckout_rewardgateway';
    
    
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
                    'redirectUrl' => $this->_url->getRouteUrl('rewardredirect/payment/redirect')
                ]
            ]
        ];
    }
    public function execute()
    {}

    
    
}
