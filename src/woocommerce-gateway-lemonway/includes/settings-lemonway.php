<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Settings for Lemonway Payment Gateway.
 */
return array(
    WC_Gateway_Lemonway::ENABLED => array(
        'title'   => __('Enable/Disable', 'woocommerce'),
        'type'    => 'checkbox',
        'label'   => __('Enable LemonWay', LEMONWAY_TEXT_DOMAIN),
        'default' => 'yes'
    ),
    WC_Gateway_Lemonway::TITLE => array(
        'title'       => __('Title', 'woocommerce'),
        'type'        => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
        'desc_tip'    => true,
        'default'     => __('Credit Card', LEMONWAY_TEXT_DOMAIN)
    ),
    WC_Gateway_Lemonway::DESCRIPTION => array(
        'title'       => __('Description', 'woocommerce'),
        'type'        => 'text',
        'description' => __("Payment method description that the customer will see on your checkout.", "woocommerce"),
        'desc_tip'    => true,
        'default'     => __('You will be redirected to payment page after submitting order.', LEMONWAY_TEXT_DOMAIN)
    ),
    'account_settings' => array(
        'title'       =>  "<hr /><span class='dashicons dashicons-admin-users'></span> " . __('Account settings', LEMONWAY_TEXT_DOMAIN),
        'type'        => 'title',
    ),
    WC_Gateway_Lemonway::API_LOGIN => array(
        'title'       => __("Login", LEMONWAY_TEXT_DOMAIN),
        'type'        => 'text',
        'description' => "<a href='https://www.lemonway.com/ecommerce/' target='_blank'>" . __("Create an account", LEMONWAY_TEXT_DOMAIN) . "</a>",
        'default' => get_option('admin_email')
    ),
    WC_Gateway_Lemonway::API_PASSWORD => array(
        'title'       => __("Password", LEMONWAY_TEXT_DOMAIN),
        'type'        => 'password',
        'description' => "<a href='" . __("https://ecommerce.lemonway.com/en/seller/lost-password", LEMONWAY_TEXT_DOMAIN) . "' target='_blank'>" . __("Forgotten password?", LEMONWAY_TEXT_DOMAIN) . "</a>"
    ),
    WC_Gateway_Lemonway::WALLET_MERCHANT_ID => array(
        'title'       => __('Your account name', LEMONWAY_TEXT_DOMAIN),
        'type'        => 'text',
        'description' => 'This information has been sent by email',
        'desc_tip'    => true,
    ),
    WC_Gateway_Lemonway::IS_TEST_MODE => array(
        'title'       => __('Test mode', LEMONWAY_TEXT_DOMAIN),
        'type'        => 'checkbox',
        'label'       => __('Enable test mode', LEMONWAY_TEXT_DOMAIN),
        'description' =>  "<a href='" . __("https://lemonway.zendesk.com/hc/en-gb/articles/212557765-2-How-do-I-test-with-the-WooCommerce-module-", LEMONWAY_TEXT_DOMAIN) . "' target='_blank'>Click here to see how to use Test mode</a>",
        'default'     => 'no'
    ),
    'payment_configuration' => array(
        'title'       => "<hr /><span class='dashicons dashicons-admin-settings'></span> " . __('Advanced settings', LEMONWAY_TEXT_DOMAIN),
        'type'        => 'title'
    ),
    WC_Gateway_Lemonway::CSS_URL => array(
        'title'       => __('CSS URL', LEMONWAY_TEXT_DOMAIN),
        'type'        => 'text',
        'description' => __("Customise the stylesheet of the payment page (Notice: If your website is in https, the CSS URL has to be in https too)", LEMONWAY_TEXT_DOMAIN),
        'desc_tip'    => true,
        'default'     => "https://webkit.lemonway.fr/css/mercanet/mercanet_lw_custom.css"
    ),
    WC_Gateway_Lemonway::ONECLICK_ENABLED => array(
        'title'       => __('One-click', LEMONWAY_TEXT_DOMAIN),
        'type'        => 'checkbox',
        'label'       => __('Enable One-click', LEMONWAY_TEXT_DOMAIN),
        'description' => __('Display One-click form when check out.', LEMONWAY_TEXT_DOMAIN),
        'desc_tip'    => true,
        'default'     => 'no'
    ),
    'lemonway_entreprise' => array(
        'title'       => "<hr /><span class='dashicons dashicons-businessman'></span> LemonWay Entreprise",
        'type'        => 'title',
        "description" => "Fields for LemonWay Entreprise partners"
    ),
    WC_Gateway_Lemonway::ENV_NAME => array(
        'title'       => __('Environment name', LEMONWAY_TEXT_DOMAIN),
        'type'        => 'text',
        'description' => __('Environment name', LEMONWAY_TEXT_DOMAIN),
        'desc_tip'    => true
    ),
    WC_Gateway_Lemonway::TPL_NAME => array(
        'title'       => __('Payment page template name', LEMONWAY_TEXT_DOMAIN),
        'type'        => 'text',
        'description' => __('Payment page template name', LEMONWAY_TEXT_DOMAIN),
        'desc_tip'    => true
    )
);
