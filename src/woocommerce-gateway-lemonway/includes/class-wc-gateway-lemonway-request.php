<?php

class WC_Gateway_Lemonway_Request
{
    /**
     * Pointer to gateway making the request.
     * @var WC_Gateway_Lemonway
     */
    protected $gateway;

    /**
     * Endpoint for notification from Lemonway.
     * @var string
     */
    protected $notify_url;


    /**
     * Constructor.
     * @param WC_Gateway_Lemonway $gateway
     */
    public function __construct($gateway)
    {
        $this->gateway = $gateway;
        $this->notify_url = WC()->api_request_url('WC_Gateway_Lemonway');
    }

    /**
     * Get the Lemonway Webkit request URL for an order.
     * @param  WC_Order $order
     * @param  bool $isTestMode
     * @return string
     */
    public function get_request_url($order)
    {   
        // LW Entreprise => autoCommission = 1
        $autoCommission = empty($this->gateway->get_option(WC_Gateway_Lemonway::ENV_NAME)) ? 0 : 1;

        $registerCard = 0;
        $useCard = 0;
        if (isset($_POST['oneclick'])) {
            $oneclick = wc_clean($_POST['oneclick']);

            switch ($oneclick) {
                case 'register_card':
                    $registerCard = 1;
                    break;
                case 'use_card':
                    $useCard = 1;
                    break;
            }
        }

        //Build args with the order
        $amount = $order->get_total();

        $comment = "Woocommerce " . sprintf(__('Order #%s by %s %s %s', LEMONWAY_TEXT_DOMAIN), $order->get_order_number(), $order->billing_last_name, $order->billing_first_name, $order->billing_email);
        $returnUrl = '';

        if (!$useCard) {
            $params = array(
                "wkToken" => $order->id,
                "wallet" => $this->gateway->get_option(WC_Gateway_Lemonway::WALLET_MERCHANT_ID),
                "amountTot" => $this->formatAmount($amount),
                "amountCom" => "0.00",
                "comment" => $comment,
                "returnUrl" => $this->notify_url,
                "cancelUrl" => esc_url_raw($order->get_cancel_order_url_raw()),
                "errorUrl" => esc_url_raw($order->get_cancel_order_url_raw()), //@TODO change for a specific error url
                "autoCommission" => $autoCommission,
                "registerCard" => $registerCard
            );

            //Call APi MoneyInWebInit in correct MODE with the args
            $moneyInWeb = $this->gateway->getDirectkit()->MoneyInWebInit($params);

            //Save card ID
            if ($registerCard) {
                update_user_meta(get_current_user_id(), '_lw_card_id', $moneyInWeb->CARD->ID);
                update_post_meta($order->id, '_register_card', true);
                WC_Gateway_Lemonway::log(sprintf(__("Card Saved for customer Id %s", LEMONWAY_TEXT_DOMAIN), get_current_user_id()));
            }

            $returnUrl = $this->gateway->getDirectkit()->formatMoneyInUrl($moneyInWeb->TOKEN, $this->gateway->get_option(WC_Gateway_Lemonway::CSS_URL), $this->gateway->get_option(WC_Gateway_Lemonway::TPL_NAME));
        } else { //Customer want to use his last card, so we call MoneyInWithCardID directly
            $cardId = get_user_meta(get_current_user_id(), '_lw_card_id', true);

            //call directkit for MoneyInWithCardId
            $params = array(
                "wkToken" => $order->id,
                "wallet" => $this->gateway->get_option(WC_Gateway_Lemonway::WALLET_MERCHANT_ID),
                "cardId" => $cardId,
                "amountTot" => $this->formatAmount($amount),
                "amountCom" => "0.00",
                "comment" => $comment . " - " . sprintf(__("One-click mode (card id: %s)", LEMONWAY_TEXT_DOMAIN), $cardId),
                "autoCommission" => $autoCommission
            );

            $operation = $this->gateway->getDirectkit()->MoneyInWithCardId($params);

            if ($operation->STATUS == "3") {
                $transaction_id = $operation->ID;

                //Set transaction id to POST array. Needed on notif handler
                $_POST['response_transactionId'] = $transaction_id;

                //Process order status
                $this->gateway->getNotifhandler()->valid_response($order);
                //Return to original wc success page
                $returnUrl = $this->gateway->get_return_url($order);
            } else {
                throw new Exception(__("Error during payment", LEMONWAY_TEXT_DOMAIN));
            }
        }
        //Return redirect url
        return $returnUrl;
    }

    protected function formatAmount($amount)
    {
        return sprintf("%.2f", (float)$amount);
    }
}
