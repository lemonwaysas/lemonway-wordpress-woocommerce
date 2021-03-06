<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * WC_LemonWay_API class.
 *
 * Communicate with Lemon Way API.
 */
class WC_LemonWay_API
{
    /**
     * Directkit URL
     * @var string
     */
    private $directkit_url = WC_LemonWay_Payment_Gateway::LW4E_DIRECTKIT_URL_PROD;

    /**
     * API username
     * @var string
     */
    private $wlLogin = '';

    /**
     * API password
     * @var string
     */
    private $wlPass = '';

    /**
     * API hashed password
     * @var string
     */
    private $wlPassHash = '';

    /**
     * API language
     * @var string
     */
    private $language = 'en';

    private function request($methodName, $params)
    {
        // API Endpoint
        $url = $this->directkit_url . '/' . $methodName;

        // User-agent
        $ua = 'WooCommerce Lemon Way Payment Gateway v' . LEMONWAY_VERSION;
        $ua .= (isset($_SERVER['HTTP_USER_AGENT'])) ? '/' . $_SERVER['HTTP_USER_AGENT'] : '';

        // IP
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $tmpip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($tmpip[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = '';
        }

        $baseParams = array(
            'wlLogin' => $this->wlLogin,
            'wlPass' => $this->wlPass,
            'language' => $this->language,
            'version' => '10.0',
            'walletIp' => $ip,
            'walletUa' => $ua,
            'wlPassHash' => $this->wlPassHash
        );

        $requestParams = array_merge($baseParams, $params);
        $requestParams = array( 'p' => $requestParams );

        $headers = array(
            'Content-type' => 'application/json; charset=utf-8',
            'Accept' => 'application/json',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache'
        );

        $response = wp_safe_remote_post(
            $url,
            array(
                'method'  => 'POST',
                'headers' => $headers,
                'body'    => json_encode($requestParams)
            )
        );

        // Log
        $requestParams['p']['wlPass'] = '*masked*';
        $requestParams['p']['wlPassHash'] = '*masked*';
        WC_LemonWay_Logger::log($url . "\n" . 'Request: ' . json_encode($requestParams));

        if (is_wp_error($response) || empty($response['body'])) {
            // WP Error
            throw new WC_LemonWay_Exception('There was a problem connecting to the Lemon Way API (WP Error: ' . print_r($response, true) . ')', __('There was a problem connecting to the Lemon Way API.', LEMONWAY_TEXT_DOMAIN));
        }

        if (wp_remote_retrieve_response_code($response) != 200) {
            throw new WC_LemonWay_Exception('There was a problem connecting to the Lemon Way API (HTTP error: ' . wp_remote_retrieve_response_message($response) . ')', __('There was a problem connecting to the Lemon Way API:', LEMONWAY_TEXT_DOMAIN) . ' ' . wp_remote_retrieve_response_message($response), wp_remote_retrieve_response_code($response));
        }

        WC_LemonWay_Logger::log('Response: ' . $response['body']);

        //General parsing
        $parsedResponse = json_decode($response['body']);
        
        if (isset($parsedResponse->d->E)) {
            // Lemon Way error
            throw new WC_LemonWay_Exception($parsedResponse->d->E->Msg, '', $parsedResponse->d->E->Code);
        }
         
        return $parsedResponse->d;
    }

    /**
     * Constructor.
     *
     * @var array
     */
    public function __construct($settings)
    {
        $this->directkit_url = $settings['directkit_url'];
        $this->wlLogin = $settings['wlLogin'];
        $this->wlPass = $settings['wlPass'];
        $this->wlPassHash = $settings['wlPassHash'];
        $this->language = $settings['language'];
    }

    // API methods

    /**
     * Get details of a wallet
     *
     * @param array API parameters
     *
     * @return Object Wallet
     */
    public function get_wallet_details($params)
    {
        $response = $this->request('GetWalletDetails', $params);
        
        return $response->WALLET;
    }

    /**
     * Generate a money-in
     *
     * @param array API parameters
     *
     * @return Object MoneyInWeb result
     */
    public function money_in_web_init($params)
    {
        $response = $this->request('MoneyInWebInit', $params);

        return $response->MONEYINWEB;
    }

    /**
     * Money-in with a card token
     *
     * @param array API parameters
     *
     * @return Object Operation
     */
    public function money_in_with_card_id($params)
    {
        $response = $this->request('MoneyInWithCardId', $params);
    
        return $response->TRANS->HPAY;
    }

    /**
     * Get details of a money-in
     *
     * @param array API parameters
     *
     * @return Object Operation
     */
    public function get_money_in_trans_details($params)
    {
        $response = $this->request('GetMoneyInTransDetails', $params);

        return $response->TRANS->HPAY[0];
    }

    /**
     * Get hased password
     *
     * @since 2.1.0
     * @param array API parameters
     *
     * @return string Hashed password
     */
    public function get_pass_hash($params)
    {
        $response = $this->request('GetPassHash', $params);

        return $response->GETPASSHASH->PASS;
    }
}
