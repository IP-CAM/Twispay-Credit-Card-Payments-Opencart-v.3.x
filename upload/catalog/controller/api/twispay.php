<?php
/**
 * @author   Twistpay
 * @version  1.0.0
 */

class ControllerApiTwispay extends Controller
{
    /**
     * Endndpoint for recurring order cancel operation
     *
     * @return json - string success - operation success message
     *                string error   - operation error message
     */
    public function cancel()
    {
        $this->language->load('extension/payment/twispay');
        $json = array();

        if (!isset($this->session->data['api_id'])) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('extension/payment/twispay_recurring');

            if (isset($this->request->get['tw_order_id']) && isset($this->request->get['order_id'])) {
                $tw_order_id = $this->request->get['tw_order_id'];
                $order_id = $this->request->get['order_id'];
            }

            $resp = $this->model_extension_payment_twispay_recurring->cancelRecurring($tw_order_id, $order_id);
            if ($resp['canceled'] != 1) {
                $json['error'] = $resp['status'];
            } else {
                $json['success'] = $resp['status'];
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Endndpoint for transaction refund operation
     *
     * @return json - string success - operation success message
     *                string error   - operation error message
     */
    public function refund()
    {
        $this->language->load('extension/payment/twispay');
        $json = array();

        if (!isset($this->session->data['api_id'])) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('extension/payment/twispay_transaction');

            if (isset($this->request->get['trans_id']) && isset($this->request->get['order_id'])) {
                $trans_id = $this->request->get['trans_id'];
                $order_id = $this->request->get['order_id'];
            }
            $resp = $this->model_extension_payment_twispay_transaction->refund($trans_id, $order_id);
            if ($resp['refunded'] != 1) {
                $json['error'] = $resp['status'];
            } else {
                $json['success'] = $resp['status'];
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Endndpoint for recuring order synchronization operation
     *
     * @return json - string success | error - operation status
     *                int synced - number of affected orders
     */
    public function sync()
    {
        $this->language->load('extension/payment/twispay');

        if (isset($_POST['subscriptions'])) {
            $subscriptions = $_POST['subscriptions'];
        }

        if (!isset($this->session->data['api_id'])) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('extension/payment/twispay_recurring');
            $resp = $this->model_extension_payment_twispay_recurring->syncRecurrings();

            if ($resp['synced'] > 0) {
                $json['success'] = $resp['status'];
                $json['synced'] = $resp['synced'];
            } else {
                $json['error'] = $resp['status'];
                $json['synced'] = 0;
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
