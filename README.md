# BerryPay Payment Gateway Wordpress Plugin using WooCommerce

> Contributors: BerryPay <br>
> Tags: payment gateway, Malaysia, online banking<br>
> Requires at least: 4.3<br>
> Tested up to: 5.8.2<br>
> Stable tag: 2.0.1<br>
> License: GPLv2 or later<br>
> License URI: http://www.gnu.org/licenses/gpl-2.0.html<br>	
> BerryPay payment gateway plugin for WooCommerce.

## Description
	
BerryPay payment gateway plugin for WooCommerce. This plugin enable online payment using online banking (for Malaysian banks only). Currently BerryPay is only available for businesses that reside in Malaysia.
	
## Supported version:
* Supports Wordpress 5.8.2
* Supports Woocommerce 6.0.0

## Installation

1. Download this plugin using .Zip format folder.
2. Make sure that you already have WooCommerce plugin installed and activated.
3. From your Wordpress admin dashboard, go to menu 'Plugins' and 'Add New'.
4. Upload the .Zip format folder inside Wordpress Dashboard.
5. It will display the plugin and press intall.
6. Activate the plugin through the 'Plugins' screen in WordPress.
7. Go to menu WooCommerce, settings, Checkout, BerryPay and fill in your "merchant_id" as "merchant_pub_key", api_key and secret_key. You can retrieve the merchant id, api_key and secret_key from BerryPay Dashboard at https://securepay.berrypay.com (login credentials will be provided upon successful merchant registration).
8. By default the hash type will be md5, but you may change it later to sha256 which is strongly recommended by BerryPay.You may refer to the hash type defined in BerryPay dashboard.
9. The environment mode by default is live. However if your website is not ready for production but for testing, you may choose sandbox (This required you to have sandbox account). 
10. Make sure the 'Enable this payment gateway' is ticked. Click on 'Save changes' button.
11. In BerryPay Dashboard make sure you key in your return URL and callback URL as http://your_domain/checkout/ and choose 'Read response and send email if error' for callback response, finally press Save. Please leave the 'Return URL Parameters' field empty.

## Frequently Asked Questions
	
**Do I need to sign up with BerryPay in order to use this plugin?**
	
Yes, we require info such as merchant id and secret key that is only available after you sign up with BerryPay.
	
**Can I use this plugin without using WooCommerce?**
	
No.
	
**What currency does it support?**
	
Currently BerryPay only support Malaysian Ringgit (RM).
	
**What if I have some other question related to BerryPay?**
	
Please open a ticket by emailing to us, servicedesk@berrypay.com.
