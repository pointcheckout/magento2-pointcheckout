 /**
 * Copyright © 2017 PointCheckout. All rights reserved.
 */
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
                type: 'pointcheckout_gateway',
                component: 'Magento_PointcheckoutPaymentGateway/js/view/payment/method-renderer/pointcheckout_gateway'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
