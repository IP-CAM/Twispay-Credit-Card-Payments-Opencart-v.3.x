opencart3-Twispay_Payments
=========================

The official [Twispay Payment Gateway][twispay] extension for Opencart 3.

At the time of purchase, after checkout confirmation, the customer will be redirected to the secure Twispay Payment Gateway.

All payments will be processed in a secure PCI DSS compliant environment so you don't have to think about any such compliance requirements in your web shop.

Install
=======

### Automatic
1. Download the Twispay payment module from Opencart Marketplace, where you can find [The Official Twispay Payment Gateway Extension][marketplace]
2. Use the Opencart 3 Extension Installer and upload the OCmod ar
3. Sign in to your OpenCart admin.
4. Click **Extensions** tab and **Payments subtab**.
5. Under **Twispay** click **Install** and then click **Edit**.
6. Select **Enabled** under **Status**.
7. Select **No** under **Test Mode**. _(Unless you are tesing)_
8. Enter your **Account ID**. _(Twispay Staging Account ID)_
9. Enter your **Secret Key**. _(Twispay Secret Key)_
10. Enter your tehnical **Contact Email**. _(This will be displayed to customers in case of a payment error)_
11. Enter the **Sort Order**. _(The order that the payment option will appear on the checkout payment tab in accordance with the other payment methods )_
12. Save your changes.

### Manually
1. Download the Twispay payment module from our [Github repository][github]
2. Unzip the archive files and upload the content of folder "uploads" in the corresponding files on the server.
3. Sign in to your OpenCart admin.
4. Click **Extensions** tab and **Payments subtab**.
5. Under **Twispay** click **Install** and then click **Edit**.
6. Select **Enabled** under **Status**.
7. Select **No** under **Test Mode**. _(Unless you are tesing)_
8. Enter your **Account ID**. _(Twispay Staging Account ID)_
9. Enter your **Secret Key**. _(Twispay Secret Key)_
10. Enter your tehnical **Contact Email**. _(This will be displayed to customers in case of a payment error)_
11. Enter the **Sort Order**. _(The order that the payment option will appear on the checkout payment tab in accordance with the other payment methods )_
12. Save your changes.

Changelog
=========

= 1.0.2 =
* Bug fix - redirect to success in case of the IPN response comes before BACKURL response
* Bug fix - added time reference to first billing date of recurring orders with trial enabled

= 1.0.1 =
* Updated the way requests are sent to the Twispay server.
* Updated the server response handling to process all the possible server response statuses.
* Added support for refunds.
* Added sort by status an filtering for transactions table.
* Added support for recurring orders.

= 1.0.0 =
* Initial Plugin version

<!-- Other Notes
===========

A functional description of the extension can be found on the [wiki page][doc] -->

[twispay]: http://twispay.com/
[marketplace]: https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=31761&filter_member=twispay
[github]: https://github.com/MichaelRotaru/OpenCart3.0
