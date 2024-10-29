<?php
add_action('admin_enqueue_scripts', 'aza_admin_scripts');

function aza_admin_scripts() {
    wp_enqueue_style('aza_styles', plugins_url('css/styles.css', __FILE__));
    wp_enqueue_style('select2', plugins_url('css/select2.css', __FILE__));
}

add_action('admin_menu', 'aza_admin_menu');

function aza_admin_menu() {
    add_menu_page(__('AZEXO Analytics', 'aza'), __('AZEXO Analytics', 'aza'), 'edit_pages', 'aza-analytics', 'aza_analytics_page');
}

function aza_analytics_page() {
    wp_enqueue_script('chart', plugins_url('js/Chart.js', __FILE__), array(), false, true);
    wp_enqueue_script('select2', plugins_url('js/select2.js', __FILE__), array('jquery'), false, true);
    wp_enqueue_script('numeral', plugins_url('js/numeral.min.js', __FILE__), array(), false, true);
    wp_enqueue_script('chosen', plugins_url('js/jquery.chosen.js', __FILE__), array('jquery'), false, true);
    wp_enqueue_script('jquery.floatThead', plugins_url('js/jquery.floatThead.js', __FILE__), array('jquery'), false, true);
    wp_enqueue_script('jquery.simplemodal', plugins_url('js/jquery.simplemodal.js', __FILE__), array('jquery'), false, true);
    wp_enqueue_script('aza_report', plugins_url('js/report.js', __FILE__), array('jquery', 'jquery-ui-datepicker', 'jquery-ui-autocomplete'), false, true);
    wp_enqueue_script('maskedinput', plugins_url('js/jquery.maskedinput.js', __FILE__), array('jquery'), false, true);
    wp_enqueue_script('dataTables', plugins_url('js/jquery.dataTables.js', __FILE__), array('jquery'), false, true);
    wp_localize_script('aza_report', 'aza', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'settings' => aza_settings(),
        'i18n' => array(
            'dataTable' => array(
                "sEmptyTable" => __("No data available in table", 'aza'),
                "sInfo" => __("Showing _START_ to _END_ of _TOTAL_ entries", 'aza'),
                "sInfoEmpty" => __("Showing 0 to 0 of 0 entries", 'aza'),
                "sInfoFiltered" => __("(filtered from _MAX_ total entries)", 'aza'),
                "sInfoPostFix" => __("", 'aza'),
                "sInfoThousands" => __(",", 'aza'),
                "sLengthMenu" => __("Show _MENU_ entries", 'aza'),
                "sLoadingRecords" => __("Loading...", 'aza'),
                "sProcessing" => __("Processing...", 'aza'),
                "sSearch" => __("Search:", 'aza'),
                "sZeroRecords" => __("No matching records found", 'aza'),
                "oPaginate" => array(
                    "sFirst" => __("First", 'aza'),
                    "sLast" => __("Last", 'aza'),
                    "sNext" => __("Next", 'aza'),
                    "sPrevious" => __("Previous", 'aza')
                ),
                "oAria" => array(
                    "sSortAscending" => __(": activate to sort column ascending", 'aza'),
                    "sSortDescending" => __(": activate to sort column descending", 'aza')
                )
            ),
            'dimension' => __('Dimension', 'aza'),
            'metric' => __('Metric', 'aza'),
            'model' => __('Model', 'aza'),
            'metrics_list' => __('Metrics list', 'aza'),
            'columns_in_table' => __('Columns in table', 'aza'),
            'equal' => __('Equal', 'aza'),
            'any_of' => __('Any of', 'aza'),
            'contain' => __('Contain', 'aza'),
            'does_not_contain' => __('Does not contain', 'aza'),
            'less' => __('Less', 'aza'),
            'more' => __('More', 'aza'),
            'report_name' => __('Report name', 'aza'),
            'new_report' => __('New report', 'aza'),
            'edit_report' => __('Edit report', 'aza'),
            'add_level' => __('Add level', 'aza'),
            'add_filter' => __('Add filter', 'aza'),
            'grouping_type' => __('Grouping type', 'aza'),
            'filters' => __('Filters', 'aza'),
            'ok' => __('OK', 'aza'),
            'cancel' => __('Cancel', 'aza'),
            'save' => __('Save', 'aza'),
            'remove_report' => __('Remove report', 'aza'),
            'leads' => __('Leads', 'aza'),
            'user_visits_for' => __('User visits history for: ', 'aza'),
            'zero_levels_warning' => __('Please add at least one grouping level', 'aza'),
            'duplicate_name_warning' => __('Please set unique report name', 'aza'),
        ),
    ));
    ?>

    <div class="wrap">
        <?php screen_icon(); ?>
        <h2><?php _e('AZEXO Analytics', 'aza'); ?></h2>
        <div class="azexo-analytics">            
            <div class="aza-menu">
                <div class="aza-item aza-active" data-class="aza-analytics">
                    <span><?php _e('Analytics', 'aza'); ?></span>
                </div>
                <?php do_action('aza_menu'); ?>
            </div>
            <div class="aza-dialogs">
                <div class="aza-item aza-analytics aza-active">
                    <div class="aza-reports-management">
                        <div class="aza-reports">
                            <span class="aza-report">
                                <span class="aza-name">report 1</span>
                                <span class="aza-settings"></span>
                            </span>                            
                            <span class="aza-report aza-active">
                                <span class="aza-name">report 2</span>
                                <span class="aza-settings"></span>
                            </span>                            
                            <span class="aza-add-report" title="<?php _e('Add new report', 'aza'); ?>">+</a>
                        </div>
                    </div>
                    <div class="aza-current-report">            
                        <div class="aza-date-filters">
                            <div class="aza-chart-type">
                                <select>
                                    <option value="day"><?php _e('Group by days', 'aza'); ?></option>
                                    <option value="week"><?php _e('Group by weeks', 'aza'); ?></option>
                                    <option value="month"><?php _e('Group by months', 'aza'); ?></option>
                                </select>                                
                            </div>
                            <input id="aza-compare-to-the-period" type="checkbox">
                            <div class="aza-first-interval">
                                <div class="aza-date-range">
                                    <input class="aza-min" type="text" placeholder="<?php _e('From date', 'aza'); ?>">
                                    <input class="aza-max" type="text" placeholder="<?php _e('To date', 'aza'); ?>">
                                </div>                            
                                <div class="aza-set-range" data-from="yesterday midnight" data-to="today midnight">
                                    <span class="aza-name"><?php _e('Yesterday', 'aza'); ?></span>
                                </div>                            
                                <div class="aza-set-range" data-from="today midnight" data-to="now">
                                    <span class="aza-name"><?php _e('Today', 'aza'); ?></span>
                                </div>                            
                                <div class="aza-set-range" data-from="-7 days" data-to="now">
                                    <span class="aza-name"><?php _e('7 days', 'aza'); ?></span>
                                </div>                            
                                <div class="aza-set-range" data-from="-30 days" data-to="now">
                                    <span class="aza-name"><?php _e('30 days', 'aza'); ?></span>
                                </div>                            
                                <div class="aza-set-range" data-from="first day of this month" data-to="now">
                                    <span class="aza-name"><?php _e('Month', 'aza'); ?></span>
                                </div>                            
                                <div class="aza-compare-to-the-period">
                                    <div class="aza-checkbox"><label for="aza-compare-to-the-period"></label></div>
                                    <span><?php _e('Compare to the period', 'aza'); ?></span>
                                </div>                          
                            </div>
                            <div class="aza-second-interval">
                                <div class="aza-date-range">
                                    <input class="aza-min" type="text" placeholder="<?php _e('From date', 'aza'); ?>">
                                    <input class="aza-max" type="text" placeholder="<?php _e('To date', 'aza'); ?>">
                                </div>                            
                                <div class="aza-set-range" data-from="yesterday midnight" data-to="today midnight">
                                    <span class="aza-name"><?php _e('Yesterday', 'aza'); ?></span>
                                </div>                            
                                <div class="aza-set-range" data-from="today midnight" data-to="now">
                                    <span class="aza-name"><?php _e('Today', 'aza'); ?></span>
                                </div>                            
                                <div class="aza-set-range" data-from="-7 days" data-to="now">
                                    <span class="aza-name"><?php _e('7 days', 'aza'); ?></span>
                                </div>                            
                                <div class="aza-set-range" data-from="-30 days" data-to="now">
                                    <span class="aza-name"><?php _e('30 days', 'aza'); ?></span>
                                </div>                            
                                <div class="aza-set-range" data-from="first day of this month" data-to="now">
                                    <span class="aza-name"><?php _e('Month', 'aza'); ?></span>
                                </div>                            
                            </div>
                        </div>                            
                        <div class="aza-chart-area">
                            <div class="aza-chart">
                                <canvas id="aza-chart" height="400"></canvas> 
                            </div>                            
                            <div class="aza-chart-metrics">
                                <input id="aza-expand-metrics-list" type="checkbox">
                                <div class="aza-expand-metrics-list">
                                    <label for="aza-expand-metrics-list" title="<?php _e('Expand metrics list', 'aza'); ?>"></label>
                                </div>
                                <div class="aza-metrics">
                                    <div class="aza-metric">  
                                        <span>Revenue</span>
                                    </div>                                                 
                                    <div class="aza-metric aza-selected">  
                                        <span>Visits</span>
                                    </div>                     
                                    <div class="aza-metric aza-selected">  
                                        <span>Leads Conversion</span>
                                    </div>                     
                                    <div class="aza-metric aza-selected">  
                                        <span>Sales Conversion</span>
                                    </div>                                                 
                                    <div class="aza-metric">  
                                        <span>Average revenue</span>
                                    </div>                                                 
                                    <div class="aza-metric">  
                                        <span>Profit</span>
                                    </div>                                                 
                                    <div class="aza-metric">  
                                        <span>ROI</span>
                                    </div>                                                 
                                    <div class="aza-other-metrics">
                                        <div class="aza-metric">  
                                            <span>Cost</span>
                                        </div>                                                 
                                        <div class="aza-metric">  
                                            <span>Leads</span>
                                        </div>                                                 
                                        <div class="aza-metric">  
                                            <span>Sales</span>
                                        </div>                                                 
                                        <div class="aza-metric">  
                                            <span>Cost</span>
                                        </div>                                                 
                                        <div class="aza-metric">  
                                            <span>Leads</span>
                                        </div>                                                 
                                        <div class="aza-metric">  
                                            <span>Sales</span>
                                        </div>                                                 
                                        <div class="aza-metric">  
                                            <span>Cost</span>
                                        </div>                                                 
                                        <div class="aza-metric">  
                                            <span>Leads</span>
                                        </div>                                                 
                                        <div class="aza-metric">  
                                            <span>Sales</span>
                                        </div>                                                 
                                        <div class="aza-metric">  
                                            <span>Cost</span>
                                        </div>                                                 
                                        <div class="aza-metric">  
                                            <span>Leads</span>
                                        </div>                                                 
                                        <div class="aza-metric">  
                                            <span>Sales</span>
                                        </div>                                                 
                                    </div>                            
                                </div>                            
                            </div>                            
                        </div>                            
                        <div class="aza-table">       
                            <table>
                                <thead>
                                    <tr>
                                        <th title="">
                                            column 1
                                        </th>
                                        <th title="">
                                            column 2
                                        </th>
                                        <th title="">
                                            column 3
                                        </th>
                                    </tr>
                                    <tr class="aza-means">
                                        <th>
                                            Total/Average
                                        </th>
                                        <th>
                                            12
                                        </th>
                                        <th>
                                            123
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="aza-level-1 aza-has-sublevels">
                                        <td>
                                            <img src="https://favicon.yandex.net/favicon/direct.yandex.ru">
                                            Yandex Direct
                                        </td>
                                        <td>
                                            2
                                        </td>
                                        <td>
                                            3
                                        </td>
                                    </tr>                            
                                    <tr class="aza-level-2 aza-has-sublevels aza-expanded">
                                        <td>
                                            test
                                        </td>
                                        <td>
                                            2
                                        </td>
                                        <td>
                                            3
                                        </td>
                                    </tr>                            
                                    <tr class="aza-level-3">
                                        <td>
                                            test
                                        </td>
                                        <td>
                                            2
                                        </td>
                                        <td>
                                            3
                                        </td>
                                    </tr>                            
                                    <tr class="aza-level-1">
                                        <td>
                                            <img src="https://favicon.yandex.net/favicon/google.com">
                                            Google AdWords
                                        </td>
                                        <td>
                                            2
                                        </td>
                                        <td>
                                            3
                                        </td>
                                    </tr>                            
                                    <tr class="aza-level-1">
                                        <td>
                                            <img src="https://favicon.yandex.net/favicon/google.com">
                                            Google AdWords
                                        </td>
                                        <td>
                                            2
                                        </td>
                                        <td>
                                            3
                                        </td>
                                    </tr>                            
                                </tbody>
                            </table>
                        </div>                            
                    </div>          
                </div>
                <?php do_action('aza_dialogs'); ?>
            </div>
            <table class="aza-leads">
                <thead>
                    <tr>
                        <th title="">
                            <?php _e('Lead ID', 'aza'); ?>
                        </th>
                        <th title="">
                            <?php _e('Lead type', 'aza'); ?>                            
                        </th>
                        <th title="">
                            <?php _e('Lead type', 'aza'); ?>                            
                        </th>
                        <th title="">
                            <?php _e('Lead status', 'aza'); ?>                            
                        </th>
                        <th title="">
                            <?php _e('Amount', 'aza'); ?>
                        </th>
                        <th title="">
                            <?php _e('Date/time', 'aza'); ?>
                        </th>
                        <th title="">                            
                            <?php _e('Promo code', 'aza'); ?>
                        </th>
                        <th title="">
                            <?php _e('Source', 'aza'); ?>
                        </th>
                        <th title="">
                            <?php _e('First cost', 'aza'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="">
                        <td>
                            1
                        </td>
                        <td>
                            1
                        </td>
                        <td>
                            1
                        </td>
                        <td>
                            2
                        </td>
                        <td>
                            3
                        </td>
                        <td>
                            3
                        </td>
                        <td>
                            3
                        </td>
                        <td>
                            3
                        </td>
                        <td>
                            3
                        </td>
                    </tr>                            
                </tbody>
            </table>
            <table class="aza-visits">
                <thead>
                    <tr>
                        <th title="">
                            <?php _e('Promo code', 'aza'); ?>
                        </th>
                        <th title="">
                            <?php _e('Visit date/time', 'aza'); ?>
                        </th>
                        <th title="">
                            <?php _e('Source', 'aza'); ?>                            
                        </th>
                        <th title="">
                            <?php _e('Landing page', 'aza'); ?>                            
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="">
                        <td>
                            1
                        </td>
                        <td>
                            1
                        </td>
                        <td>
                            1
                        </td>
                        <td>
                            1
                        </td>
                    </tr>                            
                </tbody>
            </table>

        </div>            
    </div>

    <?php
}
