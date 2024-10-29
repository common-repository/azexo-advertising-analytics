<?php

function aza_insert_names($table, $names) {
    global $wpdb;
    //$wpdb->query("TRUNCATE TABLE $table");
    foreach ($names as $id => $name) {
        $wpdb->query("REPLACE INTO $table (id, name) VALUES ($id, '$name')");
    }
}

function aza_create_page($user_id, $title) {
    $page_id = get_page_by_title($title);
    if($page_id) {
        return $page_id;
    }
    $page_data = array(
        'post_author' => $user_id,
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_title' => $title,
        'comment_status' => 'closed'
    );
    $page_id = wp_insert_post($page_data);
    return $page_id;
}

add_action('aza_load_default_settings', 'aza_load_default_settings', 10, 1);

function aza_load_default_settings($user_id) {
    if (file_exists(dirname(__FILE__) . '/aza_settings.json')) {
        aza_filesystem();
        global $wp_filesystem;
        $settings = $wp_filesystem->get_contents(dirname(__FILE__) . '/aza_settings.json');
        wp_set_current_user($user_id);
        update_option('aza-settings', json_decode($settings, true));
        update_user_meta($user_id, 'aza-reports', array($settings['default-report']));
    }
}

add_action('aza_demo_generation', 'aza_demo_generation', 10, 1);

function aza_demo_generation($user_id) {
    global $wpdb, $aza_models, $aza_search_engines, $aza_lead_statuses;
    $visitors = array();
    for ($i = 0; $i < 100; $i++) {
        $visitors[] = uniqid();
    }

    foreach (array_keys($aza_models) as $model) {
        $wpdb->query("DELETE FROM {$wpdb->prefix}aza_visits_{$model} WHERE landing_page_author = $user_id");
    }
    $wpdb->query("DELETE FROM {$wpdb->prefix}aza_leads WHERE lead_page_author = $user_id");
    $pages = array();
    $pages[aza_create_page($user_id, __('Landing page 1', 'aza'))] = __('Landing page 1', 'aza');
    $pages[aza_create_page($user_id, __('Landing page 2', 'aza'))] = __('Landing page 2', 'aza');


    $yandex_campaigns = array(
        11111 => __('First yandex campaign', 'aza'),
        22222 => __('Second yandex campaign', 'aza'),
    );
    aza_insert_names("{$wpdb->prefix}aza_yandex_campaigns", $yandex_campaigns);
    $yandex_adgroups = array(
        11111 => __('First yandex ad group', 'aza'),
        22222 => __('Second yandex ad group', 'aza'),
    );
    aza_insert_names("{$wpdb->prefix}aza_yandex_adgroups", $yandex_adgroups);
    $yandex_keywords = array(
        11111 => __('first keyword', 'aza'),
        22222 => __('second keyword', 'aza'),
        33333 => __('third keyword', 'aza'),
    );
    aza_insert_names("{$wpdb->prefix}aza_yandex_keywords", $yandex_keywords);


    $adwords_campaigns = array(
        11111 => __('First adwords campaign', 'aza'),
        22222 => __('Second adwords campaign', 'aza'),
    );
    aza_insert_names("{$wpdb->prefix}aza_adwords_campaigns", $adwords_campaigns);
    $adwords_adgroups = array(
        11111 => __('First adwords ad group', 'aza'),
        22222 => __('Second adwords ad group', 'aza'),
    );
    aza_insert_names("{$wpdb->prefix}aza_adwords_adgroups", $adwords_adgroups);
    $adwords_keywords = array(
        11111 => __('first keyword', 'aza'),
        22222 => __('second keyword', 'aza'),
        33333 => __('third keyword', 'aza'),
    );
    aza_insert_names("{$wpdb->prefix}aza_adwords_keywords", $adwords_keywords);


    $vk_campaigns = array(
        11111 => __('First VK campaign', 'aza'),
        22222 => __('Second VK campaign', 'aza'),
    );
    aza_insert_names("{$wpdb->prefix}aza_vk_campaigns", $vk_campaigns);
    $vk_ads = array(
        11111 => __('First VK ad', 'aza'),
        22222 => __('Second VK ad', 'aza'),
    );
    aza_insert_names("{$wpdb->prefix}aza_vk_ads", $vk_ads);



    $facebook_campaigns = array(
        11111 => __('First Facebook campaign', 'aza'),
        22222 => __('Second Facebook campaign', 'aza'),
    );
    aza_insert_names("{$wpdb->prefix}aza_facebook_campaigns", $facebook_campaigns);
    $facebook_ad_sets = array(
        11111 => __('First Facebook ad set', 'aza'),
        22222 => __('Second Facebook ad set', 'aza'),
    );
    aza_insert_names("{$wpdb->prefix}aza_facebook_adgroups", $facebook_ad_sets);
    $facebook_ads = array(
        11111 => __('First Facebook ad', 'aza'),
        22222 => __('Second Facebook ad', 'aza'),
    );
    aza_insert_names("{$wpdb->prefix}aza_facebook_ads", $facebook_ads);


    $from = new DateTime('- 30 days', aza_timezone());
    $from = $from->getTimestamp();
    $to = new DateTime('now', aza_timezone());
    $to = $to->getTimestamp();
    $periods = aza_get_periods($from, $to);
    foreach ($periods as $period) {
        $today_visitors = array();
        for ($i = 0; $i < 5; $i++) {
            $visitor = $visitors[rand(0, count($visitors) - 1)];
            $today_visitors[] = $visitor;
            $timestamp = rand($period['begin'], $period['end']);
            $landing_page_id = array_keys($pages);
            $landing_page_id = $landing_page_id[rand(0, count($landing_page_id) - 1)];
            $page = get_post($landing_page_id);
            $page_group_id = false;
            $cost = rand(10, 40);

            $source = array('seo', 'direct', 'vk', 'adwords', 'facebook');
            $source = $source[rand(0, count($source) - 1)];

            $aza = array();
            $utm_source = '';
            $utm_medium = '';
            $utm_campaign = '';
            $utm_content = '';
            $utm_term = '';
            switch ($source) {
                case 'seo':
                    $engine = array_keys($aza_search_engines);
                    $engine = $engine[rand(0, count($engine) - 1)];
                    $aza = array($source, $engine);
                    $utm_source = $source;
                    $utm_medium = $engine;
                    $utm_campaign = '';
                    $utm_content = '';
                    $utm_term = '';
                    break;
                case 'direct':
                    $medium = array('search', 'context');
                    $medium = $medium[rand(0, count($medium) - 1)];
                    $campaign = array_keys($yandex_campaigns);
                    $campaign = $campaign[rand(0, count($campaign) - 1)];
                    $content = array_keys($yandex_adgroups);
                    $content = $content[rand(0, count($content) - 1)];
                    $term = array_values($yandex_keywords);
                    $term = $term[rand(0, count($term) - 1)];

                    $aza = array($source, $medium, $campaign, $content, $term);
                    $utm_source = $source;
                    $utm_medium = $medium;
                    $utm_campaign = $campaign;
                    $utm_content = $content;
                    $utm_term = $term;
                    break;
                case 'adwords':
                    $medium = array('g', 'd');
                    $medium = $medium[rand(0, count($medium) - 1)];
                    $campaign = array_keys($adwords_campaigns);
                    $campaign = $campaign[rand(0, count($campaign) - 1)];
                    $content = array_keys($adwords_adgroups);
                    $content = $content[rand(0, count($content) - 1)];
                    $term = array_values($adwords_keywords);
                    $term = $term[rand(0, count($term) - 1)];

                    $aza = array($source, $medium, $campaign, $content, $term);
                    $utm_source = $source;
                    $utm_medium = $medium;
                    $utm_campaign = $campaign;
                    $utm_content = $content;
                    $utm_term = $term;
                    break;
                case 'vk':
                    $campaign = array_keys($vk_campaigns);
                    $campaign = $campaign[rand(0, count($campaign) - 1)];
                    $content = array_keys($vk_ads);
                    $content = $content[rand(0, count($content) - 1)];

                    $aza = array($source, $campaign, $content);
                    $utm_source = $source;
                    $utm_medium = '';
                    $utm_campaign = $campaign;
                    $utm_content = $content;
                    $utm_term = '';
                    break;
                case 'facebook':
                    $campaign = array_keys($facebook_campaigns);
                    $campaign = $campaign[rand(0, count($campaign) - 1)];
                    $content = array_keys($facebook_ads);
                    $content = $content[rand(0, count($content) - 1)];
                    $term = array_keys($facebook_ad_sets);
                    $term = $term[rand(0, count($term) - 1)];

                    $aza = array($source, $campaign, $content, $term);
                    $utm_source = $source;
                    $utm_medium = '';
                    $utm_campaign = $campaign;
                    $utm_content = $content;
                    $utm_term = $term;
                    break;
            }


            foreach (array_keys($aza_models) as $model) {
                $wpdb->query("INSERT INTO {$wpdb->prefix}aza_visits_{$model} ("
                        . "visit_timestamp,"
                        . "hour,"
                        . "day,"
                        . "day_of_week,"
                        . "week,"
                        . "month,"
                        . "visitor_id,"
                        . "marketing_cost,"
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
                        . $cost . ","
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
        for ($i = 0; $i < 5; $i++) {
            $visitor_id = $today_visitors[rand(0, count($today_visitors) - 1)];
            $visit = aza_get_last_visit($visitor_id);
            if (!empty($visit)) {
                $lead_timestamp = rand($visit['visit_timestamp'], $period['end']);
                $lead_page_id = array_keys($pages);
                $lead_page_id = $lead_page_id[rand(0, count($lead_page_id) - 1)];
                $lead_page = get_post($lead_page_id);
                $lead_id = rand(0, 10000);

                $type = array('azh', 'woocommerce', 'calltracking');
                $type = $type[rand(0, count($type) - 1)];
                $status = array_keys($aza_lead_statuses);
                $status = $status[rand(0, count($status) - 1)];
                $first_cost = rand(30, 100);
                $amount = $first_cost + rand(50, 700);

                $wpdb->query("REPLACE INTO {$wpdb->prefix}aza_leads (promo_code, visitor_id, lead_timestamp, lead_id, lead_page_id, lead_page_author, type, status, first_cost, amount) VALUES ({$visit['promo_code']}, '$visitor_id', $lead_timestamp, $lead_id, $lead_page_id, {$lead_page->post_author}, '$type', '$status', $first_cost, $amount)");
            }
        }
    }
    for ($i = 0; $i < count($visitors); $i++) {
        aza_refresh_leads_info($user_id, $visitors[$i]);
    }
}

add_filter('woocommerce_prevent_admin_access', 'aza_demo_prevent_admin_access', 10, 1);

function aza_demo_prevent_admin_access($prevent_admin_access) {
    return false;
}

function aza_create_user($user_email) {
    $user_login = sanitize_user($user_email, true);
    $user_login = explode('@', $user_login);
    $user_login = $user_login[0];
    if (username_exists($user_login)) {
        $i = 1;
        $user_login_tmp = $user_login;
        do {
            $user_login_tmp = $user_login . '_' . ($i++);
        } while (username_exists($user_login_tmp));
        $user_login = $user_login_tmp;
    }

    $user_fields = array(
        'user_login' => $user_login,
        'user_email' => $user_email,
        'user_pass' => wp_generate_password(),
        'role' => 'author'
    );
    $user_id = wp_insert_user($user_fields);

    $user_data = get_userdata($user_id);
    wp_set_current_user($user_id, $user_data->user_login);
    wp_clear_auth_cookie();
    wp_set_auth_cookie($user_id, true);
    do_action('wp_login', $user_data->user_login, $user_data);
    return $user_id;
}

add_action('init', 'aza_demo_init');

function aza_demo_init() {
    if (!is_user_logged_in() && isset($_GET['login']) && isset($_GET['email'])) {
        $user_email = sanitize_email($_GET['email']);
        $user_id = email_exists($user_email);
        if ($user_id) {
            $user_data = get_userdata($user_id);
            if (in_array('author', (array) $user_data->roles)) {
                wp_set_current_user($user_id, $user_data->user_login);
                wp_clear_auth_cookie();
                wp_set_auth_cookie($user_id, true);
                do_action('wp_login', $user_data->user_login, $user_data);
            }
        } else {
            $user_id = aza_create_user($user_email);
            wp_schedule_single_event(time() + 1, 'aza_demo_generation', array(
                'user_id' => (int) $user_id,
            ));
        }
    }
    if (isset($_GET['login']) && $_GET['login'] == 'analytics') {
        exit(wp_redirect(admin_url('admin.php?page=aza-analytics')));
    }
}
