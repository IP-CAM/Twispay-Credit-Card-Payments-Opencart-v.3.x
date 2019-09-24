<?php
/**
 * Twispay Helpers
 *
 * Print HTML notices.
 *
 * @author   Twistpay
 * @version  1.0.1
 */

/* Security class check */
if (! class_exists('Twispay_Notification')) :
    /**
     * Class that prints HTML notices.
     */
    class Twispay_Notification
    {
        /**
         * Print a HTML notice with cart redirect button.
         *
         * @param that: Controller instance use for accessing runtime values like configuration, active language, etc.
         * @param retry_url: URL of the notice redirect button that is printed.
         * @param text: Notice content.
         *
         * @return void
         */
        public static function notice_to_cart($that, $retry_url = '', $text = '')
        {
            if (!strlen($retry_url)) {
                $retry_url = $that->url->link('checkout/cart').'&tw_reload=TRUE';
            }
            Twispay_Notification::print_notice($that, $retry_url, $text);
        }

        /**
         * Print a HTML notice with checkout redirect button.
         *
         * @param that: Controller instance use for accessing runtime values like configuration, active language, etc.
         * @param retry_url: URL of the notice redirect button that is printed.
         * @param text: Notice content.
         *
         * @return void
         */
        public static function notice_to_checkout($that, $retry_url = '', $text = '')
        {
            if (!strlen($retry_url)) {
                $retry_url = $that->url->link('checkout/checkout').'&tw_reload=TRUE';
                // $retry_url = $this->url->link('checkout/checkout').'&tw_reload=TRUE';//wc_get_checkout_url() . 'order-pay/' . $orderId . '/?pay_for_order=TRUE&key=' . $order->get_data()['order_key'] . '&tw_reload=TRUE';
            }
            Twispay_Notification::print_notice($that, $retry_url, $text);
        }

        /**
         * Prints HTML notice.
         *
         * @param that: Controller instance use for accessing runtime values like configuration, active language, etc.
         * @param retry_url: URL of the notice redirect button that is printed.
         * @param text: Notice content.
         *
         * @return void
         */
        private static function print_notice($that, $retry_url, $text)
        {
        ?>
          <div class="error notice" style="margin-top: 20px;">
            <h3><?= $that->language->get('general_error_title'); ?></h3>
            <?php if (strlen($text)) { ?>
              <span><?= $text; ?></span>
            <?php } ?>
            <?php if ('0' == $that->config->get('config_email')) { ?>
              <p><?= $that->language->get('general_error_desc_f'); ?> <a href="<?= $retry_url; ?>"><?= $that->language->get('general_error_desc_try_again'); ?></a> <?= $that->language->get('general_error_desc_or') . $that->language->get('general_error_desc_contact') . $that->language->get('general_error_desc_s'); ?></p>
            <?php } else { ?>
              <p><?= $that->language->get('general_error_desc_f'); ?> <a href="<?= $retry_url; ?>"><?= $that->language->get('general_error_desc_try_again'); ?></a> <?= $that->language->get('general_error_desc_or'); ?> <a href="mailto:<?= $that->config->get('config_email')?$that->config->get('config_email'):$that->config->get('payment_twispay_contact_email') ?>"><?= $that->language->get('general_error_desc_contact'); ?></a> <?= $that->language->get('general_error_desc_s'); ?></p>
            <?php } ?>
          </div>
        <?php
        }
    }
endif; /* End if class_exists. */
