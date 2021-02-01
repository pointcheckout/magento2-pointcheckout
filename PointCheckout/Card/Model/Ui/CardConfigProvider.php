<?php

namespace PointCheckout\Card\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use PointCheckout\Card\Gateway\Http\Client\ClientMock;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\AbstractAction;
    

/**
 * Class CardConfigProvider
 */
 class CardConfigProvider extends AbstractAction implements ConfigProviderInterface
{
    const CODE = 'pointcheckout_cardgateway';
    
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
                    'redirectUrl' => $this->_url->getRouteUrl('cardredirect/payment/redirect'),
                    'cardsImage' => $this->moduleAssetDir->getUrl( 'PointCheckout_Card::images/pointcheckout-cards.png' )
                ]
            ]
        ];
    }
    public function execute()
    {}

    
    
}
