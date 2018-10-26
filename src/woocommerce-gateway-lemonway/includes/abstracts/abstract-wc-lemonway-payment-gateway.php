<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Abstract class that will be inherited by all LemonWay payment methods
 *
 * @extends WC_Payment_Gateway
 *
 */
abstract class WC_LemonWay_Payment_Gateway extends WC_Payment_Gateway
{
    const MAIN_GATEWAY_ID = 'lemonway';

    // LW4E
    const LW4E_DIRECTKIT_URL_PROD = 'https://ws.lemonway.fr/mb/lwecommerce/prod/lw4e_json/Service_json.asmx';
    const LW4E_DIRECTKIT_URL_TEST = 'https://sandbox-api.lemonway.fr/mb/lwecommerce/dev/lw4e_json/Service_json.asmx';
    const LW4E_WEBKIT_URL_PROD = 'https://webkit.lemonway.fr/mb/lwecommerce/prod';
    const LW4E_WEBKIT_URL_TEST = 'https://sandbox-webkit.lemonway.fr/lwecommerce/dev';

    // LW Entreprise
    const LEMONWAY_DIRECTKIT_FORMAT_URL_PROD = 'https://ws.lemonway.fr/mb/%s/prod/directkitjson2/service.asmx';
    const LEMONWAY_DIRECTKIT_FORMAT_URL_TEST = 'https://sandbox-api.lemonway.fr/mb/%s/dev/directkitjson2/service.asmx';
    const LEMONWAY_WEBKIT_FORMAT_URL_PROD = 'https://webkit.lemonway.fr/mb/%s/prod';
    const LEMONWAY_WEBKIT_FORMAT_URL_TEST = 'https://sandbox-webkit.lemonway.fr/%s/dev';

    /**
     * List of locales supported by LemonWay.
     *
     * @var array
     */
    private $supported_locales = array(
        'da' => 'da',
        'de' => 'ge',
        'en' => 'en',
        'es' => 'sp',
        'fi' => 'fi',
        'fr' => 'fr',
        'it' => 'it',
        'ko' => 'ko',
        'no' => 'no',
        'pt' => 'po',
        'sv' => 'sw'
    );

    /**
     * Validation warnings.
     *
     * @var array of strings
     */
    protected $warnings = array();

    /**
     * Directkit URL
     *
     * @var string
     */
    protected $directkit_url = self::LW4E_DIRECTKIT_URL_PROD;

    /**
     * Webkit URL
     *
     * @var string
     */
    protected $webkit_url = self::LW4E_WEBKIT_URL_PROD;

    /**
     * API username
     *
     * @var string
     */
    protected $wlLogin;

    /**
     * API password
     *
     * @var string
     */
    protected $wlPass;

    /**
     * Is test mode active?
     *
     * @var bool
     */
    protected $test_mode = false;

    /**
     * Name of LemonWay environment
     *
     * @var string
     */
    protected $env_name;

    /**
     * Wallet external ID
     *
     * @var string
     */
    protected $wallet;

    /**
     * API language
     *
     * @var string
     */
    protected $language = 'en';

    /**
     * API
     *
     * @var WC_LemonWay_API
     */
    protected $api;

    /**
     * Add an warning message for display in admin on save.
     *
     * @param string $warning Warning message.
     */
    protected function add_warning( $warning ) {
        $this->warnings[] = $warning;
    }

    /**
     * Get admin warning messages.
     */
    protected function get_warnings() {
        return $this->warnings;
    }

    /**
     * Display admin warning messages.
     */
    protected function display_warnings() {
        if ( $this->get_warnings() ) {
            echo '<div id="woocommerce_warnings" class="notice-warning notice is-dismissible">';
            foreach ( $this->get_warnings() as $warning ) {
                echo '<p>' . wp_kses_post( $warning ) . '</p>';
            }
            echo '</div>';
        }
    }

    /**
     * Load API settings
     */
    protected function load_api_settings()
    {   
        // Load main settings
        $settings_field_key = $this->get_option_key();
        $main_settings = get_option( $settings_field_key );

        $this->wlLogin = ! empty( $main_settings['wlLogin'] ) ? $main_settings['wlLogin'] : '';
        $this->wlPass = ! empty( $main_settings['wlPass'] ) ? $main_settings['wlPass'] : '';
        $this->test_mode = ( ! empty( $main_settings['test_mode'] ) && 'yes' === $main_settings['test_mode'] ) ? true : false;
        $this->env_name = ! empty( $main_settings['env_name'] ) ? $main_settings['env_name'] : '';
        $this->wallet = ! empty( $main_settings['wallet'] ) ? $main_settings['wallet'] : '';
        $this->directkit_url = ! empty( $main_settings['directkit_url'] ) ? $main_settings['directkit_url'] : '';
        $this->webkit_url = ! empty( $main_settings['webkit_url'] ) ? $main_settings['webkit_url'] : '';
    }

    /**
     * Set up API
     */
    protected function set_up_api()
    {   
        $settings = array(
            'directkit_url' => $this->directkit_url,
            'wlLogin' => $this->wlLogin,
            'wlPass' => $this->wlPass,
            'language' => $this->language
        );

        $this->api = new WC_LemonWay_API($settings);
    }

    /**
     * Test API
     */
    protected function test_api()
    {
        // Params for GetWalletDetails
        $params = array(
            'wallet' => $this->wallet
        );
        // @TODO: GetWalletDetails by email for LW4E

        try {
            $wallet_details = $this->api->get_wallet_details($params);
        } catch (WC_LemonWay_Exception $e) {
            if ( ! is_ajax() ) {
                $this->add_error( $e->getLocalizedMessage() . ' (' . $e->getCode() . ')' );
                $this->display_errors();
            }

            return false;
        }

        return true;
    }

    /**
     * Format amount to X.XX
     */
    protected function format_amount($amount)
    {
        return number_format ( $amount , 2 , '.' , '' );
    }

    /**
     * Load settings into object
     */
    public function __construct()
    {
        // Load the form fields.
        $this->init_form_fields();

        // Load the settings from the DB into $this->settings
        $this->init_settings();

        // Get setting values.
        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );

        // Load API settings
        $this->load_api_settings();

        if ( $this->test_mode ) {
            $this->title .= ' [TEST]';
            $this->description = '[' . __( 'This is only a test payment.', LEMONWAY_TEXT_DOMAIN ) . ' <a href="' . __( 'https://lemonway.zendesk.com/hc/en-gb/articles/212557765-2-How-do-I-test-with-the-WooCommerce-module-', LEMONWAY_TEXT_DOMAIN ) . '" target="_blank">' . __( 'Click here to see how to use Test mode.', LEMONWAY_TEXT_DOMAIN ) . '</a>' . ']' . "\n" . $this->description;
        }

        // Set API language
        $locale = substr( get_locale(), 0, 2 );

        if ( array_key_exists( $locale, $this->supported_locales ) ) {
            $this->language = $this->supported_locales[$locale];
        } else {
            $this->language = 'en';
        }

        // Set up API
        $this->set_up_api();

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    // @TODO: min/max amount
    // @TODO: admin_options
    // @TODO: validation options
    // @TODO: save payment method
    // @TODO: refund
}
