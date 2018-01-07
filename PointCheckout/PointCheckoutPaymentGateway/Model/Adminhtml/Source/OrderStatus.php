<?php
/**
 * Copyright © 2017 PointCheckout. All rights reserved.
 */
namespace PointCheckout\PointCheckoutPaymentGateway\Model\Adminhtml\Source;

use Magento\Sales\Model\Order;


/**
 * Class PaymentAction
 */
class OrderStatus implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => Order::STATE_PENDING_PAYMENT,
                'label' => 'Pending Payment'
            ],
            [
                'value' => Order::STATE_NEW,
                'label' => 'New PointCheckout Order'
            ]
        ];
    }
  }
 


