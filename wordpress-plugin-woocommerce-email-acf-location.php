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
    $choices['Email']['wc_email'] = 'WooCommerce Emails';
    return $choices;
});

add_filter('acf/location/rule_values/wc_email', function ($choices) {
    global $woocommerce;
    $emails = $woocommerce->mailer()->emails;
    foreach ($emails as $email) {
        $choices[$email->id] = $email->title;
    }
    return $choices;
});

add_filter('acf/location/rule_match/wc_email', function ($match, $rule, $options) {
    global $woocommerce;
    return array_filter($woocommerce->mailer()->emails, function ($email) use ($rule) {
        switch ($rule['operator']) {
            case '==':
                return $email->id == $rule['value'];
            case '!=':
                return $email->id != $rule['value'];
            default:
                return array();
        }
    });
}, 10, 3);

add_filter('woocommerce_email_order_meta_fields', function ($email_fields, $sent_to_admin, $order) {
    $groups = acf_get_field_groups(array('post_id' => $order->id));
    user_error('groups: '.print_r($groups, true));
    foreach ($groups as $g => $group) {
        $email_fields[] = array(
            'label' => $group['title'],
            'value' => $group['title'],
        );
        $fields = acf_get_fields($group);
        user_error('fields: '.print_r($fields, true));
        foreach ($fields as $f => $field) {
            $email_fields[] = array(
                'label' => $field['label'],
                'value' => $field['value'],
            );
            while (have_rows($field['name'])) {
                the_row();
                $row = get_row(true);
                user_error('row: '.print_r($row, true));
                foreach ($row as $sf => $value) {
                    $subField = get_sub_field_object($sf);
                    user_error('subField: '.print_r($subField, true));
                    $email_fields[] = array(
                        'label' => $subField['label'],
                        'value' => $value,
                    );
                }
            }
        }
    }
    return $email_fields;
}, 10, 3);

add_action('woocommerce_email_order_meta', function ($order, $sent_to_admin, $plain_text, $email) {

}, 20, 4);
