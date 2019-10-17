<?php
/**
 * @author   Twistpay
 * @version  1.0.1
 */

class ControllerExtensionPaymentTwispay extends Controller
{
    private static $live_host_name = 'https://secure.twispay.com';
    private static $stage_host_name = 'https://secure-stage.twispay.com';

    /**
     *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
     *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
     *|||||||||||||||||||||||||||||||||||| SEND ||||||||||||||||||||||||||||||||||||
     *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
     *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
     *
     * Function that loads the message that needs to be sent to the server via ajax.
     */
    public function index()
    {
        $this->language->load('extension/payment/twispay');

        $htmlOutput = "<div id='tw_submit_form_wp'><form id='twispay_payment_form'><input data-loading-text='".$this->language->get('button_processing')."' type='button' value='".$this->language->get('button_confirm')."' class='btn btn-primary' id='tw_submit_form_button' /></form></div>
        <script type='text/javascript'>
        var $ = jQuery;
        $('#tw_submit_form_button').off().on('click', function() {
          var tw_form_wp = $(this).closest('#tw_submit_form_wp');
          tw_form_wp.find('#tw_submit_form_button').button('loading');
          $.ajax({
            url: '".$this->url->link('extension/payment/twispay/send')."',
            success: function(data) {
              if(data == 'validation_error'){
                alert('".$this->language->get('validation_error')."');
                tw_form_wp.find('#tw_submit_form_button').button('reset');
              }else{
                tw_form_wp.html(data);
                tw_form_wp.find('#tw_submit_form_button').button('loading');
                tw_form_wp.find('#tw_payment_form').submit();
              }
            },error: function(xhr, ajaxOptions, thrownError) {
              alert('".$this->language->get('comunication_error')."'+thrownError);
              tw_form_wp.find('#tw_submit_form_button').button('reset');
            }
          });
        });
        </script>";

        /** Get order info */
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        if (!$order_info) {
            $htmlOutput = $this->language->get('general_error_invalid_order');
            return $htmlOutput;
        }

        /**Recurring validation*/
        /**Check if order containe recurring products*/
        if ($this->cart->hasRecurringProducts()) {
            $cart_products = $this->cart->getProducts();
            /**Check if cart contains more then one product*/
            if (sizeof($cart_products) > 1) {
                $htmlOutput = $this->language->get('checkout_error_too_many_prods');
                return $htmlOutput;
            }
            /**Get subscription*/
            $subscription = $cart_products[0]['recurring'];
            /**Check if the recurring profile has a trial period*/
            if ($subscription['trial']) {
                $trialDuration = (float) $subscription["trial_duration"]; /** how many times to repeat */
                $trialAmount = (float) $subscription["trial_price"];

                /**Check if multiple trial cycles*/
                if ($trialDuration > 1) {
                    $htmlOutput .= $this->language->get('checkout_notice_cycles_number');
                }
                /**Check if free trial period*/
                if ($trialAmount == 0) {
                    $htmlOutput = $this->language->get('checkout_notice_free_trial');
                    return $htmlOutput;
                }
            }
        }

        return $htmlOutput;
    }

    /**
     *
     * Function that populates the message that needs to be sent to the server.
     */
    public function send()
    {
        /** Load dependecies */
        $this->language->load('extension/payment/twispay');
        $this->load->model('checkout/order');
        require_once(DIR_APPLICATION.'controller/extension/payment/twispay/helpers/Twispay_Encoder.php');
        require_once(DIR_APPLICATION.'controller/extension/payment/twispay/helpers/Twispay_Logger.php');

        /** Get order info */
        $order_id = $this->session->data['order_id'];
        $order_info = $this->model_checkout_order->getOrder($order_id);

        /** Get the Site ID and the Private Key. */
        if (!empty($this->config->get('payment_twispay_testMode'))) {
            $this->hostName = ControllerExtensionPaymentTwispay::$stage_host_name;
            $this->siteID = $this->config->get('payment_twispay_staging_site_id');
            $this->secretKey = $this->config->get('payment_twispay_staging_site_key');
        } else {
            $this->hostName = ControllerExtensionPaymentTwispay::$live_host_name;
            $this->siteID = $this->config->get('payment_twispay_live_site_id');
            $this->secretKey = $this->config->get('payment_twispay_live_site_key');
        }

        if ($order_info) {
            /** Extract the customer details. */
            $customer = [ 'identifier' => (0 == $order_info['customer_id']) ? ('_ORD' . $order_id . '_' . date('YmdHis')) : ('_' . $order_info['customer_id'] . '_' . date('YmdHis'))
                        , 'firstName' => ($order_info['payment_firstname']) ? ($order_info['payment_firstname']) : ($order_info['shipping_firstname'])
                        , 'lastName' => ($order_info['payment_lastname']) ? ($order_info['payment_lastname']) : ($order_info['shipping_lastname'])
                        , 'country' => ($order_info['payment_iso_code_2']) ? ($order_info['payment_iso_code_2']) : ($order_info['shipping_iso_code_2'])
                        , 'city' => ($order_info['payment_city']) ? ($order_info['payment_city']) : ($order_info['shipping_city'])
                        , 'zipCode' => ($order_info['payment_postcode']) ? ($order_info['payment_postcode']) : ($order_info['shipping_postcode'])
                        , 'address' => ($order_info['payment_address_1']) ? ($order_info['payment_address_1'].' '.$order_info['payment_address_2']) : ($order_info['shipping_address_1'].' '.$order_info['shipping_address_2'])
                        , 'phone' => ((strlen($order_info['telephone']) && $order_info['telephone'][0] == '+') ? ('+') : ('')) . preg_replace('/([^0-9]*)+/', '', $order_info['telephone'])
                        , 'email' => $order_info['email']
                        ];
            /** Calculate the backUrl through which the server will provide the status of the order. */
            $backUrl = $this->url->link('extension/payment/twispay/callback');

            /** Build the data object to be posted to Twispay. */
            $orderData = [ 'siteId' => $this->siteID
                         , 'customer' => $customer
                         , 'order' => [ 'orderId' => $order_id
                                      , 'type' => 'purchase'
                                      , 'amount' =>  $this->currency->format($order_info['total'], $order_info['currency_code'], false, false)
                                      , 'currency' => $order_info['currency_code']
                                      ]
                         , 'cardTransactionMode' => 'authAndCapture'
                         , 'invoiceEmail' => ''
                         , 'backUrl' => $backUrl
                         ];

            $cart_products = $this->cart->getProducts();

            /** !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
            /** !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! IMPORTANT !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
            /** READ:  We presume that there will be ONLY ONE subscription product inside the order. */
            /** !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
            /** !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */

            if ($this->cart->hasRecurringProducts()) {
                $this->load->model('checkout/recurring');
                $this->load->model('checkout/order');
                if (sizeof($cart_products) == 1) {
                    $first_product = $cart_products[0];
                    $subscription = $first_product['recurring'];

                    /** Extract the subscription details. */
                    $trialNumberOfPayments = (float) $subscription["trial_duration"]; /** how long */
                    $trialFreq = $subscription["trial_frequency"]; /** unit of measurement for duration */
                    $trialCycle = (float) $subscription["trial_cycle"]; /** how many times to repeat */
                    $trialAmount = (float) $subscription["trial_price"];

                    $totalTrialPeriod = $trialCycle * $trialNumberOfPayments;
                    $totalTrialAmount = $trialAmount * $trialNumberOfPayments;
                    $today = date("Y-m-d");
                    $firstBillDate = $today;
                    switch ($trialFreq) {
                        case 'day':
                            $firstBillDate= date("Y-m-d", strtotime("$today + $totalTrialPeriod day"));
                            break;
                        case 'week':
                            $firstBillDate= date("Y-m-d", strtotime("$today + $totalTrialPeriod week"));
                            break;
                        case 'semi_month':
                            $totalTrialPeriod *= 2;
                            $firstBillDate= date("Y-m-d", strtotime("$today + $totalTrialPeriod week"));
                            break;
                        case 'month':
                            $firstBillDate= date("Y-m-d", strtotime("$today + $totalTrialPeriod month"));
                            break;
                        case 'year':
                            $firstBillDate= date("Y-m-d", strtotime("$today + $totalTrialPeriod year"));
                            break;
                        default:
                            break;
                    }
                    $firstBillDate .="T".date("H:i:s");
                    /** Calculate the subscription's interval type and value. */
                    $numberOfPayments = $subscription["duration"]; /** how long */
                    $intervalFreq = $subscription["frequency"]; /** unit of measurement for duration */
                    $intervalCycle = $subscription["cycle"]; /** how many times to repeat */

                    $intervalDuration = $intervalCycle;
                    switch ($intervalFreq) {
                        case 'week':
                            /** Convert weeks to days. */
                            $intervalFreq = 'day';
                            $intervalDuration = /**days/week*/7 * $intervalCycle;
                            break;
                        case 'semi_month':
                            /** Convert two_weeks to days. */
                            $intervalFreq = 'day';
                            $intervalDuration = /**days/two_weeks*/14 * $intervalCycle;
                            break;
                        case 'year':
                            /** Convert years to months. */
                            $intervalFreq = 'month';
                            $intervalDuration = /**months/year*/12 * $intervalCycle;
                            break;
                        default:
                            /** We change nothing in case of DAYS and MONTHS */
                            break;
                    }

                    /** Add the subscription data. */
                    $orderData['order']['intervalType'] = $intervalFreq;
                    $orderData['order']['intervalValue'] = $intervalDuration;
                    $orderData['order']['type'] = "recurring";
                    $orderData['order']['amount'] = $subscription["price"];
                    if ($subscription['trial']) {
                        if ($trialAmount == 0) {
                            echo 'validation_error';
                            return false; /** die */
                        }
                        $orderData['order']['trialAmount'] = $totalTrialAmount;
                        $orderData['order']['firstBillDate'] = $firstBillDate;
                    }
                    $orderData['order']['description'] = $intervalDuration . " " . $intervalFreq . " subscription " . $subscription['name'];

                    /** create new recurring and set to pending status as no payment has been made yet. */
                    $subscription["sub_name"] = $subscription["name"];
                    unset($subscription["name"]);
                    $recurring_item = array_merge($first_product, $subscription);
                    $this->model_checkout_order->addOrderHistory($order_id, 1/**Pending*/, $this->language->get('a_order_hold_notice'), TRUE);
                    $this->model_checkout_recurring->addRecurring($order_id, $orderData['order']['description'], $recurring_item);
                } else {
                    echo 'validation_error';
                    return false; /** die */
                }
            } else {
                /** Extract the items details. */
                $items = array();
                foreach ($cart_products as $item) {
                    $items[] = ['item' => $item['name']
                             , 'units' =>  $item['quantity']
                             , 'unitPrice' => $this->currency->format($item['price'], $order_info['currency_code'], false, false)
                             ];
                }
                $orderData['order']['items'] = $items;
            }

            $base64JsonRequest = Twispay_Encoder::getBase64JsonRequest($orderData);
            $base64Checksum = Twispay_Encoder::getBase64Checksum($orderData, $this->secretKey);

            $htmlOutput = "<form action='".$this->hostName."' method='POST' accept-charset='UTF-8' id='tw_payment_form'>
                <input type='hidden' name='jsonRequest' value='".$base64JsonRequest."'>
                <input type='hidden' name='checksum' value='".$base64Checksum."'>
                <input type='submit' data-loading-text='".$this->language->get('button_processing')."' value='".$this->language->get("button_retry")."' class='btn btn-primary disabled' disabled='disabled' id='tw_submit_form_button' />
            </form>";

            echo $htmlOutput;
            return TRUE;
        } else {
            echo 'validation_error';
            return false; /** die */
        }
    }

    /**
     *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
     *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
     *|||||||||||||||||||||||||||||||| CALLBACK ||||||||||||||||||||||||||||||||||||
     *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
     *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
     *
     * Function that processes the backUrl response of the server.
     */

    public function callback()
    {
        /** Load dependecies */
        $this->language->load('extension/payment/twispay');
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/twispay_transaction');
        require_once(DIR_APPLICATION.'controller/extension/payment/twispay/helpers/Twispay_Response.php');
        require_once(DIR_APPLICATION.'controller/extension/payment/twispay/helpers/Twispay_Logger.php');
        require_once(DIR_APPLICATION.'controller/extension/payment/twispay/helpers/Twispay_Notification.php');
        require_once(DIR_APPLICATION.'controller/extension/payment/twispay/helpers/Twispay_Status_Updater.php');
        require_once(DIR_APPLICATION.'controller/extension/payment/twispay/helpers/Twispay_Thankyou.php');

        /** Get the Site ID and the Private Key. */
        if (!empty($this->config->get('payment_twispay_testMode'))) {
            $this->secretKey = $this->config->get('payment_twispay_staging_site_key');
        } else {
            $this->secretKey = $this->config->get('payment_twispay_live_site_key');
        }

        /** Check if there is NO secret key. */
        if ('' == $this->secretKey) {
            Twispay_Logger::Twispay_log($this->language->get('log_error_invalid_private'));
            Twispay_Notification::notice_to_cart($this);
            die($this->language->get('log_error_invalid_private'));
        }

        if (!empty($_POST)) {
            echo $this->language->get('processing');
            sleep(1);

            /** Check if the POST is corrupted: Doesn't contain the 'opensslResult' and the 'result' fields. */
            if (((false == isset($_POST['opensslResult'])) && (false == isset($_POST['result'])))) {
                Twispay_Logger::Twispay_log($this->language->get('log_error_empty_response'));
                Twispay_Notification::notice_to_cart($this);
                die($this->language->get('log_error_empty_response'));
            }

            /** Extract the server response and decript it. */
            $decrypted = Twispay_Response::Twispay_decrypt_message(/**tw_encryptedResponse*/(isset($_POST['opensslResult'])) ? ($_POST['opensslResult']) : ($_POST['result']), $this->secretKey);

            /** Check if decryption failed.  */
            if (false === $decrypted) {
                Twispay_Logger::Twispay_log($this->language->get('log_error_decryption_error'));
                Twispay_Notification::notice_to_cart($this);
                die($this->language->get('log_error_decryption_error'));
            } else {
                Twispay_Logger::Twispay_log($this->language->get('log_ok_string_decrypted'). json_encode($decrypted));
            }

            /** Validate the decripted response. */
            $orderValidation = Twispay_Response::Twispay_checkValidation($decrypted, $this);
            if (TRUE !== $orderValidation) {
                Twispay_Logger::Twispay_log($this->language->get('log_error_validating_failed'));
                Twispay_Notification::notice_to_cart($this);
                die($this->language->get('log_error_validating_failed'));
            }

            /** Extract the order. */
            $orderId = explode('_', $decrypted['externalOrderId'])[0];
            $order = $this->model_checkout_order->getOrder($orderId);

            /*** Check if the order extraction failed. */
            if (false == $order) {
                Twispay_Logger::Twispay_log($this->language->get('log_error_invalid_order'));
                Twispay_Notification::notice_to_cart($this);
                die($this->language->get('log_error_invalid_order'));
            }

            /** Check if transaction already exist */
            if ($this->model_extension_payment_twispay_transaction->checkTransaction($decrypted['transactionId'], $this)) {
                Twispay_Logger::Twispay_log($this->language->get('log_error_transaction_exist') . $decrypted['transactionId']);
                /* Redirect to Twispay "Thank you Page" if it is set, if not, redirect to default "Thank you Page" */
                if ($this->config->get('twispay_redirect_page') != NULL && strlen($this->config->get('twispay_redirect_page'))) {
                    Twispay_Thankyou::custom_page($this->config->get('twispay_redirect_page'));
                } else {
                    Twispay_Thankyou::default_page();
                }
            }

            /** Extract the status received from server. */
            $decrypted['status'] = (empty($decrypted['status'])) ? ($decrypted['transactionStatus']) : ($decrypted['status']);
            Twispay_Status_Updater::updateStatus_backUrl($orderId, $decrypted, $this);
        } else {
            Twispay_Logger::Twispay_log($this->language->get('no_post'));
            Twispay_Notification::notice_to_cart($this, '', $this->language->get('no_post'));
        }
    }

    /**
     *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
     *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
     *|||||||||||||||||||||||||||||| Server to Server ||||||||||||||||||||||||||||||
     *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
     *||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
     *
     * Function that processes the IPN (Instant Payment Notification) response of the server.
     */
    public function s2s()
    {
        /** Load dependencies */
        $this->language->load('extension/payment/twispay');
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/twispay_transaction');
        require_once(DIR_APPLICATION.'controller/extension/payment/twispay/helpers/Twispay_Response.php');
        require_once(DIR_APPLICATION.'controller/extension/payment/twispay/helpers/Twispay_Logger.php');
        require_once(DIR_APPLICATION.'controller/extension/payment/twispay/helpers/Twispay_Status_Updater.php');

        /** Get the Site ID and the Private Key. */
        if (!empty($this->config->get('payment_twispay_testMode'))) {
            $this->secretKey = $this->config->get('payment_twispay_staging_site_key');
        } else {
            $this->secretKey = $this->config->get('payment_twispay_live_site_key');
        }

        /** Check if there is NO secret key. */
        if ('' == $this->secretKey) {
            Twispay_Logger::Twispay_log($this->language->get('log_error_invalid_private'));
            die($this->language->get('log_error_invalid_private'));
        }

        if (!empty($_POST)) {
            /** Check if the POST is corrupted: Doesn't contain the 'opensslResult' and the 'result' fields. */
            if (((false == isset($_POST['opensslResult'])) && (false == isset($_POST['result'])))) {
                Twispay_Logger::Twispay_log($this->language->get('log_error_empty_response'));
                die($this->language->get('log_error_empty_response'));
            }

            /** Extract the server response and decript it. */
            $decrypted = Twispay_Response::Twispay_decrypt_message(/**tw_encryptedResponse*/(isset($_POST['opensslResult'])) ? ($_POST['opensslResult']) : ($_POST['result']), $this->secretKey);

            /** Check if decryption failed.  */
            if (false === $decrypted) {
                Twispay_Logger::Twispay_log($this->language->get('log_error_decryption_error'));
                die($this->language->get('log_error_decryption_error'));
            } else {
                Twispay_Logger::Twispay_log($this->language->get('log_ok_string_decrypted'). json_encode($decrypted));
            }

            /** Validate the decripted response. */
            $orderValidation = Twispay_Response::Twispay_checkValidation($decrypted, $this);
            if (TRUE !== $orderValidation) {
                Twispay_Logger::Twispay_log($this->language->get('log_error_validating_failed'));
                die($this->language->get('log_error_validating_failed'));
            }

            /** Extract the order. */
            $orderId = explode('_', $decrypted['externalOrderId'])[0];
            $order = $this->model_checkout_order->getOrder($orderId);

            /** Check if the order extraction failed. */
            if (false == $order) {
                Twispay_Logger::Twispay_log($this->language->get('log_error_invalid_order'));
                die($this->language->get('log_error_invalid_order'));
            }

            /** Check if transaction already exist */
            if ($this->model_extension_payment_twispay_transaction->checkTransaction($decrypted['transactionId'], $this)) {
                Twispay_Logger::Twispay_log($this->language->get('log_error_transaction_exist') . $decrypted['transactionId']);
                die($this->language->get('log_error_transaction_exist') . $decrypted['transactionId']);
            }

            /** Extract the status received from server. */
            $decrypted['status'] = (empty($decrypted['status'])) ? ($decrypted['transactionStatus']) : ($decrypted['status']);

            Twispay_Status_Updater::updateStatus_IPN($orderId, $decrypted, $this);
            die('OK');
        } else {
            Twispay_Logger::Twispay_log($this->language->get('no_post'));
            die($this->language->get('no_post'));
        }
    }
}
