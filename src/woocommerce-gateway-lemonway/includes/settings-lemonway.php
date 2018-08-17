<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Settings for Lemonway Payment Gateway.
 */
return array(
    'api_configuration' => array(
        'title'       => __('Account configuration', LEMONWAY_TEXT_DOMAIN),
        'type'        => 'title',
        'description' => ''
    ),
    WC_Gateway_Lemonway::API_LOGIN => array(
        'title'       => __("Login", LEMONWAY_TEXT_DOMAIN),
        'type'        => 'text',
        'description' => '',
        'default'     => '',
        'desc_tip'    => true,
        'placeholder' => ''
    ),
    WC_Gateway_Lemonway::API_PASSWORD => array(
        'title'       => __("Password", LEMONWAY_TEXT_DOMAIN),
        'type'        => 'password',
        'description' => '',
        'default'     => '',
        'desc_tip'    => true,
        'placeholder' => ''
    ),
    WC_Gateway_Lemonway::WALLET_MERCHANT_ID => array(
        'title'       => __('Your account name', LEMONWAY_TEXT_DOMAIN),
        'type'        => 'text',
        'description' => 'This information has been sent by email',
        'default'     => '',
        'desc_tip'    => true,
        'placeholder' => ''
    ),
    WC_Gateway_Lemonway::IS_TEST_MODE => array(
        'title'       => __('Activate test mode', LEMONWAY_TEXT_DOMAIN),
        'type'        => 'checkbox',
        'label'       => __('Activate test mode', LEMONWAY_TEXT_DOMAIN),
        'default'     => 'no',
        'description' =>  __('Click to go on Test, let it empty to go on Live', LEMONWAY_TEXT_DOMAIN)
    ),
    'payment_configuration' => array(
        'title'       => __('Payment Configuration', LEMONWAY_TEXT_DOMAIN),
        'type'        => 'title',
        'description' => ''
    ),
    WC_Gateway_Lemonway::ENABLED => array(
        'title'   => __('Enable/Disable', 'woocommerce'),
        'type'    => 'checkbox',
        'label'   => __('Enable Lemonway payment', LEMONWAY_TEXT_DOMAIN),
        'default' => 'no'
    ),
    WC_Gateway_Lemonway::TITLE => array(
        'title'       => __('Title', 'woocommerce'),
        'type'        => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
        'default'     => __('Credit Card', LEMONWAY_TEXT_DOMAIN),
        'desc_tip'    => true
    ),
    WC_Gateway_Lemonway::DESCRIPTION => array(
        'title'       => __('Description', 'woocommerce'),
        'type'        => 'text',
        'desc_tip'    => true,
        'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
        'default'     => __('You will be redirect to payment page after you submit order.', LEMONWAY_TEXT_DOMAIN)
    ),
    WC_Gateway_Lemonway::CSS_URL => array(
        'title'       => __('Css url', 'woocommerce'),
        'type'        => 'text',
        'description' => __('Optionally enter the url of the page style you wish to use.', LEMONWAY_TEXT_DOMAIN),
        'default'     => 'https://webkit.lemonway.fr/css/mercanet/mercanet_lw_custom.css',
        'desc_tip'    => true,
        'placeholder' => __('Optional', 'woocommerce')
    ),
    WC_Gateway_Lemonway::ONECLIC_ENABLED => array(
        'title'       => __('Enable Oneclic', LEMONWAY_TEXT_DOMAIN),
        'type'        => 'checkbox',
        'description' => __('Display checkbox for allow customer to save his credit card.', LEMONWAY_TEXT_DOMAIN),
        'label'   	  => __('Enable Oneclic', LEMONWAY_TEXT_DOMAIN),
        'default'     => 'no',
        'desc_tip'    => true
    )
);
