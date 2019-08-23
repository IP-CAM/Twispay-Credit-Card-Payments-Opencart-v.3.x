<?php
/**
 * @author   Twistpay
 * @version  1.0.0
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
     * @return string - The query that was called
     *
     */
    public function insertTransaction($data)
    {
        $data =json_decode(json_encode($data), TRUE);
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
        $data['order_id'] = $data['externalOrderId'];
        if ($data['status'] == "refund-ok") {
            $data['refund_date'] = date('Y-m-d H:i:s');
            array_push($columns, "refund_date");
        }

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

        $query = "INSERT INTO `" . DB_PREFIX . "twispay_transactions` SET ";

        foreach ($data as $key => $value) {
            if (!in_array($key, $columns)) {
                unset($data[$key]);
            } else {
                $db_value = $this->db->escape($value);
                $query .= $key."="."'" . $db_value . "',";
            }
        }

        $query = rtrim($query, ',');
        $this->db->query($query);
        $affected_transactions = $this->db->countAffected();

        return $query;
    }

    /**
     * Function that update transactions from twispay_transactions table based on the transaction id.
     *
     * @param string id - The id of the transaction to be updated
     * @param string status - The new status of the transaction to be updated
     *
     * @return array([key => value]) - string 'query'     - The query that was called
                                       integer 'affected' - Number of affected rows
     *
     */
    public function updateTransactionStatus($id, $status)
    {
        $db_trans_id = $this->db->escape($id);
        $db_status = $this->db->escape($status);
        if ($db_status == "refund-ok") {
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
     * Function that insert a record or update it if already exists.
     *
     * @param array([key => value]) data - Array of data to be populated
     * @param boolean overwrite          - Allow a record to be updated or not
     *
     * @return {array|string} - Operation response
     *
     */
    public function logTransaction($data, $overwrite = FALSE)
    {
        if ($this->checkTransaction($data['transactionId'])) {
            if ($overwrite) {
                $resp = $this->updateTransactionStatus($data['transactionId'], $data['status']);
            }else{
                $resp = "Can't overwrite";
            }
        } else {
            $resp = $this->insertTransaction($data);
        }
        return json_encode($resp);
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
     * Function that call the refund operation via Twispay API and update the
     * local(opencart) order based on the response.
     *
     * @param string trans_id - The twispay id of the transaction to be refunded
     * @param string order_id - The local id of the order that contains the transaction that needs to be refunded
     *
     * @return array([key => value,]) - string 'status'          - API Message
                                        string 'rawdata'         - Unprocessed response
                                        string 'transaction_id'  - The twispay id of the refunded transaction
                                        string 'externalOrderId' - The opencart id of the canceled order
                                        boolean 'refunded'       - Operation success indicator
     *
     */
    public function refund($trans_id, $order_id)
    {
        $this->load->helper('Twispay_Logger');
        $this->load->helper('Twispay_Status_Updater');
        $this->load->model('extension/payment/twispay_recurring');

        $order_recurring = $this->model_extension_payment_twispay_recurring->getRecurringByOrderId($order_id);
        $order_recurring_id = $order_recurring['order_recurring_id'];

        $testMode = $this->config->get('payment_twispay_testMode');
        if (!empty($testMode)) {
            $url = 'https://api-stage.twispay.com/transaction/' . $trans_id;
            $apiKey = $this->config->get('payment_twispay_staging_site_key');
        } else {
            $url = 'https://api.twispay.com/transaction/' . $trans_id;
            $apiKey = $this->config->get('payment_twispay_live_site_key');
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array( "Authorization: Bearer " . $apiKey, "Accept: application/json" ));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $contents = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($contents);

        if ($json->message == 'Success') {
            $data = array(
               'status'          => 'refund-ok',
               'rawdata'         => $json,
               'transaction_id'  => $trans_id,
               'externalOrderId' => $order_id,
               'refunded'        => 1,
           );
           Twispay_Status_Updater::updateStatus_IPN($order_id, $data, $this);
           $this->updateTransactionStatus($trans_id, "refund-requested");
        } else {
            $data = array(
               'status'          => $json->error[0]->message,
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
