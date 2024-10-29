<?php

add_action('load-post.php', 'aza_forms_meta_boxes_setup');
add_action('load-post-new.php', 'aza_forms_meta_boxes_setup');

function aza_forms_meta_boxes_setup() {
    add_action('add_meta_boxes', 'aza_forms_add_post_meta_boxes');
}

function aza_forms_add_post_meta_boxes() {
    add_meta_box(
            'aza-forms', // Unique ID
            esc_html__('AZEXO Analytics', 'aza'), // Title
            'aza_forms_meta_box', // Callback function
            'azf_submission', // Admin page (or post type)
            'normal', // Context
            'high'         // Priority
    );
}

function aza_forms_meta_box($post) {
    wp_enqueue_script('maskedinput', AZA_URL . '/js/jquery.maskedinput.js', array('jquery'), false, true);
    $settings = get_option('aza-settings');
    $lead_statuses = array(
        "initial_contact" => __('Initial contact', 'aza'),
        "offer_made" => __('Offer made', 'aza'),
        "negotiation" => __('Negotiation', 'aza'),
        "contract_negotiation" => __('Contract negotiation', 'aza'),
        "closed_won" => __('Closed - won', 'aza'),
        "closed_lost" => __('Closed - lost', 'aza'),
    );
    ?>

    <?php wp_nonce_field(basename(__FILE__), 'aza_forms_nonce'); ?>
    <table class="form-table">
        <tbody>
            <tr>
                <th>
                    <?php esc_html_e('Lead status', 'aze'); ?>
                </th>
                <td>
                    <select name="_status">
                        <?php
                        foreach ($lead_statuses as $status => $label) {
                            ?>
                            <option value="<?php print $status; ?>" <?php selected($status, get_post_meta($post->ID, '_status', true)); ?>><?php print $label; ?></option>
                            <?php
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>
                    <?php esc_html_e('Lead first cost', 'aze'); ?>
                </th>
                <td>
                    <input type="number" step="0.01" name="_first_cost" value="<?php echo esc_attr(get_post_meta($post->ID, '_first_cost', true)); ?>" />
                </td>
            </tr>
            <tr>
                <th>
                    <?php esc_html_e('Lead amount', 'aze'); ?>
                </th>
                <td>
                    <input type="number" step="0.01" name="_amount" value="<?php echo esc_attr(get_post_meta($post->ID, '_amount', true)); ?>" />
                </td>
            </tr>
            <tr>
                <th>
                    <?php esc_html_e('Promo code', 'aze'); ?>
                    <br>                    
                    <small><?php esc_html_e('If lead created by manager', 'aze'); ?></small>
                </th>
                <td>
                    <input type="number" name="_promo_code" value="<?php echo esc_attr(get_post_meta($post->ID, '_promo_code', true)); ?>" />
                </td>
            </tr>
            <tr>
                <th>
                    <?php esc_html_e('Phone', 'aze'); ?>
                    <br>                    
                    <small><?php esc_html_e('If lead created via calltracking', 'aze'); ?></small>
                </th>
                <td>
                    <input type="text" name="phone" mask="<?php print $settings['phone-mask']; ?>" value="<?php echo esc_attr(get_post_meta($post->ID, 'phone', true)); ?>" />
                </td>
            </tr>
        </tbody>
    </table>
    <script>
        jQuery(function() {
            jQuery('input[mask]').each(function() {
                jQuery(this).mask(jQuery(this).attr('mask'));
            });
        });
    </script>
    <?php
}

add_action('save_post', 'aza_forms_save_post', 10, 2);

function aza_forms_save_post($post_id, $post) {

    if (!isset($_POST['aza_forms_nonce']) || !wp_verify_nonce($_POST['aza_forms_nonce'], basename(__FILE__))) {
        return $post_id;
    }

    $post_type = get_post_type_object($post->post_type);

    if (!current_user_can($post_type->cap->edit_post, $post_id)) {
        return $post_id;
    }

    $meta_keys = array('_status', '_first_cost', '_amount', '_promo_code', 'phone');
    foreach ($meta_keys as $meta_key) {
        $new_meta_value = ( isset($_POST[$meta_key]) ? sanitize_text_field($_POST[$meta_key]) : '' );

        $meta_value = get_post_meta($post_id, $meta_key, true);

        if ($new_meta_value && '' == $meta_value) {
            add_post_meta($post_id, $meta_key, $new_meta_value, true);
        } elseif ($new_meta_value && $new_meta_value != $meta_value) {
            update_post_meta($post_id, $meta_key, $new_meta_value);
        } elseif ('' == $new_meta_value && $meta_value) {
            delete_post_meta($post_id, $meta_key, $meta_value);
        }
    }
}

add_action('azf_process_form', 'aza_forms_process_form', 10, 3);

function aza_forms_process_form($response, $form_settings, $lead_id) {
    aza_submit_lead($lead_id);
    return $response;
}

add_action('update_post_metadata', 'aza_forms_update_post_metadata', 10, 5);

function aza_forms_update_post_metadata($check, $object_id, $meta_key, $meta_value, $prev_value) {
    if (defined('AZF_VERSION')) {
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
            if ($lead && $lead->post_type == 'azf_submission') {
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

                    $wpdb->query("REPLACE INTO {$wpdb->prefix}aza_leads (promo_code, visitor_id, lead_timestamp, lead_id, lead_page_id, lead_page_author, type, status, first_cost, amount) VALUES ({$meta_value}, '$visitor_id', $lead_timestamp, $object_id, 0, {$lead->post_author}, 'promo_code', '{$statuses_map[$status]}', $first_cost, $amount)");
                    aza_refresh_leads_info($lead->post_author, $visitor_id);
                }
            }
        }
    }
    return $check;
}

add_action('aza_calltracking_call', 'aza_forms_calltracking_call', 10, 1);

function aza_forms_calltracking_call($call_visit) {
    if (defined('AZF_VERSION')) {
        global $wpdb, $aza_multi_user;
        $lead = $wpdb->query("SELECT * FROM {$wpdb->prefix}aza_leads WHERE " . ($aza_multi_user ? "lead_page_author = {$call_visit['landing_page_author']} AND" : "") . " promo_code = {$call_visit['promo_code']} AND type = 'calltracking'");
        if (empty($lead)) {
            $lead_id = wp_insert_post(array(
                'post_title' => __('Calltracking', 'aza'),
                'post_type' => 'azf_submission',
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
