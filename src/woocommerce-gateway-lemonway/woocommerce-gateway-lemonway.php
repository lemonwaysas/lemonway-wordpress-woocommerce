<?php
/**
 * Plugin Name: WooCommerce LemonWay Gateway
 * Plugin URI: https://www.lemonway.com/ecommerce/
 * Description: Secured payment solutions for Internet E-commerce. BackOffice management. Compliance. Regulatory reporting.
 * Version: 2.0.0
 * Author: LemonWay <it@lemonway.com>
 * Author URI: https://www.lemonway.com/
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: woocommerce-gateway-lemonway
 * Domain Path: /languages
 * Requires at least: 4.4
 * Tested up to: 4.9
 * WC requires at least: 2.6
 * WC tested up to: 3.5
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('LEMONWAY_TEXT_DOMAIN', 'woocommerce-gateway-lemonway');
define('LEMONWAY_MAIN_FILE', __FILE__);

//Add menu elements
add_action('admin_menu', 'add_admin_menu');
add_action('plugins_loaded', 'init_lemonway_gateway_class');

function init_lemonway_gateway_class()
{
    load_plugin_textdomain(LEMONWAY_TEXT_DOMAIN, false, plugin_basename(dirname(LEMONWAY_MAIN_FILE)) . '/languages');

    if (! class_exists('WooCommerce')) {
        add_action('admin_notices', 'woocommerce_lemonway_missing_wc_notice');
        return false;
    }

    if (! class_exists('LemonWay')) {
        /**
         * Constants
         */
        define('LEMONWAY_VERSION', '2.0.0');
        // define( 'LEMONWAY_MIN_PHP_VERSION', '5.6.0' );
        // define( 'LEMONWAY_MIN_WC_VERSION', '2.6.0' );
        //define( 'LEMONWAY_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( LEMONWAY_MAIN_FILE ) ), basename( LEMONWAY_MAIN_FILE ) ) ) );
        // define( 'LEMONWAY_PLUGIN_PATH', untrailingslashit( plugin_dir_path( LEMONWAY_MAIN_FILE ) ) );

        class LemonWay
        {
            /**
             * @var Singleton The reference the Singleton instance of this class LemonWay
             */
            private static $instance;

            /**
             * Protected constructor to prevent creating a new instance of the Singleton via the 'new' operator from outside of this class
             */
            private function __construct()
            {
                add_action('admin_init', array( $this, 'install' ));
                
                require_once dirname(LEMONWAY_MAIN_FILE) . '/includes/class-wc-lemonway-exception.php';
                require_once dirname(LEMONWAY_MAIN_FILE) . '/includes/class-wc-lemonway-logger.php';
                require_once dirname(LEMONWAY_MAIN_FILE) . '/includes/class-wc-lemonway-helper.php';
                require_once dirname(LEMONWAY_MAIN_FILE) . '/includes/class-wc-lemonway-api.php';

                // Payment gateways
                require_once dirname(LEMONWAY_MAIN_FILE) . '/includes/abstracts/abstract-wc-lemonway-payment-gateway.php';
                require_once dirname(LEMONWAY_MAIN_FILE) . '/includes/class-wc-gateway-lemonway.php';
                // @TODO: Sofort
                // @TODO: iDeal

                // @TODO: admin notices

                add_filter('woocommerce_payment_gateways', array( $this, 'add_lemonway_gateway_class' ));
                add_filter('plugin_action_links_' . plugin_basename(LEMONWAY_MAIN_FILE), array( $this, 'plugin_action_links' ));
                add_filter('plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2);
            }

            /**
             * Ensure only one instance of LemonWay is loaded or can be loaded
             * Return the Singleton instance of this class LemonWay
             *
             * @return Singleton The Singleton instance
             */
            public static function get_instance()
            {
                if (is_null(self::$instance)) {
                    self::$instance = new self();
                }
                
                return self::$instance;
            }

            /**
             * Handle updates.
             *
             */
            public function install()
            {
                if (! is_plugin_active(plugin_basename(LEMONWAY_MAIN_FILE))) {
                    return;
                }

                if (LEMONWAY_VERSION !== get_option('lemonway_version')) {
                    update_option('lemonway_version', LEMONWAY_VERSION);
                }
            }

            /**
             * Add LemonWay gateways to WooCommerce
             *
             */
            public function add_lemonway_gateway_class($methods)
            {
                $methods[] = 'WC_Gateway_LemonWay';
                // @TODO: Sofort
                // @TODO: iDeal
                
                return $methods;
            }

            /**
             * Adds plugin action links
             *
             * @param mixed $links Plugin action links
             */
            public function plugin_action_links($links)
            {
                $action_links = array(
                    'settings' => '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=lemonway') . '" aria-label="' . esc_attr__('View LemonWay settings', LEMONWAY_TEXT_DOMAIN) . '">' . esc_html__('Settings', LEMONWAY_TEXT_DOMAIN) . '</a>',
                );

                return array_merge($action_links, $links);
            }

            /**
             * Show row meta on the plugin screen
             *
             * @param mixed $links Plugin row meta
             * @param mixed $file  Plugin base file
             * @return array
             */
            public static function plugin_row_meta($links, $file)
            {
                if (plugin_basename(LEMONWAY_MAIN_FILE) === $file) {
                    $row_meta = array(
                        'docs' => '<a href="' . esc_url(__('https://lemonway.zendesk.com/hc/en-gb/categories/201471749-WooCommerce', LEMONWAY_TEXT_DOMAIN)) . '" aria-label="' . esc_attr__('View LemonWay documentation', LEMONWAY_TEXT_DOMAIN) . '">' . esc_html__('Docs', LEMONWAY_TEXT_DOMAIN) . '</a>',
                        'apidocs' => '<a href="' . esc_url(__('http://documentation.lemonway.fr/ecommerce-en/', LEMONWAY_TEXT_DOMAIN)) . '" aria-label="' . esc_attr__('View LemonWay API docs', LEMONWAY_TEXT_DOMAIN) . '">' . esc_html__('API docs', LEMONWAY_TEXT_DOMAIN) . '</a>',
                        'support' => '<a href="' . esc_url('https://support.lemonway.com/') . '" aria-label="' . esc_attr__('Visit LemonWay customer support', LEMONWAY_TEXT_DOMAIN) . '">' . esc_html__('Support', LEMONWAY_TEXT_DOMAIN) . '</a>',
                        'signup' => '<a href="' . esc_url('https://www.lemonway.com/ecommerce/') . '" aria-label="' . esc_attr__('Sign up', LEMONWAY_TEXT_DOMAIN) . '">' . esc_html__('Sign up', LEMONWAY_TEXT_DOMAIN) . '</a>',
                        'signin' => '<a href="' . esc_url('https://ecommerce.lemonway.com') . '" aria-label="' . esc_attr__('Sign in', LEMONWAY_TEXT_DOMAIN) . '">' . esc_html__('Sign in', LEMONWAY_TEXT_DOMAIN) . '</a>'
                    );

                    return array_merge($links, $row_meta);
                }

                return (array) $links;
            }
        }

        LemonWay::get_instance();
    }
}

/**
 * Display WooCommerce requirement notice
 *
 */
function woocommerce_lemonway_missing_wc_notice()
{
    echo '<div class="error"><p><strong>' . sprintf(__('LemonWay requires <a href="%s" target="_blank">WooCommerce to be installed and active</a>.', LEMONWAY_TEXT_DOMAIN), admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce')) . '</strong></p></div>';
}

/**
 * Add menu LemonWay
 */
function add_admin_menu()
{
    add_menu_page('LemonWay', 'LemonWay', 'manage_product_terms', 'lemonway', 'redirect_configuration_page', plugins_url('assets/images/icon.png', LEMONWAY_MAIN_FILE), null);
}

function redirect_configuration_page()
{
    wp_safe_redirect(admin_url('admin.php?page=wc-settings&tab=checkout&section=lemonway'));
    exit;
}
