<?php
/**
 * @author   Twistpay
 * @version  1.0.1
 */

/**
 * Class that make available CRUD operations over twispay_transactions table.
 */
class ModelExtensionPaymentTwispayTransaction extends Model
{
    /**
     * Function that insert a recording.
     *
     * @param array([key => value]) data - Array of data to be populated
     *
     * @return string - The query that was called | FALSE
     *
     */
    public function insertTransaction($data)
    {
        $this->load->helper('Twispay_Status_Updater');

        /** Define filtred keys */
        $columns = array(
          'order_id',
          'status',
          'invoice',
          'identifier',
          'customerId',
          'orderId',
          'cardId',
          'transactionId',
          'transactionKind',
          'amount',
          'currency',
          'date',
        );
        /** Convert $data object form to local form defined in columns array */
        $data['order_id'] = $data['externalOrderId'];
        /** If refund succeeded*/
        if ($data['status'] == Twispay_Status_Updater::$RESULT_STATUSES['REFUND_OK']) {
            /** Set refund date */
            $data['refund_date'] = date('Y-m-d H:i:s');
            array_push($columns, "refund_date");
        }

        /** Convert timestamp to date format*/
        if (!empty($data['timestamp'])) {
            if (is_array($data['timestamp'])) {
                $data['date'] = date('Y-m-d H:i:s', strtotime($data['timestamp']['date']));
            } else {
                $data['date'] = date('Y-m-d H:i:s', $data['timestamp']);
            }
            unset($data['timestamp']);
        }

        if (!empty($data['identifier'])) {
            $data['identifier'] = (int)str_replace('_', '', $data['identifier']);
        }

        /** Construct the query based on $data object fields filtered by $colums keys */
        $query = "INSERT INTO `" . DB_PREFIX . "twispay_transactions` SET ";
        foreach ($data as $key => $value) {
            if (in_array($key, $columns)) {
                $query .= $key."="."'" . $this->db->escape($value) . "',";
            }
        }

        /** Trim the query */
        $query = rtrim($query, ',');
        $this->db->query($query);
        return $query;
    }

    /**
     * Function that update transactions from twispay_transactions table based on the transaction id.
     *
     * @param string id - The id of the transaction to be updated
     * @param string status - The new status of the transaction to be updated
     *
     * @return array([key => value]) - string 'query'     - The query that was called
     *                                 integer 'affected' - Number of affected rows
     *
     */
    public function updateTransactionStatus($id, $status)
    {
        $this->load->helper('Twispay_Status_Updater');
        $db_trans_id = $this->db->escape($id);
        $db_status = $this->db->escape($status);
        if ($db_status == Twispay_Status_Updater::$RESULT_STATUSES['REFUND_OK']) {
            $query = "UPDATE `" . DB_PREFIX. "twispay_transactions` SET `status`='".$db_status."',`refund_date`= NOW() WHERE `transactionId`='" . (int)$db_trans_id . "' AND `status`!='".$db_status."'";
        } else {
            $query = "UPDATE `" . DB_PREFIX. "twispay_transactions` SET `status`='".$db_status."' WHERE `transactionId`='" . (int)$db_trans_id . "' AND `status`!='".$db_status."'";
        }
        $this->db->query($query);
        $affected_transactions = $this->db->countAffected();
        $array = array(
           'query' => $query,
           'affected'  => $affected_transactions,
       );
        return $array;
    }

    /**
     * Function that check if a transaction exists.
     *
     * @param string id - The id of the transaction to be checked
     *
     * @return boolean
     *
     */
    public function checkTransaction($id)
    {
        $db_trans_id = $this->db->escape($id);
        $query = $this->db->query("SELECT * FROM `".DB_PREFIX."twispay_transactions` WHERE `transactionId`='".(int)$db_trans_id."'");
        if ($query->num_rows > 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Function that returns a database transaction by its id.
     *
     * @param string id - The id of the transaction
     *
     * @return object - the transaction object
     *         array(object) - in case of multiple transactions with the same id this will return a list of transactions
     *         NULL - if there is no transaction with the specified id
     *
     */
    public function getTransaction($id)
    {
        $db_trans_id = $this->db->escape($id);
        $query = $this->db->query("SELECT * FROM `".DB_PREFIX."twispay_transactions` WHERE `transactionId`='".(int)$db_trans_id."'");
        if ($query->num_rows > 0) {
            return $query->rows;
        } else {
            return NULL;
        }
    }

    /**
     * Function that call the refund operation via Twispay API and update the
     * local(opencart) order based on the response.
     *
     * @param string trans_id - The twispay id of the transaction to be refunded
     * @param string order_id - The local id of the order that contains the transaction that needs to be refunded
     *
     * @return array([key => value,]) - string 'status'          - API Message
     *                                  string 'rawdata'         - Unprocessed response
     *                                  string 'transaction_id'  - The twispay id of the refunded transaction
     *                                  string 'externalOrderId' - The opencart id of the canceled order
     *                                  boolean 'refunded'       - Operation success indicator
     *
     */
    public function refund($trans_id, $order_id)
    {
        $this->load->helper('Twispay_Logger');
        $this->load->helper('Twispay_Status_Updater');
        $this->load->model('extension/payment/twispay_recurring');

        $order_recurring = $this->model_extension_payment_twispay_recurring->getRecurringByOrderId($order_id);
        $transaction = $this->getTransaction($trans_id)[0];
        $postData = 'amount=' . $transaction['amount'] . '&' . 'message=' . 'Refund for order ' . $order_id;

        if (!empty($this->config->get('payment_twispay_testMode'))) {
            $url = 'https://api-stage.twispay.com/transaction/' . $trans_id;
            $apiKey = $this->config->get('payment_twispay_staging_site_key');
        } else {
            $url = 'https://api.twispay.com/transaction/' . $trans_id;
            $apiKey = $this->config->get('payment_twispay_live_site_key');
        }

        /** Create a new cURL session. */
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Authorization: ' . $apiKey]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $response = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($response);

        /** Check if curl/decode fails */
        if (!isset($json)) {
            $json = new stdClass();
            $json->message = $this->language->get('json_decode_error');
            Twispay_Logger::Twispay_api_log($this->language->get('json_decode_error'));
        }

        if ($json->message == 'Success') {
            $data = array(
               'status'          => Twispay_Status_Updater::$RESULT_STATUSES['REFUND_OK'],
               'rawdata'         => $json,
               'transaction_id'  => $trans_id,
               'externalOrderId' => $order_id,
               'refunded'        => 1,
           );
            Twispay_Status_Updater::updateStatus_IPN($order_id, $data, $this);
            $this->updateTransactionStatus($trans_id, Twispay_Status_Updater::$RESULT_STATUSES['REFUND_REQUESTED']);
        } else {
            $data = array(
               'status'          => isset($json->error)?$json->error[0]->message:$json->message,
               'rawdata'         => $json,
               'transaction_id'  => $trans_id,
               'externalOrderId' => $order_id,
               'refunded'        => 0,
           );
        }
        Twispay_Logger::Twispay_api_log($this->language->get('log_refund_response').json_encode($data));
        return $data;
    }
}
