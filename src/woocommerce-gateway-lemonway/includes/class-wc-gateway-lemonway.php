<?php
if (! defined('ABSPATH')) {
    exit;
}
require_once 'services/DirectkitJson.php';
include_once('class-wc-gateway-lemonway-notif-handler.php');

/**
 * WC_Gateway_Lemonway class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_Lemonway extends WC_Payment_Gateway
{
    /** @var WC_Logger Logger instance */
    public static $log = false;
    
    /**
     *
     * @var string $apiLogin
     */
    protected $apiLogin;
    
    /**
     *
     * @var string $apiPassword
     */
    protected $apiPassword;
    
    /**
     *
     * @var string $merchantId
     */
    protected $merchantId;
    
    /**
     *
     * @var string $directkitUrl
     */
    protected $directkitUrl;
    
    /**
     *
     * @var string $directkitUrlTest
     */
    protected $directkitUrlTest;
    
    /**
     *
     * @var string $webkitUrl
     */
    protected $webkitUrl;
    
    /**
     *
     * @var string $webkitUrlTest
     */
    protected $webkitUrlTest;
    
    /**
     *
     * @var bool $oneclickEnabled
     */
    protected $oneclickEnabled;
    
    /**
     *
     * @var bool $isTestMode
     */
    protected $isTestMode;
    
    
    /**
     *
     * @var DirectkitJson $directkit
     */
    protected $directkit;

    protected $envName;
    
    /**
     *
     * @var WC_Gateway_Lemonway_Notif_Handler $notifhandler
     */
    protected $notifhandler;

    const DEFAULT_ENV = 'lwecommerce';
    const ENABLED = 'enabled';
    const TITLE = 'title';
    const DESCRIPTION = 'description';
    const API_LOGIN = 'api_login';
    const API_PASSWORD = 'api_password';
    const WALLET_MERCHANT_ID = 'merchant_id';
    const DIRECTKIT_URL = 'https://ws.lemonway.fr/mb/%s/prod/directkitjson2/service.asmx';
    const WEBKIT_URL = 'https://webkit.lemonway.fr/mb/%s/prod/';
    const DIRECTKIT_URL_TEST = 'https://sandbox-api.lemonway.fr/mb/%s/dev/directkitjson2/service.asmx';
    const WEBKIT_URL_TEST = 'https://sandbox-webkit.lemonway.fr/%s/dev/';
    const IS_TEST_MODE = 'is_test_mode';
    const CSS_URL = 'css_url';
    const ONECLICK_ENABLED = 'oneclick_enabled';
    const ENV_NAME = 'env_name';
    const TPL_NAME = 'tpl_name';
    
    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        $this->id                 = 'woocommerce-gateway-lemonway';
        $this->icon               = ''; //@TODO
        $this->has_fields         = true;
        $this->method_title       = __('LemonWay', LEMONWAY_TEXT_DOMAIN);
        $this->method_description = __('Secured payment solutions for Internet E-commerce. BackOffice management. Compliance. Regulatory reporting.', LEMONWAY_TEXT_DOMAIN);

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();
        
        $this->title          = $this->get_option(self::TITLE);
        $this->description    = $this->get_option(self::DESCRIPTION);

        //API informations
        $this->apiLogin = $this->get_option(self::API_LOGIN);
        $this->apiPassword = $this->get_option(self::API_PASSWORD);
        $this->merchantId = $this->get_option(self::WALLET_MERCHANT_ID);
        $this->envName = $this->get_option(self::ENV_NAME);
        $this->testMode = 'yes' === $this->get_option(self::IS_TEST_MODE, 'no');
        $this->oneclickEnabled = 'yes' === $this->get_option(self::ONECLICK_ENABLED, 'no');

        if (empty($this->envName)) {
            // If LW4EC
            $envName = self::DEFAULT_ENV;
        } else {
            // If LW Entreprise
            $envName = $this->envName;
        }

        $this->directkitUrl = sprintf(self::DIRECTKIT_URL, $envName);
        $this->webkitUrl = sprintf(self::WEBKIT_URL, $envName);
        $this->directkitUrlTest = sprintf(self::DIRECTKIT_URL_TEST, $envName);
        $this->webkitUrlTest = sprintf(self::WEBKIT_URL_TEST, $envName);

        $directkitUrl = $this->testMode ? $this->directkitUrlTest : $this->directkitUrl;
        $webkitUrl = $this->testMode ? $this->webkitUrlTest : $this->webkitUrl;
        
        $this->directkit = new DirectkitJson($directkitUrl, $webkitUrl, $this->apiLogin, $this->apiPassword, get_locale());
    
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));

        //Init notification handler
        $this->notifhandler =  new WC_Gateway_Lemonway_Notif_Handler($this);
    }
    
    /**
     * @return WC_Gateway_Lemonway_Notif_Handler
     */
    public function getNotifhandler()
    {
        return $this->notifhandler;
    }
    
    /**
     * If There are no payment fields show the description if set.
     * Override this in your gateway if you have some.
     */
    public function payment_fields()
    {
        if ($this->oneclickEnabled) {
            $this->oneclick_form();
        } else {
            if ($description = $this->get_description()) {
                echo wpautop(wptexturize(esc_html($description)));
            }
        }
    }
    
    public function getMerchantWalletId()
    {
        return $this->merchantId;
    }
    
    /**
     * One-click form.
     *
     * @param  array $args
     * @param  array $fields
     */
    public function oneclick_form($args = array(), $fields = array())
    {
        $oneclick_fields = array(
                'register_card' => '<p class="form-row form-row-wide">
                <label for="' . esc_attr($this->id) . '_register_card"><input id="' . esc_attr($this->id) . '_register_card" class="input-checkbox" value="register_card" type="checkbox" name="oneclick" />'
                . __('Save your card data for a next buy.', LEMONWAY_TEXT_DOMAIN) . '</label>
            </p>'
        );
        
        $cardId = get_user_meta(get_current_user_id(), 'lw_card_id', true);
        $cardType = get_user_meta(get_current_user_id(), 'lw_card_type', true);
        $cardNum = get_user_meta(get_current_user_id(), 'lw_card_num', true);
        $cardExp = get_user_meta(get_current_user_id(),'lw_card_exp',true);
        
        if (!empty($cardId)) {
            $oneclick_fields = array(
                'use_card' => '<p class="form-row form-row-wide">
                <label for="' . esc_attr($this->id) . '_use_card"><input id="' . esc_attr($this->id) . '_use_card" class="input-radio" checked="checked" value="use_card" type="radio" name="oneclick" />'
                    . sprintf(__('Use my recorded card: %s %s - %s', LEMONWAY_TEXT_DOMAIN), $cardType, $cardNum, $cardExp) . '</label>
                
            </p>',
            'register_card' => '<p class="form-row form-row-wide">
                <label for="' . esc_attr($this->id) . '_register_card"><input id="' . esc_attr($this->id) . '_register_card" class="input-radio" value="register_card" type="radio" name="oneclick" />'
                    . __('Save new card data.', LEMONWAY_TEXT_DOMAIN) .'</label>
                
            </p>',
            'no_use_card' => '<p class="form-row form-row-wide">
                <label for="' . esc_attr($this->id) . '_no_use_card"><input id="' . esc_attr($this->id) . '_no_use_card" class="input-radio"  value="no_use_card" type="radio" name="oneclick" />'
                    . __('Not use recorded card data.', LEMONWAY_TEXT_DOMAIN) .'</label>
                
            </p>'
            );
        }
    
        $fields = wp_parse_args($fields, apply_filters('lemonway_oneclick_form_fields', $oneclick_fields, $this->id)); ?>
            <fieldset id="<?php echo esc_attr($this->id); ?>-oneclic-form">
                <?php do_action('lemonway_oneclick_form_start', $this->id); ?>
                <?php
                    foreach ($fields as $field) {
                        echo $field;
                    } ?>
                <?php do_action('lemonway_oneclick_form_end', $this->id); ?>
                <div class="clear"></div>
            </fieldset>
            <?php
    }
    
    /**
     * Process the payment and return the result.
     * @param  int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        include_once('class-wc-gateway-lemonway-request.php');
    
        $order          = wc_get_order($order_id);
        $lw_request = new WC_Gateway_Lemonway_Request($this);
    
        return array(
                'result'   => 'success',
                'redirect' => $lw_request->get_request_url($order)
        );
    }
    
    /**
     * @return DirectkitJson
     */
    public function getDirectkit()
    {
        return $this->directkit;
    }
    
    /**
     * Logging method.
     * @param string $message
     */
    public static function log($message)
    {
        if (empty(self::$log)) {
            self::$log = new WC_Logger();
        }
        self::$log->add('woocommerce-gateway-lemonway', $message);
    }
    
    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = include('settings-lemonway.php');
    }
}
