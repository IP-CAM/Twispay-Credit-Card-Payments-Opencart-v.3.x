<?php
/**
 * @author   Twistpay
 * @version  1.0.1
 */

/**
 * Class that make available CRUD operations over order_recurring_transaction table.
 */
class ModelExtensionPaymentTwispayRecurring extends Model
{
    /**
     * Function that insert a recording.
     *
     * @param array([key => value]) data - Array of data to be populated
     *
     * @return string - The query that was called
     *
     */
    public function insertRecurringTransaction($data)
    {
        $columns = array(
          'order_recurring_id',
          'order_recurring_transaction_id',
          'reference',
          'type',
          'amount',
          'date_added',
        );

        $query = "INSERT INTO `" . DB_PREFIX . "order_recurring_transaction` SET ";
        foreach ($data as $key => $value) {
            if (!in_array($key, $columns)) {
                unset($data[$key]);
            } else {
                $db_value = $this->db->escape($value);
                if ($db_value === "NOW()") {
                    $query .= $key."= NOW(),";
                } else {
                    $query .= $key."="."'" . $db_value . "',";
                }
            }
        }

        $query = rtrim($query, ',');
        $this->db->query($query);
        return $query;
    }

    /**
     * Function that update a transactions from order_recurring_transaction table based on the transaction id.
     *
     * @param string trans_id - The id of the transaction to be updated
     * @param integer status  - The status id of the transaction to be updated
     *
     * @return array([key => value]) - string 'query'     - The query that was called
     *                                 integer 'affected' - Number of affected rows
     *
     */
    public function updateRecurringTransaction($trans_id, $status)
    {
        $db_trans_id = $this->db->escape($trans_id);
        $db_type = $this->db->escape($status);
        $query = "UPDATE `" . DB_PREFIX. "order_recurring_transaction` SET `type`= '".$db_type."' WHERE `reference`='tw_" . $db_trans_id . "' AND `type`!= '".$db_type."'";
        $this->db->query($query);
        $affected_transactions = $this->db->countAffected();
        $array = array(
          'query' => $query,
          'affected' => $affected_transactions,
        );
        return $array;
    }

    /**
     * Function that insert a record or update it if already exists.
     *
     * @param array([key => value]) data - Array of data to be populated
     * @param boolean overwrite          - Allow a record to be updated or not
     *
     * @return {array|string} - Operation response | FALSE
     *
     */
    public function addRecurringTransaction($data, $overwrite = FALSE)
    {
        if (!isset($data['reference'])) {
            return FALSE;
        }
        $db_data_reference = $this->db->escape($data['reference']);
        $query = $this->db->query("SELECT * FROM`" . DB_PREFIX . "order_recurring_transaction` WHERE `reference` LIKE '" . $db_data_reference . "'");
        if ($query->num_rows > 0) {
            if ($overwrite) {
                $resp = $this->updateRecurringTransaction($data['reference'], $data['type']);
            }
        } else {
            $resp = $this->insertRecurringTransaction($data);
        }
        return $resp;
    }

    /**
     * Function that return a list with all recurring orders
     *
     * @param string data - query parameters
     *
     * @return array - All recurrings
     *
     */
    public function getAllRecurrings($data)
    {
        $sql = "SELECT `or`.order_recurring_id, `or`.order_id, `or`.reference, `or`.`status`, `or`.`date_added`, CONCAT(`o`.firstname, ' ', `o`.lastname) AS customer FROM `" . DB_PREFIX . "order_recurring` `or` LEFT JOIN `" . DB_PREFIX . "order` `o` ON (`or`.order_id = `o`.order_id)";

        $implode = array();

        if (!empty($data['filter_order_recurring_id'])) {
            $implode[] = "or.order_recurring_id = " . (int)$data['filter_order_recurring_id'];
        }

        if (!empty($data['filter_order_id'])) {
            $implode[] = "or.order_id = " . (int)$data['filter_order_id'];
        }

        if (!empty($data['filter_reference'])) {
            $implode[] = "or.reference LIKE '" . $this->db->escape($data['filter_reference']) . "%'";
        }

        if (!empty($data['filter_customer'])) {
            $implode[] = "CONCAT(o.firstname, ' ', o.lastname) LIKE '" . $this->db->escape($data['filter_customer']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $implode[] = "or.status = " . (int)$data['filter_status'];
        }

        if (!empty($data['filter_date_added'])) {
            $implode[] = "DATE(or.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
        }

        if ($implode) {
            $sql .= " WHERE " . implode(" AND ", $implode);
        }

        $sort_data = array(
            'or.order_recurring_id',
            'or.order_id',
            'or.reference',
            'customer',
            'or.status',
            'or.date_added'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY or.order_recurring_id";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * Function that return the recurring order based on opencart order id.
     *
     * @param string order_id - The id of the order
     *
     * @return {array|boolean} - Query result | 0
     *
     */
    public function getRecurringByOrderId($order_id)
    {
        $db_order_id = $this->db->escape($order_id);
        $query = $this->db->query("SELECT `or`.*,`o`.`payment_method`,`o`.`payment_code`,`o`.`currency_code` FROM `" . DB_PREFIX . "order_recurring` `or` LEFT JOIN `" . DB_PREFIX . "order` `o` ON `or`.`order_id` = `o`.`order_id` WHERE `or`.`order_id` = '" . (int)$db_order_id . "'");
        if ($query->num_rows) {
            return $query->row;
        } else {
            return 0;
        }
    }

    /**
     * Function that return the recurring order duration.
     *
     * @param string order_recurring_id - The id of the recurring order
     *
     * @return integer - The recurring profile duration
     *
     */
    public function getRecurringDuration($order_recurring_id)
    {
        $db_order_recurring_id = $this->db->escape($order_recurring_id);
        $query = $this->db->query("SELECT `recurring_duration` FROM `" . DB_PREFIX . "order_recurring` WHERE order_recurring_id = '" . $db_order_recurring_id . "'");
        return $query->row['recurring_duration'];
    }

    /**
     * Function that return the number of succesful transaction of a recurring order.
     *
     * @param string order_recurring_id - The id of the recurring order
     *
     * @return integer - Number of succesful transactions
     *
     */
    public function getTotalRecurringSuccessTransactions($order_recurring_id)
    {
        $db_order_recurring_id = $this->db->escape($order_recurring_id);
        $query = $this->db->query("SELECT `order_recurring_id` FROM `" . DB_PREFIX . "order_recurring_transaction` WHERE `order_recurring_id` = " . $db_order_recurring_id . " AND `type` = 1");
        return $query->num_rows;
    }

    /**
     * Function that update the status of a recurring order.
     *
     * @param string order_recurring_id - The id of the recurring order
     * @param integer status_id - The new status id of the recurring order
     *
     * @return void
     *
     */
    public function editOrderRecurringStatus($order_recurring_id, $status_id)
    {
        $db_status_id = $this->db->escape($status_id);
        $db_order_recurring_id = $this->db->escape($order_recurring_id);
        $this->db->query("UPDATE `" . DB_PREFIX . "order_recurring` SET `status` = '" . (int)$db_status_id . "' WHERE `order_recurring_id` = '" . (int)$db_order_recurring_id . "'");
    }


    /**
     * Function that check if the next transaction that will be added is the last one.
     *
     * @param object order_recurring - The recurring order
     *
     * @return boolean - TRUE / FALSE
     *
     */
    public function isLastRecurringTransaction($order_recurring)
    {
        $order_recurring_id = $order_recurring['order_recurring_id'];
        $trial_state = intval($order_recurring['trial']);
        $transactions = intval($this->getTotalRecurringSuccessTransactions($order_recurring_id));
        /** if number of successful transactions is lower then recurring duration + 1(the trial period) */
        $duration = intval($this->getRecurringDuration($order_recurring_id)) + $trial_state;
        if ($transactions < $duration) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * Function that calls the cancel operation via Twispay API and update the
     * local(opencart) order based on the response.
     *
     * @param string tw_order_id - The twispay order id of the transaction to be canceled
     * @param string order_id - The local id of the order that needs to be canceled
     *
     * @return array([key => value]) - string 'status'          - API Message
     *                                 string 'rawdata'         - Unprocessed response
     *                                 string 'orderId'         - The twispay id of the canceled order
     *                                 string 'externalOrderId' - The opencart id of the canceled order
     *                                 boolean 'canceled'       - Operation success indicator
     */
    public function cancelRecurring($tw_order_id, $order_id, $type = 'Manual')
    {
        $this->load->helper('Twispay_Logger');
        $this->load->helper('Twispay_Status_Updater');
        $postData = 'reason='.'customer-demand'.'&'.'message=' . $type .'cancel';

        if (!empty($this->config->get('payment_twispay_testMode'))) {
            $url = 'https://api-stage.twispay.com/order/' . $tw_order_id;
            $apiKey = $this->config->get('payment_twispay_staging_site_key');
        } else {
            $url = 'https://api.twispay.com/order/' . $tw_order_id;
            $apiKey = $this->config->get('payment_twispay_live_site_key');
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Authorization: ' . $apiKey]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $response = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($response);

        /** Check if decode / curl fails */
        if (!isset($json)) {
            $json = new stdClass();
            $json->message = $this->language->get('json_decode_error');
            Twispay_Logger::Twispay_api_log($this->language->get('json_decode_error'));
        }

        if ($json->message == 'Success') {
            $data = array(
                'status'          => Twispay_Status_Updater::$RESULT_STATUSES['CANCEL_OK'],
                'rawdata'         => $json,
                'orderId'         => $tw_order_id,
                'externalOrderId' => $order_id,
                'canceled'        => 1,
            );
            Twispay_Status_Updater::updateStatus_IPN($order_id, $data, $this);
        } else {
            $data = array(
                'status'          => isset($json->error)?$json->error[0]->message:$json->message,
                'rawdata'         => $json,
                'orderId'         => $tw_order_id,
                'externalOrderId' => $order_id,
                'canceled'        => 0,
            );
        }

        Twispay_Logger::Twispay_api_log($this->language->get('subscriptions_log_cancel_response').json_encode($data));
        return $data;
    }

    /**
     * Function that calls the GET operation via Twispay API and update the
     * local(opencart) regurring orders wich reference starts with "tw" based on the response.
     *
     *
     * @return array([key => value,]) - string 'status'- API Message
     *                                  int 'synced'   - Number of afected orders
     *
     */
    public function syncRecurrings()
    {
        /** Load dependencies */
        $this->language->load('extension/payment/twispay');
        $this->load->helper('Twispay_Logger');
        $this->load->helper('Twispay_Status_Updater');

        if (!empty($this->config->get('payment_twispay_testMode'))) {
            $baseUrl = 'https://api-stage.twispay.com/order?externalOrderId=__EXTERNAL_ORDER_ID__&orderType=recurring&page=1&perPage=1&reverseSorting=0';
            $apiKey = $this->config->get('payment_twispay_staging_site_key');
        } else {
            $baseUrl = 'https://api.twispay.com/order?externalOrderId=__EXTERNAL_ORDER_ID__&orderType=recurring&page=1&perPage=1&reverseSorting=0';
            $apiKey = $this->config->get('payment_twispay_live_site_key');
        }
        $subscriptions = $this->getAllRecurrings(['status' => 1]);
        $total_synced = 0;
        $error = array('message' => '','error' => 0);
        foreach ($subscriptions as $key => $subscription) {
            if (!isset($subscription['reference'])) {
                continue;
            }
            $subscription['reference'] = explode("_", $subscription['reference']);
            $prefix = $subscription['reference'][0];
            if ($prefix != 'tw') {
                continue;
            } else {
                $subscription['reference'] = $subscription['reference'][1];
            }
            /** Construct the URL. */
            $url = str_replace('__EXTERNAL_ORDER_ID__', $subscription['order_id'], $baseUrl);

            /** Create a new cURL session. */
            $ch = curl_init();
            /** Set the URL and other needed fields. */
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Authorization: ' . $apiKey]);
            $response = curl_exec($ch);
            /** Check if the CURL call failed. */
            if (false === $response) {
                Twispay_Logger::Twispay_api_log($this->language->get('subscriptions_log_error_call_failed') . curl_error($ch));
                curl_close($ch);
                continue;
            }

            if ((200 != curl_getinfo($ch, CURLINFO_HTTP_CODE))) {
                Twispay_Logger::Twispay_api_log($this->language->get('subscriptions_log_error_http_code') . curl_getinfo($ch, CURLINFO_HTTP_CODE));
                curl_close($ch);
                continue;
            }
            curl_close($ch);
            $json = json_decode($response, TRUE);
            /** Check if decode fails */
            if (!isset($json) || !sizeof($json['data'])) {
                Twispay_Logger::Twispay_api_log($this->language->get('subscriptions_log_error_order_not_found') . $subscription['order_id']);
                continue;
            }
            if ('Success' == $json['message']) {
                $update_data = $json['data'][0];
                /** normalize the response */
                $update_data['status'] =  $update_data['orderStatus'];
                $update_data['orderId'] = $subscription['reference'];
                /** Cancel the local recurring */
                Twispay_Status_Updater::updateStatus_IPN($subscription['order_id'], $update_data, $this);
                Twispay_Logger::Twispay_api_log($this->language->get('subscriptions_log_ok_set_status') . $subscription['order_id']);
                $total_synced += 1;
            } else {
                Twispay_Logger::Twispay_api_log($this->language->get('subscriptions_log_error_get_status') . $subscription['order_id']);
                continue;
            }
        }
        if ($total_synced == 0 && $error['error'] == 0) {
            $error = array('message' => $this->language->get('subscriptions_log_error_no_orders_found'),'error' => 1);
        }
        if ($error['error'] == 0) {
            $data = array(
              'status' => 'Success',
              'synced' => $total_synced,
          );
        } elseif ($error['error'] == 1) {
            $data = array(
              'status' => $error['message'],
              'synced' => 0,
          );
        }
        return $data;
    }
}
