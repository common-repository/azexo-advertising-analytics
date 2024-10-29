<?php
/*
  Plugin Name: AZEXO Advertising Analytics
  Plugin URI: http://azexo.com/analytics
  Description: ROI statistic based on multiple advertising channels and site sales
  Author: azexo
  Author URI: http://azexo.com
  Version: 1.27.3
  Text Domain: aza
 */

define('AZA_VERSION', '1.26');
define('AZA_URL', plugins_url('', __FILE__));
define('AZA_DIR', trailingslashit(dirname(__FILE__)));
define('AZA_FILE', __FILE__);
global $aza_lead_statuses, $aza_models, $aza_search_engines, $aza_multi_user;
$aza_multi_user = false;
$aza_models = array(
    'last' => __('Effect from only last entrance', 'aza'),
    'first' => __('Effect from only first entrance', 'aza'),
    'linear' => __('Equal contribution from all entrances', 'aza'),
    'position' => __('U-shape contribution from all entrances', 'aza')
);
$aza_lead_statuses = array(
    'trash' => __('Trash', 'aza'),
    'in_work' => __('In work', 'aza'),
    'paid' => __('Paid', 'aza'),
    'canceled' => __('Canceled', 'aza'),
);
$aza_search_engines = array(
    'bing' => __('Bing', 'aza'),
    'yahoo' => __('Yahoo', 'aza'),
    'google' => __('Google', 'aza'),
    'yandex' => __('Yandex', 'aza'),
    'ask' => __('Ask', 'aza'),
    'baidu' => __('Baidu', 'aza'),
);


include_once(AZA_DIR . 'settings.php');
include_once(AZA_DIR . 'integrations/woocommerce.php');
include_once(AZA_DIR . 'integrations/azh_dashboard.php');
include_once(AZA_DIR . 'integrations/azh_forms.php');
if (file_exists(AZA_DIR . 'integrations/adwords.php')) {
    include_once(AZA_DIR . 'integrations/adwords.php');
}
if (file_exists(AZA_DIR . 'integrations/facebook.php')) {
    include_once(AZA_DIR . 'integrations/facebook.php');
}
if (file_exists(AZA_DIR . 'integrations/yandex.php')) {
    include_once(AZA_DIR . 'integrations/yandex.php');
}
if (file_exists(AZA_DIR . 'integrations/vk.php')) {
    include_once(AZA_DIR . 'integrations/vk.php');
}
include_once(AZA_DIR . 'offline-marketing-cost.php');
include_once(AZA_DIR . 'calltracking.php');
//include_once(AZA_DIR . 'promo-codes.php');
include_once(AZA_DIR . 'report.php');
//include_once(AZA_DIR . 'demo.php');

register_activation_hook(__FILE__, 'aza_activate');

function aza_activate() {
    global $wpdb, $aza_models;
    $collate = '';

    if ($wpdb->has_cap('collation')) {
        $collate = $wpdb->get_charset_collate();
    }

    do_action('aza_activate');


    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aza_leads (
                promo_code bigint(10) unsigned NOT NULL,
                visitor_id varchar(32) NOT NULL,
                lead_timestamp int(11) unsigned NOT NULL,
                lead_id bigint(20) unsigned NOT NULL,
                lead_page_id bigint(20) unsigned,
                lead_page_author bigint(20) unsigned NOT NULL,
                type varchar(20),
                status varchar(50),
                first_cost decimal(10,2) unsigned,
                amount decimal(10,2) unsigned,
                KEY promo_code (promo_code),
                UNIQUE KEY lead (lead_id, type)
    ) $collate;");


    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aza_targets (
                promo_code bigint(10) unsigned NOT NULL,
                visitor_id varchar(32) NOT NULL,
                target_timestamp int(11) unsigned NOT NULL,
                target_id bigint(20) unsigned NOT NULL,
                target_page_id bigint(20) unsigned,
                target_page_author bigint(20) unsigned NOT NULL,
                type varchar(20),
                KEY promo_code (promo_code),
                UNIQUE KEY target (target_id, type)
    ) $collate;");

    foreach (array_keys($aza_models) as $model) {
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aza_visits_{$model} (
                visitor_id varchar(32) NOT NULL,
                visit_timestamp int(11) unsigned NOT NULL,
                landing_page_id bigint(20) unsigned NOT NULL,
                landing_page_author bigint(20) unsigned NOT NULL,
                page_group_id bigint(20) unsigned,
                promo_code bigint(10) unsigned NOT NULL AUTO_INCREMENT,
                hour int(2) unsigned,
                day int(2) unsigned,
                day_of_week int(1) unsigned,
                week int(2) unsigned,
                month int(2) unsigned,
                marker_level_1 varchar(100),
                marker_level_2 varchar(100),
                marker_level_3 varchar(100),
                marker_level_4 varchar(100),
                marker_level_5 varchar(100),
                marker_level_6 varchar(100),
                marker_level_7 varchar(100),
                utm_source varchar(50),
                utm_medium varchar(50),
                utm_campaign varchar(50),
                utm_content varchar(50),
                utm_term varchar(50),
                leads_number decimal(10,2) unsigned,
                paid_leads_number decimal(10,2) unsigned,
                in_work_leads_number decimal(10,2) unsigned,
                canceled_leads_number decimal(10,2) unsigned,
                revenue decimal(10,2) unsigned,
                in_work_revenue decimal(10,2) unsigned,
                canceled_revenue decimal(10,2) unsigned,
                offline_marketing_cost decimal(10,2) unsigned,
                marketing_cost decimal(10,2) unsigned,
                first_cost decimal(10,2) unsigned,                
                UNIQUE KEY visit (visitor_id, visit_timestamp),
                UNIQUE KEY promo_code (promo_code),
                KEY visit_timestamp (visit_timestamp),
                KEY hour (hour),
                KEY day (day),
                KEY day_of_week (day_of_week),
                KEY week (week),
                KEY month (month),
                KEY landing_page_id (landing_page_id),
                KEY landing_page_author (landing_page_author),
                KEY page_group_id (page_group_id),
                KEY marker_level_1 (marker_level_1),
                KEY marker_level_2 (marker_level_2),
                KEY marker_level_3 (marker_level_3),
                KEY marker_level_4 (marker_level_4),
                KEY marker_level_5 (marker_level_5),
                KEY marker_level_6 (marker_level_6),
                KEY marker_level_7 (marker_level_7),
                KEY utm_source (utm_source),
                KEY utm_medium (utm_medium),
                KEY utm_campaign (utm_campaign),
                KEY utm_content (utm_content),
                KEY utm_term (utm_term)
    ) $collate;");
    }
}

add_action('plugins_loaded', 'aza_plugins_loaded');

function aza_plugins_loaded() {
    load_plugin_textdomain('aza', FALSE, basename(dirname(__FILE__)) . '/languages/');
}

add_action('wp_enqueue_scripts', 'aza_scripts');

function aza_scripts() {
    wp_enqueue_script('jquery');
}

add_action('wp_footer', 'aza_footer');

function aza_footer() {
    $id = get_the_ID();
    if (is_front_page()) {
        $id = get_option('page_on_front');
    }
    if (is_home()) {
        $id = get_option('page_for_posts');
    }
    if ($id) {
        ?>
        <script>
            (function ($) {
                $(function () {
                    window.aza = $.extend({}, window.aza);
                    aza.parse_query_string = function (a) {
                        if (a == "")
                            return {};
                        var b = {};
                        for (var i = 0; i < a.length; ++i)
                        {
                            var p = a[i].split('=');
                            if (p.length != 2)
                                continue;
                            b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
                        }
                        return b;
                    };
                    $.QueryString = aza.parse_query_string(window.location.search.substr(1).split('&'));
                    var data = $.QueryString;
                    data.action = 'aza_counter';
                    data.id = <?php print $id; ?>;
                    data.referer = '<?php print (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''); ?>';
                    $.post('<?php print admin_url('admin-ajax.php'); ?>', data, function (data) {
                        if (data) {
                            data = JSON.parse(data);
                            window.aza = $.extend(window.aza, data);
                            $(window).trigger("aza-counter");
                        }
                    });
                });
            })(window.jQuery);
        </script>
        <?php
    }
}

add_action('wp_ajax_aza_counter', 'aza_counter_ajax');
add_action('wp_ajax_nopriv_aza_counter', 'aza_counter_ajax');

function aza_counter_ajax() {
    $visitor_id = aza_counter();
    $visit = aza_get_last_visit($visitor_id);
    print json_encode(apply_filters('aza_counter_ajax', array(
        'landing_page_id' => $visit['landing_page_id'],
        'visitor_id' => $visitor_id,
        'promo_code' => $visit['promo_code'],
    )));
    wp_die();
}

function aza_counter($id = NULL) {
    global $wpdb, $aza_models, $aza_search_engines;
    $visitor = false;
    if (is_null($id)) {
        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            $id = sanitize_text_field($_REQUEST['id']);
        } else {
            $id = get_the_ID();
        }
    }
    $first_interaction = false;
    if (isset($_COOKIE['aza-visitor'])) {
        $visitor = $_COOKIE['aza-visitor'];
    } else {
        $first_interaction = true;
        $visitor = uniqid();
        setcookie('aza-visitor', $visitor, time() + 365 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
    }

    if (is_user_logged_in()) {
        $meta_visitor = get_user_meta(get_current_user_id(), 'aza-visitor', true);
        if (empty($meta_visitor)) {
            update_user_meta(get_current_user_id(), 'aza-visitor', $visitor);
        } else {
            if ($visitor != $meta_visitor) {
                setcookie('aza-visitor', $meta_visitor, time() + 365 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
                foreach (array_keys($aza_models) as $model) {
                    $wpdb->query("UPDATE {$wpdb->prefix}aza_visits_{$model} SET visitor_id = '$meta_visitor' WHERE visitor_id = '$visitor'");
                }
                $visitor = $meta_visitor;
            }
        }
    }
    if (is_numeric($id)) {
        $utm_source = '';
        if (isset($_REQUEST['utm_source'])) {
            $utm_source = sanitize_text_field($_REQUEST['utm_source']);
        }
        $utm_medium = '';
        if (isset($_REQUEST['utm_medium'])) {
            $utm_medium = sanitize_text_field($_REQUEST['utm_medium']);
        }
        $utm_campaign = '';
        if (isset($_REQUEST['utm_campaign'])) {
            $utm_campaign = sanitize_text_field($_REQUEST['utm_campaign']);
        }
        $utm_content = '';
        if (isset($_REQUEST['utm_content'])) {
            $utm_content = sanitize_text_field($_REQUEST['utm_content']);
        }
        $utm_term = '';
        if (isset($_REQUEST['utm_term'])) {
            $utm_term = sanitize_text_field($_REQUEST['utm_term']);
        }
        $aza = array();
        if (isset($_REQUEST['aza'])) {
            $aza = sanitize_text_field($_REQUEST['aza']);
            $aza = explode('_', $aza);
        }
        if (empty($utm_source) && empty($aza) && !empty($_REQUEST['referer'])) {
            $parts = parse_url(sanitize_text_field($_REQUEST['referer']));
            preg_match('/(' . implode('|', array_keys($aza_search_engines)) . ')\./', $parts['host'], $matches);
            if ($matches) {
                $utm_source = 'seo';
                $utm_medium = $matches[1];
                $aza = array('seo', $matches[1]);
            }
        }


        if ($first_interaction || !empty($aza) || !empty($utm_source) || !empty($utm_medium) || !empty($utm_campaign) || !empty($utm_content) || !empty($utm_term)) {
            $last_visit = aza_get_last_visit($visitor);
            $duplicate = false;
            if (!empty($last_visit)) {
                if (!empty($aza)) {
                    if ($aza[0] == $last_visit['marker_level_1'] && ($aza[1] == $last_visit['marker_level_2'] || (empty($aza[1]) && empty($last_visit['marker_level_2']))) && ($aza[2] == $last_visit['marker_level_3'] || (empty($aza[2]) && empty($last_visit['marker_level_3']))) && ($aza[3] == $last_visit['marker_level_4'] || (empty($aza[3]) && empty($last_visit['marker_level_4']))) && ($aza[4] == $last_visit['marker_level_5'] || (empty($aza[4]) && empty($last_visit['marker_level_5']))) && ($aza[5] == $last_visit['marker_level_6'] || (empty($aza[5]) && empty($last_visit['marker_level_6']))) && ($aza[6] == $last_visit['marker_level_7'] || (empty($aza[6]) && empty($last_visit['marker_level_7'])))
                    ) {
                        $duplicate = true;
                    }
                }
                if (!empty($utm_source)) {
                    if ($utm_source == $last_visit['utm_source'] && ($utm_medium == $last_visit['utm_medium'] || (empty($utm_medium) && empty($last_visit['utm_medium']))) && ($utm_campaign == $last_visit['utm_campaign'] || (empty($utm_campaign) && empty($last_visit['utm_campaign']))) && ($utm_content == $last_visit['utm_content'] || (empty($utm_content) && empty($last_visit['utm_content']))) && ($utm_term == $last_visit['utm_term'] || (empty($utm_term) && empty($last_visit['utm_term'])))
                    ) {
                        $duplicate = true;
                    }
                }
            }
            if (!$duplicate) {
                $timestamp = time();
                $page_group_id = false;
                $landing_page_id = apply_filters('aza_counter_page', $id);
                if ($id != $landing_page_id) {
                    $page_group_id = $id;
                }
                $page = get_post($landing_page_id);
                foreach (array_keys($aza_models) as $model) {
                    $wpdb->query("INSERT INTO {$wpdb->prefix}aza_visits_{$model} ("
                            . "visit_timestamp,"
                            . "hour,"
                            . "day,"
                            . "day_of_week,"
                            . "week,"
                            . "month,"
                            . "visitor_id,"
                            . "landing_page_id,"
                            . "landing_page_author,"
                            . "page_group_id,"
                            . "marker_level_1,"
                            . "marker_level_2,"
                            . "marker_level_3,"
                            . "marker_level_4,"
                            . "marker_level_5,"
                            . "marker_level_6,"
                            . "marker_level_7,"
                            . "utm_source,"
                            . "utm_medium,"
                            . "utm_campaign,"
                            . "utm_content,"
                            . "utm_term)"
                            . "VALUES("
                            . $timestamp . ","
                            . "'" . gmdate('H', $timestamp + ( get_option('gmt_offset') * HOUR_IN_SECONDS )) . "',"
                            . "'" . gmdate('d', $timestamp + ( get_option('gmt_offset') * HOUR_IN_SECONDS )) . "',"
                            . "'" . gmdate('w', $timestamp + ( get_option('gmt_offset') * HOUR_IN_SECONDS )) . "',"
                            . "'" . gmdate('W', $timestamp + ( get_option('gmt_offset') * HOUR_IN_SECONDS )) . "',"
                            . "'" . gmdate('m', $timestamp + ( get_option('gmt_offset') * HOUR_IN_SECONDS )) . "',"
                            . "'" . $visitor . "',"
                            . $landing_page_id . ","
                            . $page->post_author . ","
                            . ($page_group_id ? $page_group_id : 'NULL') . ","
                            . (isset($aza[0]) ? "'" . $aza[0] . "'" : 'NULL') . ","
                            . (isset($aza[1]) ? "'" . $aza[1] . "'" : 'NULL') . ","
                            . (isset($aza[2]) ? "'" . $aza[2] . "'" : 'NULL') . ","
                            . (isset($aza[3]) ? "'" . $aza[3] . "'" : 'NULL') . ","
                            . (isset($aza[4]) ? "'" . $aza[4] . "'" : 'NULL') . ","
                            . (isset($aza[5]) ? "'" . $aza[5] . "'" : 'NULL') . ","
                            . (isset($aza[6]) ? "'" . $aza[6] . "'" : 'NULL') . ","
                            . (!empty($utm_source) ? "'" . $utm_source . "'" : 'NULL') . ","
                            . (!empty($utm_medium) ? "'" . $utm_medium . "'" : 'NULL') . ","
                            . (!empty($utm_campaign) ? "'" . $utm_campaign . "'" : 'NULL') . ","
                            . (!empty($utm_content) ? "'" . $utm_content . "'" : 'NULL') . ","
                            . (!empty($utm_term) ? "'" . $utm_term . "'" : 'NULL')
                            . ")");
                }
            }
        }
    }
    return $visitor;
}

add_action('user_register', 'aza_user_register', 10, 1);

function aza_user_register($user_id) {
    update_user_meta($user_id, 'aza-visitor', aza_get_visitor_id());
}

function aza_get_all_visits($visitor_id) {
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aza_visits_last WHERE visitor_id = '$visitor_id' ORDER BY visit_timestamp", ARRAY_A);
}

function aza_get_last_visit($visitor_id, $timestamp = NULL) {
    global $wpdb;
    if (is_null($timestamp)) {
        $timestamp = '';
    } else {
        $timestamp = "AND visit_timestamp <= $timestamp";
    }
    return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}aza_visits_last WHERE visitor_id = '$visitor_id' $timestamp ORDER BY visit_timestamp DESC LIMIT 0,1", ARRAY_A);
}

function aza_get_visit($promo_code) {
    global $wpdb;
    return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}aza_visits_last WHERE promo_code = $promo_code LIMIT 0,1", ARRAY_A);
}

function aza_get_visitor_id() {
    $visitor_id = $_COOKIE['aza-visitor'];
    if (empty($visitor_id)) {
        $visitor_id = aza_counter();
    }
    return $visitor_id;
}

function aza_add_lead(&$visits, $lead, $model) {
    $lead_visits = array();
    for ($i = 0; $i < count($visits); $i++) {
        if ($visits[$i]['visit_timestamp'] <= $lead['lead_timestamp']) {
            $lead_visits[] = $i;
        }
    }
    $values = array(
        'first_cost' => ($lead['status'] == 'paid' ? $lead['first_cost'] : 0),
        'leads_number' => 1,
        'paid_leads_number' => ($lead['status'] == 'paid' ? 1 : 0),
        'in_work_leads_number' => ($lead['status'] == 'in_work' ? 1 : 0),
        'canceled_leads_number' => ($lead['status'] == 'canceled' ? 1 : 0),
        'revenue' => ($lead['status'] == 'paid' ? $lead['amount'] : 0),
        'in_work_revenue' => ($lead['status'] == 'in_work' ? $lead['amount'] : 0),
        'canceled_revenue' => ($lead['status'] == 'canceled' ? $lead['amount'] : 0),
    );

    switch ($model) {
        case 'last':
            $last_visit = end($lead_visits);
            foreach ($values as $name => $value) {
                $visits[$last_visit][$name] += $value;
            }
            break;
        case 'first':
            $first_visit = reset($lead_visits);
            foreach ($values as $name => $value) {
                $visits[$first_visit][$name] += $value;
            }
            break;
        case 'linear':
            foreach ($lead_visits as $i) {
                foreach ($values as $name => $value) {
                    $visits[$i][$name] += $value / count($lead_visits);
                }
            }
            break;
        case 'position':
            if (count($lead_visits) == 1) {
                $i = reset($lead_visits);
                foreach ($values as $name => $value) {
                    $visits[$i][$name] += $value;
                }
            }
            if (count($lead_visits) == 2) {
                $first_visit = reset($lead_visits);
                foreach ($values as $name => $value) {
                    $visits[$first_visit][$name] += $value * 0.4;
                }

                $last_visit = end($lead_visits);
                foreach ($values as $name => $value) {
                    $visits[$last_visit][$name] += $value * 0.6;
                }
            }
            if (count($lead_visits) > 2) {
                $first_visit = array_shift($lead_visits);
                foreach ($values as $name => $value) {
                    $visits[$first_visit][$name] += $value * 0.4;
                }

                $last_visit = array_pop($lead_visits);
                foreach ($values as $name => $value) {
                    $visits[$last_visit][$name] += $value * 0.4;
                }

                foreach ($lead_visits as $i) {
                    foreach ($values as $name => $value) {
                        $visits[$i][$name] += $value * 0.2 / (count($lead_visits) + 2);
                    }
                }
            }
            break;
    }
}

function aza_refresh_leads_info($user_id = NULL, $visitor_id = NULL) {
    $lead_metrics = array('first_cost', 'leads_number', 'paid_leads_number', 'in_work_leads_number', 'canceled_leads_number', 'revenue', 'in_work_revenue', 'canceled_revenue');
    if (is_null($visitor_id)) {
        $visitor_id = aza_get_visitor_id();
    }
    if (is_null($user_id)) {
        $user_id = get_current_user_id();
    }
    if ($visitor_id) {
        global $wpdb, $aza_models, $aza_multi_user;
        $visits = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aza_visits_last WHERE " . ($aza_multi_user ? "landing_page_author = $user_id AND" : "") . " visitor_id = '$visitor_id' ORDER BY visit_timestamp", ARRAY_A);
        $leads = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aza_leads WHERE " . ($aza_multi_user ? "lead_page_author = $user_id AND" : "") . " visitor_id = '$visitor_id'", ARRAY_A);

        foreach (array_keys($aza_models) as $model) {
            foreach ($visits as &$visit) {
                foreach ($lead_metrics as $metric) {
                    $visit[$metric] = 0;
                }
            }
            foreach ($leads as $lead) {
                aza_add_lead($visits, $lead, $model);
            }
            foreach ($visits as $visit) {
                $set = array();
                foreach ($lead_metrics as $metric) {
                    $set[] = $metric . ' = ' . $visit[$metric];
                }
                $set = implode(', ', $set);
                $wpdb->query("UPDATE {$wpdb->prefix}aza_visits_{$model} SET $set WHERE visitor_id = '$visitor_id' AND visit_timestamp = {$visit['visit_timestamp']}");
            }
        }
    }
}

function aza_add_target(&$visits, $target, $model) {
    $target_visits = array();
    for ($i = 0; $i < count($visits); $i++) {
        if ($visits[$i]['visit_timestamp'] <= $target['lead_timestamp']) {
            $target_visits[] = $i;
        }
    }
    $values = array(
        'targets_number' => 1,
    );
    $values = apply_filters('aza_add_target', $values, $target);

    switch ($model) {
        case 'last':
            $last_visit = end($target_visits);
            foreach ($values as $name => $value) {
                $visits[$last_visit][$name] += $value;
            }
            break;
        case 'first':
            $first_visit = reset($target_visits);
            foreach ($values as $name => $value) {
                $visits[$first_visit][$name] += $value;
            }
            break;
        case 'linear':
            foreach ($target_visits as $i) {
                foreach ($values as $name => $value) {
                    $visits[$i][$name] += $value / count($target_visits);
                }
            }
            break;
        case 'position':
            if (count($target_visits) == 1) {
                $i = reset($target_visits);
                foreach ($values as $name => $value) {
                    $visits[$i][$name] += $value;
                }
            }
            if (count($target_visits) == 2) {
                $first_visit = reset($target_visits);
                foreach ($values as $name => $value) {
                    $visits[$first_visit][$name] += $value * 0.4;
                }

                $last_visit = end($target_visits);
                foreach ($values as $name => $value) {
                    $visits[$last_visit][$name] += $value * 0.6;
                }
            }
            if (count($target_visits) > 2) {
                $first_visit = array_shift($target_visits);
                foreach ($values as $name => $value) {
                    $visits[$first_visit][$name] += $value * 0.4;
                }

                $last_visit = array_pop($target_visits);
                foreach ($values as $name => $value) {
                    $visits[$last_visit][$name] += $value * 0.4;
                }

                foreach ($target_visits as $i) {
                    foreach ($values as $name => $value) {
                        $visits[$i][$name] += $value * 0.2 / (count($target_visits) + 2);
                    }
                }
            }
            break;
    }
}

function aza_refresh_targets_info($user_id = NULL, $visitor_id = NULL) {
    $target_metrics = apply_filters('aza_target_metrics', array());
    if (is_null($visitor_id)) {
        $visitor_id = aza_get_visitor_id();
    }
    if (is_null($user_id)) {
        $user_id = get_current_user_id();
    }
    if ($visitor_id) {
        global $wpdb, $aza_models, $aza_multi_user;
        $visits = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aza_visits_last WHERE " . ($aza_multi_user ? "landing_page_author = $user_id AND" : "") . " visitor_id = '$visitor_id' ORDER BY visit_timestamp", ARRAY_A);
        $targets = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aza_targets WHERE " . ($aza_multi_user ? "target_page_author = $user_id AND" : "") . " visitor_id = '$visitor_id'", ARRAY_A);

        foreach (array_keys($aza_models) as $model) {
            foreach ($visits as &$visit) {
                foreach ($target_metrics as $metric) {
                    $visit[$metric] = 0;
                }
            }
            foreach ($targets as $target) {
                aza_add_target($visits, $target, $model);
            }
            foreach ($visits as $visit) {
                $set = array();
                foreach ($target_metrics as $metric) {
                    $set[] = $metric . ' = ' . $visit[$metric];
                }
                $set = implode(', ', $set);
                $wpdb->query("UPDATE {$wpdb->prefix}aza_visits_{$model} SET $set WHERE visitor_id = '$visitor_id' AND visit_timestamp = {$visit['visit_timestamp']}");
            }
        }
    }
}

function aza_make_set($values) {
    $set = array();
    foreach ($values as $column => $value) {
        if (is_null($value)) {
            $set[] = $column . ' = NULL';
        } else {
            if (is_string($value)) {
                $set[] = $column . " = '" . $value . "'";
            } else {
                $set[] = $column . ' = ' . $value;
            }
        }
    }
    return implode(', ', $set);
}

function aza_make_where($where) {
    $where_clause = array();
    foreach ($where as $column => $value) {
        if (is_null($value)) {
            $set[] = '(' . $column . ' = NULL)';
        } else {
            if (is_string($value)) {
                $set[] = '(' . $column . " = '" . $value . "')";
            } else {
                $set[] = '(' . $column . ' = ' . $value . ")";
            }
        }
    }
    return empty($where_clause) ? '' : implode(' AND ', $where_clause) . ' AND ';
}

function aza_update_visit($visit, $values, $where = array(), $visitor_id = NULL) {
    if (is_null($visitor_id)) {
        $visitor_id = aza_get_visitor_id();
    }
    if ($visitor_id) {
        global $wpdb, $aza_models;
        $set = aza_make_set($values);
        $where_clause = aza_make_where($where);
        foreach (array_keys($aza_models) as $model) {
            $wpdb->query("UPDATE {$wpdb->prefix}aza_visits_{$model} SET $set WHERE $where_clause visitor_id = '$visitor_id' AND visit_timestamp = {$visit['visit_timestamp']}");
        }
    }
}

function aza_update_visits($values, $where = array(), $visitor_id = NULL, $timestamp = NULL) {
    if (is_null($visitor_id)) {
        $visitor_id = aza_get_visitor_id();
    }
    if ($visitor_id) {
        global $wpdb, $aza_models;
        $set = aza_make_set($values);
        if (is_null($timestamp)) {
            $timestamp = '';
        } else {
            $timestamp = "AND visit_timestamp <= $timestamp";
        }
        if (empty($where)) {
            $visits = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aza_visits_last WHERE visitor_id = '$visitor_id' $timestamp ORDER BY visit_timestamp DESC", ARRAY_A);
            foreach (array_keys($aza_models) as $model) {
                switch ($model) {
                    case 'last':
                        $last_visit = end($visits);
                        $wpdb->query("UPDATE {$wpdb->prefix}aza_visits_{$model} SET $set WHERE visitor_id = '$visitor_id' AND visit_timestamp = {$last_visit['visit_timestamp']}");
                        break;
                    case 'first':
                        $first_visit = reset($visits);
                        $wpdb->query("UPDATE {$wpdb->prefix}aza_visits_{$model} SET $set WHERE visitor_id = '$visitor_id' AND visit_timestamp = {$first_visit['visit_timestamp']}");
                        break;
                    case 'linear':
                    case 'position':
                        foreach ($visits as $visit) {
                            $wpdb->query("UPDATE {$wpdb->prefix}aza_visits_{$model} SET $set WHERE visitor_id = '$visitor_id' AND visit_timestamp = {$visit['visit_timestamp']}");
                        }
                        break;
                }
            }
        } else {
            $where_clause = aza_make_where($where) . " visitor_id = '$visitor_id'";
            foreach (array_keys($aza_models) as $model) {
                $wpdb->query("UPDATE {$wpdb->prefix}aza_visits_{$model} SET $set WHERE $where_clause");
            }
        }
    }
}

function aza_settings() {
    global $wp_locale, $aza_models, $wpdb, $aza_lead_statuses, $aza_search_engines;
    $settings = get_option('aza-settings');
    $reports = get_user_meta(get_current_user_id(), 'aza-reports', true) ? json_decode(get_user_meta(get_current_user_id(), 'aza-reports', true), true) : array();
    if (empty($reports) && !empty($settings['default-report'])) {
        $reports = array($settings['default-report']);
    }
    return apply_filters('aza_settings', array(
        'date_format' => get_option('date_format'),
        'gmt_offset' => get_option('gmt_offset'),
        'money_format' => $settings['money-format'],
        'float_format' => $settings['float-format'],
        'thousands_delimiter' => $settings['thousands-delimiter'],
        'decimal_delimiter' => $settings['decimal-delimiter'],
        'currency_symbol' => $settings['currency-symbol'],
        'phone_mask' => $settings['phone-mask'],
        'icons' => array(
            'seo' => plugins_url('/images/magnifying-glass.png', __FILE__),
            'adwords' => plugins_url('/images/google.com.png', __FILE__),
            'direct' => plugins_url('/images/direct.yandex.ru.png', __FILE__),
            'facebook' => plugins_url('/images/facebook.com.png', __FILE__),
            'vk' => plugins_url('/images/vk.com.png', __FILE__),
            'market' => plugins_url('/images/market.yandex.ru.png', __FILE__),
        ),
        'dimensions' => array(
            'hour' => array(
                'label' => __('Hour', 'aza'),
                'icon' => '',
            ),
            'day' => array(
                'label' => __('Day', 'aza'),
                'icon' => '',
            ),
            'day_of_week' => array(
                'label' => __('Day of week', 'aza'),
                'icon' => '',
                'converter' => array(
                    'map' => array(
                        $wp_locale->get_weekday(0),
                        $wp_locale->get_weekday(1),
                        $wp_locale->get_weekday(2),
                        $wp_locale->get_weekday(3),
                        $wp_locale->get_weekday(4),
                        $wp_locale->get_weekday(5),
                        $wp_locale->get_weekday(6),
                    ),
                ),
            ),
            'week' => array(
                'label' => __('Week number of year', 'aza'),
                'icon' => '',
            ),
            'month' => array(
                'label' => __('Month', 'aza'),
                'icon' => '',
                'converter' => array(
                    'map' => array(
                        '',
                        $wp_locale->get_month(1),
                        $wp_locale->get_month(2),
                        $wp_locale->get_month(3),
                        $wp_locale->get_month(4),
                        $wp_locale->get_month(5),
                        $wp_locale->get_month(6),
                        $wp_locale->get_month(7),
                        $wp_locale->get_month(8),
                        $wp_locale->get_month(9),
                        $wp_locale->get_month(10),
                        $wp_locale->get_month(11),
                        $wp_locale->get_month(12),
                    ),
                ),
            ),
            'landing_page_id' => array(
                'label' => __('Landing page', 'aza'),
                'icon' => '',
                'converter' => array(
                    'table' => $wpdb->posts,
                    'from' => 'ID',
                    'to' => 'post_title',
                ),
            ),
            'page_group_id' => array(
                'label' => __('Split-testing group', 'aza'),
                'icon' => '',
                'converter' => array(
                    'table' => $wpdb->posts,
                    'from' => 'ID',
                    'to' => 'post_title',
                ),
            ),
            'marker_level_1' => array(
                'label' => __('Source (level 1)', 'aza'),
                'icon' => '',
                'converters' => array(
                    array(
                        'map' => array(
                            'seo' => __('SEO', 'aza'),
                        ),
                    ),
                ),
            ),
            'marker_level_2' => array(
                'label' => __('Source (level 2)', 'aza'),
                'icon' => '',
                'converters' => array(
                    array(
                        'condition' => array(
                            'column' => 'marker_level_1',
                            'value' => 'seo',
                        ),
                        'map' => $aza_search_engines,
                    ),
                ),
            ),
            'marker_level_3' => array(
                'label' => __('Source (level 3)', 'aza'),
                'icon' => '',
            ),
            'marker_level_4' => array(
                'label' => __('Source (level 4)', 'aza'),
                'icon' => '',
            ),
            'marker_level_5' => array(
                'label' => __('Source (level 5)', 'aza'),
                'icon' => '',
            ),
            'marker_level_6' => array(
                'label' => __('Source (level 6)', 'aza'),
                'icon' => '',
            ),
            'marker_level_7' => array(
                'label' => __('Source (level 7)', 'aza'),
                'icon' => '',
            ),
            'utm_source' => array(
                'label' => 'utm_source',
                'icon' => '',
                'converters' => array(
                    array(
                        'map' => array(
                            'seo' => __('SEO', 'aza'),
                        ),
                    ),
                ),
            ),
            'utm_medium' => array(
                'label' => 'utm_medium',
                'icon' => '',
                'converters' => array(
                    array(
                        'condition' => array(
                            'column' => 'utm_source',
                            'value' => 'seo',
                        ),
                        'map' => $aza_search_engines,
                    ),
                ),
            ),
            'utm_campaign' => array(
                'label' => 'utm_campaign',
                'icon' => '',
            ),
            'utm_content' => array(
                'label' => 'utm_content',
                'icon' => '',
            ),
            'utm_term' => array(
                'label' => 'utm_term',
                'icon' => '',
            ),
            'lead_page_id' => array(
                'table' => $wpdb->prefix . 'aza_leads',
                'label' => __('Page of the lead', 'aza'),
                'icon' => '',
                'converter' => array(
                    'table' => $wpdb->posts,
                    'from' => 'ID',
                    'to' => 'post_title',
                ),
            ),
            'status' => array(
                'table' => $wpdb->prefix . 'aza_leads',
                'label' => __('Lead status', 'aza'),
                'icon' => '',
                'converter' => array(
                    'map' => $aza_lead_statuses,
                ),
            ),
            'type' => array(
                'table' => $wpdb->prefix . 'aza_leads',
                'label' => __('Lead type', 'aza'),
                'icon' => '',
                'converter' => array(
                    'map' => array(
                        'azh' => __('Form submit', 'aza'),
                        'promo_code' => __('Promo code', 'aza'),
                        'calltracking' => __('Phone call', 'aza'),
                        'woocommerce' => __('WooCommerce order', 'aza'),
                    ),
                ),
            ),
        ),
        'metrics' => array(
            'visits_count' => array(
                'label' => __('Visits', 'aza'),
                'desc' => __('Number of website visits with unique advertising source', 'aza'),
                'formula' => 'COUNT(*)',
                'type' => 'integer',
            ),
            'conversion_visits_to_leads' => array(
                'label' => __('Leads conversion', 'aza'),
                'desc' => __('Share of leads from total number of visits: Leads / Visits * 100%', 'aza'),
                'formula' => 'SUM(COALESCE(leads_number,0)) / COUNT(*) * 100',
                'type' => 'percent',
            ),
            'leads_count' => array(
                'label' => __('Leads', 'aza'),
                'desc' => __('Leads are any types of requests received by your business.', 'aza'),
                'formula' => 'SUM(COALESCE(leads_number,0))',
                'type' => 'integer',
            ),
            'conversion_leads_to_sales' => array(
                'label' => __('Sales conversion', 'aza'),
                'desc' => __('Share of sales from total number of Leads: Sales / Leads * 100%', 'aza'),
                'formula' => "SUM(COALESCE(paid_leads_number,0)) / SUM(COALESCE(leads_number,0)) * 100",
                'type' => 'percent',
            ),
            'sales_count' => array(
                'label' => __('Sales', 'aza'),
                'desc' => __('Leads with Paid-status', 'aza'),
                'formula' => "SUM(COALESCE(paid_leads_number,0))",
                'type' => 'integer',
            ),
            'revenue_sum' => array(
                'label' => __('Revenue', 'aza'),
                'desc' => __('Revenue from leads with Paid-status', 'aza'),
                'formula' => "SUM(COALESCE(revenue,0))",
                'type' => 'money',
            ),
            'average_revenue' => array(
                'label' => __('Average revenue', 'aza'),
                'desc' => __('Average amount of sales: Revenue / Sales', 'aza'),
                'formula' => "SUM(COALESCE(revenue,0)) / SUM(COALESCE(paid_leads_number,0))",
                'type' => 'money',
            ),
            'cpo' => array(
                'label' => __('CPO', 'aza'),
                'desc' => __('Cost per Order: marketing costs devided by sales amount', 'aza'),
                'formula' => "100 * (SUM(COALESCE(marketing_cost,0) + COALESCE(offline_marketing_cost,0)) / SUM(COALESCE(paid_leads_number,0)))",
                'type' => 'percent',
            ),
            'profit_sum' => array(
                'label' => __('Profit', 'aza'),
                'desc' => __('Difference between Revenue and Costs: Revenue - Firsts', 'aza'),
                'formula' => "SUM(COALESCE(revenue,0) - COALESCE(first_cost,0))",
                'type' => 'money',
            ),
            'net_profit' => array(
                'label' => __('Net profit', 'aza'),
                'desc' => __('Difference between Revenue and First Cost: Revenue - First Cost', 'aza'),
                'formula' => "SUM(COALESCE(revenue,0) - COALESCE(first_cost,0) - COALESCE(marketing_cost,0) - COALESCE(offline_marketing_cost,0))",
                'type' => 'money',
            ),
            'average_profit' => array(
                'label' => __('Average profit', 'aza'),
                'desc' => __('Average profit from a single sale: Profit / Sales', 'aza'),
                'formula' => "SUM(COALESCE(revenue,0) - COALESCE(first_cost,0)) / SUM(COALESCE(paid_leads_number,0))",
                'type' => 'money',
            ),
            'first_cost_sum' => array(
                'label' => __('First Cost', 'aza'),
                'desc' => __('First cost of leads', 'aza'),
                'formula' => 'SUM(COALESCE(first_cost,0))',
                'type' => 'money',
            ),
            'marketing_cost_sum' => array(
                'label' => __('Marketing Costs', 'aza'),
                'desc' => __('Marketing Costs', 'aza'),
                'formula' => 'SUM(COALESCE(marketing_cost,0) + COALESCE(offline_marketing_cost,0))',
                'type' => 'money',
            ),
            'roi' => array(
                'label' => __('ROI', 'aza'),
                'desc' => __('Return on investment: (Profit - Costs) / Costs * 100%', 'aza'),
                'formula' => "100 * (SUM(COALESCE(revenue,0) - COALESCE(first_cost,0)) - SUM(COALESCE(marketing_cost,0) + COALESCE(offline_marketing_cost,0))) / (SUM(COALESCE(first_cost,0)) + SUM(COALESCE(marketing_cost,0) + COALESCE(offline_marketing_cost,0)))",
                'type' => 'percent',
            ),
            'absolute_conversion' => array(
                'label' => __('Absolute conversion', 'aza'),
                'desc' => __('Share of sales from total number of visits: Sales / Visits * 100%', 'aza'),
                'formula' => "100 * SUM(COALESCE(paid_leads_number,0)) / COUNT(*)",
                'type' => 'percent',
            ),
            'potential_sales' => array(
                'label' => __('Potential sales', 'aza'),
                'desc' => __('Leads with status \'In work\'', 'aza'),
                'formula' => "SUM(COALESCE(in_work_leads_number,0))",
                'type' => 'integer',
            ),
            'canceled_leads' => array(
                'label' => __('Canceled leads', 'aza'),
                'desc' => __('Leads with status \'Canceled\'', 'aza'),
                'formula' => "SUM(COALESCE(canceled_leads_number,0))",
                'type' => 'integer',
            ),
            'potential_revenue' => array(
                'label' => __('Potential revenue', 'aza'),
                'desc' => __('Potential revenue from leads with status \'In progress\'', 'aza'),
                'formula' => "SUM(COALESCE(in_work_revenue,0))",
                'type' => 'money',
            ),
            'canceled_revenue' => array(
                'label' => __('Revenue of canceled leads', 'aza'),
                'desc' => __('Revenue of leads with status \'Canceled\'', 'aza'),
                'formula' => "SUM(COALESCE(canceled_revenue,0))",
                'type' => 'money',
            ),
            'clients' => array(
                'label' => __('Clients', 'aza'),
                'desc' => __('Number of clients', 'aza'),
                'formula' => "COUNT(DISTINCT visitor_id)",
                'type' => 'integer',
            ),
            'paid_customers' => array(
                'label' => __('Paid customers', 'aza'),
                'desc' => __('Customers with paid lead(s)', 'aza'),
                'formula' => "COUNT(DISTINCT(CASE WHEN paid_leads_number > 0 THEN visitor_id END))",
                'type' => 'integer',
            ),
            'repeated_leads' => array(
                'label' => __('Repeated leads', 'aza'),
                'desc' => __('Number of re-leads: Leads - Clients', 'aza'),
                'formula' => "SUM(COALESCE(leads_number,0)) - COUNT(DISTINCT visitor_id)",
                'type' => 'integer',
            ),
            'repeated_sales' => array(
                'label' => __('Repeated sales', 'aza'),
                'desc' => __('Number of re-sales: Sales - Paid Clients', 'aza'),
                'formula' => "SUM(COALESCE(paid_leads_number,0)) - COUNT(DISTINCT(CASE WHEN paid_leads_number > 0 THEN visitor_id END))",
                'type' => 'integer',
            ),
            'romi' => array(
                'label' => __('ROMI', 'aza'),
                'desc' => __('Return on investment without fist cost: (Revenue - Costs) / Costs * 100%', 'aza'),
                'formula' => "100 * (SUM(COALESCE(revenue,0)) - SUM(COALESCE(marketing_cost,0) + COALESCE(offline_marketing_cost,0))) / (SUM(COALESCE(marketing_cost,0) + COALESCE(offline_marketing_cost,0)))",
                'type' => 'percent',
            ),
            'cpc' => array(
                'label' => __('CPC', 'aza'),
                'desc' => __('Average cost of a visit: Costs / Visits', 'aza'),
                'formula' => "SUM(COALESCE(marketing_cost,0) + COALESCE(offline_marketing_cost,0)) / COUNT(*)",
                'type' => 'money',
            ),
            'cpl' => array(
                'label' => __('CPL', 'aza'),
                'desc' => __('Average cost of a lead: Costs / Leads', 'aza'),
                'formula' => "SUM(COALESCE(marketing_cost,0) + COALESCE(offline_marketing_cost,0)) / SUM(COALESCE(leads_number,0))",
                'type' => 'money',
            ),
            'cac' => array(
                'label' => __('CAC', 'aza'),
                'desc' => __('Average cost of retention of a paid client: Costs / Paid clients', 'aza'),
                'formula' => "SUM(COALESCE(marketing_cost,0) + COALESCE(offline_marketing_cost,0)) / COUNT(DISTINCT(CASE WHEN paid_leads_number > 0 THEN visitor_id END))",
                'type' => 'money',
            ),
            'ltv' => array(
                'label' => __('LTV', 'aza'),
                'desc' => __('Average profit per client with paid leads:  Profit / Paid clients', 'aza'),
                'formula' => "SUM(COALESCE(revenue,0) - COALESCE(first_cost,0)) / COUNT(DISTINCT(CASE WHEN paid_leads_number > 0 THEN visitor_id END))",
                'type' => 'money',
            ),
            'margin' => array(
                'label' => __('Margin, %', 'aza'),
                'desc' => __('Share of first cost from profit: Profit / First cost * 100%', 'aza'),
                'formula' => "100 * SUM(COALESCE(revenue,0) - COALESCE(first_cost,0)) / SUM(COALESCE(first_cost,0))",
                'type' => 'percent',
            ),
            'soc' => array(
                'label' => __('SOC, %', 'aza'),
                'desc' => __('Share of advertising costs in profit: Costs / Profit * 100%', 'aza'),
                'formula' => "100 * SUM(COALESCE(marketing_cost,0) + COALESCE(offline_marketing_cost,0)) / SUM(COALESCE(revenue,0) - COALESCE(first_cost,0))",
                'type' => 'percent',
            ),
        ),
        'models' => $aza_models,
        'reports' => $reports,
    ));
}

function aza_get_column_values($column) {
    global $wpdb;
    return $wpdb->get_col("SELECT DISTINCT $column FROM {$wpdb->prefix}aza_visits_last");
}

function aza_dimension_filters($dimension_settings, &$where, &$having) {
    $settings = aza_settings();
    if (isset($dimension_settings['filters'])) {
        foreach ($dimension_settings['filters'] as $filter) {
            if (isset($settings['metrics'][$filter['field']])) {
                switch ($filter['operator']) {
                    case 'equal':
                        $having[] = '(' . $filter['field'] . ' = ' . $filter['value'] . ')';
                        break;
                    case 'less':
                        $having[] = '(' . $filter['field'] . ' < ' . $filter['value'] . ')';
                        break;
                    case 'more':
                        $having[] = '(' . $filter['field'] . ' > ' . $filter['value'] . ')';
                        break;
                }
            } else {
                switch ($filter['operator']) {
                    case 'equal':
                        $where[] = '(' . $filter['field'] . ' = \'' . $filter['value'] . '\')';
                        break;
                    case 'any_of':
                        $values = explode('|', $filter['value']);
                        $values = array_map(function($value) {
                            return "'" . $value . "'";
                        }, $values);
                        $where[] = '(' . $filter['field'] . ' IN (' . implode(', ', $values) . '))';
                        break;
                    case 'contain':
                        $where[] = '(' . $filter['field'] . ' LIKE \'%' . $filter['value'] . '%\')';
                        break;
                    case 'does_not_contain':
                        $where[] = '(' . $filter['field'] . ' NOT LIKE \'%' . $filter['value'] . '%\')';
                        break;
                }
            }
        }
    }
}

function aza_get_report_leads($report_settings, $from, $to, $levels = array()) {
    
}

function aza_get_report($report_settings, $from, $to, $levels = array()) {
    global $wpdb, $aza_models;
    $settings = aza_settings();
    $columns = array();
    foreach ($report_settings['metrics'] as $metric_settings) {
        $field = $metric_settings['metric'];
        if (isset($settings['metrics'][$field]['formula'])) {
            $columns[] = '(' . $settings['metrics'][$field]['formula'] . ') as ' . $field;
        } else {
            $columns[] = $field;
        }
    }
    $where = array();
    $where[] = "(visit_timestamp >= $from AND visit_timestamp < $to)";



    $group_by = array();
    $having = array();
    for ($i = 0; $i < count($levels); $i++) {
        $value = $levels[$i];
        $dimension_settings = $report_settings['dimensions'][$i];
        $group_by[] = $dimension_settings['dimension'];
        $columns[] = $dimension_settings['dimension'];
        if (empty($value)) {
            $where[] = '(' . $dimension_settings['dimension'] . ' = \'' . $value . '\' OR  ' . $dimension_settings['dimension'] . ' IS NULL)';
        } else {
            $where[] = '(' . $dimension_settings['dimension'] . ' = \'' . $value . '\')';
        }
        aza_dimension_filters($dimension_settings, $where, $having);
    }
    $dimension_settings = $report_settings['dimensions'][count($levels)];
    $group_by[] = $dimension_settings['dimension'];
    $columns[] = $dimension_settings['dimension'];
    //$where[] = '(' . $dimension_settings['dimension'] . ' IS NOT NULL)';
    aza_dimension_filters($dimension_settings, $where, $having);
    $group_by = implode(', ', $group_by);
    if (empty($having)) {
        $having = '';
    } else {
        $having = 'HAVING ' . implode(' AND ', $having);
    }



    $user = wp_get_current_user();
    if (!in_array('administrator', (array) $user->roles)) {
        $where[] = '(landing_page_author = ' . get_current_user_id() . ')';
    }
    $columns = implode(', ', $columns);
    $where = implode(' AND ', $where);

    $rows = array();

    $models = array();
    foreach ($report_settings['metrics'] as $metric_settings) {
        if (isset($metric_settings['model'])) {
            $models[$metric_settings['model']] = true;
        }
    }
    $models = array_keys($models);

    foreach ($models as $model) {
        $sql = "SELECT $columns FROM {$wpdb->prefix}aza_visits_{$model} WHERE $where GROUP BY $group_by $having";
        $rows[$model] = $wpdb->get_results($sql, ARRAY_A);
    }

    $report = array();
    for ($i = 0; $i < count($rows[reset($models)]); $i++) {
        $row = array();

        for ($j = 0; $j <= count($levels); $j++) {
            $dimension_settings = $report_settings['dimensions'][$j];
            $row[$dimension_settings['dimension']] = $rows[$model][$i][$dimension_settings['dimension']];
        }

        foreach ($report_settings['metrics'] as $metric_settings) {
            $field = $metric_settings['metric'];
            $model = reset($models);
            if (isset($metric_settings['model'])) {
                $model = $metric_settings['model'];
            }
            $row[$field . '-' . $model] = $rows[$model][$i][$field];
        }
        $report[] = $row;
    }
    return $report;
}

function aza_timezone() {

    $tzstring = get_option('timezone_string');
    $offset = get_option('gmt_offset');

    //Manual offset...
    //@see http://us.php.net/manual/en/timezones.others.php
    //@see https://bugs.php.net/bug.php?id=45543
    //@see https://bugs.php.net/bug.php?id=45528
    //IANA timezone database that provides PHP's timezone support uses POSIX (i.e. reversed) style signs
    if (empty($tzstring) && 0 != $offset && floor($offset) == $offset) {
        $offset_st = $offset > 0 ? "-$offset" : '+' . absint($offset);
        $tzstring = 'Etc/GMT' . $offset_st;
    }

    //Issue with the timezone selected, set to 'UTC'
    if (empty($tzstring)) {
        $tzstring = 'UTC';
    }

    $timezone = new DateTimeZone($tzstring);
    return $timezone;
}

function aza_get_periods($from, $to, $type = 'day', $gmt_offset = NULL) {
    if (is_null($gmt_offset)) {
        $gmt_offset = get_option('gmt_offset');
    }
    $periods = array();
    $date = new DateTime('now', aza_timezone());
    $date->setTimestamp($from);
    switch ($type) {
        case 'day':
            $date->modify('midnight');
            break;
        case 'week':
            $date->modify('Monday this week');
            $date->modify('midnight');
            break;
        case 'month':
            $date->modify('first day of this month');
            $date->modify('midnight');
            break;
    }
    do {
        $period = array();
        $period['begin'] = $date->getTimestamp();
        switch ($type) {
            case 'day':
                $date->modify('+ 1 day');
                break;
            case 'week':
                $date->modify('+ 1 week');
                break;
            case 'month':
                $date->modify('+ 1 month');
                break;
        }

        $period['end'] = $date->getTimestamp();
        $periods[] = array(
            'day' => date_i18n(get_option('date_format'), $period['begin'] + ( $gmt_offset * HOUR_IN_SECONDS )),
            'day_iso' => date("Y-m-d", $period['begin'] + ( $gmt_offset * HOUR_IN_SECONDS )),
            'begin' => $period['begin'],
            'end' => $period['end'],
        );
    } while ($date->getTimestamp() < $to);
    //array_shift($periods);
    return $periods;
}

function aza_get_chart($report_settings, $from, $to, $type, $levels = array()) {
    global $wpdb, $aza_models;
    $settings = aza_settings();
    $periods = aza_get_periods($from, $to, $type);
    $columns = array();
    $metrics = array();
    if (isset($report_settings['chart'])) {
        $metrics = array_keys($report_settings['chart']);
    } else {
        foreach ($report_settings['metrics'] as $metric_settings) {
            $metrics[] = $metric_settings['metric'];
        }
    }
    if (empty($metrics)) {
        return array();
    }
    foreach ($metrics as $metric) {
        $metric = explode('-', $metric);
        $metric = reset($metric);
        if (isset($settings['metrics'][$metric]['formula'])) {
            $columns[] = '(' . $settings['metrics'][$metric]['formula'] . ') as ' . $metric;
        } else {
            $columns[] = $metric;
        }
    }


    $where = array();
    $where[] = "(visit_timestamp >= $from AND visit_timestamp < $to)";


    $group_by = array();
    $having = array();
    for ($i = 0; $i < count($levels); $i++) {
        $value = $levels[$i];
        $dimension_settings = $report_settings['dimensions'][$i];
        $group_by[] = $dimension_settings['dimension'];
        $columns[] = $dimension_settings['dimension'];
        if (empty($value)) {
            $where[] = '(' . $dimension_settings['dimension'] . ' = \'' . $value . '\' OR  ' . $dimension_settings['dimension'] . ' IS NULL)';
        } else {
            $where[] = '(' . $dimension_settings['dimension'] . ' = \'' . $value . '\')';
        }
        aza_dimension_filters($dimension_settings, $where, $having);
    }
    if (empty($group_by)) {
        $group_by = '';
    } else {
        $group_by = 'GROUP BY ' . implode(' AND ', $group_by);
    }
    if (empty($having)) {
        $having = '';
    } else {
        $having = 'HAVING ' . implode(' AND ', $having);
    }



    $user = wp_get_current_user();
    if (!in_array('administrator', (array) $user->roles)) {
        $where[] = '(landing_page_author = ' . get_current_user_id() . ')';
    }
    $where = implode(' AND ', $where);
    $columns = implode(', ', $columns);


    $models = array();
    foreach ($metrics as $metric) {
        $metric = explode('-', $metric);
        $model = end($metric);
        $models[$model] = true;
    }
    $models = array_keys($models);
    $rows = array();
    foreach ($models as $model) {
        $rows[$model] = array();
        foreach ($periods as $period) {
            $sql = "SELECT $columns FROM {$wpdb->prefix}aza_visits_{$model} WHERE $where AND visit_timestamp >= {$period['begin']} AND visit_timestamp < {$period['end']} $group_by $having";
            $rows[$model][] = array(
                'day' => $period['day'],
                'row' => $wpdb->get_row($sql, ARRAY_A),
            );
        }
    }
    $chart = array();
    for ($i = 0; $i < count($periods); $i++) {
        $row = array();
        foreach ($metrics as $metric) {
            $m = explode('-', $metric);
            $metric = reset($m);
            $model = end($m);
            $row[$metric . '-' . $model] = $rows[$model][$i]['row'][$metric];
        }
        $chart[] = array(
            'day' => $periods[$i]['day'],
            'row' => $row,
        );
    }
    return $chart;
}

add_action('wp_ajax_aza_get_dimension_values', 'aza_get_dimension_values');

function aza_get_dimension_values() {
    if (isset($_POST['dimension'])) {
        global $wpdb, $aza_models, $aza_multi_user;
        $settings = aza_settings();
        $user_id = false;
        $user = wp_get_current_user();
        if (!in_array('administrator', (array) $user->roles)) {
            $user_id = get_current_user_id();
        }
        $dimension = esc_sql($_POST['dimension']);
        if ($user_id) {
            $values = $wpdb->get_col("SELECT DISTINCT {$dimension} FROM {$wpdb->prefix}aza_visits_last WHERE " . ($aza_multi_user ? "landing_page_author = $user_id AND" : "") . " $dimension IS NOT NULL AND $dimension != ''");
        } else {
            $values = $wpdb->get_col("SELECT DISTINCT {$dimension} FROM {$wpdb->prefix}aza_visits_last WHERE $dimension IS NOT NULL AND $dimension != ''");
        }
        $values = array_combine($values, $values);
        if (isset($settings['dimensions'][$dimension]['converter'])) {
            foreach ($values as $value => &$label) {
                $label = aza_convert($settings['dimensions'][$dimension]['converter'], $value);
            }
        }
        if (isset($settings['dimensions'][$dimension]['converters'])) {
            foreach ($settings['dimensions'][$dimension]['converters'] as $converter) {
                if (!isset($converter['condition'])) {
                    foreach ($values as $value => &$label) {
                        $label = aza_convert($converter, $value);
                    }
                }
            }
        }
        print json_encode($values);
    }
    wp_die();
}

add_action('wp_ajax_aza_update_user', 'aza_update_user');

function aza_update_user() {
    if (isset($_POST['user']) && is_array($_POST['user'])) {
        $user_data = $_POST['user'];
        foreach ($user_data as $key => &$value) {
            $value = sanitize_text_field($value);
        }
        $user_data['ID'] = get_current_user_id();
        if (count($user_data) > 1) {
            wp_update_user($user_data);
        }
    }
    if (isset($_POST['meta']) && is_array($_POST['meta'])) {
        $user_meta = $_POST['meta'];
        foreach ($user_meta as $key => $value) {
            update_user_meta(get_current_user_id(), sanitize_text_field($key), sanitize_text_field($value));
        }
    }
    wp_die();
}

function aza_convert($converter, $value) {
    if (isset($converter['pattern'])) {
        if (preg_match($converter['pattern'], $value, $matches)) {
            $value = $matches[1];
        }
    }
    if (isset($converter['map']) && isset($converter['map'][$value])) {
        $value = $converter['map'][$value];
    }
    if (isset($converter['table'])) {
        global $wpdb;
        $to = $converter['to'];
        $from = $converter['from'];
        $table = $converter['table'];
        if (is_string($value)) {
            $table_value = $wpdb->get_var("SELECT $to FROM $table WHERE $from = '$value'");
        } else {
            $table_value = $wpdb->get_var("SELECT $to FROM $table WHERE $from = $value");
        }
        if (!is_null($table_value)) {
            $value = $table_value;
        }
    }
    return $value;
}

function aza_get_full_source($row) {
    $full_source = array();
    if (!empty($row['marker_level_1'])) {
        $full_source[] = aza_dimension_convert($row, 'marker_level_1', $row['marker_level_1']);
        if (!empty($row['marker_level_2'])) {
            $full_source[] = aza_dimension_convert($row, 'marker_level_2', $row['marker_level_2']);
        }
        if (!empty($row['marker_level_3'])) {
            $full_source[] = aza_dimension_convert($row, 'marker_level_3', $row['marker_level_3']);
        }
        if (!empty($row['marker_level_4'])) {
            $full_source[] = aza_dimension_convert($row, 'marker_level_4', $row['marker_level_4']);
        }
        if (!empty($row['marker_level_5'])) {
            $full_source[] = aza_dimension_convert($row, 'marker_level_5', $row['marker_level_5']);
        }
        if (!empty($row['marker_level_6'])) {
            $full_source[] = aza_dimension_convert($row, 'marker_level_6', $row['marker_level_6']);
        }
        if (!empty($row['marker_level_7'])) {
            $full_source[] = aza_dimension_convert($row, 'marker_level_7', $row['marker_level_7']);
        }
    } elseif (!empty($row['utm_source'])) {
        $full_source[] = aza_dimension_convert($row, 'utm_source', $row['utm_source']);
        if (!empty($row['utm_medium'])) {
            $full_source[] = aza_dimension_convert($row, 'utm_medium', $row['utm_medium']);
        }
        if (!empty($row['utm_campaign'])) {
            $full_source[] = aza_dimension_convert($row, 'utm_campaign', $row['utm_campaign']);
        }
        if (!empty($row['utm_content'])) {
            $full_source[] = aza_dimension_convert($row, 'utm_content', $row['utm_content']);
        }
        if (!empty($row['utm_term'])) {
            $full_source[] = aza_dimension_convert($row, 'utm_term', $row['utm_term']);
        }
    }
    return implode(' - ', $full_source);
}

function aza_dimension_convert($row, $dimension, $value) {
    $settings = aza_settings();
    if (isset($settings['dimensions'][$dimension])) {
        $converters = array();
        if (isset($settings['dimensions'][$dimension]['converters'])) {
            $converters = $settings['dimensions'][$dimension]['converters'];
        }
        if (isset($settings['dimensions'][$dimension]['converter'])) {
            $converters = array($settings['dimensions'][$dimension]['converter']);
        }
        if (!empty($converters)) {
            foreach ($converters as $converter) {
                $condition = true;
                if (isset($converter['condition'])) {
                    if (isset($row[$converter['condition']['column']]) && $row[$converter['condition']['column']] != $converter['condition']['value']) {
                        $condition = false;
                    }
                }
                if ($condition) {
                    $value = aza_convert($converter, $value);
                }
            }
        }
    }

    return $value;
}

function aza_row_convert($row) {
    $settings = aza_settings();
    if (isset($row['visit_timestamp'])) {
        $row['visit_datetime'] = date_i18n(get_option('date_format'), $row['visit_timestamp'] + ( get_option('gmt_offset') * HOUR_IN_SECONDS )) . ' - ' . date_i18n(get_option('time_format'), $row['visit_timestamp'] + ( get_option('gmt_offset') * HOUR_IN_SECONDS ));
    }
    if (isset($row['lead_timestamp'])) {
        $row['lead_datetime'] = date_i18n(get_option('date_format'), $row['lead_timestamp'] + ( get_option('gmt_offset') * HOUR_IN_SECONDS )) . ' - ' . date_i18n(get_option('time_format'), $row['lead_timestamp'] + ( get_option('gmt_offset') * HOUR_IN_SECONDS ));
    }
    $row['source'] = '';
    if (!empty($row['utm_source'])) {
        $row['source'] = $row['utm_source'];
    }
    if (!empty($row['marker_level_1'])) {
        $row['source'] = $row['marker_level_1'];
    }
    $row['full_source'] = aza_get_full_source($row);

    foreach ($row as $dimension => $value) {
        $row['display_' . $dimension] = aza_dimension_convert($row, $dimension, $value);
        if (empty($row['display_' . $dimension])) {
            $row['display_' . $dimension] = __('Unknown', 'aza');
        }
    }
    return $row;
}

add_action('wp_ajax_aza_load_report', 'aza_load_report');

function aza_load_report() {
    if (isset($_POST['report']) && isset($_POST['from']) && isset($_POST['to'])) {
        $settings = aza_settings();
        $levels = array();
        if (isset($_POST['levels']) && is_array($_POST['levels'])) {
            $levels = $_POST['levels'];
            foreach ($levels as &$level) {
                $level = sanitize_text_field($level);
            }
        }
        if (is_numeric($_POST['from'])) {
            $from = sanitize_text_field($_POST['from']);
        } else {
            $from = new DateTime(sanitize_text_field($_POST['from']), aza_timezone());
            $from = $from->getTimestamp();
        }
        if (is_numeric($_POST['to'])) {
            $to = sanitize_text_field($_POST['to']);
        } else {
            $to = new DateTime(sanitize_text_field($_POST['to']), aza_timezone());
            $to = $to->getTimestamp();
        }
        foreach ($settings['reports'] as $report_settings) {
            if ($report_settings['name'] == sanitize_text_field($_POST['report'])) {
                $rows = aza_get_report($report_settings, $from, $to, $levels);

                $display_rows = array();
                for ($i = 0; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    $display_row = $row;
                    for ($j = 0; $j <= count($levels); $j++) {
                        $dimension_settings = $report_settings['dimensions'][$j];
                        $converters = array();
                        if (isset($settings['dimensions'][$dimension_settings['dimension']]['converters'])) {
                            $converters = $settings['dimensions'][$dimension_settings['dimension']]['converters'];
                        }
                        if (isset($settings['dimensions'][$dimension_settings['dimension']]['converter'])) {
                            $converters = array($settings['dimensions'][$dimension_settings['dimension']]['converter']);
                        }
                        if (!empty($converters)) {
                            foreach ($converters as $converter) {
                                $condition = true;
                                if (isset($converter['condition'])) {
                                    if (isset($row[$converter['condition']['column']]) && $row[$converter['condition']['column']] != $converter['condition']['value']) {
                                        $condition = false;
                                    }
                                }
                                if ($condition) {
                                    $display_row[$dimension_settings['dimension']] = aza_convert($converter, $row[$dimension_settings['dimension']]);
                                    if ($row[$dimension_settings['dimension']] != $display_row[$dimension_settings['dimension']]) {
                                        break;
                                    }
                                }
                            }
                        }
                        if (is_null($row[$dimension_settings['dimension']]) || $row[$dimension_settings['dimension']] === '') {
                            $display_row[$dimension_settings['dimension']] = __('Unknown', 'aza');
                        }
                    }
                    $display_rows[] = $display_row;
                }

                print json_encode(array(
                    'rows' => $rows,
                    'display_rows' => $display_rows,
                ));
                break;
            }
        }
    }
    wp_die();
}

add_action('wp_ajax_aza_load_chart', 'aza_load_chart');

function aza_load_chart() {
    if (isset($_POST['report']) && isset($_POST['from']) && isset($_POST['to'])) {
        $settings = aza_settings();
        if (is_numeric($_POST['from'])) {
            $from = sanitize_text_field($_POST['from']);
        } else {
            $from = new DateTime(sanitize_text_field($_POST['from']), aza_timezone());
            $from = $from->getTimestamp();
        }
        if (is_numeric($_POST['to'])) {
            $to = sanitize_text_field($_POST['to']);
        } else {
            $to = new DateTime(sanitize_text_field($_POST['to']), aza_timezone());
            $to = $to->getTimestamp();
        }
        $type = 'day';
        if (isset($_POST['type'])) {
            $type = sanitize_text_field($_POST['type']);
        }
        foreach ($settings['reports'] as $report_settings) {
            if ($report_settings['name'] == sanitize_text_field($_POST['report'])) {
                $levels = array();
                if (isset($_POST['levels']) && is_array($_POST['levels'])) {
                    $levels = $_POST['levels'];
                    foreach ($levels as &$level) {
                        $level = sanitize_text_field($level);
                    }
                }
                $rows = aza_get_chart($report_settings, $from, $to, $type, $levels);
                print json_encode($rows);
                break;
            }
        }
    }
    wp_die();
}

function aza_filesystem() {
    static $creds = false;

    require_once ABSPATH . '/wp-admin/includes/template.php';
    require_once ABSPATH . '/wp-admin/includes/file.php';

    if ($creds === false) {
        if (false === ( $creds = request_filesystem_credentials(admin_url()) )) {
            exit();
        }
    }

    if (!WP_Filesystem($creds)) {
        request_filesystem_credentials(admin_url(), '', true);
        exit();
    }
}

add_shortcode('aza_promo_code', 'aza_promo_code');

function aza_promo_code($atts) {
    ob_start();
    ?>
    <script>
        (function ($) {
            $(function () {
                $(window).on("aza-counter", function () {
                    if ('promo_code' in aza) {
                        $('.aza-promo-code').text(aza.promo_code);
                    }
                });
            });
        })(window.jQuery);
    </script>
    <span class="aza-promo-code"></span>
    <?php
    return ob_get_clean();
}

add_action('wp_ajax_aza_leads_datatable', 'aza_leads_datatable');

function aza_leads_datatable() {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $user_id = false;
        if (!in_array('administrator', (array) $user->roles)) {
            $user_id = get_current_user_id();
        }

        if (isset($_POST['draw']) && is_numeric($_POST['draw']) && isset($_POST['start']) && is_numeric($_POST['start']) && isset($_POST['length']) && is_numeric($_POST['length']) && is_array($_POST['dimensions'])) {
            global $wpdb, $aza_multi_user;
            $dimensions = array();
            foreach ($_POST['dimensions'] as $dimension => $value) {
                $dimensions[] = " (v." . esc_sql($dimension) . " = '" . esc_sql($value) . "') ";
            }
            $dimensions = implode(' AND ', $dimensions);
            if (!empty($dimensions)) {
                $dimensions = 'AND ' . $dimensions;
            }
            $search = '';
            if (!empty($_POST['search']['value'])) {
                $search = sanitize_text_field($_POST['search']['value']);
                $search = "AND l.lead_id LIKE '%$search%'";
            }
            $start = sanitize_text_field($_POST['start']);
            $length = sanitize_text_field($_POST['length']);
            if ($user_id && $aza_multi_user) {
                $leads = $wpdb->get_results("SELECT l.*, v.marker_level_1, v.utm_source FROM {$wpdb->prefix}aza_leads as l INNER JOIN {$wpdb->prefix}aza_visits_last as v ON v.promo_code = l.promo_code WHERE " . ($aza_multi_user ? "v.landing_page_author = $user_id" : "") . " $dimensions $search ORDER BY l.lead_timestamp DESC LIMIT $start,$length", ARRAY_A);
                $num_rows = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}aza_leads as l INNER JOIN {$wpdb->prefix}aza_visits_last as v ON v.promo_code = l.promo_code WHERE " . ($aza_multi_user ? "v.landing_page_author = $user_id" : "") . " $dimensions $search");
            } else {
                $leads = $wpdb->get_results("SELECT l.*, v.marker_level_1, v.utm_source FROM {$wpdb->prefix}aza_leads as l INNER JOIN {$wpdb->prefix}aza_visits_last as v ON v.promo_code = l.promo_code WHERE 1=1 $dimensions $search ORDER BY l.lead_timestamp DESC LIMIT $start,$length", ARRAY_A);
                $num_rows = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}aza_leads as l INNER JOIN {$wpdb->prefix}aza_visits_last as v ON v.promo_code = l.promo_code WHERE 1=1 $dimensions $search");
            }
            $converted_leads = array();
            foreach ($leads as $lead) {
                $converted_leads[] = aza_row_convert($lead);
            }
            $data_leads = array();
            foreach ($converted_leads as $lead) {
                $data_lead = array();
                foreach ($_POST['columns'] as $column) {
                    if (isset($lead[$column['name']])) {
                        $data_lead[] = $lead[$column['name']];
                    } else {
                        $data_lead[] = __('Unknown', 'aza');
                    }
                }
                $data_leads[] = $data_lead;
            }
            $data = array(
                'draw' => sanitize_text_field($_POST['draw']),
                'recordsTotal' => $num_rows,
                'recordsFiltered' => $num_rows,
                'data' => $data_leads,
            );
            print json_encode($data);
        }
    }
    wp_die();
}

add_action('wp_ajax_aza_lead_visits_history_datatable', 'aza_lead_visits_history_datatable');

function aza_lead_visits_history_datatable() {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $user_id = false;
        if (!in_array('administrator', (array) $user->roles)) {
            $user_id = get_current_user_id();
        }

        if (isset($_POST['draw']) && is_numeric($_POST['draw']) && isset($_POST['start']) && is_numeric($_POST['start']) && isset($_POST['length']) && is_numeric($_POST['length'])) {
            if (isset($_REQUEST['type']) && isset($_REQUEST['lead_id']) && is_numeric($_REQUEST['lead_id'])) {
                global $wpdb, $aza_multi_user;
                $type = sanitize_text_field($_REQUEST['type']);
                $lead_id = sanitize_text_field($_REQUEST['lead_id']);
                $start = sanitize_text_field($_POST['start']);
                $length = sanitize_text_field($_POST['length']);
                if ($user_id) {
                    $lead = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}aza_leads WHERE " . ($aza_multi_user ? "lead_page_author = $user_id AND" : "") . " lead_id = $lead_id AND type = '$type' LIMIT 0,1", ARRAY_A);
                    $visits = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aza_visits_last WHERE " . ($aza_multi_user ? "landing_page_author = $user_id AND" : "") . " visitor_id = '{$lead['visitor_id']}' ORDER BY visit_timestamp DESC LIMIT $start,$length", ARRAY_A);
                    $num_rows = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}aza_visits_last WHERE " . ($aza_multi_user ? "landing_page_author = $user_id AND" : "") . " visitor_id = '{$lead['visitor_id']}'");
                } else {
                    $lead = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}aza_leads WHERE lead_id = $lead_id AND type = '$type' LIMIT 0,1", ARRAY_A);
                    $visits = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aza_visits_last WHERE visitor_id = '{$lead['visitor_id']}'  ORDER BY visit_timestamp DESC LIMIT $start,$length", ARRAY_A);
                    $num_rows = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}aza_visits_last WHERE visitor_id = '{$lead['visitor_id']}'");
                }

                $converted_visits = array();
                foreach ($visits as $visit) {
                    $converted_visits[] = aza_row_convert($visit);
                }
                $data_visits = array();
                foreach ($converted_visits as $visit) {
                    $data_visit = array();
                    foreach ($_POST['columns'] as $column) {
                        if (isset($visit[$column['name']])) {
                            $data_visit[] = $visit[$column['name']];
                        } else {
                            $data_visit[] = __('Unknown', 'aza');
                        }
                    }
                    $data_visits[] = $data_visit;
                }
                $data = array(
                    'draw' => sanitize_text_field($_POST['draw']),
                    'recordsTotal' => $num_rows,
                    'recordsFiltered' => $num_rows,
                    'data' => $data_visits,
                );
                print json_encode($data);
            }
        }
    }
    wp_die();
}

function aza_submit_lead($lead_id) {
    global $wpdb;
    $visitor_id = aza_get_visitor_id();
    $visit = aza_get_last_visit($visitor_id);
    $lead_timestamp = time();
    $lead = get_post($lead_id);
    $lead_page_id = $lead->post_parent;
    $lead_page = get_post($lead_page_id);
    $wpdb->query("REPLACE INTO {$wpdb->prefix}aza_leads (promo_code, visitor_id, lead_timestamp, lead_id, lead_page_id, lead_page_author, type, status, first_cost, amount) VALUES ({$visit['promo_code']}, '$visitor_id', $lead_timestamp, $lead_id, $lead_page_id, {$lead_page->post_author}, 'azh', 'in_work', 0, 0)");
    update_post_meta($lead_id, '_aza-visitor', $visitor_id);
    aza_refresh_leads_info($lead_page->post_author, $visitor_id);
}
