<?php
/**
 * Copyright © 2017 PointCheckout. All rights reserved.
 */
namespace Modym\PointcheckoutPaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class AuthorizationRequest implements BuilderInterface
{
    /**
     * @var ConfigInterface
     */
    protected $_totals;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        \Magento\Checkout\Block\Cart\Totals $totals
    ) {
        $this->_totals = $totals;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $payment */
        $payment = $buildSubject['payment'];
        $order = $payment->getOrder();
        $grandTotal =
        $cartItems= $order->getItems();
        $items = array();
        $i = 0;
        foreach ($cartItems as $orderItem){
            $item = (object) array(
                'name'=> $orderItem->getName(),
                'sku' => $orderItem->getSku(),
                'quantity' => $orderItem->getQtyOrdered(),
                'type'=>$orderItem->getProductType(),
                'total' => (intval($orderItem->getQtyOrdered())*intval($orderItem->getPrice())));
            //in case of bundles the bundle group item total is set to zero here to prevent conflict in totals
            //IMPORTANT (pointCheckout should handel items with total zero)  
            //not sure about the grouped type but I add it just in case.
            if($orderItem->getProductType()=='bundle' || $orderItem->getProductType()=='grouped'){
                $item->total =0;
            }
            $items[$i++] = $item;
        }
        //prepare the request body 
        $storeOrder = array();
        $storeOrder['referenceId'] = $order->getOrderIncrementId();
        $storeOrder['items'] = array_values($items);
        //collecting totals
        //the only model that did not return zero values for shipping and tax \Magento\Checkout\Block\Cart\Totals
        $totals = $this->_totals->getTotalsCache();
        $storeOrder['grandtotal'] =   $order->getGrandTotalAmount();
        foreach ($totals as $total){
//             file_put_contents("/Applications/XAMPP/xamppfiles/htdocs/magento/var/log/yaser.log",date("Y-m-d") .'######  total is    #'.$total->getCode().'#'.$total->getValue(),FILE_APPEND);
            switch($total->getCode()){
                case 'shipping':
                    $storeOrder['shipping']=$total->getValue();
                    break;
                case 'tax':
                    $storeOrder['tax'] =$total->getValue();
                    break;
                case 'discount':
                    $storeOrder['discount'] = $total->getValue();
                    break;
                case 'subtotal':
                    $storeOrder['subtotal'] = $total->getValue();
                    break;
            }
        }
        
        
        $storeOrder['currency'] = $order->getCurrencyCode();
        
        //prepare customer Information
        $customer = array();
            
        $billingAddress = array();
        $billingAddress['name'] = $order->getBillingAddress()->getFirstname().$order->getBillingAddress()->getLastname();
        $billingAddress['address1'] = $order->getBillingAddress()->getStreetLine1();
        $billingAddress['address2'] = $order->getBillingAddress()->getStreetLine2();
        $billingAddress['city'] = $order->getBillingAddress()->getCity();
        $billingAddress['country'] = $order->getBillingAddress()->getCountryId();
        
        $shippingAddress = array();
        $shippingAddress['name'] = $order->getShippingAddress()->getFirstname().$order->getShippingAddress()->getLastname();
        $shippingAddress['address1'] = $order->getShippingAddress()->getStreetLine1();
        $shippingAddress['address2'] = $order->getShippingAddress()->getStreetLine2();
        $shippingAddress['city'] = $order->getShippingAddress()->getCity();
        $shippingAddress['country'] = $order->getShippingAddress()->getCountryId();
        
        $customer['billingAddress'] = $billingAddress;
        $customer['shippingAddress'] = $shippingAddress;
        $customer['firstname'] = $order->getBillingAddress()->getFirstname();
        $customer['lastname'] = $order->getBillingAddress()->getLastname();
        $customer['email'] = $order->getBillingAddress()->getEmail();
        $customer['phone'] = $order->getBillingAddress()->getTelephone();
        
        $storeOrder['customer'] = $customer;
        
        return $storeOrder;
    }
}
