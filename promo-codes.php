<?php

add_action('aza_menu', 'aza_promo_codes_menu');

function aza_promo_codes_menu() {
    ?>
    <div class="aza-item" data-class="aza-promo-codes">
        <span><?php _e('Promo codes', 'aza'); ?></span>
    </div>
    <?php
}

add_action('aza_dialogs', 'aza_promo_codes_dialogs');

function aza_promo_codes_dialogs() {
    ?>
    <div class="aza-item aza-promo-codes">
        <div class="aza-row">
            <div class="aza-col-sm-12">
                <div class="aza-panel">
                    <div class="aza-panel-header">
                        <span><?php _e('Promo codes', 'aza'); ?></span>
                    </div>
                    <div class="aza-panel-content">
                        <form class="aza-add-promo-code">
                            <div class="aza-field">
                                <label><?php _e('Promo code', 'aza'); ?></label>
                                <input type="number" name="promo_code" placeholder="<?php _e('Promo code', 'aza'); ?>">
                            </div>
                            <div>
                                <button><?php _e('Add', 'aza'); ?></button>
                            </div>
                        </form>

                    </div>                        
                </div>

            </div>
        </div>
    </div>                                

    <?php
}





add_action('wp_ajax_aza_add_promo_codes_call', 'aza_add_promo_codes_call');

function aza_add_promo_codes_call() {
    if (isset($_POST['promo_code'])) {
        if (is_user_logged_in()) {
            global $wpdb, $aza_multi_user;
            $user_id = get_current_user_id();
            $promo_code = sanitize_text_field($_POST['promo_code']);
            $visit = $wpdb->get_row("SELECT * FROM  {$wpdb->prefix}aza_visits_last WHERE " . ($aza_multi_user ? "landing_page_author = $user_id AND" : "") . " promo_code=$promo_code AND v.visit_timestamp < $call_timestamp", ARRAY_A);
            if (!empty($visit)) {
                do_action('aza_add_promo_code', $visit);
                print '1';
            } else {
                print __('Visit not found for this promo code', 'aza');
            }
        }
    }
    wp_die();
}