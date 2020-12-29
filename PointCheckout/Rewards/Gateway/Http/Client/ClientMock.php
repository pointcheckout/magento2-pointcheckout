<?php
namespace PointCheckout\Rewards\Gateway\Http\Client;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
            



class ClientMock  implements ClientInterface 
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
                'X-PointCheckout-Api-Key:'.$this->config->getValue('pointcheckout_api_key'),
                'X-PointCheckout-Api-Secret:'.$this->config->getValue('pointcheckout_api_secret')
            );

            $ch = curl_init($this->getCheckoutUrl());
            
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
            }
            // close cURL resource, and free up system resources
            curl_close($ch);
       
            
        } catch (\Exception $e) {
            $debugData['http_error'] = ['error' => $e->getMessage(), 'code' => $e->getCode()];
            throw $e;
        }
        $response_info = json_decode($response);
        if (($response_info->success == 'true' && $response_info->result->id != null )) {
            $this->_session->clearStorage();
            $this->_session->setData('pointcheckoutRedirectUrl', $response_info->result->redirectUrl);
            //add checkoutId in session to use it in confirm controller
            $this->_session->setData('checkoutId',$response_info->result->id);
            //add referenceId as it is the order id to change the order status in later stages 
            $this->_session->setData('referenceId',$response_info->result->referenceId);

            $response = $this->generateResponseForCode(
                self::SUCCESS,$response_info
                );
            return $response;
        }else{
            $response = $this->generateResponseForCode(
                self::FAILURE,$response_info
                );
            $this->logger->debug(
               [
                   'cause' => 'error',
                   'message' =>$response_info->error
               ]
               );
            return $response;
        }
    }

    /**
     * Generates response
     *
     * @return array
     */
    protected function generateResponseForCode($resultCode,$response)
    {
        return array_merge(
            [
                'RESULT_CODE' => $resultCode,
                'ERROR'       => isset($response->error)? $response->error : "",
                'TXN_ID'      => isset($response->error)? "" : $response->result->id
            ],
            $this->getFieldsBasedOnResponseType($resultCode)
        );
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
                    'ERROR_MSG_LIST' => [
                        'error connecting to pointcheckout'
                    ]
                ];
        }

        return [];
    }
    
    /**
     * 
     */
    private function getCheckoutUrl(){
        $mode = $this->config->getValue('pointcheckout_mode');
        if ($mode == '2'){
            return 'https://api.staging.pointcheckout.com/mer/v1.2/checkouts';
        }elseif($mode == '1'){
            return 'https://api.pointcheckout.com/mer/v1.2/checkouts';
        }
        return 'https://api.test.pointcheckout.com/mer/v1.2/checkouts';
    }
}
