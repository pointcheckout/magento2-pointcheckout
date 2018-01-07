Magento 2 extension for PointCheckout

Extract the content to your magento\app\code folder 

under magento\bin folder excute the folowing 

php magento module:status ( you should see PointCheckout_PointCheckoutPaymentGateway in disabled modules )

excute command

php magento module:enable PointCheckout_PointCheckoutPaymentGateway --clear-static-content

php magento setup:upgrade 

php setup:di:compile 

now go to your admin panal stores->configuration->sales->PaymentMethods you would find pointcheckout listed on the availble payment methods 
enter the configuration and save 


if the above successfully done your store is now fully loaded with most amazing payment gateway ever -- enjoy growing business 

