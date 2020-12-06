/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/action/place-order',
    ],
    function ($, Component, quote, fullScreenLoader, setPaymentInformationAction, placeOrder) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'PointCheckout_Rewards/payment/form'
            },
          
            getCode: function() {
                return 'pointcheckout_rewardgateway';
            },
            isActive: function() {
                return true;
            },
            context: function() {
                return this;
            },
            // Overwrite properties / functions
            redirectAfterPlaceOrder: false,
            
            /**
             * @override
             */
            placeOrder: function () {
                var self = this;
                var paymentData = quote.paymentMethod();
                paymentData = JSON.parse(JSON.stringify(paymentData));
                delete paymentData['title'];
                var messageContainer = this.messageContainer;
                fullScreenLoader.startLoader();
                this.isPlaceOrderActionAllowed(false);
                $.when(setPaymentInformationAction(this.messageContainer, {
                    'method': self.getCode()
                })).done(function () {
                        $.when(placeOrder(paymentData, messageContainer)).done(function () {
                            $.mage.redirect(window.checkoutConfig.payment.pointcheckout_rewardgateway.redirectUrl);
                        });
                }).fail(function () {
                    self.isPlaceOrderActionAllowed(true);
                }).always(function(){
                    fullScreenLoader.stopLoader();
                });
            }
        });
    }
);
