<?php
/**
 * @author   Twistpay
 * @version  1.0.1
 */

class ModelExtensionPaymentTwispayTransactions extends Model
{
    /**
     * Function that create the database twispay_transactions table.
     *
     * @return string - The query that was called
     *
     */
    public function createTransactionTable()
    {
        $sql = "
          CREATE TABLE IF NOT EXISTS `". DB_PREFIX ."twispay_transactions` (
              `id_transaction` int(11) NOT NULL AUTO_INCREMENT,
              `status` varchar(16) NOT NULL,
              `invoice` varchar(30) NOT NULL,
              `order_id` int(11) NOT NULL,
              `identifier` int(11) NOT NULL,
              `customerId` int(11) NOT NULL,
              `orderId` int(11) NOT NULL,
              `cardId` int(11) NOT NULL,
              `transactionId` int(11) NOT NULL UNIQUE KEY,
              `transactionKind` varchar(16) NOT NULL,
              `amount` decimal NOT NULL,
              `currency` varchar(8) NOT NULL,
              `date` DATETIME NOT NULL,
              `refund_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
              PRIMARY KEY (`id_transaction`)
          ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
        return $this->db->query($sql);
    }

    /**
     * Function that remove the database twispay_transactions table.
     *
     * @return void
     *
     */
    public function deleteTransactionTable()
    {
        $this->db->query("DROP TABLE IF EXISTS `". DB_PREFIX ."twispay_transactions`");
    }

    /**
     * Function that return the database transactions
     *
     * @param string user        - User id filter
     * @param string status      - Status filter
     * @param string sort_col    - Order by column
     * @param string sort_order  - Order ASC | DESC
     *
     * @return array - Array of transactions
     *
     */
    public function getTransactions($user='0', $status='0', $sort_col='0', $sort_order='0')
    {
        $db_user = $this->db->escape($user);
        $db_status = $this->db->escape($status);
        $db_sort_col = $this->db->escape($sort_col);
        $db_sort_order = $this->db->escape($sort_order);

        $where = "";
        if (!empty($db_user)) {
            if (strlen($where)) {
                $where .=" AND ";
            } else {
                $where .=" WHERE ";
            }
            $where .= "`identifier`='".$db_user."'";
        }

        if (!empty($db_status)) {
            if (strlen($where)) {
                $where .=" AND ";
            } else {
                $where .=" WHERE ";
            }
            $where .="`status` LIKE '".$db_status."'";
        }

        $order = "ORDER BY `date` DESC";
        if ($db_sort_col && $db_sort_order) {
            $order = "ORDER BY `".$db_sort_col."` ".$db_sort_order."";
        }

        $query = "SELECT t.*,s.`store_id` FROM `" . DB_PREFIX . "twispay_transactions` as t LEFT JOIN `".DB_PREFIX."order` AS s ON t.`order_id`=s.`order_id` ".$where.' '.$order;
        $data = $this->db->query($query);

        $trans = array();
        if ($data->num_rows) {
            foreach ($data->rows as $dt) {
                array_push($trans, $dt);
            }
        }
        return $trans;
    }

    /**
     * Function that returns transaction table columns
     *
     * @return array - Array of columns
     *
     */
    public function getTransactionColsName()
    {
        $query = "SHOW COLUMNS FROM `" . DB_PREFIX . "twispay_transactions`";
        $data = $this->db->query($query);
        $cols = array();
        if ($data->num_rows) {
            foreach ($data->rows as $dt) {
                array_push($cols, $dt);
            }
        }
        return $cols;
    }

    /**
     * Function that returns all customers
     *
     * @return array - Array of customers
     *
     */
    public function getCustomers()
    {
        $query = "SELECT `customer_id`,(CONCAT_WS(' ',`firstname`,`lastname`)) AS name,`email` FROM `" . DB_PREFIX . "customer`";
        $data = $this->db->query($query);
        $customers = array();
        if ($data->num_rows) {
            foreach ($data->rows as $dt) {
                array_push($customers, $dt);
            }
        }
        return $customers;
    }

    /**
     * Function that returns all available values for twispay_transactions table
     *
     * @return array - Array of statuses
     *
     */
    public function getStatuses()
    {
        $query = "SELECT DISTINCT `status` FROM `" . DB_PREFIX . "twispay_transactions` ORDER BY `status` ASC";
        $data = $this->db->query($query);
        $statuses = array();
        if ($data->num_rows) {
            foreach ($data->rows as $dt) {
                array_push($statuses, $dt);
            }
        }
        return $statuses;
    }

    /**
     * Function that returns the order status based on order id
     *
     * @return string - Order status id
     *                - NULL
     *
     */
    public function getOrderStatusId($order_id)
    {
        $db_order_id = $this->db->escape($order_id);
        $query = $this->db->query("SELECT `order_status_id` FROM `" . DB_PREFIX . "order` WHERE `order_id` = '".$db_order_id."'");
        if ($query->num_rows) {
            return $query->row['order_status_id'];
        } else {
            return NULL;
        }
    }

    /**
     * Function that check if a order has recurring products or not.
     *
     * @param string order_id - The id of the order to be checked
     *
     * @return boolean - TRUE | FALSE
     *
     */
    public function isRecurring($order_id)
    {
        $db_order_id = $this->db->escape($order_id);
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_recurring` WHERE `order_id` = '".(int)$db_order_id . "'");
        if ($query->num_rows) {
            return TRUE;
        } else {
            return false;
        }
    }
}
