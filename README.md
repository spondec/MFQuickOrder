# Shopware 6 Quick Order Plugin
A simple plugin to add multiple items with desired quantities to the cart at once.

## Installation

* Clone this repository into `/src/custom/plugins` folder
* `cd` into your `src` directory
* Run the following commands in order
```bash 
php bin/console plugin:refresh
```
```bash 
php bin/console plugin:install --activate MFQuickOrder
```
```bash 
php bin/console cache:clear
```
* Lastly, visit `/quick-order` route in your browser and you should be able to see the quick order form.
  * If you can't see the quick order page, clear caches at `/admin#/sw/settings/cache/index`.
