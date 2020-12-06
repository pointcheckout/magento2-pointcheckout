<?php

namespace PointCheckout\Rewards\Model\Adminhtml\Source;


/**
 * Class PaymentAction
 */
class Allspecificusergroup implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 0,
                'label' => 'All User Groups Allowed'
            ],
            [
                'value' => 1,
                'label' => 'Specific User Groups Allowed'
            ]
        ];
    }
}
