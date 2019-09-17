# magento2-pointcheckout
Magento 2 extension for PointCheckout

Extract the content to your magento\app\code folder 

under magento\bin folder excute the folowing commands :

`sudo sh magento-cli module:enable PointCheckout_PointCheckoutPaymentGateway --clear-static-content`

`sudo sh magento-cli setup:upgrade`

`sudo sh magento-cli setup:di:compile`

now go to your admin panal stores->configuration->sales->PaymentMethods you would find pointcheckout listed on the available payment methods 

setup the configuration and set the Environment, Api key and Secret as aquired from PoinrCheckout
