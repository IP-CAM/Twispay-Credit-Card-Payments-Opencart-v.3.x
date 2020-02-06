<?php
/**
 * @author   Twistpay
 * @version  1.0.2
 */

class ControllerExtensionPaymentTwispay extends Controller
{
    private $error = array();
    private $baseurl;

    /**
    *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
    *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
    *|||||||||||||||||||||||||||||||||| TWISPAY |||||||||||||||||||||||||||||||||||
    *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
    *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
    */

    /**
     * Controller function that initialize Twispay View
     */
    public function index()
    {
        $this->baseurl = (!empty($_SERVER['HTTPS'])) ? 'https://' : 'http://';
        $this->baseurl .= $_SERVER['HTTP_HOST'];

        $this->language->load('extension/payment/twispay');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_twispay', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_saved');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', TRUE));
        }
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], TRUE),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', TRUE),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/twispay', 'user_token=' . $this->session->data['user_token'], TRUE),
        );

        /** Labels */
        $data['heading_title'] = $this->language->get('heading_title');
        $data['button_save'] = $this->language->get('text_button_save');
        $data['button_cancel'] = $this->language->get('text_button_cancel');

        $data['text_testMode'] = $this->language->get('text_testMode');
        $data['text_live_site_id'] = $this->language->get('text_live_site_id');
        $data['text_live_site_key'] = $this->language->get('text_live_site_key');
        $data['text_staging_site_id'] = $this->language->get('text_staging_site_id');
        $data['text_staging_site_key'] = $this->language->get('text_staging_site_key');
        $data['text_contact_email'] = $this->language->get('text_contact_email');

        $data['desc_testMode'] = $this->language->get('desc_testMode');
        $data['desc_live_site_id'] = $this->language->get('desc_live_site_id');
        $data['desc_live_site_key'] = $this->language->get('desc_live_site_key');
        $data['desc_staging_site_id'] = $this->language->get('desc_staging_site_id');
        $data['desc_staging_site_key'] = $this->language->get('desc_staging_site_key');
        $data['desc_s_t_s_notification'] = $this->language->get('desc_s_t_s_notification');

        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['text_logs'] = $this->language->get('text_logs');

        $data['action'] = $this->url->link('extension/payment/twispay', 'user_token=' . $this->session->data['user_token'], TRUE);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', TRUE);
        $data['logs'] = $this->url->link('extension/payment/twispay/twispay_transactions/', '&user_token=' . $this->session->data['user_token'], TRUE);

        $data['payment_twispay_status'] = (isset($this->request->post['payment_twispay_status'])) ? $this->request->post['payment_twispay_status'] : $this->config->get('payment_twispay_status');
        $data['payment_twispay_testMode'] = (isset($this->request->post['payment_twispay_testMode'])) ? $this->request->post['payment_twispay_testMode'] : $this->config->get('payment_twispay_testMode');
        $data['payment_twispay_live_site_id'] = (isset($this->request->post['payment_twispay_live_site_id'])) ? $this->request->post['payment_twispay_live_site_id'] : $this->config->get('payment_twispay_live_site_id');
        $data['payment_twispay_live_site_key'] = (isset($this->request->post['payment_twispay_live_site_key'])) ? $this->request->post['payment_twispay_live_site_key'] : $this->config->get('payment_twispay_live_site_key');
        $data['payment_twispay_staging_site_id'] = (isset($this->request->post['payment_twispay_staging_site_id'])) ? $this->request->post['payment_twispay_staging_site_id'] : $this->config->get('payment_twispay_staging_site_id');
        $data['payment_twispay_staging_site_key'] = (isset($this->request->post['payment_twispay_staging_site_key'])) ? $this->request->post['payment_twispay_staging_site_key'] : $this->config->get('payment_twispay_staging_site_key');
        $data['payment_twispay_s_t_s_notification'] = $this->baseurl.'/index.php?route=extension/payment/twispay/s2s';
        $data['payment_twispay_sort_order'] = (isset($this->request->post['payment_twispay_sort_order'])) ? $this->request->post['payment_twispay_sort_order'] : $this->config->get('payment_twispay_sort_order');
        $data['payment_twispay_contact_email'] = (isset($this->request->post['payment_twispay_contact_email'])) ? $this->request->post['payment_twispay_contact_email'] : $this->config->get('payment_twispay_contact_email');

        /**load template components*/
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        /**load the view*/
        $this->response->setOutput($this->load->view('extension/payment/twispay', $data));
    }

    /**
     * Function called when install event is triggered
     *
     * @return void
     *
     */
    public function install()
    {
        $path = DIR_LOGS.'/twispay_logs/';
        $this->makeDir($path);

        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('payment_twispay', array('payment_twispay_testMode' => '1','payment_twispay_logs' => $path));

        $this->load->model('extension/payment/twispay_transactions');
        $this->model_extension_payment_twispay_transactions->createTransactionTable();
    }

    /**
     * Function called when uninstall event is triggered
     *
     * @return void
     *
     */
    public function uninstall()
    {
        $this->delTree(DIR_LOGS.'/twispay_logs/');

        $this->load->model('extension/payment/twispay_transactions');
        $this->model_extension_payment_twispay_transactions->deleteTransactionTable();
    }

    /**
     *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
     *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
     *|||||||||||||||||||||||||||| TWISPAY TRANSACTIONS ||||||||||||||||||||||||||||
     *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
     *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
    */

    /**
     *  Controller function that initialize Twispay Transactions view
     */
    public function twispay_transactions()
    {
        $this->language->load('extension/payment/twispay');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/payment/twispay_transactions');

        $all_statuses = $this->model_extension_payment_twispay_transactions->getStatuses();
        $all_customers = $this->model_extension_payment_twispay_transactions->getCustomers();
        $transaction_columns = $this->model_extension_payment_twispay_transactions->getTransactionColsName();

        $user_id = '0';
        if (isset($_GET["f_uid"])) {
            $user_id = $this->escape_val($_GET["f_uid"], $all_customers);
        }

        $status = '0';
        if (isset($_GET["f_status"])) {
            $status = $this->escape_val($_GET["f_status"], $all_statuses);
        }

        $sort_col = '0';
        $sort_order = '0';
        if (isset($_GET["sort"])) {
            $sort = $_GET["sort"];
            if (strpos($sort, '_') !== false) {
                $sort = explode("_", $sort);
                $sort_col = $this->escape_val($sort[0], $transaction_columns);
                $sort_order = $this->escape_val($sort[1], array("ASC","DESC"));
            }
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], TRUE)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', TRUE)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/twispay', 'user_token=' . $this->session->data['user_token'], TRUE)
        );

        $data['button_cancel'] = $this->language->get('text_button_cancel');
        $data['filter_customer'] = $this->language->get('text_filter_customer');
        $data['filter_status'] = $this->language->get('text_filter_status');
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', TRUE);
        $data['token'] = $this->session->data['user_token'];
        $data['statuses'] = $all_statuses;
        $data['customers'] = $all_customers;
        $data['selected_user_id'] = $user_id;
        $data['selected_status'] = $status;
        $data['sort_col'] = $sort_col;
        $data['sort_order'] = $sort_order;

        require_once(DIR_CATALOG.'controller/extension/payment/twispay/helpers/Twispay_Status_Updater.php');
        $data['status_refund_ok'] = Twispay_Status_Updater::$RESULT_STATUSES['REFUND_OK'];
        $data['status_complete_ok'] = Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_OK'];
        $data['order_status_processing_id'] = 2;

        $transactions = $this->model_extension_payment_twispay_transactions->getTransactions($user_id, $status, $sort_col, $sort_order);
        foreach ($transactions as  $key => $trans) {
            $transactions[$key]['order_status'] = $this->model_extension_payment_twispay_transactions->getOrderStatusId($transactions[$key]['order_id']);
            $transactions[$key]['is_recurring'] = $this->model_extension_payment_twispay_transactions->isRecurring($transactions[$key]['order_id']);
        }
        $data['trans'] = $transactions;

        $this->load->model('sale/recurring');
        $data['subscriptions'] = json_encode($this->model_sale_recurring->getRecurrings(['status' => 1]));
        $data['catalog'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;

        $this->load->model('user/api');
        $api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));

        /** If API defined and user has write permision over orders **/
        if ($api_info && $this->user->hasPermission('modify', 'sale/order')) {
            $session = new Session($this->config->get('session_engine'), $this->registry);
            $session->start();
            $this->model_user_api->deleteApiSessionBySessonId($session->getId());
            $this->model_user_api->addApiSession($api_info['api_id'], $session->getId(), $this->request->server['REMOTE_ADDR']);
            $session->data['api_id'] = $api_info['api_id'];

            /** Set the api_token with current user_token**/
            $data['api_token'] = $this->session->data['user_token'];
        } else {
            $data['api_token'] = '';
        }

        /**load template components*/
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        /**load the view*/
        $this->response->setOutput($this->load->view('extension/payment/twispay_transactions', $data));
    }

    /**
     * Check if the current logged user has "modify" permission in the current context.
     *
     * @return boolean - TRUE / FALSE
     *
     */
    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/twispay')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    /**
     * Attempts to create the directory specified by pathname.
     *
     * @param string path - The id of the order to be checked
     *
     * @return boolean - TRUE / FALSE
     *
     */
    private function makeDir($path)
    {
        return is_dir($path) || mkdir($path);
    }

    /**
     * Recursively removes directory and its content
     *
     * @param string path - Path to the directory.
     *
     * @return boolean - TRUE / FALSE
     *
     */
    private function delTree($path)
    {
        $files = array_diff(scandir($path), array('.', '..'));

        foreach ($files as $file) {
            (is_dir("$path/$file")) ? delTree("$path/$file") : unlink("$path/$file");
        }

        return rmdir($path);
    }

    /**
     * Checks if a string exists in a multidimensional array of strings and return it
     *
     * @param string item - Item to be searched.
     * @param array array - Target array.
     *
     * @return string - The found item | '0'
     *
     */
    private function escape_val($item, $array)
    {
        if ($this->in_array_r($item, $array)) {
            return $item;
        } else {
            return '0';
        }
    }

    /**
     * Checks if a string exists in a multidimensional array of strings
     *
     * @param string item - Item to be searched.
     * @param array array - Target array.
     *
     * @return boolean - TRUE / FALSE
     *
     */
    private function in_array_r($item, $array)
    {
        return preg_match('/"'.preg_quote($item, '/').'"/i', json_encode($array));
    }
}
