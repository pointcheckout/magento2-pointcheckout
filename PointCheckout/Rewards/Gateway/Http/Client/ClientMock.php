<?php

namespace PointCheckout\Rewards\Gateway\Http\Client;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
class ClientMock  implements ClientInterface
{

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        return [
            'RESULT_CODE' => 1
        ];
    }
}
