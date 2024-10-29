<?php


add_filter('option_aza-settings', 'aza_get_user_settings');

function aza_get_user_settings($value) {
    $user = wp_get_current_user();
    if (!in_array('administrator', (array) $user->roles)) {
        return get_user_meta($user->ID, 'aza-settings', true);
    }
    return $value;
}

add_filter('pre_update_option_aza-settings', 'aza_update_user_settings', 10, 2);

function aza_update_user_settings($value, $old_value) {
    $user = wp_get_current_user();
    if (!in_array('administrator', (array) $user->roles)) {
        update_user_meta($user->ID, 'aza-settings', $value);
        return $old_value; //prevent updating
    }
    return $value;
}

add_filter('option_page_capability_aza-settings', 'aza_option_page_capability');

function aza_option_page_capability($capability) {
    return 'edit_pages';
}

add_action('admin_menu', 'aza_admin_menu_settings', 11);

function aza_admin_menu_settings() {
    add_submenu_page('aza-analytics', __('AZEXO Analytics Settings', 'aza'), __('Settings', 'aza'), 'edit_pages', 'aza-settings', 'aza_settings_page');
}

function aza_settings_page() {
    wp_enqueue_script('aza_admin', plugins_url('js/admin.js', __FILE__), array('jquery'), false, true);
    wp_enqueue_style('aza_admin', plugins_url('css/admin.css', __FILE__));
    ?>

    <div class="wrap">
        <?php screen_icon(); ?>
        <h2><?php _e('AZEXO Analytics Settings', 'aza'); ?></h2>

        <form method="post" action="options.php" class="aza-form">
            <?php
            settings_errors();
            settings_fields('aza-settings');
            do_settings_sections('aza-settings');
            submit_button(__('Save Settings', 'aza'));
            ?>
        </form>
    </div>

    <?php
}

function aza_options_callback() {
    
}

add_action('admin_init', 'aza_general_options');

function aza_general_options() {
    $settings = get_option('aza-settings');
    if (file_exists(dirname(__FILE__) . '/aza_settings.json')) {
        if (!is_array($settings)) {
            aza_filesystem();
            global $wp_filesystem;
            $settings = $wp_filesystem->get_contents(dirname(__FILE__) . '/aza_settings.json');
            update_option('aza-settings', json_decode($settings, true));
        }
    }
    register_setting('aza-settings', 'aza-settings', array());
    add_settings_section(
            'aza_options_section', // Section ID
            esc_html__('General', 'aza'), // Title above settings section
            'aza_options_callback', // Name of function that renders a description of the settings section
            'aza-settings'                     // Page to show on
    );
    add_settings_field(
            'phone-mask', // Field ID
            esc_html__('Phone mask', 'aza'), // Label to the left
            'aza_textfield', // Name of function that renders options on the page
            'aza-settings', // Page to show on
            'aza_options_section', // Associate with which settings section?
            array(
        'id' => 'phone-mask',
        'desc' => esc_html__('Use: "9", "a", "*"', 'aza'),
        'default' => '(999) 999-9999',
            )
    );

    add_settings_field(
            'thousands-delimiter', // Field ID
            esc_html__('Thousands delimiter', 'azf'), // Label to the left
            'aza_textfield', // Name of function that renders options on the page
            'aza-settings', // Page to show on
            'aza_options_section', // Associate with which settings section?
            array(
        'id' => 'thousands-delimiter',
        'default' => ',',
            )
    );

    add_settings_field(
            'decimal-delimiter', // Field ID
            esc_html__('Decimal delimiter', 'azf'), // Label to the left
            'aza_textfield', // Name of function that renders options on the page
            'aza-settings', // Page to show on
            'aza_options_section', // Associate with which settings section?
            array(
        'id' => 'decimal-delimiter',
        'default' => '.',
            )
    );
    add_settings_field(
            'currency-symbol', // Field ID
            esc_html__('Currency symbol', 'azf'), // Label to the left
            'aza_textfield', // Name of function that renders options on the page
            'aza-settings', // Page to show on
            'aza_options_section', // Associate with which settings section?
            array(
        'id' => 'currency-symbol',
        'default' => '$',
            )
    );
    add_settings_field(
            'money-format', // Field ID
            esc_html__('Money format', 'azf'), // Label to the left
            'aza_textfield', // Name of function that renders options on the page
            'aza-settings', // Page to show on
            'aza_options_section', // Associate with which settings section?
            array(
        'id' => 'money-format',
        'default' => '$0,0.00',
            )
    );
    add_settings_field(
            'float-format', // Field ID
            esc_html__('Float format', 'azf'), // Label to the left
            'aza_textfield', // Name of function that renders options on the page
            'aza-settings', // Page to show on
            'aza_options_section', // Associate with which settings section?
            array(
        'id' => 'float-format',
        'default' => '0,0.00',
            )
    );
}

function aza_textfield($args) {
    extract($args);
    $settings = get_option('aza-settings');
    if (isset($default) && !isset($settings[$id])) {
        $settings[$id] = $default;
    }
    if (!isset($type)) {
        $type = 'text';
    }
    ?>
    <input type="<?php print esc_attr($type); ?>" name="aza-settings[<?php print esc_attr($id); ?>]" value="<?php print esc_attr($settings[$id]); ?>">
    <p>
        <em>
            <?php if (isset($desc)) print $desc; ?>
        </em>
    </p>
    <?php
}

function aza_textarea($args) {
    extract($args);
    $settings = get_option('aza-settings');
    if (isset($default) && !isset($settings[$id])) {
        $settings[$id] = $default;
    }
    ?>
    <textarea name="aza-settings[<?php print esc_attr($id); ?>]" cols="50" rows="5"><?php print esc_attr($settings[$id]); ?></textarea>
    <p>
        <em>
            <?php if (isset($desc)) print $desc; ?>
        </em>
    </p>
    <?php
}

function aza_checkbox($args) {
    extract($args);
    $settings = get_option('aza-settings');
    if (isset($default) && !isset($settings[$id])) {
        $settings[$id] = $default;
    }
    foreach ($options as $value => $label) {
        ?>
        <div>
            <input id="<?php print esc_attr($id) . esc_attr($value); ?>" type="checkbox" name="aza-settings[<?php print esc_attr($id); ?>][<?php print esc_attr($value); ?>]" value="1" <?php @checked($settings[$id][$value], 1); ?>>
            <label for="<?php print esc_attr($id) . esc_attr($value); ?>"><?php print esc_html($label); ?></label>
        </div>
        <?php
    }
    ?>
    <p>
        <em>
            <?php if (isset($desc)) print $desc; ?>
        </em>
    </p>
    <?php
}

function aza_select($args) {
    extract($args);
    $settings = get_option('aza-settings');
    if (isset($default) && !isset($settings[$id])) {
        $settings[$id] = $default;
    }
    ?>
    <select name="aza-settings[<?php print esc_attr($id); ?>]">
        <?php
        foreach ($options as $value => $label) {
            ?>
            <option value="<?php print esc_attr($value); ?>" <?php @selected($settings[$id], $value); ?>><?php print esc_html($label); ?></option>
            <?php
        }
        ?>
    </select>
    <p>
        <em>
            <?php if (isset($desc)) print $desc; ?>
        </em>
    </p>
    <?php
}

function aza_radio($args) {
    extract($args);
    $settings = get_option('aza-settings');
    if (isset($default) && !isset($settings[$id])) {
        $settings[$id] = $default;
    }
    ?>
    <div>
        <?php
        foreach ($options as $value => $label) {
            ?>
            <input id="<?php print esc_attr($id) . esc_attr($value); ?>" type="radio" name="aza-settings[<?php print esc_attr($id); ?>]" value="<?php print esc_attr($value); ?>" <?php @checked($settings[$id], $value); ?>>
            <label for="<?php print esc_attr($id) . esc_attr($value); ?>"><?php print esc_html($label); ?></label>
            <?php
        }
        ?>
    </div>
    <p>
        <em>
            <?php if (isset($desc)) print $desc; ?>
        </em>
    </p>
    <?php
}
