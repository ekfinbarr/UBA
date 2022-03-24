# UBA DHL Express Shipping for Magento 2.x
---------------------------
DHL offers a convenient plug-in for Magento 2 online stores. This plug-in allows you to add multiple online delivery options and to print shipping labels directly in your online store, which makes shipping packages significantly easier and a lot more fun. Please note that this plug-in is only available for online stores that ship orders from the Benelux region.

# Install / Update
## Update instructions
- If you're installed a previous version with composer with the recommended version range, just run the following commands to complete the update  
`composer update ekfinbarr/UBA:~1.0.0`  
`php bin/magento setup:upgrade`  
`php bin/magento setup:di:compile (only for production environments)`

## Installation with composer
- Add the plugin to your composer with the command (recommended version range)  
`composer require ekfinbarr/uba:~1.0.0`

- Enable the DHL module by executing the following from the Magento root:  
`php bin/magento module:enable UBA_DHLExpress`

- Upgrade the database  
`php bin/magento setup:upgrade`

- When running in production, complete the installation by recompiling  
`php bin/magento setup:di:compile`


- Enable the DHL module by executing the following from the Magento root:  
`php bin/magento module:enable UBA_DHLExpress`

- Upgrade the database  
`php bin/magento setup:upgrade`

- When running in production, complete the installation by recompiling  
`php bin/magento setup:di:compile`
