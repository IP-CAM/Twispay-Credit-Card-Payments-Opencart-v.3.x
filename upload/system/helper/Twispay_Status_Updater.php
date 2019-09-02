<?php
/**
 * Twispay Helpers
 *
 * Updates the statused of orders and subscriptions based
 *  on the status read from the server response.
 *
 * @author   Twistpay
 * @version  1.0.0
 */

/* Security class check */
if (! class_exists('Twispay_Status_Updater')) :
    /**
     * Class that implements methods to update the statuses
     * of orders and subscriptions based on the status received
     * from the server.
     */
    class Twispay_Status_Updater
    {
        /* Array containing the possible result statuses. */
        public static $RESULT_STATUSES = [ 'UNCERTAIN' => 'uncertain' /* No response from provider */
                                         , 'IN_PROGRESS' => 'in-progress' /* Authorized */
                                         , 'COMPLETE_OK' => 'complete-ok' /* Captured */
                                         , 'COMPLETE_FAIL' => 'complete-failed' /* Not authorized */
                                         , 'CANCEL_OK' => 'cancel-ok' /* Capture reversal */
                                         , 'REFUND_OK' => 'refund-ok' /* Settlement reversal */
                                         , 'VOID_OK' => 'void-ok' /* Authorization reversal */
                                         , 'CHARGE_BACK' => 'charge-back' /* Charge-back received */
                                         , 'THREE_D_PENDING' => '3d-pending' /* Waiting for 3d authentication */
                                         , 'EXPIRING' => 'expiring' /* The recurring order has expired */

                                         , 'REFUND_REQUESTED' => 'refund-requested' /* The recurring order has expired */
                                         ];
        /**
         * Update the status of an order according to the received server status.
         *
         * @param string order_id: The id of the order for which to update the status.
         * @param object decrypted: Decrypted order message.
         * @param object that: Controller instance use for accessing runtime values like configuration, active language, etc.
         *
         * @return void
         */
        public static function updateStatus_backUrl($order_id, $decrypted, $that)
        {
            #load dependencies
            $that->language->load('extension/payment/twispay');
            $that->load->model('checkout/order');
            $that->load->model('extension/payment/twispay');
            $that->load->model('extension/payment/twispay_recurring');
            $that->load->model('extension/payment/twispay_transaction');
            $that->load->helper('Twispay_Logger');
            $that->load->helper('Twispay_Status_Updater');
            $that->load->helper('Twispay_Notification');
            $that->load->helper('Twispay_Thankyou');

            /* Extract the order. */
            $order = $that->model_checkout_order->getOrder($order_id);
            $order_recurring = $that->model_extension_payment_twispay_recurring->getRecurringByOrderId($order_id);

            switch ($decrypted['status']) {
                case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_FAIL']:
                    /* Mark order as Failed. */
                    $that->model_checkout_order->addOrderHistory($order_id, 10/*Failed*/, $that->language->get('a_order_failed_notice'), TRUE);
                    $that->model_extension_payment_twispay_transaction->insertTransaction($decrypted);

                    Twispay_Logger::Twispay_log($that->language->get('log_ok_status_failed') . $order_id);
                    Twispay_Notification::notice_to_checkout($that);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['THREE_D_PENDING']:
                    /* Mark order as Pending. */
                    $that->model_checkout_order->addOrderHistory($order_id, 1/*Pending*/, $that->language->get('a_order_hold_notice'), TRUE);
                    $that->model_extension_payment_twispay_transaction->insertTransaction($decrypted);

                    Twispay_Logger::Twispay_log($that->language->get('log_ok_status_hold') . $order_id);
                    Twispay_Notification::notice_to_checkout($that, '', $that->lang('general_error_hold_notice'));
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['IN_PROGRESS']:
                case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_OK']:
                    /* If there is no invoice created*/
                    if (!$order['invoice_no']) {
                        /* Create invoice */
                        $invoice = $that->model_extension_payment_twispay->createInvoiceNo($decrypted['externalOrderId'], $order['invoice_prefix']);
                    } else {
                        $invoice = $that->model_extension_payment_twispay->getInvoiceNo($order['invoice_no'], $order['invoice_prefix']);
                    }
                    $decrypted['invoice'] = $invoice;

                    /* Mark order as Processing. */
                    $that->model_checkout_order->addOrderHistory($order_id, 2/*Processing*/, $that->language->get('a_order_paid_notice').$decrypted['transactionId'], TRUE);
                    $that->model_extension_payment_twispay_transaction->insertTransaction($decrypted);
                    Twispay_Logger::Twispay_log($that->language->get('log_ok_status_complete') . $order_id);

                    /* Redirect to Twispay "Thank you Page" if it is set, if not, redirect to default "Thank you Page" */
                    if ($that->config->get('twispay_redirect_page') != NULL && strlen($that->config->get('twispay_redirect_page'))) {
                        Twispay_Thankyou::custom_page($that->config->get('twispay_redirect_page'));
                    } else {
                        Twispay_Thankyou::default_page();
                    }
                break;

                default:
                    Twispay_Logger::Twispay_log($that->language->get('log_error_wrong_status') . $decrypted['status']);
                    Twispay_Notification::notice_to_checkout($that);
                break;
            }

            //In case the order is a subscription, update it
            if ($order_recurring) {
                Twispay_Status_Updater::updateSubscription($order_recurring, $decrypted, $that);
            }
        }

        /**
         * Update the status of an subscription according to the received server status.
         *
         * @param string order_id: The ID of the order to be updated.
         * @param object decrypted: Decrypted order message.
         * @param object that: Controller instance use for accessing runtime values like configuration, active language, etc.
         *
         * @return void
         */
        public static function updateStatus_IPN($order_id, $decrypted, $that)
        {
            #load dependencies
            $that->language->load('extension/payment/twispay');
            $that->load->model('checkout/order');
            $that->load->model('extension/payment/twispay');
            $that->load->model('extension/payment/twispay_recurring');
            $that->load->model('extension/payment/twispay_transaction');
            $that->load->helper('Twispay_Logger');
            $that->load->helper('Twispay_Status_Updater');

            /* Extract the order. */
            $order = $that->model_checkout_order->getOrder($order_id);
            $order_recurring = $that->model_extension_payment_twispay_recurring->getRecurringByOrderId($order_id);

            switch ($decrypted['status']) {
                /** no case for UNCERTAIN status */
                case Twispay_Status_Updater::$RESULT_STATUSES['EXPIRING']:
                case Twispay_Status_Updater::$RESULT_STATUSES['CANCEL_OK']:
                    /* Mark order as canceled. */
                    $message = $that->language->get('a_order_canceled_notice');
                    if(isset($decrypted['transactionId'])){
                      $that->model_extension_payment_twispay_transaction->insertTransaction($decrypted);
                    }
                    $that->model_checkout_order->addOrderHistory($order_id, 7/*Canceled*/, $message, TRUE);
                    Twispay_Logger::Twispay_log($that->language->get('log_ok_status_canceled') . $order_id);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_FAIL']:
                    /* Mark order as Failed. */
                    $message = $that->language->get('a_order_failed_notice');
                    if(isset($decrypted['transactionId'])){
                      $that->model_extension_payment_twispay_transaction->insertTransaction($decrypted);
                    }
                    $that->model_checkout_order->addOrderHistory($order_id, 10/*Failed*/, $message, TRUE);
                    Twispay_Logger::Twispay_log($that->language->get('log_ok_status_failed') . $order_id);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['VOID_OK']:
                    /* Mark order as voided. */
                    $message = $that->language->get('a_order_void_notice');
                    if(isset($decrypted['transactionId'])){
                      $that->model_extension_payment_twispay_transaction->insertTransaction($decrypted);
                      $message = $message.$decrypted['transactionId'];
                    }
                    $that->model_checkout_order->addOrderHistory($order_id, 16/*Voided*/, $message, TRUE);
                    Twispay_Logger::Twispay_log($that->language->get('log_ok_status_voided') . $order_id);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['CHARGE_BACK']:
                    /* Mark order as refunded. */
                    $message = $that->language->get('a_order_chargedback_notice');
                    if(isset($decrypted['transactionId'])){
                      $that->model_extension_payment_twispay_transaction->insertTransaction($decrypted);
                      $message = $message.$decrypted['transactionId'];
                    }
                    $that->model_checkout_order->addOrderHistory($order_id, 13/*Chargeback*/, $message, TRUE);

                    Twispay_Logger::Twispay_log($that->language->get('log_ok_status_charged_back') . $order_id);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['REFUND_OK']:
                    /* Mark order as refunded. */
                    $message = $that->language->get('a_order_refunded_requested_notice');
                    if(isset($decrypted['transactionId'])){
                      /* Add transaction or update it if already exists */
                      $that->model_extension_payment_twispay_transaction->insertTransaction($decrypted);
                      $message = $that->language->get('a_order_refunded_notice').$decrypted['transactionId'];
                    }
                    if($order_recurring){
                      $that->model_checkout_order->addOrderHistory($order_id, $order['order_status_id']/*Current status*/, $message, TRUE);
                    }else{
                      $that->model_checkout_order->addOrderHistory($order_id, 11/*Refunded*/, $message, TRUE);
                    }
                    Twispay_Logger::Twispay_log($that->language->get('log_ok_status_refund') . $order_id);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['THREE_D_PENDING']:
                    /* Mark order as on-hold. */
                    $message = $that->language->get('a_order_hold_notice');
                    if(isset($decrypted['transactionId'])){
                      $that->model_extension_payment_twispay_transaction->insertTransaction($decrypted);
                    }
                    $that->model_checkout_order->addOrderHistory($order_id, 1/*`Pending`*/, $that->language->get('a_order_hold_notice'), TRUE);
                    Twispay_Logger::Twispay_log($that->language->get('log_ok_status_hold') . $order_id);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['IN_PROGRESS']:
                case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_OK']:
                    $message = $that->language->get('a_order_paid_notice');
                    if(isset($decrypted['transactionId'])){
                      $message = $message.$decrypted['transactionId'];
                      /* If there is no invoice created*/
                      if (!$order['invoice_no']) {
                          /* Create invoice */
                          $invoice = $that->model_extension_payment_twispay->createInvoiceNo($decrypted['externalOrderId'], $order['invoice_prefix']);
                      } else {
                          $invoice = $that->model_extension_payment_twispay->getInvoiceNo($order['invoice_no'], $order['invoice_prefix']);
                      }
                      $decrypted['invoice'] = $invoice;
                      /* Add transaction */
                      $that->model_extension_payment_twispay_transaction->insertTransaction($decrypted);
                    }
                    /* Mark order as completed. */
                    $that->model_checkout_order->addOrderHistory($order_id, 2/*Processing*/, $message, TRUE);
                    Twispay_Logger::Twispay_log($that->language->get('log_ok_status_complete') . $order_id);
                break;

                default:
                  Twispay_Logger::Twispay_log($that->language->get('log_error_wrong_status') . $decrypted['status']);
                break;
            }

            //In case the order is a subscription, update it
            if ($order_recurring) {
                Twispay_Status_Updater::updateSubscription($order_recurring, $decrypted, $that);
            }
        }

        /**
         * Update the status of an subscription according to the received server status.
         *
         * @param object order_recurring: The recurring order object.
         * @param object decrypted: Decrypted order message.
         * @param object that: Controller instance use for accessing runtime values like configuration, active language, etc.
         *
         * @return void
         */
        private static function updateSubscription($order_recurring, $decrypted, $that)
        {
            /** load dependencies */
            $that->load->model('extension/payment/twispay_recurring');

            $order_id = $decrypted['externalOrderId'];
            if(isset($decrypted['orderId'])){
                $tw_order_id = $decrypted['orderId'];
            }
            $order_recurring_id = $order_recurring['order_recurring_id'];

            //link twispay order with opencart order
            if (!$order_recurring['reference']) {
                $that->load->model('checkout/recurring');
                if(isset($tw_order_id)){
                  $resp = $that->model_checkout_recurring->editReference($order_recurring_id, 'tw_'.$tw_order_id);
                }
            }

            //transaction header
            $transaction_data = [ 'order_recurring_id' => (int)$order_recurring_id
                                , 'date_added' => "NOW()"
                                , 'amount' => isset($decrypted['amount'])?(float)$decrypted['amount']:0
                                , 'type' => NULL
                                , 'reference' => isset($decrypted['transactionId'])?'tw_'.$decrypted['transactionId']:0 /** tw_@transaction_id */ ];

            switch ($decrypted['status']) {
              // no case for UNCERTAIN status
              case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_FAIL']:
                $that->model_extension_payment_twispay_recurring->editOrderRecurringStatus($order_recurring_id, 2);//inactive
                if($transaction_data['reference']){
                  $transaction_data['type'] = 4;//payment_failed
                  $that->model_extension_payment_twispay_recurring->addRecurringTransaction($transaction_data);
                }
              break;

              case Twispay_Status_Updater::$RESULT_STATUSES['CANCEL_OK']:
                $that->model_extension_payment_twispay_recurring->editOrderRecurringStatus($order_recurring_id, 3);//cancelled
                if($transaction_data['reference']){
                  $transaction_data['type'] = 5;//cancelled
                  $that->model_extension_payment_twispay_recurring->addRecurringTransaction($transaction_data);
                }
              break;

              case Twispay_Status_Updater::$RESULT_STATUSES['REFUND_OK']:
                // $that->model_extension_payment_twispay_recurring->editOrderRecurringStatus($order_recurring_id, 4);//suspended
                if($transaction_data['reference']){
                  $transaction_data['type'] = 6;//suspended
                  $that->model_extension_payment_twispay_recurring->addRecurringTransaction($transaction_data);
                }
              break;

              case Twispay_Status_Updater::$RESULT_STATUSES['VOID_OK']:
                $that->model_extension_payment_twispay_recurring->editOrderRecurringStatus($order_recurring_id, 4);//suspended
                //no transaction
              break;

              case Twispay_Status_Updater::$RESULT_STATUSES['CHARGE_BACK']:
                $that->model_extension_payment_twispay_recurring->editOrderRecurringStatus($order_recurring_id, 4);//suspended
                if($transaction_data['reference']){
                  $transaction_data['type'] = 6;//suspended
                  $that->model_extension_payment_twispay_recurring->addRecurringTransaction($transaction_data);
                }
              break;

              case Twispay_Status_Updater::$RESULT_STATUSES['THREE_D_PENDING']:
                $that->model_extension_payment_twispay_recurring->editOrderRecurringStatus($order_recurring_id, 6);//pending
                if($transaction_data['reference']){
                  $transaction_data['type'] = 4;//payment_failed
                  $that->model_extension_payment_twispay_recurring->addRecurringTransaction($transaction_data);
                }
              break;

              case Twispay_Status_Updater::$RESULT_STATUSES['EXPIRING']:
                $that->model_extension_payment_twispay_recurring->editOrderRecurringStatus($order_recurring_id, 5);//expired
                if($transaction_data['reference']){
                  $transaction_data['type'] = 9;//transaction_expired
                  $that->model_extension_payment_twispay_recurring->addRecurringTransaction($transaction_data);
                }
              break;

              case Twispay_Status_Updater::$RESULT_STATUSES['IN_PROGRESS']:
              case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_OK']:
                  $that->model_extension_payment_twispay_recurring->editOrderRecurringStatus($order_recurring_id, 1);//active
                  if($transaction_data['reference']){
                    $transaction_data['type'] = 1;//payment_ok
                    $that->model_extension_payment_twispay_recurring->addRecurringTransaction($transaction_data);
                    if ($that->model_extension_payment_twispay_recurring->isLastRecurringTransaction($order_recurring)) {
                        $that->model_extension_payment_twispay_recurring->cancelRecurring($tw_order_id, $order_id, 'Automatic');
                    }
                  }
              break;
           }
        }
    }
endif; /* End if class_exists. */
