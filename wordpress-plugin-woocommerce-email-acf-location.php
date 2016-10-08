<?php
/*
Plugin Name: WooCommerce Email ACF Location
Version: 1.0
Description: Adds WooCommerce Email locations for ACF
Author: Nick Breen
Author URI: http://foobar.net.nz
Plugin URI: https://github.com/nickbreen/wordpress-plugin-woocommerce-email-acf-location
*/

add_filter('acf/location/rule_types', function ($choices) {
    $choices['WooCommerce']['email'] = 'Email';
    return $choices;
});

add_filter('acf/location/rule_values/email', function ($choices) {
    global $woocommerce;
    $emails = $woocommerce->mailer()->emails;

    user_error(print_r($emails, true));

    foreach ($emails as $email) {
        $choices[$email->id] = $email->title;
    }

    return $choices;
});
