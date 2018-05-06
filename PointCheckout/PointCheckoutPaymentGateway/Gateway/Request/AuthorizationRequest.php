<?php
/**
 * Copyright © 2017 PointCheckout. All rights reserved.
 */
namespace PointCheckout\PointCheckoutPaymentGateway\Gateway\Request;

use Magento\Checkout\Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Zend_Gdata_App_InvalidArgumentException;

class AuthorizationRequest implements BuilderInterface
{
    /**
     * @var ConfigInterface
     */
    protected $_totals;
    private $config;
    private $_pointcheckoutAlert;
    private $countryMsg;
    private $groupMsg;
    protected $_session;
    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        \Magento\Checkout\Block\Cart\Totals $totals,
        ConfigInterface $config,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Phrase $countryMsg,
        \Magento\Framework\Phrase $groupMsg,
        \Magento\Customer\Model\Session $session
    ) {
        $this->_totals = $totals;
        $this->config = $config;
        $this->_pointcheckoutAlert = $messageManager;
        $this->countryMsg = $countryMsg;
        $this->groupMsg = $groupMsg;
        $this->_session = $session;
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
            throw new Zend_Gdata_App_InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $payment */
        $payment = $buildSubject['payment'];
        $order = $payment->getOrder();
        
        
        if($this->config->getValue('allowuserspecific') == 1){
            $validGroups = explode(', ', $this->config->getValue('specificusergroup'));
            if(!$this->_session->isLoggedIn()){
                $notValidException = new LocalizedException($this->groupMsg);
                throw $notValidException;
            }else{
                $valid=false;
                $customer = $customer = $this->_session->getCustomer();
                $customerGroup = $customer->getGroupId();
                foreach($validGroups as $validGroup){
                    if($customerGroup == $validGroup){
                        $valid=true;
                    }
                }
                if(!$valid){
                    $notValidException = new LocalizedException($this->groupMsg);
                    throw $notValidException;
                }
            }
            
        }
        if ($this->config->getValue('allowspecific') == 1){
            $validCountries = explode(', ', $this->config->getValue('specificcountry'));
            $valid=false;
            $billingCountry = $order->getBillingAddress()->getCountryId();
            
            foreach($validCountries as $validCountry){
                if ($validCountry === $billingCountry){
                    $valid=true;
                }
            }
            if(!$valid){
                $notValidException = new LocalizedException($this->countryMsg);
                $this->_pointcheckoutAlert->addExceptionMessage($notValidException,'Payment is not valid on '.$order->getBillingAddress()->getCountryId());
                throw $notValidException;
            }
        }
        
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
