# magento2-pointcheckout
PointCheckout Reward and Card payment Magento 2 extensions


Download the latest extensions archive from [Releases section](https://github.com/pointcheckout/magento2-pointcheckout/releases/latest), Extract the extension archive content to your {magento-home}\app\code folder.

###### - if the folder `{magento-root}/app/code` does not exist, create it 

you should have the following folders : 
```
 {magento-root}/app/code/PopintCheckout/Card
 {magento-root}/app/code/PopintCheckout/Rewads
```


under magento root folder execute the following commands:

`# Install Card payment extension`
`sudo bin/magento-cli module:enable PointCheckout_Card --clear-static-content`

`# Install Rewards payment extension`
`sudo bin/magento-cli module:enable PointCheckout_Rewards --clear-static-content`

`sudo bin/magento-cli setup:upgrade`

`sudo bin/magento-cli setup:di:compile`

now go to your admin pannel `Stores`->`Configuration`->`Sales`->`Payment Methods` you will find PointCheckout Card Payment and PointCheckout Rewards listed on the available payment methods 

setup the configuration and set the Environment, Api key and Secret as required from PointCheckout
