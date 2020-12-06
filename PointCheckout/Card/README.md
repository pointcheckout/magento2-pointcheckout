# magento2-pointcheckout
PointCheckout Card payment Magento 2 extension

Extract the extension archive content to your magento\app\code folder 

under magento root folder execute the following commands:

`sudo bin/magento-cli module:enable PointCheckout_Card --clear-static-content`

`sudo bin/magento-cli setup:upgrade`

`sudo bin/magento-cli setup:di:compile`

now go to your admin panel `Stores`->`Configuration`->`Sales`->`Payment Methods` you will find PointCheckout Card Payment listed on the available payment methods 

setup the configuration and set the Environment, Api key and Secret as required from PointCheckout
