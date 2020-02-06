=== Twispay Credit Card Payments ===
Contributors: twispay
Tags: payment, gateway, module
Requires at least: OpenCart 3.0.0.0
Tested up to: OpenCart 3.0.3.2

Twispay enables new and existing store owners to quickly and effortlessly accept online credit card payments over their OpenCart 3 shop

Description
===========

Credit Card Payments by Twispay is the official [payment module for OpenCart](https://www.twispay.com/opencart) which allows for a quick and easy integration to Twispay’s Payment Gateway for accepting online credit card payments through a secure environment and a fully customisable checkout process. Give your customers the shopping experience they expect, and boost your online sales with our simple and elegant payment plugin.

[Twispay](https://www.twispay.com) is a European certified acquiring bank with a sleek payment gateway optimized for online shops. We process payments from worldwide customers using Mastercard or Visa debit and credit cards. Increase your purchases by using our conversion rate optimized checkout flow and manage your transactions with our dashboard created specifically for online merchants like you.

Twispay provides merchants with a lean way of accessing a complete portfolio of online payment services at the most competitive rates. For more details concerning our pricing in your area, please check out our [pricing page](https://www.twispay.com/prices). To use our payment module and start processing you will need a [Twispay merchant account](https://merchant-stage.twispay.com/register). For any assistance during the on-boarding process, our [sales and compliance](https://www.twispay.com/contact) team are happy to assist you with any enquiries you may have.

We take pride in offering world class, free customer support to all our merchants during the integration phase, and at any time thereafter. Our [support team](https://www.twispay.com/contact) is available non-stop during regular business hours EET.

Our OpenCart 3 payment extension allows for fast and easy integration with the Twispay Payment Gateway. Quickly start accepting online credit card payments through a secure environment and a fully customizable checkout process. Give your customers the shopping experience they expect, and boost your online sales with our simple and elegant payment plugin.

At the time of purchase, after checkout confirmation, the customer will be redirected to the secure Twispay Payment Gateway.

All payments will be processed in a secure PCI DSS compliant environment so you don't have to think about any such compliance requirements in your web shop.

Install
=======

### Automatic
1. Download the Twispay payment module from Opencart Marketplace, where you can find [The Official Twispay Payment Gateway Extension](https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=31761&filter_member=twispay).

2. Use the Opencart 3 Extension Installer and upload the OCmod archive.

### Manually
1. Download the Twispay payment module from our [Github repository](https://github.com/Twispay/OpenCart3.0).

2. Unzip the archive and upload the content of folder “uploads” (from inside the archive) inside the root directory of your opencart 3 store from the server.

### ========

3. Sign in to your OpenCart admin.

4. Click **Extensions** tab and **Payments subtab**.

5. Under **Twispay** click **Install** and then click **Edit**.

6. Select **Enabled** under **Status**.

7. Select **No** under **Test Mode**. _(Unless you are testing)_

8. Enter your **Account ID**. _(Twispay Staging Account ID)_ https://merchant-stage.twispay.com/login

9. Enter your **Secret Key**. _(Twispay Secret Key)_ https://merchant-stage.twispay.com/login

10. Enter your tehnical **Contact Email**. _(This will be displayed to customers in case of a payment error)_

11. Enter the **Sort Order**. _(The order that the payment option will appear on the checkout payment tab in accordance with the other payment methods )_

12. Save your changes.

Changelog
=========

= 1.0.2 =
* Bug fix - redirect to success in case of the IPN response comes before BACKURL response.
* Bug fix - added time reference to first billing date of recurring orders with trial enabled.

= 1.0.1 =
* Updated the way requests are sent to the Twispay server.
* Updated the server response handling to process all the possible server response statuses.
* Added support for refunds.
* Added filter by status an sorting support for transactions table.
* Added support for recurring orders.

= 1.0.0 =
* Initial Plugin version
* Merchant config interface
* Integration with Twispay's Secure Hosted Payment Page
* Listening URL which accepts the server’s Instant Payment Notifications
* Replaced FORM used for server notification with JSON
