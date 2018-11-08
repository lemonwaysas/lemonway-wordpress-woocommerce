<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Settings for Lemon Way Gateway.
 */
return array(
    'enabled' => array(
        'title' => __('Enable/Disable', 'woocommerce'),
        'type' => 'checkbox',
        'label'   => __('Enable Lemon Way', LEMONWAY_TEXT_DOMAIN),
        'default' => 'yes'
    ),
    'title' => array(
        'title' => __('Title', 'woocommerce'),
        'type' => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
        'default' => __('Credit Card', LEMONWAY_TEXT_DOMAIN),
        'desc_tip'      => true
    ),
    'description' => array(
        'title' => __('Description', 'woocommerce'),
        'type' => 'textarea',
        'description' => __('This controls the description which the user sees during checkout.', LEMONWAY_TEXT_DOMAIN),
        'default' => __('Pay with your credit card.', LEMONWAY_TEXT_DOMAIN),
        'desc_tip'      => true
    ),
    'css_url' => array(
        'title' => __('Payment page CSS URL', LEMONWAY_TEXT_DOMAIN),
        'type' => 'text',
        'description' => __('Customise the stylesheet of the payment page(Notice: If your website is in https, the CSS URL has to be in https too)', LEMONWAY_TEXT_DOMAIN),
        'desc_tip' => true,
        'default' => self::DEFAULT_CSS_URL
    ),
    'one_click' => array(
        'title' => __('One-click', LEMONWAY_TEXT_DOMAIN),
        'type' => 'checkbox',
        'label' => __('Enable One-click', LEMONWAY_TEXT_DOMAIN),
        'description' => __('Display One-click form when check out.', LEMONWAY_TEXT_DOMAIN),
        'desc_tip' => true,
        'default' => 'no'
    ),
    'account_settings' => array(
        'title' =>  '<hr /><span class="dashicons dashicons-admin-users"></span> ' . __('Account settings', LEMONWAY_TEXT_DOMAIN),
        'type' => 'title'
    ),
    'wlLogin' => array(
        'title' => __('Username', LEMONWAY_TEXT_DOMAIN),
        'type' => 'text',
        'description' => '<a href="https://www.lemonway.com/ecommerce/" target="_blank">' . __('Create an account', LEMONWAY_TEXT_DOMAIN) . '</a>',
        'custom_attributes' => array(
            'required' => 'required'
        )
    ),
    'wlPass' => array(
        'title' => __('Password', LEMONWAY_TEXT_DOMAIN),
        'type' => 'password',
        'description' => '<a href="' . __('https://ecommerce.lemonway.com/en/seller/lost-password', LEMONWAY_TEXT_DOMAIN) . '" target="_blank">' . __('Forgot password?', LEMONWAY_TEXT_DOMAIN) . '</a>',
        'custom_attributes' => array(
            'required' => 'required'
        )
    ),
    'wallet' => array(
        'title' => __('Wallet external ID', LEMONWAY_TEXT_DOMAIN),
        'type' => 'text',
        'description' => __('External ID of your technical wallet', LEMONWAY_TEXT_DOMAIN),
        'desc_tip' => true,
        'custom_attributes' => array(
            'required' => 'required'
        )
    ),
    'test_mode' => array(
        'title' => __('Test mode', LEMONWAY_TEXT_DOMAIN),
        'type' => 'checkbox',
        'label' => __('Enable test mode', LEMONWAY_TEXT_DOMAIN),
        'description' =>  '<a href="' . __('https://lemonway.zendesk.com/hc/en-gb/articles/212557765-2-How-do-I-test-with-the-WooCommerce-module-', LEMONWAY_TEXT_DOMAIN) . '" target="_blank">' . __('Click here to see how to use Test mode.', LEMONWAY_TEXT_DOMAIN) . '</a>',
        'default' => 'no'
    ),
    'lw_enterprise' => array(
        'title' => '<hr /><span class="dashicons dashicons-businessman"></span> LemonWay Enterprise',
        'type' => 'title',
        'description' => __('Settings for Lemon Way Enterprise partners only', LEMONWAY_TEXT_DOMAIN)
    ),
    'env_name' => array(
        'title' => __('Environment name', LEMONWAY_TEXT_DOMAIN),
        'type' => 'text',
        'description' => __('Name of your Lemon Way environment', LEMONWAY_TEXT_DOMAIN),
        'desc_tip' => true
    ),
    'tpl_name' => array(
        'title' => __('Payment page template name', LEMONWAY_TEXT_DOMAIN),
        'type' => 'text',
        'description' => __('Template name that Lemon Way sent to you.', LEMONWAY_TEXT_DOMAIN),
        'desc_tip' => true
    )
);
