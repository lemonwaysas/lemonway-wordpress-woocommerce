<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Providesstatic methods as helpers
 *
 */
class WC_LemonWay_Helper
{
    /**
     * Checks if WC version is less than passed in version.
     *
     * @param string $version Version to check against.
     * @return bool
     */
    public static function is_wc_lt($version)
    {
        return version_compare(WC_VERSION, $version, '<');
    }
}
