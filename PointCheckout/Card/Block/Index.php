<?php
namespace PointCheckout\Card\Block;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Model\Order;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Sales\Helper\Reorder as ReorderHelper;
use Magento\Framework\App\ObjectManager;


use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Cart\CustomerCartResolver;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\GuestCart\GuestCartResolver;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as ItemCollection;


class Index extends \Magento\Framework\View\Element\Template
{
    protected $_session;
    protected $_logger;
    private $url;
    private $config;
    private $cart;
    private $quoteResourceModel;
    private $productFactory;
    private $quotePaymentToOrderPayment;
    private $paymentFactory;
    private $orderFactory;
    private $guestCartResolver;

    private $customerCartProvider;
    private $reorderHelper;
    private $checkoutSession;
    private $productCollectionFactory;
    private $quoteManagement;
    private $customerSession;
    private $checkoutHelper;
    private $cartManagement;
    private $quoteRepository;
    private $quoteItem;
    private $quote;

    private $pointCheckout;
    

    /**
     * @var \Magento\Sales\Model\Reorder\Reorder
     */
    private $reorder;

    public function __construct(\Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $session,
        ConfigInterface $config,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResourceModel,

        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\Quote\Payment\ToOrderPayment $quotePaymentToOrderPayment,
        \Magento\Quote\Model\Quote\PaymentFactory $paymentFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Quote\Model\Cart\CustomerCartResolver $customerCartProvider,
        \Magento\Sales\Helper\Reorder $reorderHelper, 
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Store\Model\StoreManagerInterface $storemanager,
        \Magento\Quote\Model\Quote\Item $quoteItem,

        ProductCollectionFactory $productCollectionFactory,
        \Magento\Quote\Model\GuestCart\GuestCartResolver $guestCartResolver,
        
        \PointCheckout\Card\Model\PointCheckout $pointCheckout,
        CheckoutSession $checkoutSession = null,
        \Magento\Sales\Model\Reorder\Reorder $reorder = null)
    {
        parent::__construct($context);
        $this->_session = $session;
        $this->config = $config;
        $this->cart = $cart;
        $this->url = $context->getUrlBuilder();
        $this->quoteResourceModel = $quoteResourceModel;
        $this->productFactory = $productFactory;
        $this->quotePaymentToOrderPayment = $quotePaymentToOrderPayment;
        $this->paymentFactory = $paymentFactory;
        $this->orderFactory = $orderFactory; 
        $this->guestCartResolver = $guestCartResolver;
        $this->customerCartProvider = $customerCartProvider;
        $this->checkoutSession = $checkoutSession ?: \Magento\Framework\App\ObjectManager::getInstance()->get(CheckoutSession::class);
        $this->reorderHelper = $reorderHelper;
        $this->pointCheckout = $pointCheckout;
        $this->quoteManagement = $quoteManagement;
        $this->customerSession = $customerSession;
        $this->checkoutHelper = $checkoutHelper;
        $this->cartManagement = $cartManagement;
        $this->quoteRepository = $quoteRepository;
        $this->storemanager = $storemanager;
        $this->quoteItem = $quoteItem;
        $this->quote = $quote;

        $pointCheckout->setConfig($config);
        $this->reorder = $reorder ?: ObjectManager::getInstance()->get(\Magento\Sales\Model\Reorder\Reorder::class);

        $this->productCollectionFactory = $productCollectionFactory;


        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/pc.log');
        $this->_logger = new \Zend\Log\Logger();
        $this->_logger->addWriter($writer);
    }

    public function  log($message) { 
        $this->_logger->info($message);
    }
    
    
    /**
     * 
     * @return string
     */
    public function getSuccessUrl()
    {
        return $this->url->getRouteUrl('checkout/onepage/success');
    }
    
    /**
     * 
     * @return string
     */

    public function getFailureUrl()
    {
        return $this->url->getRouteUrl('checkout/cart');
    }

    public function getStoreHome(){
        return $this->storemanager->getStore()->getBaseUrl();
    }

    public function getQuote(){
        $quote = $this->checkoutSession->getQuote();

        if(!$quote->getId()) {
            $quote =  $this->cart->getQuote();
        }
        if(!$quote->getId()) {
            $quote = $this->quote;
        }

        if(!$quote->getId()) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $onepage = $objectManager->get(\Magento\Checkout\Model\Type\Onepage::class);
            $quote = $onepage->getQuote();
        }

        return $quote;
    }

    public function isReadyForPayment($quote){
        $paymentcode = "";
        if ($quote->getPayment()->hasMethodInstance() || $quote->getPayment()->getMethod()) {
            $paymentcode = $quote->getPayment()->getMethodInstance()->getCode();
        }

        
        $this->log("isReadyForPayment : " . $paymentcode 
            .", id : " . $quote->getId()
            .", hasMethodInstance : " . ($quote->getPayment()->hasMethodInstance() ? "yes" : "no")
            . ", Method : " . ($quote->getPayment()->getMethod() ? "yes" : "no")
            . ", ItemsSet : " . ($quote->itemsCollectionWasSet() ? "yes" : "no")
            . ", hasItems : " . ($quote->hasItems() ? "yes" : "no"));

        return ($paymentcode == \PointCheckout\Card\Model\Ui\CardConfigProvider::CODE && $quote->itemsCollectionWasSet() && $quote->hasItems());
    }

    public function submitPayment($quote) {

        $paymentcode = "";
        if ($quote->getPayment()->hasMethodInstance() || $quote->getPayment()->getMethod()) {
            $paymentcode = $quote->getPayment()->getMethodInstance()->getCode();
        }

        $this->log("Before payment submit : " . $paymentcode 
            .", id : " . $quote->getId()
            .", hasMethodInstance : " . ($quote->getPayment()->hasMethodInstance() ? "yes" : "no")
            . ", Method : " . ($quote->getPayment()->getMethod() ? "yes" : "no")
            . ", ItemsSet : " . ($quote->itemsCollectionWasSet() ? "yes" : "no")
            . ", hasItems : " . ($quote->hasItems() ? "yes" : "no"));

        return  $this->pointCheckout->submitOrder($quote, true);
    }

    /**
     * getting the checkoutId from request and make an api call to confirm payment 
     */
    public function confirmPayment()
    {
        if(!isset($_REQUEST['checkout'])) {
            throw new \Exception("invalid return url, parameter `checkout` is required");
        }
        $checkoutId = $_REQUEST['checkout'];
        if (empty($checkoutId)) {
            throw new \Exception("invalid return url, parameter `checkout` is required");
        }

        try {
            $response = $this->pointCheckout->getCheckoutDetails($checkoutId);
            $failed = false;
            if ($response) {
                $response_info = json_decode($response);
            }

            $quote = $this->getQuote();

            if ($response_info && $response_info->success && $response_info->result->status == 'PAID') {
                $this->_logger->info("Order confirmed");
                $this->orderPaid($response_info, $quote);
            } else if (!$response_info) {
                $this->_logger->info("Failed to confirm order : failed to decode response [" . $response . "]");
                $failed = true;
            } else {
                // NOT PAID
                $failed = true;
            }

            if ($failed) {
                // see Magento\Quote\Model\Quote::reserveOrderId()
                $quote->setReservedOrderId($this->quoteResourceModel->getReservedOrderId($quote))->save();
            }
        } catch (\Exception $e) {
            $debugData['http_error'] = ['error' => $e->getMessage(), 'code' => $e->getCode()];
            $this->_logger->info("confirm error : " . $e->getMessage());
            throw $e;
        }

        return $response_info;
    }

    private function orderPaid($response_info, $quote)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        //if payment failed or pending order change to cancel so customer will notice that his order did not pass.

        // prepare quote for order submission
        if ($this->getCheckoutMethod($quote) === \Magento\Checkout\Model\Type\Onepage::METHOD_GUEST) {
            $this->prepareGuestQuote($quote);
        }
        $this->disabledQuoteAddressValidation($quote);
        $quote->collectTotals();

        $quote->save();
        $this->quoteRepository->save($quote);

        $order = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($response_info->result->referenceId);
        $this->_logger->info("order : " . $order->getId() . " - status :  " . $order->getStatus() . " - increment : " . $order->getIncrementId());
        if ($quote->getIsActive() && ( !$order->getId() || !$order->getStatus()) ) {
            $this->cartManagement->placeOrder($quote->getId());
        }


        // fetch the order
        $order = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($response_info->result->referenceId);

        if ($this->cart->getIsActive()) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            //$quoteId = get quote id 
            // $cartData = $objectManager->create('Magento\Quote\Model\QuoteRepository')->get($quoteId);
            $items = $quote->getAllItems();
            foreach ($items as $item) {
                $quoteItem = $this->quoteItem->load($item->getItemId());
                $quoteItem->delete(); //delete item
            }
            $this->cart->truncate()->save();
            $this->checkoutSession->clearQuote();

            $quote->setTotalsCollectedFlag(false);
            $this->_session->setCartWasUpdated(true);

        }

        // check if order paid fully
        if (
            round($order->getBaseGrandTotal(), 2) != round($response_info->result->grandtotal, 2) ||
            $order->getBaseCurrencyCode() != $response_info->result->currency
        ) {

            // something does not add up
            $order->setState(Order::STATUS_FRAUD)->setStatus(Order::STATE_CANCELED);

            $this->_logger->info(
                'Payment suspected fraud : Order Amount (' .  round($quote->getBaseGrandTotal(), 2) . ' ' . $quote->getBaseCurrencyCode()
                    . ') !=  Payment Amount (' . round($response_info->result->grandtotal, 2) . ' ' . $response_info->result->currency
            );

            $order->addStatusHistoryComment(
                'Payment suspected fraud : Order Amount (' .  round($quote->getBaseGrandTotal(), 2) . ' ' . $quote->getBaseCurrencyCode()
                    . ') !=  Payment Amount (' . round($response_info->result->grandtotal, 2) . ' ' . $response_info->result->currency
                    . '</b><br/>PointCheckout Status: <b style="color:red;">PAID</b><br/>PointCheckout Transaction ID: <b style="color:blue;">' . $response_info->result->id . '</b> <br/>'
            );

            $order->save();
        } else {
            // paid successfully
            $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);

            // Create Order From Quote
            $order->addStatusHistoryComment($this->getOrderHistoryMessage($response_info->result->status, $response_info->result->cash, $_REQUEST['checkout'], $order, true));
            $order->save();
        }
    }

    /**
     * 
     * @return string
     */
    public function getReturnUrl()
    {
        return $this->url->getRouteUrl('pointcheckoutcard/payment/confirm');
    }
    
    /*
     * 
     */
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
    
    private function getPointcheckoutApiUrl(){
        $mode = $this->config->getValue('pointcheckout_mode');
        if ($mode == '2'){
            return 'https://api.staging.pointcheckout.com';
        }elseif($mode == '1'){
            return 'https://api.pointcheckout.com';
        }
        return 'https://api.test.pointcheckout.com';
    }

    private function getAdminUrl(){
        $mode = $this->config->getValue('pointcheckout_mode');
        if ($mode == '2'){
            return 'https://admin.staging.pointcheckout.com';
        }elseif($mode == '1'){
            return 'https://admin.pointcheckout.com';
        }
        return 'https://admin.test.pointcheckout.com';
    }

    /**
     * Get checkout method
     *
     * @param Quote $quote
     * @return string
     */
    private function getCheckoutMethod($quote): string
    {
        if ($this->customerSession->isLoggedIn()) {
            return \Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER;
        }
        if (!$quote->getCheckoutMethod()) {
            if ($this->checkoutHelper->isAllowedGuestCheckout($quote)) {
                $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
            } else {
                $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER);
            }
        }

        return $quote->getCheckoutMethod();
    }

    /**
     * Prepare quote for guest checkout order submit
     *
     * @param Quote $quote
     * @return void
     */
    private function prepareGuestQuote($quote)
    {
        $quote->setCustomerId(null)
            ->setCustomerEmail($quote->getBillingAddress()->getEmail())
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
    }

    /**
     *
     */
    protected function disabledQuoteAddressValidation($quote)
    {
        $billingAddress = $quote->getBillingAddress();
        $billingAddress->setShouldIgnoreValidation(true);

        if (!$quote->getIsVirtual()) {
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setShouldIgnoreValidation(true);
            if (!$billingAddress->getEmail()) {
                $billingAddress->setSameAsBilling(1);
            }
        }
    }
}