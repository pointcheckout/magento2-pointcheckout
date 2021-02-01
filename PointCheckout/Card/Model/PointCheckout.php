<?php

namespace PointCheckout\Card\Model;

use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Checkout\Block\Cart\Totals;
use Magento\Payment\Gateway\ConfigInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
 
class PointCheckout
{
    const PC_EXT_VERSION = "Magento2-Card-2.1.0";

    const API_URL = 'https://api.pointcheckout.com/mer/v1.2/';
    
    const API_TEST_URL = 'https://api.test.pointcheckout.com/mer/v1.2/';

    const SUCCESS = 1;

    const FAILURE = 0;

     
    /**
     * @var \Magento\Checkout\Block\Cart\Totals
     */
     protected $_config;
     protected $_totals;
     protected $_logger;
     protected $_urlBuilder;
     protected $_quoteRepository;

    /**
     * @param \Magento\Framework\App\Helper\Context $context,
     * @param \Magento\Checkout\Block\Cart\Totals $totals
     */
	
	
    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Quote\Model\ResourceModel\Quote $quoteRepository,
        \Magento\Checkout\Block\Cart\Totals $totals
    ) {
        $this->_totals = $totals;
        $this->_urlBuilder = $urlBuilder;
        $this->_quoteRepository = $quoteRepository;

		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/pc.log');
        $this->_logger = new \Zend\Log\Logger();
		$this->_logger->addWriter($writer);
    }
    
    public function setConfig($config){ 
        $this->_config = $config;
    }

    /**
     * Post order to pointcheckout and return response
     */
    public function submitOrder($quote, $retry = true) {
        $request = $this->getRequestBody($quote);
        $this->_logger->info("pointcheckout.getRequestBody : " . json_encode($request));

        try {
            // create a new cURL resource
            $headers = array(
                'Content-Type: application/json',
                'X-PointCheckout-Api-Key:' . $this->getConfigData('pointcheckout_api_key'),
                'X-PointCheckout-Api-Secret:'.$this->getConfigData('pointcheckout_api_secret')
            );

            $ch = curl_init($this->getCheckoutUrl());
            
            // set URL and other appropriate options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS ,json_encode($request));
            $objectData = json_encode($request);
            // grab URL and pass it to the browser
            $response = curl_exec($ch);

            $this->_logger->info("pointcheckout.response : " . $response);

            
            if (!$response){
                $this->_logger->debug(
                    [
                        'request' => $request,
                        'response' =>$response
                    ]
                    );
                //here if there is no response from PointCheckout throw exception so user stay in payment stage and have the chance to try again 
                throw new \Exception('no response');
            }
            // close cURL resource, and free up system resources
            curl_close($ch);
       
            
        } catch (\Exception $e) {
            $debugData['http_error'] = ['error' => $e->getMessage(), 'code' => $e->getCode()];

            $this->_logger->info("pointcheckout.error : " . $e->getMessage());
            throw $e;
        }
        $response_info = json_decode($response);
        if (($response_info->success == 'true' && $response_info->result->id != null )) {
            // $this->_session->clearStorage();
            // $this->_session->setData('pointcheckoutRedirectUrl', $response_info->result->redirectUrl);
            // //add checkoutId in session to use it in confirm controller
            // $this->_session->setData('checkoutId',$response_info->result->id);
            // //add referenceId as it is the order id to change the order status in later stages 
            // $this->_session->setData('referenceId',$response_info->result->referenceId);

            $response = $this->generateResponseForCode(
                self::SUCCESS,$response_info
                );
        }else{
            // handle A checkout already exists for this merchant with transaction id 'xyz' error
            if($retry  && stripos($response_info->error, "A checkout already exists for this merchant with transaction id" ) !== false){
                // reserve new Order ID
                $quote->setReservedOrderId($this->_quoteRepository->getReservedOrderId($quote))->save();
                // try submitting the order again 
                return $this->submitOrder($quote, false);
            }
            $response = $this->generateResponseForCode(
                self::FAILURE,$response_info
                );
                $this->_logger->debug(
               [
                   'cause' => 'error',
                   'message' =>$response_info->error
               ]
               );
            $this->_logger->info("pointcheckout.request.error : " . $response_info->error);
        }
        return $response_info;
    }

    public function getCheckoutDetails($checkoutId)
    {
        // create a new cURL resource
        $headers = array(
            'Content-Type: application/json',
            'X-PointCheckout-Api-Key:' . $this->getConfigData('pointcheckout_api_key'),
            'X-PointCheckout-Api-Secret:' . $this->getConfigData('pointcheckout_api_secret')
        );
        $endpoint = $this->getCheckoutUrl() . '/' . $checkoutId;

        $this->_logger->info("confirming order : " . $endpoint);

        $ch = curl_init($endpoint);

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // grab URL and pass it to the browser
        $response = curl_exec($ch);
        // close cURL resource, and free up system resources
        curl_close($ch);

        return $response;
    }
    
    /**
     * 
     */
    private function getConfigData(string $key){
        $val =  $this->_config->getValue($key);
        return $val;
    }
    
    /**
     * 
     */
    private function getCheckoutUrl(){
        $mode = $this->getConfigData('pointcheckout_mode');
        if ($mode == '2'){
            return 'https://api.staging.pointcheckout.com/mer/v1.2/checkouts';
        }elseif($mode == '1'){
            return 'https://api.pointcheckout.com/mer/v1.2/checkouts';
        }
        return 'https://api.test.pointcheckout.com/mer/v1.2/checkouts';
    }

    private function getRequestBody($quote) {
        // $this->checkoutSession->getQuote();

        $quote->collectTotals()->save();

        $cartItems= $quote->getAllItems();
        $items = array();
        $i = 0;
        foreach ($cartItems as $orderItem){
            $item = (object) array(
                'name'=> $orderItem->getName(),
                'sku' => $orderItem->getSku(),
                'quantity' => $orderItem->getQty(),
                'type'=>$orderItem->getProductType(),
                'total' => (intval($orderItem->getQty())*intval($orderItem->getPrice())));
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
        if(!$quote->getReservedOrderId()) {
            $quote->reserveOrderId()->save();
        }
        $storeOrder['transactionId'] = $quote->getReservedOrderId();

        // SAMEER
        $storeOrder['paymentMethods'] = ["CARD"];
        $storeOrder['extVersion'] = self::PC_EXT_VERSION;
        $storeOrder['ecommerce'] = 'Magento2 ' . $this->getMagentoVersion();


        $storeOrder['items'] = array_values($items);
        //collecting totals
        //the only model that did not return zero values for shipping and tax \Magento\Checkout\Block\Cart\Totals
        $totals = $this->_totals->getTotalsCache();

        $storeOrder['amount']   = round($quote->getBaseGrandTotal(), 2);
        $storeOrder['currency'] = $quote->getBaseCurrencyCode();


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

        //prepare customer Information
        $customer = array();

        $customer['id'] = $quote->getCustomerId() != null && strlen($quote->getCustomerId()) > 0 ? $quote->getCustomerId() : null;
        $customer['firstName'] = $quote->getBillingAddress()->getFirstname();
        $customer['lastName'] = $quote->getBillingAddress()->getLastname();
        $customer['email'] = $quote->getBillingAddress()->getEmail();
        $customer['phone'] = $quote->getBillingAddress()->getTelephone();

        $billingAddress = array();
        $billingAddress['name'] = $quote->getBillingAddress()->getFirstname(). ' ' .$quote->getBillingAddress()->getLastname();
        $billingAddress['address1'] = $quote->getBillingAddress()->getStreetLine1();
        $billingAddress['address2'] = $quote->getBillingAddress()->getStreetLine2();
        $billingAddress['city'] = $quote->getBillingAddress()->getCity();
        $billingAddress['country'] = $quote->getBillingAddress()->getCountryId();
        $customer['billingAddress'] = $billingAddress;
        
        $shippingAddress = array();
        $shippingAddress['name'] = $quote->getShippingAddress()->getFirstname(). ' ' .$quote->getShippingAddress()->getLastname();
        $shippingAddress['address1'] = $quote->getShippingAddress()->getStreetLine1();
        $shippingAddress['address2'] = $quote->getShippingAddress()->getStreetLine2();
        $shippingAddress['city'] = $quote->getShippingAddress()->getCity();
        $shippingAddress['country'] = $quote->getShippingAddress()->getCountryId();
        $customer['shippingAddress'] = $shippingAddress;

        $storeOrder['customer'] = $customer;
        $storeOrder['resultUrl']  = $this->getPointCheckoutReturnUrl();

        return $storeOrder;
    }

    private function getPointCheckoutReturnUrl() {
        $params = [];
        return $this->_urlBuilder->getUrl('pointcheckoutcard/payment/confirm', $params);
    }

    /**
     * Generates response
     *
     * @return array
     */
    private function generateResponseForCode($resultCode,$response)
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

    private function getMagentoVersion() {
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
            return $productMetadata->getVersion();
        } catch( \Exception $ex ){
        }

        try{
            return \Magento\Framework\AppInterface::VERSION;
        } catch( \Exception $ex ){
        }
        return "N.A.";
    }
}
