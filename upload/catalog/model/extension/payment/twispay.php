<?php
/**
 * @author   Twistpay
 * @version  1.0.1
 */

class ModelExtensionPaymentTwispay extends Model
{
    /**
     * Payment method detection. Used by OpenCart when listing the active payment methods during the checkout process
     *
     * @param string $address - checkout address
     * @param string $total   - checkout total
     *
     * @return array - Array formated object
     */
    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/twispay');
        if (!empty($this->config->get('payment_twispay_testMode'))) {
            $siteId = trim($this->config->get('payment_twispay_staging_site_id'));
            $privateKEY = trim($this->config->get('payment_twispay_staging_site_key'));
        } else {
            $siteId = trim($this->config->get('payment_twispay_live_site_id'));
            $privateKEY = trim($this->config->get('payment_twispay_live_site_key'));
        }
        if (empty($siteId) || empty($privateKEY)) {
            return false;
        }
        $method_data = array(
            'code'       => 'twispay',
            'title'      => $this->language->get('text_title'),
            'terms'      =>'',
            'sort_order' => $this->config->get('custom_sort_order')
        );

        return $method_data;
    }

    /**
     * Core function that decide if recurring orders are suported
     */
    public function recurringPayments()
    {
        /**
         * Used by the checkout to state the module
         * supports recurring recurrings.
         */
        return TRUE;
    }

    /**
     * Function that generates a new invoice number for an order.
     *
     * @param string order_id - Invoice number
     * @param string prefix   - Invoice prefix
     *
     * @return string - String formated invoice number
     *
     */
    public function createInvoiceNo($order_id, $prefix)
    {
        $db_prefix = $this->db->escape($prefix);
        $db_order_id = $this->db->escape($order_id);
        $query = $this->db->query("SELECT MAX(invoice_no) AS invoice_no FROM `" . DB_PREFIX . "order` WHERE invoice_prefix = '" . $db_prefix . "'");
        if ($query->row['invoice_no']) {
            $invoice_no = $query->row['invoice_no'] + 1;
        } else {
            $invoice_no = 1;
        }
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET invoice_no = '" . (int)$invoice_no . "', invoice_prefix = '" . $db_prefix . "' WHERE order_id = '" . (int)$db_order_id . "'");

        return $this->getInvoiceNo($invoice_no, $db_prefix);
    }

    /**
    * Getter for invoice number.
    *
    * @param string number - Invoice number
    * @param string prefix - Invoice prefix
    *
    * @return string - String formated invoice number
    *
    */
    public function getInvoiceNo($no, $prefix)
    {
        return $prefix . $no;
    }
}
