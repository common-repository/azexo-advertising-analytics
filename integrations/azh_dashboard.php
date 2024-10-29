<?php

add_action('azd_process_form', 'aza_process_form', 10, 2);

function aza_process_form($lead_id, $lead_page_id) {
    global $wpdb;
    $visitor_id = aza_get_visitor_id();
    $visit = aza_get_last_visit($visitor_id);
    $lead_timestamp = time();
    $lead_page = get_post($lead_page_id);
    $wpdb->query("REPLACE INTO {$wpdb->prefix}aza_leads (promo_code, visitor_id, lead_timestamp, lead_id, lead_page_id, lead_page_author, type, status, first_cost, amount) VALUES ({$visit['promo_code']}, '$visitor_id', $lead_timestamp, $lead_id, $lead_page_id, {$lead_page->post_author}, 'azh', 'in_work', 0, 0)");
    update_post_meta($lead_id, '_aza-visitor', $visitor_id);
    aza_refresh_leads_info($lead_page->post_author, $visitor_id);
}

add_action('update_post_metadata', 'aza_update_post_metadata', 10, 5);

function aza_update_post_metadata($check, $object_id, $meta_key, $meta_value, $prev_value) {
    if (defined('AZD_VERSION')) {
        if (in_array($meta_key, array('_first_cost', '_status', '_amount', '_promo_code'))) {
            global $wpdb;
            $statuses_map = array(
                "initial_contact" => 'in_work',
                "offer_made" => 'in_work',
                "negotiation" => 'in_work',
                "contract_negotiation" => 'in_work',
                "closed_won" => 'paid',
                "closed_lost" => 'canceled',
            );
            $lead = get_post($object_id);
            if ($lead && $lead->post_type == 'azd_lead') {
                $visitor_id = get_post_meta($object_id, '_aza-visitor', true);
                if ($visitor_id) {
                    if ($meta_key == '_status') {
                        $wpdb->query("UPDATE {$wpdb->prefix}aza_leads SET status = '{$statuses_map[$meta_value]}' WHERE visitor_id = '$visitor_id' AND lead_id = $object_id AND (type = 'azh' OR type = 'calltracking' OR type = 'promo_code')");
                        aza_refresh_leads_info($lead->post_author, $visitor_id);
                    }
                    if ($meta_key == '_amount') {
                        $wpdb->query("UPDATE {$wpdb->prefix}aza_leads SET amount = $meta_value WHERE visitor_id = '$visitor_id' AND lead_id = $object_id AND (type = 'azh' OR type = 'calltracking' OR type = 'promo_code')");
                        aza_refresh_leads_info($lead->post_author, $visitor_id);
                    }
                    if ($meta_key == '_first_cost') {
                        $wpdb->query("UPDATE {$wpdb->prefix}aza_leads SET first_cost = $meta_value WHERE visitor_id = '$visitor_id' AND lead_id = $object_id AND (type = 'azh' OR type = 'calltracking' OR type = 'promo_code')");
                        aza_refresh_leads_info($lead->post_author, $visitor_id);
                    }
                }
                if ($meta_key == '_promo_code') {
                    $visit = aza_get_visit($meta_value);
                    $visitor_id = $visit['visitor_id'];
                    update_post_meta($object_id, '_aza-visitor', $visitor_id);
                    $status = get_post_meta($object_id, '_status', true);
                    $amount = (float) get_post_meta($object_id, '_amount', true);
                    $first_cost = (float) get_post_meta($object_id, '_first_cost', true);

                    $date = new DateTime($lead->post_date, aza_timezone());
                    $lead_timestamp = $date->getTimestamp();

                    $wpdb->query("REPLACE INTO {$wpdb->prefix}aza_leads (promo_code, visitor_id, lead_timestamp, lead_id, lead_page_id, lead_page_author, type, status, first_cost, amount) VALUES ({$meta_value}, '$visitor_id', $lead_timestamp, $object_id, 0, {$lead->post_author}, 'promo_code', {$statuses_map[$status]}, $first_cost, $amount)");
                    aza_refresh_leads_info($lead->post_author, $visitor_id);
                }
            }
        }
    }
    return $check;
}

add_action('aza_calltracking_call', 'azd_calltracking_call', 10, 1);

function azd_calltracking_call($call_visit) {
    if (defined('AZD_VERSION')) {
        global $wpdb, $aza_multi_user;
        $lead = $wpdb->query("SELECT * FROM {$wpdb->prefix}aza_leads WHERE " . ($aza_multi_user ? "lead_page_author = {$call_visit['landing_page_author']} AND" : "") . " promo_code = {$call_visit['promo_code']} AND type = 'calltracking'");
        if (empty($lead)) {
            $lead_id = wp_insert_post(array(
                'post_title' => __('Calltracking', 'aza'),
                'post_type' => 'azd_lead',
                'post_status' => 'publish',
                'post_author' => $call_visit['landing_page_author'],
                'post_parent' => $call_visit['landing_page_id'],
                    ), true);
            if (!is_wp_error($lead_id)) {
                update_post_meta($lead_id, '_hash', uniqid());
                update_post_meta($lead_id, '_status', 'initial_contact');
                update_post_meta($lead_id, 'form_title', 'calltracking');
                update_post_meta($lead_id, 'phone', $call_visit['caller_phone']);
                update_post_meta($lead_id, '_aza-visitor', $call_visit['visitor_id']);
                
                $wpdb->query("REPLACE INTO {$wpdb->prefix}aza_leads (promo_code, visitor_id, lead_timestamp, lead_id, lead_page_id, lead_page_author, type, status, first_cost, amount) VALUES ('{$call_visit['promo_code']}', '{$call_visit['visitor_id']}', {$call_visit['call_timestamp']}, $lead_id, 0, {$call_visit['landing_page_author']}, 'calltracking', 'in_work', 0, 0)");
            }
        }
    }
}
