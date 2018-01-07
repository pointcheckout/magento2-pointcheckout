<?php
/**
 * Copyright © 2017 PointCheckout. All rights reserved.
 */
namespace Modym\PointcheckoutPaymentGateway\Model\Adminhtml\Source;

use Magento\Payment\Gateway\ConfigInterface;

/**
 * Class PaymentAction
 */
class PaymentMode implements \Magento\Framework\Option\ArrayInterface
{
    protected $config;
    
    public function __construct(ConfigInterface $config
        )
    {
        $this->config = $config;
    }
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        if ($this->config->getValue('pointcheckout_staging')){
            return [
                [
                    'value' => 0,
                    'label' => 'Test Mode'
                ],
                [
                    'value' => 1,
                    'label' => 'Live Mode'
                ],
                [
                    'value' => 2,
                    'label' => 'Staging Mode'
                ]
            ];
        }else{
        return [
            [
                'value' => 0,
                'label' => 'Test Mode'
            ],
            [
                'value' => 1,
                'label' => 'Live Mode'
            ]
        ];
    }
  }
 
}

