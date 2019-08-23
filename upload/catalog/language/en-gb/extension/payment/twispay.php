<?php
/**
 * Twispay Language Configurator
 *
 * Twispay general language handler for front-store
 *
 * @author   Twistpay
 * @version  1.0.0
 */

/* General */
$_['text_title']                                = 'Credit card secure payment| Twispay';
$_['button_confirm']                            = 'Pay now';
$_['button_processing']                         = 'Processing ...';
$_['button_retry']                              = 'Try again';
$_['comunication_error']                        = 'Comunications Error: ';
$_['validation_error']                          = 'Validation error';
$_['processing']                                = 'Processing ...';
$_['error_permission']                          = 'Permission denied';
$_['no_post']                                   = '[RESPONSE-ERROR]: no_post';

$_['general_error_title']                       = 'An error occurred:';
$_['general_error_desc_f']                      = 'The payment could not be processed. Please';
$_['general_error_desc_try_again']              = ' try again';
$_['general_error_desc_or']                     = ' or';
$_['general_error_desc_contact']                = ' contact';
$_['general_error_desc_s']                      = ' the website administrator.';
$_['general_error_hold_notice']                 = ' Payment is on hold.';

/* Checkout validation */
$_['checkout_error_too_many_prods']             = 'In case of recurring products, the order must contain only one subscription at a time.';
$_['checkout_notice_free_trial']                = 'Free trial is not suported by payment processor.';
$_['checkout_notice_cycles_number']             = 'Trial period can only have one cycle and therefore only one payment. If multiple trial cycles are configured, the periods and payments will be summed up and only one payment will be performed.';

/* Order Notice */
$_['a_order_failed_notice']                     = 'Twispay payment failed';
$_['a_order_hold_notice']                       = 'Twispay payment is on hold';
$_['a_order_void_notice']                       = 'Twispay payment was voided #';
$_['a_order_chargedback_notice']                = 'Twispay payment was charged_back #';
$_['a_order_refunded_notice']                   = 'Twispay payment was refunded #';
$_['a_order_refunded_requested_notice']         = 'Twispay refund requested';
$_['a_order_paid_notice']                       = 'Paid Twispay #';
$_['a_order_canceled_notice']                   = 'Twispay payment was canceled';


/* LOG insertor */
$_['log_refund_response']                       = '[RESPONSE]: Refund operation data: ';
$_['log_cancel_response']                       = '[RESPONSE]: Cancel operation data: ';
$_['log_sync_response']                         = '[RESPONSE]: Sync operation data: ';

$_['log_ok_response_data']                      = '[RESPONSE]: Data: ';
$_['log_ok_string_decrypted']                   = '[RESPONSE]: decrypted string: ';
$_['log_ok_status_complete']                    = '[RESPONSE]: Status complete-ok for order ID: ';
$_['log_ok_status_refund']                      = '[RESPONSE]: Status refund-ok for order ID: ';
$_['log_ok_status_failed']                      = '[RESPONSE]: Status failed for order ID: ';
$_['log_ok_status_voided']                      = '[RESPONSE]: Status voided for order ID: ';
$_['log_ok_status_canceled']                    = '[RESPONSE]: Status canceled for order ID: ';
$_['log_ok_status_charged_back']                = '[RESPONSE]: Status charged back for order ID: ';
$_['log_ok_status_hold']                        = '[RESPONSE]: Status on-hold for order ID: ';
$_['log_ok_validating_complete']                = '[RESPONSE]: Validating completed for order ID: ';

$_['log_error_validating_failed']               = '[RESPONSE-ERROR]: Validation failed.';
$_['log_error_decryption_error']                = '[RESPONSE-ERROR]: Decryption failed.';
$_['log_error_invalid_order']                   = '[RESPONSE-ERROR]: Order does not exist.';
$_['log_error_wrong_status']                    = '[RESPONSE-ERROR]: Wrong status: ';
$_['log_error_empty_status']                    = '[RESPONSE-ERROR]: Empty status.';
$_['log_error_empty_identifier']                = '[RESPONSE-ERROR]: Empty identifier.';
$_['log_error_empty_external']                  = '[RESPONSE-ERROR]: Empty externalOrderId.';
$_['log_error_empty_transaction']               = '[RESPONSE-ERROR]: Empty transactionId.';
$_['log_error_empty_response']                  = '[RESPONSE-ERROR]: Received empty response.';
$_['log_error_invalid_private']                 = '[RESPONSE-ERROR]: Private key is not valid.';
$_['log_error_transaction_exist']               = '[RESPONSE-ERROR]: Transaction cannot be overwritten #';

$_['subscriptions_log_ok_set_status']           = '[RESPONSE]: Server status set for order ID: ';
$_['subscriptions_log_error_set_status']        = '[RESPONSE-ERROR]: Failed to set server status for order ID: ';
$_['subscriptions_log_error_get_status']        = '[RESPONSE-ERROR]: Failed to get server status for order ID: ';
$_['subscriptions_log_error_call_failed']       = '[RESPONSE-ERROR]: Failed to call server: ';
$_['subscriptions_log_error_http_code']         = '[RESPONSE-ERROR]: Unexpected HTTP response code: ';
$_['subscriptions_log_error_order_not_found']   = '[RESPONSE-ERROR]: Not found by twispay server for order ID: ';
$_['subscriptions_log_error_no_orders_found']   = '[RESPONSE-ERROR]: No orders found.';
