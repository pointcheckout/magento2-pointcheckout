
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'pointcheckout_cardgateway',
                component: 'PointCheckout_Card/js/view/payment/method-renderer/pointcheckout_cardgateway'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
