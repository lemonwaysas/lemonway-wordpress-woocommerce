<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * WC_Gateway_LemonWay class
 *
 * @extends WC_LemonWay_Payment_Gateway
 */
class WC_Gateway_LemonWay extends WC_LemonWay_Payment_Gateway
{   
    const DEFAULT_CSS_URL = 'https://webkit.lemonway.fr/css/mercanet/mercanet_lw_custom.css';

    /**
     * Template for Atos v2
     *
     * @var string
     */
    private $tpl_name;

    /**
     * CSS URL for Atos v1
     *
     * @var string
     */
    private $css_url = self::DEFAULT_CSS_URL;

    /**
     * Is one_click active?
     *
     * @var bool
     */
    protected $one_click = false;

    /**
     * One-click form.
     *
     */
    private function one_click_form()
    {   
        $card_id = get_user_meta( get_current_user_id(), '_lw_card_id', true );
        $card_num = get_user_meta( get_current_user_id(), '_lw_card_num', true );
        $card_exp = get_user_meta( get_current_user_id(), '_lw_card_exp', true );
        $card_typ = get_user_meta( get_current_user_id(), '_lw_card_typ', true );
        
        if ( empty( $card_id ) || empty( $card_typ ) || empty( $card_num ) || empty( $card_exp ) ) {
            // No saved card
            $fields = array(
                'register_card' => '<p class="form-row form-row-wide">
                    <label for="' . esc_attr( $this->id ) . '_register_card">
                        <input id="' . esc_attr( $this->id ) . '_register_card" class="input-checkbox" value="register_card" type="checkbox" name="one_click" />'
                        . __( 'Save your card data for a next buy.', LEMONWAY_TEXT_DOMAIN )
                    . '</label>
                </p>'
            );
        } else {
            // Saved card
            $fields = array(
                'use_card' => '<p class="form-row form-row-wide">
                    <label for="' . esc_attr( $this->id ) . '_use_card">
                        <input id="' . esc_attr( $this->id ) . '_use_card" class="input-radio" checked="checked" value="use_card" type="radio" name="one_click" /> '
                        . sprintf( __( 'Use my saved card: %s %s - %s', LEMONWAY_TEXT_DOMAIN ), $card_typ, $card_num, $card_exp )
                    . '</label>
                </p>',
                'register_card' => '<p class="form-row form-row-wide">
                    <label for="' . esc_attr($this->id) . '_register_card">
                        <input id="' . esc_attr($this->id) . '_register_card" class="input-radio" value="register_card" type="radio" name="one_click" /> '
                        . __( 'Save new card data.', LEMONWAY_TEXT_DOMAIN )
                    .'</label>
                </p>',
                'no_use_card' => '<p class="form-row form-row-wide">
                    <label for="' . esc_attr( $this->id ) . '_no_use_card">
                        <input id="' . esc_attr( $this->id ) . '_no_use_card" class="input-radio" value="no_use_card" type="radio" name="one_click" /> '
                        . __( 'Not use saved card.', LEMONWAY_TEXT_DOMAIN )
                    .'</label>
                </p>'
            );
        }

        ob_start();

            echo '<fieldset id="' . esc_attr($this->id). '-one-click-form">';
                foreach ($fields as $field) {
                    echo $field;
                }
                echo '<div class="clear"></div>';
            echo '</fieldset>';

        ob_end_flush();
    }

    private function isGet()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) == 'GET';
    }

    private function isPost()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) == 'POST';
    }

    private function abort_order($order, $order_note = '')
    {
        $order_id = WC_LemonWay_Helper::is_wc_lt( '3.0' ) ? $order->id : $order->get_id();
        $wk_token = get_post_meta( $order_id, '_wk_token', true );

        // add_post_meta unique => prevent double validation
        if ( ! $wk_token || add_post_meta( $order_id, '_' . $wk_token . '_is_validated', false, true ) ) {
            // If not validated yet
            if ( ! $order->has_status( 'failed' ) ) {
                // If order is not already failed, fail it
                $order->update_status( 'failed', $order_note );
            } else {
                // If already fail it => add note
                $order->add_order_note( $order_note );
            }
        } elseif ( update_post_meta( $order_id, '_' . $wk_token . '_is_validated', false ) ) {
                // If already validated but didn't fail it => fail it
                $order->update_status( 'failed', $order_note );
        }
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id = self::MAIN_GATEWAY_ID;
        $this->method_title = 'Lemon Way';
        $this->method_description = __('Secured payment solutions for Internet E-commerce. BackOffice management. Compliance. Regulatory reporting.', LEMONWAY_TEXT_DOMAIN);
        // @TODO: add meta link to method_description
        $this->icon = ''; // @TODO: Credit cards icon
        // @TODO: other properties

        parent::__construct();

        $this->tpl_name = $this->get_option( 'tpl_name' );
        $this->css_url = $this->get_option( 'css_url' );
        $this->one_click = ( ! empty( $this->get_option( 'one_click' ) ) && 'yes' === $this->get_option( 'one_click' ) ) ? true : false;

        // Has fields if one click
        $this->has_fields = $this->one_click;

        add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'callback_handler' ) );
    }

    /**
     * Initialise gateway settings form fields
     */
    public function init_form_fields()
    {
        $this->form_fields = require dirname( __FILE__ ) . '/settings/settings-lemonway.php';
    }

    /**
     * Processes and saves options.
     */
    public function process_admin_options() {
        // Hash password
        //$_POST['woocommerce_lemonway_wlPass'] = hash('sha256', $_POST['woocommerce_lemonway_wlPass']);

        // Save settings into DB
        parent::process_admin_options();

        // Load new settings
        $this->load_api_settings();

        // Generate API endpoints
        if (empty($this->env_name)) {
            // If LW4E
            if (!$this->test_mode) {
                // If live mode
                $this->directkit_url = self::LW4E_DIRECTKIT_URL_PROD;
                $this->webkit_url = self::LW4E_WEBKIT_URL_PROD;
            } else {
                // If test mode
                $this->directkit_url = self::LW4E_DIRECTKIT_URL_TEST;
                $this->webkit_url = self::LW4E_WEBKIT_URL_TEST;
            }
        } else {
            // If LW Entreprise
            if (!$this->test_mode) {
                // If live mode
                $this->directkit_url = sprintf(self::LEMONWAY_DIRECTKIT_FORMAT_URL_PROD, $this->env_name);
                $this->webkit_url = sprintf(self::LEMONWAY_WEBKIT_FORMAT_URL_PROD, $this->env_name);
            } else {
                // If test mode
                $this->directkit_url = sprintf(self::LEMONWAY_DIRECTKIT_FORMAT_URL_TEST, $this->env_name);
                $this->webkit_url = sprintf(self::LEMONWAY_WEBKIT_FORMAT_URL_TEST, $this->env_name);
            }
        }

        // Save into DB
        $this->update_option('directkit_url', $this->directkit_url);
        $this->update_option('webkit_url', $this->webkit_url);

        // Set up API
        $this->set_up_api();

        // Test API
        $this->test_api();
    }

    /**
     * Override the payment fields with one-click form
     */
    public function payment_fields()
    {
        parent::payment_fields();

        if ( is_user_logged_in() && $this->one_click ) {
            $this->one_click_form();
        }
    }
 
    /**
     * Process payments
     *
     * @param int $order_id Order ID
     *
     * @return array
     */
    public function process_payment( $order_id )
    {
        try {
            $order = wc_get_order( $order_id );

            // Get order info
            $customer_id = WC_LemonWay_Helper::is_wc_lt( '3.0' ) ? $order->customer_user : $order->get_customer_id();
            $shop_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
            $order_number = $order->get_order_number();
            $billing_first_name = WC_LemonWay_Helper::is_wc_lt( '3.0' ) ? $order->billing_first_name : $order->get_billing_first_name();
            $billing_last_name  = WC_LemonWay_Helper::is_wc_lt( '3.0' ) ? $order->billing_last_name : $order->get_billing_last_name();
            $billing_email = WC_LemonWay_Helper::is_wc_lt( '3.0' ) ? $order->billing_email : $order->get_billing_email();
            
            // Generate an unique wkToken
            $wk_token = $order_id . '_' . $customer_id . '_' . current_time( 'timestamp' );
            update_post_meta( $order_id, '_wk_token', $wk_token );

            $amount = $order->get_total();

            // LW Entreprise => autocom
            $auto_commission = empty( $this->env_name ) ? 0 : 1;

            // One-click
            $register_card = 0;
            $use_card = 0;

            if ( is_user_logged_in() && isset( $_POST['one_click'] ) ) {
                $one_click = wc_clean( $_POST['one_click'] );

                switch ( $one_click ) {
                    case 'register_card':
                        $register_card = 1;
                        break;
                    case 'use_card':
                        $use_card = 1;
                        break;
                }
            }

            // Generate comment for transaction with order info
            $comment = 'Woocommerce - ' . sprintf('%1$s - Order %2$s by %3$s %4$s (%5$s)', $shop_name, $order_number, $billing_first_name, $billing_last_name, $billing_email);
            
            if ( $this->test_mode ) {
                $comment = '[TEST] ' . $comment;
            }

            if ( ! is_user_logged_in() || ! $use_card ) {
                // MoneyInWebInit

                // Callback params
                $args = array(
                    'order_id' => $order_id,
                    'customer_id' => $customer_id
                );

                $return_args = array_merge( $args, array(
                    'action' => 'return'
                ) );

                $error_args = array_merge( $args, array(
                    'action' => 'error'
                ) );

                $cancel_args = array_merge( $args, array(
                    'action' => 'cancel'
                ) );

                // Params for MoneyInWebInit
                $params = array(
                    'wallet' => $this->wallet,
                    'amountTot' => $this->formatAmount($amount),
                    'amountCom' => '0.00',
                    'comment' => $comment,
                    'wkToken' => $wk_token,
                    'returnUrl' => add_query_arg( $return_args, WC()->api_request_url( get_class( $this ) ) ),
                    'errorUrl' => add_query_arg( $error_args, WC()->api_request_url( get_class( $this ) ) ),
                    'cancelUrl' => add_query_arg( $cancel_args, WC()->api_request_url( get_class( $this ) ) ),
                    'autoCommission' => $auto_commission,
                    'registerCard' => (int) ( is_user_logged_in() && $register_card )
                );

                $money_in_web = $this->api->money_in_web_init($params);

                // Save transaction ID to the order
                WC_LemonWay_Helper::is_wc_lt( '3.0' ) ? update_post_meta( $order_id, '_transaction_id', $money_in_web->ID ) : $order->set_transaction_id( $money_in_web->ID );

                //Save card ID
                if ( is_user_logged_in() && $register_card ) {
                    update_user_meta( get_current_user_id(), '_lw_card_id', $money_in_web->CARD->ID );
                    update_post_meta( $order_id, '_register_card', true );
                }

                // Save order information into DB
                $order->save();

                $redirect_url = $this->webkit_url . '?moneyintoken=' . $money_in_web->TOKEN . '&tpl=' . urlencode($this->tpl_name) . '&p=' . urlencode($this->css_url) .  '&lang=' . $this->language;

                return array(
                    'result' => 'success',
                    'redirect' => esc_url_raw( $redirect_url )
                );
            } else {
                // MoneyInWithCardId
                $card_id = get_user_meta( get_current_user_id(), '_lw_card_id', true );

                // Params for MoneyInWithCardId
                $params = array(
                    'wallet' => $this->wallet,
                    "cardId" => $card_id,
                    'amountTot' => $this->formatAmount($amount),
                    'amountCom' => '0.00',
                    'comment' => $comment . ' (One-click)',
                    'autoCommission' => $auto_commission
                );

                $hpay = $this->api->money_in_with_card_id($params);

                if ($hpay->INT_STATUS == 0) {
                    // Status 0 means success
                    $order->payment_complete( $hpay->ID );

                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url( $order )
                    );
                } else {
                    throw new WC_LemonWay_Exception( $hpay->INT_MSG, '', $order_id );
                }
            }
        } catch (WC_LemonWay_Exception $e) {
            WC_LemonWay_Logger::log( 'Error: ' . $e->getMessage() . ' (' . $e->getCode() . ')');

            $localized_message = $e->getLocalizedMessage() . ' (' . $e->getCode() . ')';
            $this->abort_order( $order, $localized_message );
            wc_add_notice(  __( 'Payment error:', LEMONWAY_TEXT_DOMAIN ) . ' ' . $localized_message, 'error' );

            return array(
                'result' => 'fail',
                'redirect' => ''
            );
        }
    }

    /**
     * Handle the payment callback
     *
     */
    public function callback_handler()
    {
        WC_LemonWay_Logger::log('Callback ' . $_SERVER['REQUEST_METHOD'] . ': ' . print_r($_REQUEST, true));

        try {
            if ( ! $this->isGet() && ! $this->isPost() ) {
                throw new WC_LemonWay_Exception( 'HTTP method not allowed.',  __('HTTP method not allowed.', LEMONWAY_TEXT_DOMAIN), 405 );
            }

            if ( ! isset( $_REQUEST['response_wkToken'] ) || ! isset( $_REQUEST['order_id'] ) || ! isset( $_REQUEST['customer_id'] ) ) {
                throw new WC_LemonWay_Exception( 'Bad request: Missing response_wkToken, order_id or customer_id.', __('Bad request.', LEMONWAY_TEXT_DOMAIN) );
            }

            $wk_token = wc_clean( $_REQUEST['response_wkToken'] );
            // TODO: get order by meta value _wk_token
            $order_id = wc_clean( $_REQUEST['order_id'] );

            $order = wc_get_order( $order_id );

            if ( ! $order ) {
                throw new WC_LemonWay_Exception( 'Order not found.', __('Order not found.', LEMONWAY_TEXT_DOMAIN), $order_id );
            }
            // Order found, from now we have to fail it in case of error

            $customer_id = WC_LemonWay_Helper::is_wc_lt( '3.0' ) ? $order->customer_user : $order->get_customer_id();

            if ( wc_clean( $_REQUEST['customer_id'] ) != $customer_id ) {
                $this->abort_order($order, __('Bad request.', LEMONWAY_TEXT_DOMAIN));
                throw new WC_LemonWay_Exception( 'Bad request: Customer ID doesn\'t match.', __('Bad request.', LEMONWAY_TEXT_DOMAIN), $order_id );
            }

            $transaction_id = WC_LemonWay_Helper::is_wc_lt( '3.0' ) ? get_post_meta( $order_id, '_transaction_id', true ) : $order->get_transaction_id();

            if ( ! $transaction_id ) {
                $this->abort_order($order, __('Transaction not found.', LEMONWAY_TEXT_DOMAIN));
                throw new WC_LemonWay_Exception( 'Transaction not found.', __('Transaction not found.', LEMONWAY_TEXT_DOMAIN), $order_id );
            }

            $params = array(
                'transactionId' => $transaction_id,
                'transactionMerchantToken' => $wk_token
            );

            $hpay = $this->api->get_money_in_trans_details( $params );

            $register_card = get_post_meta( $order_id, '_register_card', true );

            if ( $register_card ) {
                update_user_meta( $customer_id, '_lw_card_num', $hpay->EXTRA->NUM );
                update_user_meta( $customer_id, '_lw_card_exp', $hpay->EXTRA->EXP );
                update_user_meta( $customer_id, '_lw_card_typ', $hpay->EXTRA->TYP );
            }

            $action = wc_clean( $_REQUEST['action'] );

            switch ($action) {
                case 'return':
                    switch ($hpay->INT_STATUS) {
                        case 0:
                            // Success
                            // add_post_meta unique => prevent double validation
                            if ( add_post_meta( $order_id, '_' . $wk_token . '_is_validated', true, true ) ) {
                                $order->payment_complete( $transaction_id );
                            }
                            
                            if ( $this->isGet() ) {
                                wp_safe_redirect( $this->get_return_url( $order ) );
                                exit;
                            }
                            break;
                        
                        case 6:
                            // Error
                            $this->abort_order($order, $hpay->INT_MSG);
                            throw new WC_LemonWay_Exception( $hpay->INT_MSG, '', $order_id );
                            break;

                        default:
                            throw new WC_LemonWay_Exception( 'Payment pending.', __('Payment pending.', LEMONWAY_TEXT_DOMAIN), $order_id );
                            break;
                    }
                    break;
                
                case 'error':
                    $this->abort_order($order, $hpay->INT_MSG);
                    throw new WC_LemonWay_Exception( $hpay->INT_MSG, '', $order_id );
                    break;

                case 'cancel':
                    wp_safe_redirect( $order->get_cancel_order_url() );
                    break;

                default:
                    throw new WC_LemonWay_Exception( 'Bad request: Unknown action.', __('Bad request.', LEMONWAY_TEXT_DOMAIN), $order_id );
                    break;
            }

        } catch (WC_LemonWay_Exception $e) {
            WC_LemonWay_Logger::log( $_SERVER['REQUEST_METHOD'] . ' - Error: ' . $e->getMessage() . ' (' . $e->getCode() . ')');

            // If it's not IPN, display error to user
            if ( $this->isGet() ) {
                wc_add_notice(  __('Payment error:', LEMONWAY_TEXT_DOMAIN) . ' ' . $e->getLocalizedMessage() . ' (' . $e->getCode() . ')', 'error' );
                wp_safe_redirect( wc_get_cart_url() );
                exit;
            }  
        }
    }
}
