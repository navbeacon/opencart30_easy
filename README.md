# NETS A/S - Opencart 3 Payment Module
============================================

|Module | Nets Easy Payment Module for Opencart 3
|------|----------
|Author | `Nets eCom`
|Prefix | `EASY-OC3`
|Shop Version | `3+`
|Version | `1.1.4`
|Guide | https://developers.nets.eu/nets-easy/en-EU/docs/nets-easy-for-opencart/nets-easy-for-opencart-opencart-30/
|Github | https://github.com/Nets-eCom/opencart30_easy

## INSTALLATION

### Download / Installation

You have two options when installing the Nets Easy module:

Alternative 1:

1. Download the plugin from above github link.
2. Extract the zip file.
3. Copy the extracted files and paste in Opencart root directory.
4. Login to Admin Panel.
5. Click on Admin -> Modifications Refresh button.
6. Go To Modules -> Admin -> Extensions -> Payments -> Easy Checkout.
7. Click on "Install" plus button.

Alternative 2:

1. It is possible to install module using modman.
2. Run the below commands in the terminal on opencart root directory.
	- git clone https://github.com/sitewards/modman-php OR Copy files into opencart root directory from https://github.com/sitewards/modman-php
	- php modman.php clone https://github.com/Nets-eCom/opencart30_easy
3. Apply modifications in (Admin -> Modifications) click Refresh button
4. Go To Modules -> Admin -> Extensions -> Payments -> Easy Checkout.
5. Click on "Install" plus button.

NOTE :

### Features

1. Supports shipping methods in embedded checkout
2. Supports traditional checkout with Hosted Payment Window
3. Supports discount, auto-capture, merchant terms url, cancel url and auto update payment status 
4. Fully syncronized payment statuses in admin order page with Easy portal. 
5. Intuitive order management with synchronized captures and refunds from admin order details page.
6. Custom webhook events for real-time payment statuses.
7. Custom Build-in debugging features.

### Configuration

1. To configure and setup the plugin navigate to : Admin > Extensions > Extensions > Choose the extension type > Payments 
2. Locate the Nets payment plugin and press the Edit button to access Configuration.

* Settings Description
1. Login to your Nets Easy account (https://portal.dibspayment.eu/). Test and Live Keys can be found in Company > Integration.
2. Payment Environment. Select between Test/Live transactions. Live mode requires an approved account. Testcard information can be found here: https://tech.dibspayment.com/easy/test-information 
3. Checkout Flow. Redirect / Embedded. Select between checkout types. Redirect - Nets Hosted loads a new payment page. Embedded checkout inserts the payment window directly on the checkout page.
4. Enable auto-capture. This function allows you to instantly charge a payment straight after the order is placed.
   NOTE. Capturing a payment before shipment of the order might be lia ble to restrictions based upon legislations set in your country. Misuse can result in your Easy account bei ng forfeit.
5. Webhook URL. It is the custom webhook event listener url, that handles payment statuses on your orders paid with Nets Easy.
6. Webhook Auth. Set your custom authorization password on incoming webhook event data to tighten security.
7. Debug mode. Optionally activate this feature and copy/send debug content to our support, in case you experience issues with your transactions.


### Operations

* Order Details / Order Status
1. Navigate to admin > Sales > Orders. Press on view (Eye Icon) to access order details.
2. Choose your desired action: cancel, capture, or refund. The Nets Easy plugin will synchronize automatically. The payment status will also be updated in Nets Easy portal.
4. All transactions by Nets are accessible in our portal : https://portal.dibspayment.eu/login

### Troubleshooting

* Nets payment plugin is not visible as a payment method
- Ensure the Nets plugin is available in the extension configuration.
- Edit the Easy Checkout configuration, Choose the status Enable.

* Nets payment window is blank
- Ensure your keys in Nets plugin Settings are correct and with no additional blank spaces.
- Temporarily deactivate 3.rd party plugins that might effect the functionality of the Nets plugin.
- Check if there is any temporary technical inconsistencies : https://nets.eu/Pages/operational-status.aspx

* Payments in live mode dont work
- Ensure you have an approved Live Easy account for production.
- Ensure your Live Easy account is approved for payments with selected currency.
- Ensure payment method data is correct and supported by your Nets Easy agreement.

### Contact

* Nets customer service
- Nets Easy provides support for both test and live Easy accounts. Contact information can be found here : https://nets.eu/en/payments/customerservice/

** CREATE YOUR FREE NETS EASY TEST ACCOUNT HERE : https://portal.dibspayment.eu/registration **
