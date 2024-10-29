<?php
add_action('aza_activate', 'aza_calltracking_activate');

function aza_calltracking_activate() {
    global $wpdb;
    $collate = '';

    if ($wpdb->has_cap('collation')) {
        $collate = $wpdb->get_charset_collate();
    }
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aza_calltracking (
                promo_code bigint(10) unsigned NOT NULL,
                phone varchar(100),
                caller_phone varchar(100),
                call_timestamp int(11) unsigned,
                UNIQUE KEY promo_code (promo_code),
                KEY caller_phone (caller_phone),
                KEY phone (phone)
    ) $collate;");
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aza_calltracking_phones (
                user_id bigint(20) unsigned NOT NULL,
                phone varchar(100),
                UNIQUE KEY phone (user_id, phone)
    ) $collate;");
    if (!empty($wpdb->is_mysql) && version_compare($wpdb->db_version(), '5.5')) {
//        $wpdb->query("ALTER TABLE {$wpdb->prefix} ADD INDEX(meta_value(30))");
//        $wpdb->query("ALTER TABLE {$wpdb->prefix} ADD FULLTEXT(comment_content)");
    }
}

add_action('aza_menu', 'aza_calltracking_menu');

function aza_calltracking_menu() {
    ?>
    <div class="aza-item" data-class="aza-calltracking">
        <span><?php _e('Calltracking', 'aza'); ?></span>
    </div>
    <?php
}

add_action('aza_dialogs', 'aza_calltracking_dialogs');

function aza_calltracking_dialogs() {
    ?>
    <div class="aza-item aza-calltracking">
        <div class="aza-row">
            <div class="aza-col-sm-8">
                <div class="aza-panel">
                    <div class="aza-panel-header">
                        <span><?php _e('Calls', 'aza'); ?></span>
                    </div>
                    <div class="aza-panel-content">
                        <form class="aza-add-call">
                            <div class="aza-field aza-date-time">
                                <label><?php _e('Date and time (in 24 hours format)', 'aza'); ?></label>
                                <div>
                                    <input name="date" type="text" class="aza-date" placeholder="<?php _e('Date', 'aza'); ?>">
                                    <input name="time" type="text" class="aza-time" pattern="^([01]\d|2[0-3]):?([0-5]\d)$" placeholder="<?php _e('Time', 'aza'); ?>">
                                </div>                                
                            </div>
                            <div class="aza-field">
                                <label><?php _e('Your phone', 'aza'); ?></label>
                                <select name="phone">
                                    <?php
                                    global $wpdb;
                                    $user_id = get_current_user_id();
                                    $phones = $wpdb->get_results("SELECT phone FROM {$wpdb->prefix}aza_calltracking_phones WHERE user_id = $user_id ORDER BY phone", ARRAY_A);
                                    foreach ($phones as $phone) {
                                        print "<option value='{$phone['phone']}'>{$phone['phone']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="aza-field">
                                <label><?php _e('Caller phone', 'aza'); ?></label>
                                <input type="tel" name="caller_phone" placeholder="<?php _e('Caller phone', 'aza'); ?>">
                            </div>
                            <div>
                                <button><?php _e('Add', 'aza'); ?></button>
                            </div>
                        </form>
                        <table class="aza-calls">
                            <thead>
                                <tr>
                                    <th>
                                        <?php _e('Promo code', 'aza'); ?>
                                    </th>
                                    <th>
                                        <?php _e('Date and time', 'aza'); ?>
                                    </th>
                                    <th>
                                        <?php _e('Your phone', 'aza'); ?>
                                    </th>
                                    <th>
                                        <?php _e('Caller phone', 'aza'); ?>
                                    </th>
                                    <th>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        2103-1999
                                    </td>
                                    <td>
                                        2103-1999
                                    </td>
                                    <td>
                                        2103-1999
                                    </td>
                                    <td>
                                        2103-1999
                                    </td>
                                    <td class="aza-remove">
                                    </td>
                                </tr>                            
                            </tbody>
                        </table>

                    </div>                        
                </div>

            </div>
            <div class="aza-col-sm-4">
                <div class="aza-panel">
                    <div class="aza-panel-header">
                        <span><?php _e('Your default phone', 'aza'); ?></span>
                    </div>
                    <div class="aza-panel-content">
                        <form class="aza-set-default-phone">
                            <div class="aza-field">
                                <label><?php _e('Phone number for non-advertising visits', 'aza'); ?></label>
                                <input type="tel" name="phone_number" value="<?php print get_user_meta(get_current_user_id(), 'aza-phone', true); ?>" placeholder="<?php _e('Phone number', 'aza'); ?>">
                            </div>
                            <div>
                                <button><?php _e('Set', 'aza'); ?></button>
                            </div>
                        </form>
                    </div>                        
                </div>
                <div class="aza-panel">
                    <div class="aza-panel-header">
                        <span><?php _e('Your phone numbers for calltracking', 'aza'); ?></span>
                    </div>
                    <div class="aza-panel-content">
                        <form class="aza-add-phone-number">
                            <div class="aza-field">
                                <label><?php _e('Phone number', 'aza'); ?></label>
                                <input type="tel" name="phone_number" placeholder="<?php _e('Phone number', 'aza'); ?>">
                            </div>
                            <div>
                                <button><?php _e('Add', 'aza'); ?></button>
                            </div>
                        </form>
                        <table class="aza-phones">
                            <thead>
                                <tr>
                                    <th>
                                        <?php _e('Phone number', 'aza'); ?>
                                    </th>
                                    <th>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        2103-1999
                                    </td>
                                    <td class="aza-remove">
                                    </td>
                                </tr>                            
                            </tbody>
                        </table>
                    </div>                        
                </div>

            </div>
        </div>
    </div>
    <?php
}

add_filter('aza_counter_ajax', 'aza_calltracking_counter_ajax');

function aza_calltracking_counter_ajax($data) {
    $visitor_id = $data['visitor_id'];
    $visit = aza_get_last_visit($visitor_id);
    $phone = '';
    if (empty($visit) || (empty($visit['marker_level_1']) && empty($visit['utm_source']))) {
        $post = get_post($data['landing_page_id']);
        $phone = get_user_meta($post->post_author, 'aza-phone', true);
    } else {
        global $wpdb, $aza_multi_user;
        $calltracking = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}aza_calltracking WHERE promo_code={$visit['promo_code']}", ARRAY_A);
        if (empty($calltracking) || empty($calltracking['phone'])) {            
            $phones = $wpdb->get_results("SELECT phone FROM {$wpdb->prefix}aza_calltracking_phones " . ($aza_multi_user ? "WHERE user_id = {$visit['landing_page_author']}" : "") . " ORDER BY phone", ARRAY_A);
            $last_phone = $wpdb->get_var("SELECT phone FROM {$wpdb->prefix}aza_calltracking ORDER BY promo_code DESC LIMIT 0,1");
            if (empty($last_phone)) {
                $phone = $phones[0]['phone'];
            } else {
                for ($i = 0; $i < count($phones); $i++) {
                    if ($phones[$i]['phone'] == $last_phone) {
                        if (($i + 1) < count($phones)) {
                            $phone = $phones[$i + 1]['phone'];
                        } else {
                            $phone = $phones[0]['phone'];
                        }
                    }
                }
            }
            $wpdb->query("REPLACE INTO {$wpdb->prefix}aza_calltracking (promo_code, phone) VALUES ({$visit['promo_code']}, '$phone')");
        } else {
            $phone = $calltracking['phone'];
        }
    }
    $data['phone'] = $phone;
    return $data;
}

add_shortcode('aza_phone', 'aza_calltracking_phone');

function aza_calltracking_phone($atts) {
    ob_start();
    ?>
    <script>
        (function($) {
            $(function() {
                $(window).on("aza-counter", function() {
                    if ('phone' in aza) {
                        $('.aza-phone').text(aza.phone);
                    }
                });
            });
        })(window.jQuery);
    </script>
    <span class="aza-phone"></span>
    <?php
    return ob_get_clean();
}

add_action('wp_ajax_aza_get_calltracking_phones', 'aza_get_calltracking_phones');

function aza_get_calltracking_phones() {
    if (is_user_logged_in()) {
        global $wpdb;
        $user_id = get_current_user_id();
        $phones = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aza_calltracking_phones WHERE user_id=$user_id", ARRAY_A);
        print json_encode($phones);
    }
    wp_die();
}

add_action('wp_ajax_aza_add_calltracking_phone', 'aza_add_calltracking_phone');

function aza_add_calltracking_phone() {
    if (isset($_POST['phone'])) {
        if (is_user_logged_in()) {
            global $wpdb;
            $user_id = get_current_user_id();
            $phone = sanitize_text_field($_POST['phone']);
            $wpdb->query("REPLACE INTO {$wpdb->prefix}aza_calltracking_phones (user_id, phone) VALUES ($user_id, '$phone')");
        }
    }
    wp_die();
}

add_action('wp_ajax_aza_remove_calltracking_phone', 'aza_remove_calltracking_phone');

function aza_remove_calltracking_phone() {
    if (isset($_POST['phone'])) {
        if (is_user_logged_in()) {
            global $wpdb;
            $user_id = get_current_user_id();
            $phone = sanitize_text_field($_POST['phone']);
            $wpdb->query("DELETE FROM {$wpdb->prefix}aza_calltracking_phones WHERE user_id=$user_id AND phone='$phone'");
        }
    }
    wp_die();
}

add_action('wp_ajax_aza_get_calltracking_calls', 'aza_get_calltracking_calls');

function aza_get_calltracking_calls() {
    if (is_user_logged_in()) {
        global $wpdb;
        $user_id = get_current_user_id();
        $calls = $wpdb->get_results("SELECT c.* FROM {$wpdb->prefix}aza_calltracking as c INNER JOIN {$wpdb->prefix}aza_calltracking_phones as p ON p.phone=c.phone WHERE p.user_id=$user_id AND c.caller_phone IS NOT NULL ORDER BY promo_code DESC", ARRAY_A);
        foreach ($calls as &$call) {
            $call['call_datetime'] = date_i18n(get_option('date_format'), $call['call_timestamp'] + ( get_option('gmt_offset') * HOUR_IN_SECONDS )) . ' - ' . date_i18n(get_option('time_format'), $call['call_timestamp'] + ( get_option('gmt_offset') * HOUR_IN_SECONDS ));
        }
        print json_encode($calls);
    }
    wp_die();
}

add_action('wp_ajax_aza_add_calltracking_call', 'aza_add_calltracking_call');

function aza_add_calltracking_call() {
    if (isset($_POST['call_timestamp']) && isset($_POST['phone']) && isset($_POST['caller_phone'])) {
        if (is_user_logged_in()) {
            global $wpdb, $aza_multi_user;
            $user_id = get_current_user_id();
            $call_timestamp = sanitize_text_field($_POST['call_timestamp']);
            $phone = sanitize_text_field($_POST['phone']);
            $caller_phone = sanitize_text_field($_POST['caller_phone']);
            $call = $wpdb->get_row("SELECT c.*, v.* FROM {$wpdb->prefix}aza_calltracking as c INNER JOIN {$wpdb->prefix}aza_visits_last as v ON v.promo_code=c.promo_code WHERE " . ($aza_multi_user ? "v.landing_page_author = $user_id AND" : "") . " c.phone='$phone' AND v.visit_timestamp < $call_timestamp AND (($call_timestamp - v.visit_timestamp) < 43200) AND (($call_timestamp - v.visit_timestamp) > 10) ORDER BY visit_timestamp DESC LIMIT 0,1", ARRAY_A);
            if (!empty($call) && is_null($call['caller_phone'])) {
                $wpdb->query("UPDATE {$wpdb->prefix}aza_calltracking SET caller_phone = '$caller_phone', call_timestamp = $call_timestamp WHERE promo_code={$call['promo_code']}");
                $call['caller_phone'] = $caller_phone;
                $call['call_timestamp'] = $call_timestamp;
                do_action('aza_calltracking_call', $call);
                print '1';
            } else {
                print __('Visit not found for this call', 'aza');
            }
        }
    }
    wp_die();
}

add_action('wp_ajax_aza_remove_calltracking_call', 'aza_remove_calltracking_call');

function aza_remove_calltracking_call() {
    if (isset($_POST['promo_code'])) {
        if (is_user_logged_in()) {
            global $wpdb;
            $user_id = get_current_user_id();
            $promo_code = sanitize_text_field($_POST['promo_code']);
            $call = $wpdb->get_row("SELECT c.* FROM {$wpdb->prefix}aza_calltracking as c INNER JOIN {$wpdb->prefix}aza_calltracking_phones as p ON p.phone=c.phone WHERE p.user_id=$user_id AND c.promo_code = $promo_code", ARRAY_A);
            if (!empty($call)) {
                $wpdb->query("UPDATE {$wpdb->prefix}aza_calltracking SET caller_phone = NULL, call_timestamp = NULL WHERE promo_code='$promo_code'");
            }
        }
    }
    wp_die();
}

add_action('wp_ajax_aza_set_default_phone', 'aza_set_default_phone');

function aza_set_default_phone() {
    if (isset($_POST['phone'])) {
        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'aza-phone', sanitize_text_field($_POST['phone']));
            print __('Saved', 'aza');
        }
    }
    wp_die();
}

add_action('wp_ajax_aza_calltracking_calls_datatable', 'aza_calltracking_calls_datatable');

function aza_calltracking_calls_datatable() {
    $user_id = get_current_user_id();
    if ($user_id) {
        if (isset($_POST['draw']) && is_numeric($_POST['draw']) && isset($_POST['start']) && is_numeric($_POST['start']) && isset($_POST['length']) && is_numeric($_POST['length'])) {
            global $wpdb;
            $search = 'AND c.caller_phone IS NOT NULL';
            if (!empty($_POST['search']['value'])) {
                $search = sanitize_text_field($_POST['search']['value']);
                $search = "AND c.caller_phone LIKE '%$search%'";
            }
            $start = sanitize_text_field($_POST['start']);
            $length = sanitize_text_field($_POST['length']);
            $calls = $wpdb->get_results("SELECT c.* FROM {$wpdb->prefix}aza_calltracking as c INNER JOIN {$wpdb->prefix}aza_calltracking_phones as p ON p.phone=c.phone WHERE p.user_id=$user_id $search ORDER BY promo_code DESC LIMIT $start,$length", ARRAY_A);
            $num_rows = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}aza_calltracking as c INNER JOIN {$wpdb->prefix}aza_calltracking_phones as p ON p.phone=c.phone WHERE p.user_id=$user_id $search");
            foreach ($calls as &$call) {
                $call['call_datetime'] = date_i18n(get_option('date_format'), $call['call_timestamp'] + ( get_option('gmt_offset') * HOUR_IN_SECONDS )) . ' - ' . date_i18n(get_option('time_format'), $call['call_timestamp'] + ( get_option('gmt_offset') * HOUR_IN_SECONDS ));
            }
            $data_calls = array();
            foreach ($calls as $call) {
                $data_call = array();
                foreach ($_POST['columns'] as $column) {
                    if (isset($call[$column['name']])) {
                        $data_call[] = $call[$column['name']];
                    }
                }
                $data_call[] = '';
                $data_calls[] = $data_call;
            }
            $data = array(
                'draw' => sanitize_text_field($_POST['draw']),
                'recordsTotal' => $num_rows,
                'recordsFiltered' => $num_rows,
                'data' => $data_calls,
            );
            print json_encode($data);
        }
    }
    wp_die();
}
