<?php
/**
 * Copyright © 2017 PointCheckout. All rights reserved.
 */
namespace PointCheckout\PointCheckoutPaymentGateway\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Payment\Gateway\ConfigInterface;
    



 class ClientMock   implements ClientInterface 
{
    const SUCCESS = 1;
    const FAILURE = 0;

    /**
     * @var array
     */
    private $results = [
        self::SUCCESS,
        self::FAILURE
    ];

    /**
     * @var Logger
     */
    private $logger;
    private $config;
    protected $_session;
    /**
     * @var EventManager
     */
    
   

    /**
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger,ConfigInterface $config,
        \Magento\Checkout\Model\Session $session
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->_session = $session;
    }
    
    
    
    

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        
        try {
            // create a new cURL resource
            $headers = array(
                'Content-Type: application/json',
                'Api-Key:'.$this->config->getValue('point_checkout_api_key'),
                'Api-Secret:'.$this->config->getValue('point_checkout_api_secret')
            );
            
            $_BASE_URL='';
            if ($this->config->getValue('point_checkout_mode') == '2'){
                $_BASE_URL='https://pay.staging.pointcheckout.com';
            }elseif(!$this->config->getValue('point_checkout_mode')){
                $_BASE_URL='https://pay.pointcheckout.com';
            }else{
                $_BASE_URL='https://pay.test.pointcheckout.com';
            }
            $ch = curl_init($_BASE_URL.'/api/v1.0/checkout');
            
            // set URL and other appropriate options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS ,json_encode($transferObject->getBody()));
            $objectData = json_encode($transferObject->getBody());
            // grab URL and pass it to the browser
            $response = curl_exec($ch);
            
            if (!$response){
                $this->logger->debug(
                            [
                                    'request' => $transferObject->getBody(),
                                    'response' =>$response
                                ]
                        );
                //here if there is no response from PointCheckout throw exception so user stay in payment stage and have the chance to try again 
                throw new \Exception();
            }else{
            }
            // close cURL resource, and free up system resources
            curl_close($ch);
       
            
        } catch (\Exception $e) {
            $debugData['http_error'] = ['error' => $e->getMessage(), 'code' => $e->getCode()];
            throw $e;
        }
        $response_info = json_decode($response);
        if (($response_info->success == 'true' && $response_info->result->checkoutKey != null )) {
            $this->_session->clearStorage();
            //add checkoutKey in session to use it in redirect controller
            $this->_session->setData('checkoutKey',$response_info->result->checkoutKey);
            //add checkoutId in session to use it in confirm controller
            $this->_session->setData('checkoutId',$response_info->result->checkoutId);
            //add referenceId as it is the order id to change the order status in later stages 
            $this->_session->setData('referenceId',$response_info->result->referenceId);
            
            $response = $this->generateResponseForCode(
                $this->results[0]
                );
            return $response;
        }else{
            $response = $this->generateResponseForCode(
                $this->results[1]
                );
            return $response;
        }
        
    }

    /**
     * Generates response
     *
     * @return array
     */
    protected function generateResponseForCode($resultCode)
    {

        return array_merge(
            [
                'RESULT_CODE' => $resultCode,
                'TXN_ID' => $this->generateTxnId(),
                
            ],
            $this->getFieldsBasedOnResponseType($resultCode)
        );
    }

    /**
     * @return string
     */
    protected function generateTxnId()
    {
        return md5(mt_rand(0, 1000));
    }


    /**
     * Returns response fields for result code
     *
     * @param int $resultCode
     * @return array
     */
    private function getFieldsBasedOnResponseType($resultCode)
    {
        switch ($resultCode) {
            case self::FAILURE:
                return [
                    'FRAUD_MSG_LIST' => [
                        'Stolen card',
                        'Customer location differs'
                        
                    ]
                ];
        }

        return [];
    }
    

}
