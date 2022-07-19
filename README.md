# Shopware Quick Order Plugin
A simple plugin to add multiple items with desired quantities to the cart at once.

## Installation

    1. Clone this repository into /src/custom/plugins folder
    2. cd into your src directory
    3. Run `php bin/console plugin:refresh`
    3. Run `php bin/console plugin:install --activate MFQuickOrder`
    4. Run `php bin/console cache:clear`
    5. Lastly visit `/quick-order` route in your browser and you should be able to see the quick order form