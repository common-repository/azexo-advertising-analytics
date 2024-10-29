<?php
global $aza_woocommerce_events;
$aza_woocommerce_events = array(
    'woo_product_page_visit' => __('Product page visit', 'aza'),
    'woo_added_to_cart' => __('Added to cart', 'aza'),
    'woo_cart_page_visit' => __('Cart page visit', 'aza'),
    'woo_checkout_page_visit' => __('Checkout page visit', 'aza'),
);

add_action('woocommerce_checkout_update_order_meta', 'aza_woocommerce_checkout_update_order_meta', 10, 2);

function aza_woocommerce_checkout_update_order_meta($order_id, $data) {
    global $wpdb;
    $visitor_id = aza_get_visitor_id();
    $visit = aza_get_last_visit($visitor_id);
    $lead_timestamp = time();
    $lead_page_id = get_the_ID();
    $lead_page = get_post($lead_page_id);

    $order = new WC_Order($order_id);
    $amount = $order->get_total();
    $first_cost = NULL;
    $items = $order->get_items();
    foreach ($items as $item_id => $item) {
        if ($item['type'] == 'line_item') {
            $fc = get_post_meta($item['product_id'], '_first_cost', true);
            if ($fc !== false) {
                $first_cost += (float) $fc * $item['qty'];
            }
        }
    }
    $wpdb->query("REPLACE INTO {$wpdb->prefix}aza_leads (promo_code, visitor_id, lead_timestamp, lead_id, lead_page_id, lead_page_author, type, status, first_cost, amount) VALUES ({$visit['promo_code']}, '$visitor_id', $lead_timestamp, $order_id, $lead_page_id, {$lead_page->post_author}, 'woocommerce', 'in_work', $first_cost, $amount)");


    $order->add_meta_data('_aza-visitor', $visitor_id, true);
    $order->add_meta_data('_aza-lead-page-id', $lead_page_id, true);
    $order->save_meta_data();
    if (!is_null($first_cost)) {
        $first_cost = (float) $first_cost;
        $wpdb->query("UPDATE {$wpdb->prefix}aza_leads SET first_cost = $first_cost WHERE visitor_id = '$visitor_id' AND lead_id = $order_id AND type = 'woocommerce'");
    }
    aza_refresh_leads_info($lead_page->post_author, $visitor_id);
}

add_action('woocommerce_order_status_changed', 'aza_woocommerce_order_status_changed', 10, 4);

function aza_woocommerce_order_status_changed($order_id, $from, $to, $order) {
    global $wpdb;
    $order = new WC_Order($order_id);
    $visitor_id = $order->get_meta('_aza-visitor');
    if ($visitor_id) {
        $woo_statuses_map = array(
            'trash' => 'trash',
            'pending' => 'in_work',
            'processing' => 'in_work',
            'on-hold' => 'in_work',
            'completed' => 'paid',
            'cancelled' => 'canceled',
            'refunded' => 'canceled',
            'failed' => 'canceled',
        );
        if(isset($woo_statuses_map[$to])) {
            $wpdb->query("UPDATE {$wpdb->prefix}aza_leads SET status = '{$woo_statuses_map[$to]}' WHERE visitor_id = '$visitor_id' AND lead_id = $order_id AND type = 'woocommerce'");
        } else {
            $wpdb->query("UPDATE {$wpdb->prefix}aza_leads SET status = 'in_work' WHERE visitor_id = '$visitor_id' AND lead_id = $order_id AND type = 'woocommerce'");
        }        
        $lead_page_id = $order->get_meta('_aza-lead-page-id');
        if ($lead_page_id) {
            $lead_page = get_post($lead_page_id);
            if ($from == 'completed') {
                aza_refresh_leads_info($lead_page->post_author, $visitor_id);
            }
            if ($to == 'completed') {
                aza_refresh_leads_info($lead_page->post_author, $visitor_id);
            }
        }
    }
}

add_action('woocommerce_payment_complete', 'aza_woocommerce_payment_complete');

function aza_woocommerce_payment_complete($order_id) {
    global $wpdb;
    $order = new WC_Order($order_id);
    $visitor_id = $order->get_meta('_aza-visitor');
    if ($visitor_id) {
        $amount = $order->get_total();
        $wpdb->query("UPDATE {$wpdb->prefix}aza_leads SET status = 'paid', amount = $amount WHERE visitor_id = '$visitor_id' AND lead_id = $order_id AND type = 'woocommerce'");

        $lead_page_id = $order->get_meta('_aza-lead-page-id');
        if ($lead_page_id) {
            $lead_page = get_post($lead_page_id);
            aza_refresh_leads_info($lead_page->post_author, $visitor_id);
        }
    }
}

//add_action('wp', 'aza_woocommerce_page_event');

function aza_woocommerce_page_event() {
    global $aza_woocommerce_events;
    $new_event = false;
    if (function_exists('is_product') && is_product()) {
        $new_event = 'woo_product_page_visit';
    }
    if (function_exists('is_cart') && is_cart()) {
        $new_event = 'woo_cart_page_visit';
    }
    if (function_exists('is_checkout') && is_checkout()) {
        $new_event = 'woo_checkout_page_visit';
    }
    if ($new_event) {
        $visitor_id = aza_get_visitor_id();
        $last_visit = aza_get_last_visit($visitor_id);
        $events = array_keys($aza_woocommerce_events);
        if (array_search($last_visit['event'], $events) !== false) {
            if (array_search($new_event, $events) > array_search($last_visit['event'], $events)) {
            }
        }
    }
}

//add_action('woocommerce_add_to_cart', 'aza_woocommerce_add_to_cart', 10, 6);

function aza_woocommerce_add_to_cart() {
    global $aza_woocommerce_events;
    $visitor_id = aza_get_visitor_id();
    if ($visitor_id) {
        $last_visit = aza_get_last_visit($visitor_id);
        if (!empty($last_visit)) {
            $events = array_keys($aza_woocommerce_events);
            if (array_search($last_visit['event'], $events) !== false) {
                if (array_search('woo_added_to_cart', $events) > array_search($last_visit['event'], $events)) {
                }
            }
        }
    }
}

add_filter('woocommerce_product_data_tabs', 'aza_product_data_tabs');

function aza_product_data_tabs($product_data_tabs) {

    $product_data_tabs['AZA'] = array(
        'label' => __('AZEXO Analytics', 'aza'),
        'target' => 'aza_product_data',
        'class' => array('show_if_simple'),
    );

    return $product_data_tabs;
}

add_action('woocommerce_product_data_panels', 'aza_data_panels');

function aza_data_panels() {
    ?>
    <div id="aza_product_data" class="panel woocommerce_options_panel"><div class="options_group">
            <?php
            woocommerce_wp_text_input(
                    array(
                        'id' => '_first_cost',
                        'label' => __('First cost', 'aza'),
                        'placeholder' => '',
                        'desc_tip' => 'true',
                        'description' => __('First product cost.', 'aza'),
                        'data_type' => 'decimal'
            ));
            ?>
        </div></div>
    <?php
}

add_action('woocommerce_process_product_meta', 'aza_save_custom_settings');

function aza_save_custom_settings($post_id) {
    if (isset($_POST['_first_cost'])) {
        update_post_meta($post_id, '_first_cost', sanitize_text_field($_POST['_first_cost']));
    }
}
