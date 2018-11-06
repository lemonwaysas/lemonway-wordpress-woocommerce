<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logger
 *
 */
class WC_LemonWay_Logger
{
    public static $logger;
    const WC_LOG_FILENAME = 'woocommerce-gateway-lemonway';

    /**
     * Logging method.
     * @param string $message
     */
    public static function log($message)
    {
        if (empty(self::$logger)) {
            if (WC_LemonWay_Helper::is_wc_lt('3.0')) {
                self::$logger = new WC_Logger();
            } else {
                self::$logger = wc_get_logger();
            }
        }

        $log_entry = ' - LemonWay v' . LEMONWAY_VERSION . "\n";
        $log_entry .= $message . "\n";

        if (WC_LemonWay_Helper::is_wc_lt('3.0')) {
            self::$logger->add(self::WC_LOG_FILENAME, $log_entry);
        } else {
            self::$logger->debug($log_entry, array( 'source' => self::WC_LOG_FILENAME ));
        }
    }
}
