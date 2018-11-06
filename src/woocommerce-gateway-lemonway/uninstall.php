<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// if uninstall not called from WordPress exit
defined('WP_UNINSTALL_PLUGIN') || exit;

/*
 * Only remove ALL product and page data if WC_REMOVE_ALL_DATA constant is set to true in user's
 * wp-config.php. This is to prevent data loss when deleting the plugin from the backend
 * and to ensure only the site owner can perform this action.
 */
if (defined('WC_REMOVE_ALL_DATA') && true === WC_REMOVE_ALL_DATA) {
    // Delete options.
    delete_option('lemonway_version');
    delete_option('woocommerce_lemonway_settings');

    // Clear any cached data that has been removed.
    wp_cache_flush();
}
