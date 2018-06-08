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
            $_BASE_URL= $this->getPointcheckoutBaseUrl();
            $ch = curl_init($_BASE_URL.'/api/v1.0/checkout/'.$_REQUEST['checkout']);
            
            // set URL and other appropriate options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
            // grab URL and pass it to the browser
            $response = curl_exec($ch);
            if($response)
               $response_info = json_decode($response);
            
               if (!$response || ($response_info->success == 'true' && $response_info->result->status != 'PAID' && $response_info->result->status != 'PENDING')){
                //if payment failed or pending order change to cancel so customer will notice that his order did not pass.
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $order = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($_REQUEST['reference']);
                $orderState = Order::STATE_CANCELED;
                $order->setState($orderState)->setStatus(Order::STATE_CANCELED);
                $order->addStatusHistoryComment('payment by pointcheckout failed ');
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
        
        return $this->url->getRouteUrl('checkout/onepage/failure'');
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
        $order->setState($orderState)->setStatus(Order::STATE_PENDING_PAYMENT);
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
    
    /**
     * 
     * @return string
     */
    
    public function getReturnUrl()
    
    {
        return $this->url->getRouteUrl('pointcheckout/payment/confirm');
    }
    
    
}