<?php
/**
 * Twispay Helpers
 *
 * Logs messages and transactions.
 *
 * @author   Twistpay
 * @version  1.0.2
 */

/* Security class check */
if (! class_exists('Twispay_Logger')) :
    /**
     * Class that implements methods to log
     * messages and transactions.
     */
    class Twispay_Logger
    {
        /**
         * Function that logs a message to the transaction log file.
         *
         * @param string - Message to log to file.
         *
         * @return void
         */
        public static function Twispay_log($message = FALSE)
        {
            $log_file = DIR_LOGS.'/twispay_logs/transactions.log';
            /* Build the log message. */
            $message = (!$message) ? (PHP_EOL . PHP_EOL) : ("[" . date('Y-m-d H:i:s') . "] " . $message);
            /* Try to append log to file and silence and PHP errors may occur. */
            @file_put_contents($log_file, $message . PHP_EOL, FILE_APPEND);
        }

        /**
         * Function that logs a message to the requests log file.
         *
         * @param string - Message to log to file.
         *
         * @return void
         */
        public static function Twispay_api_log($message = FALSE)
        {
            $log_file = DIR_LOGS.'/twispay_logs/requests.log';
            /* Build the log message. */
            $message = (!$message) ? (PHP_EOL . PHP_EOL) : ("[" . date('Y-m-d H:i:s') . "] " . $message);
            /* Try to append log to file and silence and PHP errors may occur. */
            @file_put_contents($log_file, $message . PHP_EOL, FILE_APPEND);
        }
    }
endif; /* End if class_exists. */
