<?php
/**
 * Twispay Helpers
 *
 * Decodes and validates notifications sent by the Twispay server.
 *
 * @author   Twistpay
 * @version  1.0.1
 */

/* Security class check */
if (! class_exists('Twispay_Response')) :
    /**
     * Class that implements methods to decrypt
     * Twispay server responses.
     */
    class Twispay_Response
    {
        /**
         * Decrypt the response from Twispay server.
         *
         * @param string $tw_encryptedMessage - The encripted server message.
         * @param string $tw_secretKey        - The secret key (from Twispay).
         *
         * @return Array([key => value,]) - If everything is ok array containing the decrypted data.
         *         bool(FALSE)            - If decription fails.
         */
        public static function Twispay_decrypt_message($tw_encryptedMessage, $tw_secretKey)
        {
            $encrypted = ( string )$tw_encryptedMessage;

            if (!strlen($encrypted) || (FALSE == strpos($encrypted, ','))) {
                return FALSE;
            }

            /* Get the IV and the encrypted data */
            $encryptedParts = explode(/*delimiter*/',', $encrypted, /*limit*/2);
            $iv = base64_decode($encryptedParts[0]);
            if (FALSE === $iv) {
                return FALSE;
            }

            $encryptedData = base64_decode($encryptedParts[1]);
            if (FALSE === $encryptedData) {
                return FALSE;
            }

            /* Decrypt the encrypted data */
            $decryptedResponse = openssl_decrypt($encryptedData, /*method*/'aes-256-cbc', $tw_secretKey, /*options*/OPENSSL_RAW_DATA, $iv);

            if (FALSE === $decryptedResponse) {
                return FALSE;
            }

            /* JSON decode the decrypted data. */
            return json_decode($decryptedResponse, /*assoc*/TRUE, /*depth*/4);
        }

        /**
         * Function that validates a decripted response.
         *
         * @param tw_response The server decripted and JSON decoded response
         * @param that Controller instance use for accessing runtime values like configuration, active language, etc.
         *
         * @return bool(FALSE)     - If any error occurs
         *         bool(TRUE)      - If the validation is successful
         */

        public static function Twispay_checkValidation($tw_response, $that)
        {
            $tw_errors = array();

            if (!$tw_response) {
                return FALSE;
            }

            if (empty($tw_response['status']) && empty($tw_response['transactionStatus'])) {
                $tw_errors[] = $that->language->get('log_error_empty_status');
            }

            if (empty($tw_response['identifier'])) {
                $tw_errors[] = $that->language->get('log_error_empty_identifier');
            }

            if (empty($tw_response['externalOrderId'])) {
                $tw_errors[] = $that->language->get('log_error_empty_external');
            }

            if (empty($tw_response['transactionId'])) {
                $tw_errors[] = $that->language->get('log_error_empty_transaction');
            }

            if (sizeof($tw_errors)) {
                foreach ($tw_errors as $err) {
                    Twispay_Logger::Twispay_log($err);
                }

                return FALSE;
            } else {
                $data = [ 'id_cart'          => explode('_', $tw_response['externalOrderId'])[0]
                        , 'status'           => (empty($tw_response['status'])) ? ($tw_response['transactionStatus']) : ($tw_response['status'])
                        , 'identifier'       => $tw_response['identifier']
                        , 'orderId'          => (int)$tw_response['orderId']
                        , 'transactionId'    => (int)$tw_response['transactionId']
                        , 'customerId'       => (int)$tw_response['customerId']
                        , 'cardId'           => (!empty($tw_response['cardId'])) ? (( int )$tw_response['cardId']) : (0)];

                Twispay_Logger::Twispay_log($that->language->get('log_ok_response_data') . json_encode($data));

                if (!in_array($data['status'], Twispay_Status_Updater::$RESULT_STATUSES)) {
                    Twispay_Logger::Twispay_log($that->language->get('log_error_wrong_status') . $data['status']);
                    return FALSE;
                }

                Twispay_Logger::Twispay_log($that->language->get('log_ok_validating_complete') . $data['id_cart']);
                return TRUE;
            }
        }
    }
endif; /* End if class_exists. */
