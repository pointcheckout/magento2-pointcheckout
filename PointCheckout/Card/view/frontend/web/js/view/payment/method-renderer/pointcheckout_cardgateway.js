/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
    ],
    function ($,
                Component,
                placeOrderAction,
                selectPaymentMethodAction,
                customer,
                checkoutData,
                additionalValidators,
                url
        ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'PointCheckout_Card/payment/form'
            },

            getCode: function() {
                return 'pointcheckout_cardgateway';
            },
            isActive: function() {
                return true;
            },
            context: function() {
                return this;
            },
            getCardImage: function() {
                try {
                    if(window.checkoutConfig.payment.pointcheckout_cardgateway.cardsImage)
                        return window.checkoutConfig.payment.pointcheckout_cardgateway.cardsImage;
                } catch(ex) {
                }
                return "https://static.pointcheckout.com/1763833054d8ffc9/original";
                // var self = this;
                // return self.getViewFileUrl( 'PointCheckout_Card::images/pointcheckout-cards.png' );
            },

            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }
                var self = this,
                        placeOrder,
                        emailValidationResult = customer.isLoggedIn(),
                        loginFormSelector = 'form[data-role=email-with-possible-login]';
                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }
                if (emailValidationResult && this.validate() && additionalValidators.validate()) {
                    // this.isPlaceOrderActionAllowed(false);
                    // placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

                    // $.when(placeOrder).fail(function () {
                    //     self.isPlaceOrderActionAllowed(true);
                    // }).done(this.afterPlaceOrder.bind(this));
                    // return true;
                    window.location.replace(url.build('cardredirect/payment/redirect/'));
                }
                return false;
            },

            selectPaymentMethod: function () {
                selectPaymentMethodAction(this.getData());
                checkoutData.setSelectedPaymentMethod(this.item.method);
                return true;
            },

            afterPlaceOrder: function () {
                // window.location.replace(url.build('cardredirect/payment/redirect/'));
            },
            /** Returns send check to info */
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            }
        });
});
