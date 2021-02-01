# magento2-pointcheckout
PointCheckout Rewards payment Magento 2 extension

Extract the extension archive content to your magento\app\code folder 

under magento root folder execute the following commands:

`sudo bin/magento module:enable PointCheckout_Rewards --clear-static-content`

`sudo bin/magento setup:upgrade`

`sudo bin/magento setup:di:compile`

now go to your admin panel `Stores`->`Configuration`->`Sales`->`Payment Methods` you will find PointCheckout Rewards listed on the available payment methods 

setup the configuration and set the Environment, Api key and Secret as required from PointCheckout
