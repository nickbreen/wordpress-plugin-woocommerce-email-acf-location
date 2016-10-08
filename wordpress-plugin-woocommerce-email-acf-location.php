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

$layouts = array(
    'table' => function ($field, $rows) use ($layouts) {
        $meta = sprintf(
            '<table id="%1$s" title="%2$s" style="%s" cellspacing="0">',
            $field['id'],
            $field['label'],
            'white-space: pre; text-align: left; vertical-align: text-top'
        );
        $meta .= '<thead><tr><th style="border: 1px #e4e4e4 solid">#</th>';
        foreach ($field['sub_fields'] as $s => $sub) {
            $meta .= sprintf(
                '<th class="%s" style="border: 1px #e4e4e4 solid">%s</th>',
                $sub['name'],
                $sub['label']
            );
        }
        $meta .= '</tr></thead><tbody>';
        foreach ($rows as $r => $row) {
            $meta .= sprintf('<tr><td style="border: 1px #e4e4e4 solid">%d</td>', $r + 1);
            foreach ($row as $f => $sub) {
                if (have_rows($sub['name'])) {
                    $subRows = array();
                    while (have_rows($sub['name'])) {
                        the_row();
                        $subRows[] = get_row(true);
                    }
                    $sub['value'] = $layouts[$sub['layout']]($sub, $subRows);
                }
                $meta .= sprintf(
                    '<td class="%s" style="%s">%s</td>',
                    $r,
                    'border: 1px #e4e4e4 solid; white-space: pre',
                    $sub['value']
                );
            }
            $meta .= '</tr>';
        }
        $meta .= '</tr></tbody></table>';
        return $meta;
    },
    'row' => function ($field, $rows) use ($layouts) {
        $meta = sprintf(
            '<table id="%1$s" title="%2$s" style="%s" cellspacing="0">',
            $field['id'],
            $field['label'],
            'white-space: pre; text-align: left; vertical-align: text-top'
        );
        $meta .= '<tbody>';
        foreach ($rows as $r => $row) {
            $first = sprintf(
                '<td rowspan="%d" style="%s">%d</td>',
                count($row),
                'border: 1px #e4e4e4 solid',
                $r + 1
            );
            foreach ($row as $f => $sub) {
                $meta .= '<tr style="white-space: pre; text-align: left; vertical-align: text-top">';
                if ($first) {
                    $meta .= $first;
                    $first = null;
                }
                if (have_rows($sub['name'])) {
                    $subRows = array();
                    while (have_rows($sub['name'])) {
                        the_row();
                        $subRows[] = get_row(true);
                    }
                    $sub['value'] = $layouts[$sub['layout']]($sub, $subRows);
                }
                $meta .= sprintf(
                    '<th style="%2$s">%1$s</th><td style="%2$s">%3$s</td>',
                    $sub['label'],
                    'border: 1px #e4e4e4 solid; ',
                    $sub['value']
                );
                $meta .= '</tr>';
            }
        }
        $meta .= '</tbody></table>';
        return $meta;
    },
    'block' => function ($field, $rows) use ($layouts) {
        foreach ($rows as $r => $row) {
            $meta .= sprintf('<dl id="%s" title="%s">', $field['id'], $field['label']);
            foreach ($row as $f => $sub) {
                if (have_rows($sub['name'])) {
                    $subRows = array();
                    while (have_rows($sub['name'])) {
                        the_row();
                        $subRows[] = get_row(true);
                    }
                    $sub['value'] = $layouts[$sub['layout']]($sub, $subRows);
                }
                $meta .= sprintf(
                    '<dt class="%s" style="font-weight: bold">%s</dt><dd style="%s">%s</dd>',
                    $sub['name'],
                    $sub['label'],
                    'white-space: pre',
                    $sub['value']
                );
            }
            $meta .= '</dl><hr/>';
        }
        return $meta;
    },
);

add_filter('woocommerce_email_after_order_table', function ($order, $sent_to_admin, $plaintext, $email) use ($layouts) {
    $groups = acf_get_field_groups(array('post_id' => $order->id));
    foreach ($groups as $g => $group) {
        $meta .= sprintf('<h2 class="%s">%s</h2>', $group['key'], $group['title']);
        $meta .= '<dl>';
        $fields = acf_get_fields($group);
        foreach ($fields as $f => $field) {
            $meta .= sprintf('<dt class="%s" style="font-weight: bold">%s</dt>', $field['name'], $field['label']);
            if (have_rows($field['name'], $order->id)) {
                $rows = array();
                while (have_rows($field['name'], $order->id)) {
                    the_row();
                    $row = get_row(true);
                    foreach ($row as $x => $value) {
                        $row[$x] = get_sub_field_object($x);
                        $row[$x]['value'] = $value;
                    }
                    $rows[] = $row;
                }
                $field['value'] = $layouts[$field['layout']]($field, $rows);
            }
            $meta .= sprintf(
                '<dd class="%s" style="white-space: pre">%s</dd>',
                $field['name'],
                $field['value'] ?: $field['default_value']
            );
        }
        $meta .= '</dl>';
        echo $meta;
    }
}, 10, 4);
