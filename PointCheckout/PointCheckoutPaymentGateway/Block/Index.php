<?php

namespace PointCheckout\PointCheckoutPaymentGateway\Block;

/**
 * Copyright © 2017 PointCheckout. All rights reserved.
 */

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Sales\Model\Order;

class Index extends \Magento\Framework\View\Element\Template
{
    protected $_session;
    protected $url;
    private $config;
    
    
    public function __construct(\Magento\Framework\View\Element\Template\Context $context,ConfigInterface $config,
        \Magento\Checkout\Model\Session $session)
    {
        parent::__construct($context);
        $this->_session = $session;
        $this->config = $config;
        $this->url = $context->getUrlBuilder();
    }
    
    /**
     * getting the checkoutId from request and make an api call to confirm payment 
     */
    public function confirmPayment()
    {
        try {
            // create a new cURL resource
            $headers = array(
                'Content-Type: application/json',
                'Api-Key:'.$this->config->getValue('point_checkout_api_key'),
                'Api-Secret:'.$this->config->getValue('point_checkout_api_secret')
            );
            $_BASE_URL=$this->getPointcheckoutBaseUrl();
            $ch = curl_init($_BASE_URL.'/api/v1.0/checkout/'.$_REQUEST['checkout']);
            
            // set URL and other appropriate options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
            // grab URL and pass it to the browser
            $response = curl_exec($ch);
            if($response)
               $response_info = json_decode($response);
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $order = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($_REQUEST['reference']);
            if (!$response ){
                //if payment failed or pending order change to cancel so customer will notice that his order did not pass.
                $orderState = Order::STATE_CANCELED;
                $order->setState($orderState)->setStatus(Order::STATE_CANCELED);
                $order->addStatusHistoryComment('payment by pointcheckout failed ');
                $order->save();
            }else if ($response_info->success && $response_info->result->status == 'PAID'){
                //if payment failed or pending order change to cancel so customer will notice that his order did not pass.
                $order->addStatusHistoryComment($this->getOrderHistoryMessage($response_info->result->status, $response_info->result->cod, $_REQUEST['checkout'], $order,true));
                $order->save();
            }else if(!$response_info->success){
                //if payment failed or pending order change to cancel so customer will notice that his order did not pass.
                $orderState = Order::STATE_CANCELED;
                $order->setState($orderState)->setStatus(Order::STATE_CANCELED);
                $order->addStatusHistoryComment('payment by pointcheckout failed [ERROR_MSG] = '.$response_info->error);
                $order->save();
            }else{//success true but with unpaid status
                $order->addStatusHistoryComment($this->getOrderHistoryMessage($response_info->result->status, $response_info->result->cod, $_REQUEST['checkout'], $order,true));
                $order->save();
            }
            // close cURL resource, and free up system resources
            curl_close($ch);
            
            
        } catch (\Exception $e) {
            $debugData['http_error'] = ['error' => $e->getMessage(), 'code' => $e->getCode()];
            throw $e;
        }
        
        return $response;
    }
    
    
    /**
     * 
     * @param STRING $key
     * @param STRING $value
     * @return STRING
     */
    public function setSessionData($key, $value)
    {
        return $this->_session->setData($key, $value);
    }
    
    /**
     * 
     * @param STRING $key
     * @param STRING $remove
     * @return STRING
     */
    
    public function getSessionData($key, $remove = false)
    {
        return $this->_session->getData($key, $remove);
    }
    
    
    /**
     * 
     * @return string
     */
    public function getSuccessUrl()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($_REQUEST['reference']);
        $orderState = Order::STATE_PROCESSING;
        $order->setState($orderState)->setStatus(Order::STATE_PROCESSING);
        $order->save();
        return $this->url->getRouteUrl('checkout/onepage/success');
    }
    
    /**
     * 
     * @return string
     */

    public function getFailureUrl()
    {
        
        return $this->url->getRouteUrl('checkout/onepage/failure');
    }
    
    /**
     * 
     * @return string
     */
    public function getPointCheckoutUrl()
    
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($this->_session->getData('referenceId'));
        $orderState = Order::STATE_PENDING_PAYMENT;
        $order->setState($orderState)->setStatus($this->config->getValue('order_status'));
        $order->addStatusHistoryComment($this->getOrderHistoryMessage("PENDING", 0, $this->_session->getData('checkoutId'), $order,false));
        $order->save();
        return $this->getPointcheckoutBaseUrl().'/checkout/'.$this->_session->getData('checkoutKey').'?returnUrl='.$this->url->getRouteUrl('pointcheckout/payment/confirm');
    }
    
    /*
     * 
     */
    
    private function getPointcheckoutBaseUrl(){
        if ($this->config->getValue('point_checkout_mode') == '2'){
            return 'https://pay.staging.pointcheckout.com';
        }elseif(!$this->config->getValue('point_checkout_mode')){
            return 'https://pay.pointcheckout.com';
        }
        return 'https://pay.test.pointcheckout.com';
    }
    
    private function getOrderHistoryMessage($orderStatus,$codAmount,$checkout,$order,$onConfirm){
        switch($orderStatus){
            case 'PAID':
                $color='style="color:green;"';
                break;
            case 'PENDING':
                $color='style="color:BLUE;"';
                break;
            default:
                $color='style="color:RED;"';
        }
        $message = 'PointCheckout Status: <b '.$color.'>'.$orderStatus.'</b><br/>PointCheckout Transaction ID: <b style="color:blue;">'.$checkout.'</b> <br/>';
        if(!$onConfirm){
            $message .= 'Transaction Url: <b style="color:#a26a7b;">'. $this->getAdminUrl().'/merchant/transactions/'.$checkout.'/read </b>'."\n" ;
        }
        if($codAmount>0){
            $message.= '<b style="color:red;">[NOTICE] </b><i>COD Amount: <b>'.$codAmount.' '.$order->getCurrencyCode().'</b></i>'."\n";
        }
        
        return $message;
    }
    
    
    private function getAdminUrl(){
        if ($this->config->getValue('point_checkout_mode') == '2'){
            $_ADMIN_URL='https://admin.staging.pointcheckout.com';
        }elseif(!$this->config->getValue('point_checkout_mode')){
            $_ADMIN_URL='https://admin.pointcheckout.com';
        }else{
            $_ADMIN_URL='https://admin.test.pointcheckout.com';
        }
  
        return $_ADMIN_URL;
        
    }
    /**
     * 
     * @return string
     */
    
    public function getReturnUrl()
    
    {
        return $this->url->getRouteUrl('pointcheckout/payment/confirm');
    }
    
    
}