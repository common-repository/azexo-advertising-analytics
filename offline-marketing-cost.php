<?php
add_action('aza_activate', 'aza_omc_activate');

function aza_omc_activate() {
    global $wpdb;
    $collate = '';

    if ($wpdb->has_cap('collation')) {
        $collate = $wpdb->get_charset_collate();
    }
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aza_offline_marketing_costs (
                user_id bigint(20) unsigned NOT NULL,
                channel varchar(100),
                from_date int(11) unsigned NOT NULL,
                to_date int(11) unsigned NOT NULL,
                cost decimal(10,2) unsigned,                
                UNIQUE KEY cost (user_id, channel, from_date, to_date)
    ) $collate;");
}

add_action('aza_menu', 'aza_omc_menu');

function aza_omc_menu() {
    ?>
    <div class="aza-item" data-class="aza-marketing-costs">
        <span><?php _e('Enter marketing costs', 'aza'); ?></span>
    </div>
    <?php
}

add_action('aza_dialogs', 'aza_omc_dialogs');

function aza_omc_dialogs() {
    ?>
    <div class="aza-item aza-marketing-costs">
        <div class="aza-row">
            <div class="aza-col-sm-12">
                <div class="aza-panel">
                    <div class="aza-panel-header">
                        <span><?php _e('Marketing costs history', 'aza'); ?></span>
                    </div>
                    <div class="aza-panel-content">
                        <form class="aza-add-marketing-costs">
                            <div class="aza-field">
                                <label><?php _e('Period', 'aza'); ?></label>
                                <div class="aza-date-range">
                                    <input name="from" class="aza-min" type="text" placeholder="<?php _e('From', 'aza'); ?>">
                                    <input name="to" class="aza-max" type="text" placeholder="<?php _e('To', 'aza'); ?>">
                                </div>                 
                            </div>
                            <div class="aza-field">
                                <label><?php _e('Marketing channel', 'aza'); ?></label>
                                <select name="channel">
                                    <?php
                                    global $wpdb, $aza_multi_user;
                                    $user_id = get_current_user_id();
                                    $values = array();
                                    $marker_level_1 = $wpdb->get_col("SELECT DISTINCT marker_level_1 FROM {$wpdb->prefix}aza_visits_last WHERE " . ($aza_multi_user ? "landing_page_author = $user_id AND" : "") . " marker_level_1 IS NOT NULL AND marker_level_1 != '' ");
                                    foreach ($marker_level_1 as $value) {
                                        $values[$value] = true;
                                    }
                                    $utm_source = $wpdb->get_col("SELECT DISTINCT utm_source FROM {$wpdb->prefix}aza_visits_last WHERE " . ($aza_multi_user ? "landing_page_author = $user_id AND" : "") . " utm_source IS NOT NULL AND utm_source != ''");
                                    foreach ($utm_source as $value) {
                                        $values[$value] = true;
                                    }
                                    $values = array_keys($values);
                                    foreach ($values as $value) {
                                        print "<option value='$value'>$value</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="aza-field">
                                <label><?php _e('Costs', 'aza'); ?></label>
                                <input type="number" name="cost" placeholder="<?php _e('Costs', 'aza'); ?>">
                            </div>
                            <div>
                                <button><?php _e('Add', 'aza'); ?></button>
                            </div>
                        </form>
                        <table>
                            <thead>
                                <tr>
                                    <th>
                                        <?php _e('Period', 'aza'); ?>
                                    </th>
                                    <th>
                                        <?php _e('Marketing channel', 'aza'); ?>
                                    </th>
                                    <th>
                                        <?php _e('Costs', 'aza'); ?>
                                    </th>
                                    <th>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        21.03.1999
                                    </td>
                                    <td>
                                        channel
                                    </td>
                                    <td>
                                        30
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

function aza_fill_offline_marketing_cost($user_id, $channel, $from, $to, $cost) {
    global $wpdb, $aza_models, $aza_multi_user;
    $count = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}aza_visits_last WHERE " . ($aza_multi_user ? "landing_page_author = $user_id AND" : "") . " (marker_level_1='$channel' OR utm_source='$channel') AND visit_timestamp BETWEEN $from AND $to");
    if ($count) {
        $mc = $cost / $count;
        foreach (array_keys($aza_models) as $model) {
            $wpdb->query("UPDATE {$wpdb->prefix}aza_visits_{$model} SET offline_marketing_cost = (coalesce(offline_marketing_cost, 0) + ($mc)) WHERE " . ($aza_multi_user ? "landing_page_author = $user_id AND" : "") . " (marker_level_1='$channel' OR utm_source='$channel') AND visit_timestamp BETWEEN $from AND $to");
        }
    }
}

add_action('wp_ajax_aza_get_offline_marketing_costs', 'aza_get_offline_marketing_costs');

function aza_get_offline_marketing_costs() {
    if (is_user_logged_in()) {
        global $wpdb;
        $user_id = get_current_user_id();
        $marketing_costs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aza_offline_marketing_costs WHERE user_id=$user_id", ARRAY_A);
        foreach ($marketing_costs as &$marketing_cost) {
            $marketing_cost['period'] = date_i18n(get_option('date_format'), $marketing_cost['from_date'] + ( get_option('gmt_offset') * HOUR_IN_SECONDS )) . ' - ' . date_i18n(get_option('date_format'), $marketing_cost['to_date'] + ( get_option('gmt_offset') * HOUR_IN_SECONDS ));
        }
        print json_encode($marketing_costs);
    }
    wp_die();
}

add_action('wp_ajax_aza_add_offline_marketing_cost', 'aza_add_offline_marketing_cost');

function aza_add_offline_marketing_cost() {
    if (isset($_POST['channel']) && isset($_POST['from']) && isset($_POST['to']) && isset($_POST['cost'])) {
        if (is_user_logged_in()) {
            global $wpdb;
            $user_id = get_current_user_id();
            $channel = sanitize_text_field($_POST['channel']);
            $from = sanitize_text_field($_POST['from']);
            $to = sanitize_text_field($_POST['to']);
            $cost = sanitize_text_field($_POST['cost']);
            $wpdb->query("REPLACE INTO {$wpdb->prefix}aza_offline_marketing_costs (user_id, channel, from_date, to_date, cost) VALUES ($user_id, '$channel', $from, $to, $cost)");
            aza_fill_offline_marketing_cost($user_id, $channel, $from, $to, $cost);
        }
    }
    wp_die();
}

add_action('wp_ajax_aza_remove_offline_marketing_cost', 'aza_remove_offline_marketing_cost');

function aza_remove_offline_marketing_cost() {
    if (isset($_POST['channel']) && isset($_POST['from']) && isset($_POST['to'])) {
        if (is_user_logged_in()) {
            global $wpdb;
            $user_id = get_current_user_id();
            $channel = sanitize_text_field($_POST['channel']);
            $from = sanitize_text_field($_POST['from']);
            $to = sanitize_text_field($_POST['to']);
            $cost = $wpdb->get_var("SELECT cost FROM {$wpdb->prefix}aza_offline_marketing_costs WHERE user_id=$user_id AND channel='$channel' AND from_date=$from AND to_date=$to");
            $wpdb->query("DELETE FROM {$wpdb->prefix}aza_offline_marketing_costs WHERE user_id=$user_id AND channel='$channel' AND from_date=$from AND to_date=$to");
            aza_fill_offline_marketing_cost($user_id, $channel, $from, $to, -$cost);
        }
    }
    wp_die();
}
