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
$_['text_title']                                = 'Plătește în siguranță cu cardul | Twispay';
$_['button_confirm']                            = 'Plătește';
$_['button_processing']                         = 'Se procesează ...';
$_['button_retry']                              = 'Încearcă din nou';
$_['comunication_error']                        = 'A intervenit o eroare de comunicare: ';
$_['validation_error']                          = 'A intervenit o eroare de validare';
$_['processing']                                = 'Se procesează ...';
$_['error_permission']                          = 'Acces refuzat';
$_['no_post']                                   = '[RESPONSE-ERROR]: lipsă POST';

$_['general_error_title']                       = 'S-a petrecut o eroare:';
$_['general_error_desc_f']                      = 'Plata nu a putut fi procesată. Te rog reincearcă.';
$_['general_error_desc_try_again']              = ' încearcă din nou';
$_['general_error_desc_or']                     = ' sau';
$_['general_error_desc_contact']                = ' contactează';
$_['general_error_desc_s']                      = ' administratorul site-ului.';
$_['general_error_hold_notice']                 = ' Plata este in așteptare.';

/* Checkout validation */
$_['checkout_error_too_many_prods']             = 'în cazul produselor recurente, comanda trebuie să conțină doar un singur abonament.';
$_['checkout_notice_free_trial']                = 'Perioada de încercare gratuită nu este suportată.';
$_['checkout_notice_cycles_number']             = 'Periada de de încercare poate avea un singur ciclu și prin urmare o singură plată. În cazul în care numărul de cicluri este mai mare de unu, perioada și valoarea de încercare vor fi însumate și procesate într-o singură plată.';

/* Order Notice */
$_['a_order_failed_notice']                     = 'Plata Twispay a fost finalizată cu eroare';
$_['a_order_hold_notice']                       = 'Plata Twispay este în așteptare';
$_['a_order_void_notice']                       = 'Plata Twispay a fost anulată #';
$_['a_order_chargedback_notice']                = 'Plata Twispay a fost returnată #';
$_['a_order_refunded_notice']                   = 'Plata Twispay a fost returnată #';
$_['a_order_refunded_requested_notice']         = 'Cerere de returnare inițializată';
$_['a_order_paid_notice']                       = 'Platit Twispay #';
$_['a_order_canceled_notice']                   = 'Comanda twispay a fost anulată';

/* LOG insertor */
$_['log_refund_response']                       = '[RESPONSE]: Rezultatul operațiunii de Refund: ';
$_['log_cancel_response']                       = '[RESPONSE]: Rezultatul operațiunii de Cancel: ';
$_['log_sync_response']                         = '[RESPONSE]: Rezultatul operațiunii de Sincronizare: ';

$_['log_ok_response_data']                      = '[RESPONSE]: Data: ';
$_['log_ok_string_decrypted']                   = '[RESPONSE]: string decriptat: ';
$_['log_ok_status_complete']                    = '[RESPONSE]: Status complet-ok';
$_['log_ok_status_refund']                      = '[RESPONSE]: Status refund-ok pentru comanda cu ID-ul: ';
$_['log_ok_status_failed']                      = '[RESPONSE]: Status failed pentru comanda cu ID-ul: ';
$_['log_ok_status_voided']                      = '[RESPONSE]: Status voided pentru comanda cu ID-ul: ';
$_['log_ok_status_cenceled']                    = '[RESPONSE]: Status canceled pentru comanda cu ID-ul: ';
$_['log_ok_status_charged_back']                = '[RESPONSE]: Status charged back pentru comanda cu ID-ul: ';
$_['log_ok_status_hold']                        = '[RESPONSE]: Status on-hold pentru comanda cu ID-ul: ';
$_['log_ok_validating_complete']                = '[RESPONSE]: Validare cu succes pentru comanda cu ID-ul: %s';

$_['log_error_validating_failed']               = '[RESPONSE-ERROR]: Validare esuată pentru comanda cu ID-ul: ';
$_['log_error_decryption_error']                = '[RESPONSE-ERROR]: Decriptarea nu a funcționat.';
$_['log_error_invalid_order']                   = '[RESPONSE-ERROR]: Comanda nu există.';
$_['log_error_wrong_status']                    = '[RESPONSE-ERROR]: Status greșit: ';
$_['log_error_empty_status']                    = '[RESPONSE-ERROR]: Status nul.';
$_['log_error_empty_identifier']                = '[RESPONSE-ERROR]: Identificator nul.';
$_['log_error_empty_external']                  = '[RESPONSE-ERROR]: ExternalOrderId gol.';
$_['log_error_empty_transaction']               = '[RESPONSE-ERROR]: TransactionID nul.';
$_['log_error_empty_response']                  = '[RESPONSE-ERROR]: Răspunsul primit este nul.';
$_['log_error_invalid_private']                 = '[RESPONSE-ERROR]: Cheie privată nevalidă.';
$_['log_error_transaction_exist']               = '[RESPONSE-ERROR]: Tranzacția nu poate fi suprascrisă #';

$_['subscriptions_log_ok_set_status']           = '[RESPONSE]: Starea de pe server setată pentru comanda cu ID-ul: ';
$_['subscriptions_log_error_set_status']        = '[RESPONSE-ERROR]: Eroare la setarea stării pentru comanda cu ID-ul: ';
$_['subscriptions_log_error_get_status']        = '[RESPONSE-ERROR]: Eroare la extragerea stării de pe server pentru comanda cu ID-ul: ';
$_['subscriptions_log_error_call_failed']       = '[RESPONSE-ERROR]: Eroare la apelarea server-ului: ';
$_['subscriptions_log_error_http_code']         = '[RESPONSE-ERROR]: Cod HTTP neașteptat: ';
$_['subscriptions_log_error_order_not_found']   = '[RESPONSE-ERROR]: Nu a fost gasită nici o comandă cu ID-ul: ';
$_['subscriptions_log_error_no_orders_found']   = '[RESPONSE-ERROR]: 0 comenzi modificate.';
