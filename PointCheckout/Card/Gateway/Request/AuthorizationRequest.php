<?php

namespace PointCheckout\Card\Gateway\Request;

use Magento\Checkout\Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Zend_Gdata_App_InvalidArgumentException;

class AuthorizationRequest implements BuilderInterface
{
    /**
     * @param ConfigInterface $config
     */
    public function __construct(
    ) {
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        //prepare the request body 
        $storeOrder = array();
        $storeOrder['transactionId'] = "";
        
        return $storeOrder;
    }
}
