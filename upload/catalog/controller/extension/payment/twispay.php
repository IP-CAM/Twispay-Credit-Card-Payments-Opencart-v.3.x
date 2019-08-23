<?php
/**
 * @author   Twistpay
 * @version  1.0.0
 */

class ControllerExtensionPaymentTwispay extends Controller
{
    private static $live_host_name = 'https://secure.twispay.com';
    private static $stage_host_name = 'https://secure-stage.twispay.com';

    /*
    ////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////// INDEX /////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
     *
     * Function that loads the message that needs to be sent to the server via ajax.
     */
    public function index(){
      $this->language->load('extension/payment/twispay');

      $htmlOutput = "<div id='submit_form_wp'><form id='twispay_payment_form'><input data-loading-text='".$this->lang('button_processing')."' type='button' value='".$this->lang('button_confirm')."' class='btn btn-primary' id='submit_form_button' /></form></div>
      <script type='text/javascript'>
        var $ = jQuery;
        $(document).on('click', '#submit_form_button', function() {
          $('#submit_form_button').button('loading');
          $.ajax({
            url: '".$this->url->link('extension/payment/twispay/send')."',
            success: function(data) {
              if(data == 'validation_error'){
                alert('".$this->lang('validation_error')."');
                $('#submit_form_button').button('reset');
              }else{
                $('#submit_form_wp').html(data);
                $('#submit_form_button').button('loading');
                $('#twispay_payment_form').submit();
              }
            },error: function(xhr, ajaxOptions, thrownError) {
              alert('".$this->lang('comunication_error')."'+thrownError);
              $('#submit_form_button').button('reset');
            }
          });
        });
      </script>";

      //Recurring validation
      if ($this->cart->hasRecurringProducts()) {
        $cart_products = $this->cart->getProducts();
        $this->load->model('checkout/recurring');
        if (sizeof($cart_products) > 1) {
          $htmlOutput = $this->lang('checkout_error_too_many_prods');
        }
        $subscription = $cart_products[0]['recurring'];
        if ($subscription['trial']) {
            $trialCycle = (float) $subscription["trial_cycle"]; //how many times to repeat
            $trialAmount = (float) $subscription["trial_price"];
            if($trialCycle > 1){
              $htmlOutput .= $this->lang('checkout_notice_cycles_number');
            }
            if($trialAmount == 0){
              $htmlOutput = $this->lang('checkout_notice_free_trial');
            }
        }
      }

      return $htmlOutput;
    }

    /*
    ////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////// SEND //////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
     *
     * Function that populates the message that needs to be sent to the server.
     */
    public function send()
    {
        /* Load dependecies */
        $this->language->load('extension/payment/twispay');
        $this->load->model('checkout/order');
        $this->load->helper('Twispay_Encoder');
        $this->load->helper('Twispay_Logger');

        /* Get order info */
        $order_id = $this->session->data['order_id'];
        $order_info = $this->model_checkout_order->getOrder($order_id);

        /* Get the Site ID and the Private Key. */
        if (!$this->config->get('payment_twispay_testMode')) {
            $this->hostName = ControllerExtensionPaymentTwispay::$live_host_name;
            $this->siteID = $this->config->get('payment_twispay_staging_site_id');
            $this->secretKey = $this->config->get('payment_twispay_staging_site_key');
        } else {
            $this->hostName = ControllerExtensionPaymentTwispay::$stage_host_name;
            $this->siteID = $this->config->get('payment_twispay_staging_site_id');
            $this->secretKey = $this->config->get('payment_twispay_staging_site_key');
        }

        if ($order_info) {
            /* Extract the customer details. */
            $customer = [ 'identifier' => (0 == $order_info['customer_id']) ? ('_' . $order_id . '_' . date('YmdHis')) : ('_' . $order_info['customer_id'] . '_' . date('YmdHis'))
                        , 'firstName' => ($order_info['payment_firstname']) ? ($order_info['payment_firstname']) : ($order_info['shipping_firstname'])
                        , 'lastName' => ($order_info['payment_lastname']) ? ($order_info['payment_lastname']) : ($order_info['shipping_lastname'])
                        , 'country' => ($order_info['payment_iso_code_2']) ? ($order_info['payment_iso_code_2']) : ($order_info['shipping_iso_code_2'])
                        , 'city' => ($order_info['payment_city']) ? ($order_info['payment_city']) : ($order_info['shipping_city'])
                        , 'zipCode' => ($order_info['payment_postcode']) ? ($order_info['payment_postcode']) : ($order_info['shipping_postcode'])
                        , 'address' => ($order_info['payment_address_1']) ? ($order_info['payment_address_1'].' '.$order_info['payment_address_2']) : ($order_info['shipping_address_1'].' '.$order_info['shipping_address_2'])
                        , 'phone' => ((strlen($order_info['telephone']) && $order_info['telephone'][0] == '+') ? ('+') : ('')) . preg_replace('/([^0-9]*)+/', '', $order_info['telephone'])
                        , 'email' => $order_info['email']
                        ];

            /* Calculate the backUrl through which the server will provide the status of the order. */
            $backUrl = $this->url->link('extension/payment/twispay/callback');

            /* Build the data object to be posted to Twispay. */
            $orderData = [ 'siteId' => $this->siteID
                     , 'customer' => $customer
                     , 'order' => [ 'orderId' => (isset($_GET['tw_reload']) && $_GET['tw_reload']) ? ($order_id . '_' . date('YmdHis')) : ($order_id)
                                  , 'type' => 'purchase'
                                  , 'amount' =>  $this->currency->format($order_info['total'], $order_info['currency_code'], FALSE, FALSE)
                                  , 'currency' => $order_info['currency_code']
                                  ]
                     , 'cardTransactionMode' => 'authAndCapture'
                     , 'invoiceEmail' => ''
                     , 'backUrl' => $backUrl
            ];

            $cart_products = $this->cart->getProducts();

            /* !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
            /* !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! IMPORTANT !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
            /* READ:  We presume that there will be ONLY ONE subscription product inside the order. */
            /* !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
            /* !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */

            if ($this->cart->hasRecurringProducts()) {
              	$this->load->model('checkout/recurring');
                if (sizeof($cart_products) == 1) {
                    $first_product = $cart_products[0];
                    $subscription = $first_product['recurring'];

                    /* Extract the subscription details. */
                    $trialNumberOfPayments = (float) $subscription["trial_duration"]; //how long
                    $trialFreq = $subscription["trial_frequency"]; //unit of measurement for duration
                    $trialCycle = (float) $subscription["trial_cycle"]; //how many times to repeat
                    $trialAmount = (float) $subscription["trial_price"];

                    $totalTrialPeriod = $trialCycle * $trialNumberOfPayments;
                    $totalTrialAmount = $trialAmount * $trialNumberOfPayments;
                    $today = date("Y-m-d");
                    $firstBillDate = $today;
                    switch ($trialFreq) {
                        case 'day':
                            $firstBillDate= date("Y-m-d", strtotime("$today +$totalTrialPeriod day"));
                            break;
                        case 'week':
                            $firstBillDate= date("Y-m-d", strtotime("$today +$totalTrialPeriod week"));
                            break;
                        case 'semi_month':
                            $totalTrialPeriod *= 2;
                            $firstBillDate= date("Y-m-d", strtotime("$today +$totalTrialPeriod week"));
                            break;
                        case 'month':
                            $firstBillDate= date("Y-m-d", strtotime("$today +$totalTrialPeriod month"));
                            break;
                        case 'year':
                            $firstBillDate= date("Y-m-d", strtotime("$today +$totalTrialPeriod year"));
                            break;
                        default:
                            break;
                    }

                    /* Calculate the subscription's interval type and value. */
                    $numberOfPayments = $subscription["duration"]; //how long
                    $intervalFreq = $subscription["frequency"]; //unit of measurement for duration
                    $intervalCycle = $subscription["cycle"]; //how many times to repeat

                    $intervalDuration = $intervalCycle;
                    switch ($intervalFreq) {
                        case 'week':
                            /* Convert weeks to days. */
                            $intervalFreq = 'day';
                            $intervalDuration = /*days/week*/7 * $intervalCycle;
                            break;
                        case 'semi_month':
                            /* Convert two_weeks to days. */
                            $intervalFreq = 'day';
                            $intervalDuration = /*days/two_weeks*/14 * $intervalCycle;
                            break;
                        case 'year':
                            /* Convert years to months. */
                            $intervalFreq = 'month';
                            $intervalDuration = /*months/year*/12 * $intervalCycle;
                            break;
                        default:
                            /* We change nothing in case of DAYS and MONTHS */
                            break;
                    }

                    /* Add the subscription data. */
                    $orderData['order']['intervalType'] = $intervalFreq;
                    $orderData['order']['intervalValue'] = $intervalDuration;
                    $orderData['order']['type'] = "recurring";
                    $orderData['order']['amount'] = $subscription["price"];
                    if ($subscription['trial']) {
                        if($trialAmount == 0){
                          echo 'validation_error';
                          return FALSE; // die
                        }
                        $orderData['order']['trialAmount'] = $totalTrialAmount;
                        $orderData['order']['firstBillDate'] = $firstBillDate;
                    }
                    $orderData['order']['description'] = $intervalDuration . " " . $intervalFreq . " subscription " . $subscription['name'];

                    $this->load->model('checkout/recurring');
                    $this->load->model('checkout/order');

                    // create new recurring and set to pending status as no payment has been made yet.
                    $subscription["sub_name"] = $subscription["name"];
                    unset($subscription["name"]);
                    $recurring_item = array_merge($first_product, $subscription);
                    $this->model_checkout_order->addOrderHistory($order_id, 1/*Pending*/, $this->lang('a_order_hold_notice'), TRUE);
                    $recurring_id = $this->model_checkout_recurring->addRecurring($order_id, $orderData['order']['description'], $recurring_item);
                } else {
                    echo 'validation_error';
                    return FALSE; // die
                }
            } else {
                /* Extract the items details. */
                $items = array();
                foreach ($cart_products as $item) {
                    $items[] = ['item' => $item['name']
                             , 'units' =>  $item['quantity']
                             , 'unitPrice' => $this->currency->format($item['price'], $order_info['currency_code'] , FALSE, FALSE)
                             ];
                }
                $orderData['order']['items'] = $items;
            }

            $base64JsonRequest = Twispay_Encoder::getBase64JsonRequest($orderData);
            $base64Checksum = Twispay_Encoder::getBase64Checksum($orderData, $this->secretKey);

            $htmlOutput = "<form action='".$this->hostName."' method='POST' accept-charset='UTF-8' id='twispay_payment_form'>
                <input type='hidden' name='jsonRequest' value='".$base64JsonRequest."'>
                <input type='hidden' name='checksum' value='".$base64Checksum."'>
                <input type='submit' data-loading-text='".$this->lang('button_processing')."' value='".$this->lang("button_retry")."' class='btn btn-primary disabled' disabled='disabled' id='submit_form_button' />
            </form>";

            echo $htmlOutput;
            return TRUE;
          }
    }

    /*
    ////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////// CALLBACK //////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
     *
     * Function that processes the backUrl response of the server.
     */

    public function callback()
    {
        /* Load dependecies */
        $this->language->load('extension/payment/twispay');
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/twispay_transaction');
        $this->load->helper('Twispay_Response');
        $this->load->helper('Twispay_Logger');
        $this->load->helper('Twispay_Notification');
        $this->load->helper('Twispay_Status_Updater');

        /* Get the Site ID and the Private Key. */
        if (!$this->config->get('payment_twispay_testMode')) {
            $this->secretKey = $this->config->get('payment_twispay_live_site_key');
        } else {
            $this->secretKey = $this->config->get('payment_twispay_staging_site_key');
        }

        if (!empty($_POST)) {
            echo $this->lang('processing');
            sleep(1);

            /* Check if the POST is corrupted: Doesn't contain the 'opensslResult' and the 'result' fields. */
            if (((FALSE == isset($_POST['opensslResult'])) && (FALSE == isset($_POST['result'])))) {
                $this->_log($this->lang('log_error_empty_response'));
                Twispay_Notification::notice_to_cart($this);
                die($this->lang('log_error_empty_response'));
            }

            /* Check if there is NO secret key. */
            if ('' == $this->secretKey) {
                $this->_log($this->lang('log_error_invalid_private'));
                Twispay_Notification::notice_to_cart($this);
                die($this->lang('log_error_invalid_private'));
            }

            /* Extract the server response and decript it. */
            $decrypted = Twispay_Response::Twispay_decrypt_message(/*tw_encryptedResponse*/(isset($_POST['opensslResult'])) ? ($_POST['opensslResult']) : ($_POST['result']), $this->secretKey);

            /* Check if decryption failed.  */
            if (FALSE === $decrypted) {
                $this->_log($this->lang('log_error_decryption_error'));
                Twispay_Notification::notice_to_cart($this);
                die($this->lang('log_error_decryption_error'));
            } else {
                $this->_log($this->lang('log_ok_string_decrypted'). json_encode($decrypted));
            }

            /* Validate the decripted response. */
            $orderValidation = Twispay_Response::Twispay_checkValidation($decrypted, $this);
            if (TRUE !== $orderValidation) {
                $this->_log($this->lang('log_error_validating_failed'));
                Twispay_Notification::notice_to_cart($this);
                die($this->lang('log_error_validating_failed'));
            }

            /* Extract the order. */
            $orderId = explode('_', $decrypted['externalOrderId'])[0];
            $order = $this->model_checkout_order->getOrder($orderId);

            /* Check if the order extraction failed. */
            if (FALSE == $order) {
                $this->_log($this->lang('log_error_invalid_order'));
                Twispay_Notification::notice_to_cart($this);
                die($this->lang('log_error_invalid_order'));
            }

            // Check if transaction already exist
            $transaction_id = $decrypted['transactionId'];
            if($this->model_extension_payment_twispay_transaction->checkTransaction($transaction_id, $this)){
              $this->_log($this->lang('log_error_transaction_exist') . $transaction_id);
              Twispay_Notification::notice_to_checkout($this);
              die($this->lang('log_error_transaction_exist') . $transaction_id);
            }

            /* Extract the status received from server. */
            $decrypted['status'] = (empty($decrypted['status'])) ? ($decrypted['transactionStatus']) : ($decrypted['status']);

            Twispay_Status_Updater::updateStatus_backUrl($orderId, $decrypted, $this);
        } else {
            $this->_log($this->lang('no_post'));
            Twispay_Notification::notice_to_cart($this, '', $this->lang('no_post'));
        }
    }

    /*
    ////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////// Server to Server ////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
     *
     * Function that processes the IPN (Instant Payment Notification) response of the server.
     */
    public function s2s()
    {
        /* Load dependencies */
        $this->language->load('extension/payment/twispay');
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/twispay_transaction');
        $this->load->helper('Twispay_Response');
        $this->load->helper('Twispay_Logger');
        $this->load->helper('Twispay_Status_Updater');

        /* Get the Site ID and the Private Key. */
        if (!$this->config->get('payment_twispay_testMode')) {
            $this->secretKey = $this->config->get('payment_twispay_live_site_key');
        } else {
            $this->secretKey = $this->config->get('payment_twispay_staging_site_key');
        }

        if (!empty($_POST)) {
            /* Check if the POST is corrupted: Doesn't contain the 'opensslResult' and the 'result' fields. */
            if (((FALSE == isset($_POST['opensslResult'])) && (FALSE == isset($_POST['result'])))) {
                $this->_log($this->lang('log_error_empty_response'));
                die($this->lang('log_error_empty_response'));
            }

            /* Check if there is NO secret key. */
            if ('' == $this->secretKey) {
                $this->_log($this->lang('log_error_invalid_private'));
                die($this->lang('log_error_invalid_private'));
            }
            /* Extract the server response and decript it. */
            $decrypted = Twispay_Response::Twispay_decrypt_message(/*tw_encryptedResponse*/(isset($_POST['opensslResult'])) ? ($_POST['opensslResult']) : ($_POST['result']), $this->secretKey);

            /* Check if decryption failed.  */
            if (FALSE === $decrypted) {
                $this->_log($this->lang('log_error_decryption_error'));
                die($this->lang('log_error_decryption_error'));
            } else {
                $this->_log($this->lang('log_ok_string_decrypted'). json_encode($decrypted));
            }

            /* Validate the decripted response. */
            $orderValidation = Twispay_Response::Twispay_checkValidation($decrypted, $this);
            if (TRUE !== $orderValidation) {
                $this->_log($this->lang('log_error_validating_failed'));
                die($this->lang('log_error_validating_failed'));
            }

            /* Extract the order. */
            $orderId = explode('_', $decrypted['externalOrderId'])[0];
            $order = $this->model_checkout_order->getOrder($orderId);

            /* Check if the order extraction failed. */
            if (FALSE == $order) {
                $this->_log($this->lang('log_error_invalid_order'));
                die($this->lang('log_error_invalid_order'));
            }

            //Check if transaction already exist
            $transaction_id = $decrypted['transactionId'];
            if($this->model_extension_payment_twispay_transaction->checkTransaction($transaction_id, $this)){
              $this->_log($this->lang('log_error_transaction_exist') . $transaction_id);
              die($this->lang('log_error_transaction_exist') . $transaction_id);
            }

            /* Extract the status received from server. */
            $decrypted['status'] = (empty($decrypted['status'])) ? ($decrypted['transactionStatus']) : ($decrypted['status']);

            Twispay_Status_Updater::updateStatus_IPN($orderId, $decrypted, $this);
            die('OK');
        } else {
            $this->_log($this->lang('no_post'));
            die($this->lang('no_post'));
        }
    }

    /**
     * Log a message
     *
     * @param string: The message to be logged.
     *
     * @return void
     */
    private function _log($string='')
    {
        Twispay_Logger::Twispay_log($string);
    }

    /**
     * Get a string from store language
     *
     * @param string: The string identifier.
     *
     * @return void
     */
    private function lang($string='')
    {
        return $this->language->get($string);
    }
}
