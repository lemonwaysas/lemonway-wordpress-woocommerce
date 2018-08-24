<?php
/*
 Plugin Name: WooCommerce LemonWay Payment Gateway
 Plugin URI: https://www.lemonway.com
 Description: Secured payment solutions for Internet E-commerce. BackOffice management. Compliance. Regulatory reporting.
 Version: 1.1.0
 Author: LemonWay <it@lemonway.com>
 Author URI: https://www.lemonway.com
 License: GPL3
 */

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

final class Lemonway
{
    /**
     * @var Lemonway The single instance of the class
     */
    protected static $_instance = null;
    
    protected $name = "Secured payment solutions for Internet E-commerce. BackOffice management. Compliance. Regulatory reporting.";
    protected $slug = 'woocommerce-gateway-lemonway';
    
    /**
     * Pointer to gateway making the request.
     * @var WC_Gateway_Lemonway
     */
    protected $gateway;
    
    const DB_VERSION = '1.0.0';
     
     
    /**
     * Constructor
     */
    public function __construct()
    {
        // Define constants
        $this->define_constants();
         
        // Check plugin requirements
        $this->check_requirements();
         
        register_activation_hook(__FILE__, array($this,'lw_install'));
         
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'plugin_action_links' ));
        add_action('plugins_loaded', array( $this, 'init_gateway' ), 0);
        add_filter('woocommerce_payment_gateways', array( $this, 'add_gateway' ));
         
        //Add menu elements
        add_action('admin_menu', array($this, 'add_admin_menu'), 57);

        $this->load_plugin_textdomain();
    }
     
    /**
     * Add menu LemonWay
     */
    public function add_admin_menu()
    {
        add_menu_page(__('LemonWay', LEMONWAY_TEXT_DOMAIN), __('LemonWay', LEMONWAY_TEXT_DOMAIN), 'manage_product_terms', $this->slug . 'configuration', array($this, 'redirect_configuration'), plugins_url('woocommerce-gateway-lemonway/assets/img/icon.png'), null);
    }
     
    /**
     * Init Gateway
     */
    public function init_gateway()
    {
        if (! class_exists('WC_Payment_Gateway')) {
            return;
        }
     
        // Includes
        include_once('includes/class-wc-gateway-lemonway.php');
        $this->gateway = new WC_Gateway_Lemonway();
    }
     
    /**
     * Load Localisation files.
     *
     * Note: the first-loaded translation file overrides any following ones if
     * the same translation is present.
     *
     * Locales found in:
     *      - WP_LANG_DIR/woocommerce-gateway-lemonway/woocommerce-gateway-lemonway-LOCALE.mo
     *      - WP_LANG_DIR/plugins/woocommerce-gateway-lemonway-LOCALE.mo
     */
    public function load_plugin_textdomain()
    {
        $locale = apply_filters('plugin_locale', get_locale(), LEMONWAY_TEXT_DOMAIN);
        $dir    = trailingslashit(WP_LANG_DIR);
     
        load_textdomain(LEMONWAY_TEXT_DOMAIN, $dir . 'woocommerce-gateway-lemonway/woocommerce-gateway-lemonway-' . $locale . '.mo');
        load_plugin_textdomain(LEMONWAY_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
     
    /**
     * Add the gateway to methods
     */
    public function add_gateway($methods)
    {
        $methods[] = 'WC_Gateway_Lemonway';
        return $methods;
    }
     
    public function redirect_configuration()
    {
        wp_redirect(admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_lemonway'));
    }
     
    /**
     * Add relevant links to plugins page
     * @param  array $links
     * @return array
     */
    public function plugin_action_links($links)
    {
        $plugin_links = array(
                 '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_lemonway') . '">' . __('Settings', LEMONWAY_TEXT_DOMAIN) . '</a>',
         );
        return array_merge($plugin_links, $links);
    }
     
    /**
     * Main Lemonway Instance
     *
     * Ensures only one instance of Lemonway is loaded or can be loaded.
     *
     * @static
     * @see LW()
     * @return Lemonway - Main instance
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
     
    /**
     * Define Constants
     *
     * @access private
     */
    private function define_constants()
    {
        $woo_version_installed = get_option('woocommerce_version');
        define('LEMONWAY_WOOVERSION', $woo_version_installed);
        define('LEMONWAY_NAME', $this->name);
        define('LEMONWAY_TEXT_DOMAIN', $this->slug);
    }
     
     
    /**
     * Checks that the WordPress setup meets the plugin requirements.
     *
     * @access private
     * @global string $wp_version
     * @return boolean
     */
    private function check_requirements()
    {
        //global $wp_version, $woocommerce;
     
        require_once(ABSPATH.'/wp-admin/includes/plugin.php');
     
        //@TODO version compare
     
        if (function_exists('is_plugin_active')) {
            if (!is_plugin_active('woocommerce/woocommerce.php')) {
                add_action('admin_notices', array( &$this, 'alert_woo_not_active' ));
                return false;
            }
        }
     
        return true;
    }
     

    /**
     * Display the WooCommerce requirement notice.
     *
     * @access static
     */
    public static function alert_woo_not_active()
    {
        echo '<div id="message" class="error"><p>';
        echo sprintf(__('Sorry, <strong>%s</strong> requires WooCommerce to be installed and activated first. Please <a href="%s">install WooCommerce</a> first.', LEMONWAY_TEXT_DOMAIN), LEMONWAY_NAME, admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce'));
        echo '</p></div>';
    }
     
    /**
     * Setup SQL
     */
      
    public function lw_install()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
     
        $sql = array();

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'.$wpdb->prefix.'lemonway_wktoken` (
					    `id_cart_wktoken` int(11) NOT NULL AUTO_INCREMENT,
						`id_cart` int(11) NOT NULL,
						`wktoken` varchar(190) NOT NULL,
					    PRIMARY KEY  (`id_cart_wktoken`),
		   				UNIQUE KEY `wktoken` (`wktoken`),
		   				UNIQUE KEY `id_cart` (`id_cart`)
					) ENGINE=InnoDB '.$charset_collate.';';
     
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
     
        foreach ($sql as $q) {
            dbDelta($q);
        }
     
        add_option('lw_db_version', self::DB_VERSION);
    }
}

function LW()
{
    return Lemonway::instance();
}
LW();
