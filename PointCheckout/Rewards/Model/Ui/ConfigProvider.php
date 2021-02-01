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
    
    private $moduleAssetDir;

    private $config;
    
    public function __construct(
        Context $context,
        \Magento\Framework\View\Asset\Repository $moduleAssetDir
        // ,\Magento\Payment\Gateway\ConfigInterface $config
        ) {
            parent::__construct($context);
            $this->_url = $context->getUrl();
            $this->moduleAssetDir = $moduleAssetDir;
            // $this->config = $config;
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
                        1 => __('Success'),
                        0 => __('Fraud')
                    ],
                    'redirectUrl' => $this->_url->getRouteUrl('rewardredirect/payment/redirect')
                ]
            ]
        ];
    }
    public function execute()
    {}

    
    
}
