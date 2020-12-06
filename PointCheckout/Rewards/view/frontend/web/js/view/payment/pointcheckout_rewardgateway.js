
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
                type: 'pointcheckout_rewardgateway',
                component: 'PointCheckout_Rewards/js/view/payment/method-renderer/pointcheckout_rewardgateway'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
