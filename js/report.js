(function($) {
    "use strict";
    $(function() {
        function hexToRgbA(hex, alpha) {
            var c;
            if (/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)) {
                c = hex.substring(1).split('');
                if (c.length === 3) {
                    c = [c[0], c[0], c[1], c[1], c[2], c[2]];
                }
                c = '0x' + c.join('');
                return 'rgba(' + [(c >> 16) & 255, (c >> 8) & 255, c & 255].join(',') + ',' + alpha + ')';
            }
            throw new Error('Bad Hex');
        }
        function dateToStr(d) {
            var day = ("0" + d.getDate()).slice(-2);
            var month = ("0" + (d.getMonth() + 1)).slice(-2);
            var date = d.getFullYear() + "-" + (month) + "-" + (day);
            return date;
        }
        function getGMTtimestamp(date) {
            return date.getTime() / 1000 - date.getTimezoneOffset() * 60;
        }
        function open_modal(options, values, callback) {
            var $modal = $('<div class="aza-modal"></div>');
            $('<div class="aza-modal-title">' + options['title'] + '</div>').appendTo($modal);
            $('<div class="aza-modal-desc">' + options['desc'] + '</div>').appendTo($modal);
            var $controls = $('<div class="aza-modal-controls"></div>').appendTo($modal);
            $controls.css('column-count', '2');
            if ('columns' in options) {
                $controls.css('column-count', options.columns);
            }
            if ('fields' in options) {
                for (var name in options['fields']) {
                    (function(name) {
                        var field = options['fields'][name];
                        var $control = $('<div class="aza-modal-control"></div>').appendTo($controls);
                        $('<div class="aza-modal-label">' + field['label'] + '</div>').appendTo($control);
                        if ('options' in field) {
                            var $select = $('<select ' + (('multiple' in field && field['label']) ? 'multiple' : '') + '></select>').appendTo($control).on('change', function() {
                                if (('multiple' in field && field['label'])) {
                                    values[name] = $(this).find('option:selected').map(function() {
                                        return $(this).attr('value');
                                    }).toArray();
                                } else {
                                    values[name] = $(this).find('option:selected').attr('value');
                                }
                            });
                            for (var value in field['options']) {
                                if (value === values[name] || ('multiple' in field && values[name].indexOf(value) >= 0)) {
                                    $('<option value="' + value + '" selected>' + field['options'][value] + '</option>').appendTo($select);
                                } else {
                                    $('<option value="' + value + '">' + field['options'][value] + '</option>').appendTo($select);
                                }
                            }
                            $select.trigger('change');
                        } else {
                            $('<input type="text" value="' + values[name] + '">').appendTo($control).on('change', function() {
                                values[name] = $(this).val();
                            });
                        }
                    })(name);
                }
            }
            var $actions = $('<div class="aza-modal-actions"></div>').appendTo($modal);
            $('<div class="aza-modal-ok">' + aza.i18n.ok + '</div>').appendTo($actions).on('click', function() {
                $.modal.close();
                setTimeout(function() {
                    callback(values);
                }, 0);
                return false;
            });
            $('<div class="aza-modal-cancel">' + aza.i18n.cancel + '</div>').appendTo($actions).on('click', function() {
                $.modal.close();
                return false;
            });
            $modal.modal({
                autoResize: true,
                overlayClose: true,
                opacity: 0,
                overlayCss: {
                    "background-color": "black"
                },
                closeClass: "aza-close",
                onClose: function() {
                    setTimeout(function() {
                        $.modal.close();
                    }, 0);
                }
            });
        }
        function open_report_modal(settings, callback) {
            function add_level(level_settings) {
                function add_filter(filter_settings) {
                    function refresh_operators() {
                        $operator.empty();
                        if (filter_settings['field'] in aza.settings.dimensions) {
                            $('<option value="equal" ' + (((filter_settings['operator'] === 'equal' || filter_settings['operator'] === '')) ? 'selected' : '') + '>' + aza.i18n.equal + '</option>').appendTo($operator);
                            $('<option value="any_of" ' + ((filter_settings['operator'] === 'any_of') ? 'selected' : '') + '>' + aza.i18n.any_of + '</option>').appendTo($operator);
                            $('<option value="contain" ' + ((filter_settings['operator'] === 'contain') ? 'selected' : '') + '>' + aza.i18n.contain + '</option>').appendTo($operator);
                            $('<option value="does_not_contain" ' + ((filter_settings['operator'] === 'does_not_contain') ? 'selected' : '') + '>' + aza.i18n.does_not_contain + '</option>').appendTo($operator);
                        }
                        if (filter_settings['field'] in aza.settings.metrics) {
                            $('<option value="equal" ' + (((filter_settings['operator'] === 'equal' || filter_settings['operator'] === '')) ? 'selected' : '') + '>' + aza.i18n.equal + '</option>').appendTo($operator);
                            $('<option value="less" ' + ((filter_settings['operator'] === 'less') ? 'selected' : '') + '>' + aza.i18n.less + '</option>').appendTo($operator);
                            $('<option value="more" ' + ((filter_settings['operator'] === 'more') ? 'selected' : '') + '>' + aza.i18n.more + '</option>').appendTo($operator);
                        }
                        $operator.trigger('change');
                    }
                    function refresh_value() {
                        $value_col.empty();
                        if (filter_settings['field'] in  aza.settings.metrics) {
                            $value = $('<input class="aza-value" type="text" value="' + ('value' in filter_settings ? filter_settings.value : '') + '">').appendTo($value_col).on('change', function() {
                                filter_settings['value'] = $(this).val();
                            }).trigger('change');
                        }
                        if (filter_settings['field'] in  aza.settings.dimensions) {
                            switch (filter_settings['operator']) {
                                case 'equal':
                                    $.post(aza.ajaxurl, {
                                        action: 'aza_get_dimension_values',
                                        dimension: filter_settings['field']
                                    }, function(values) {
                                        if (values) {
                                            $value_col.empty();
                                            $value = $('<select class="aza-value">').appendTo($value_col).on('change', function() {
                                                filter_settings['value'] = $(this).val();
                                            });
                                            values = JSON.parse(values);
                                            for (var value in values) {
                                                $('<option value="' + value + '" ' + ((filter_settings['value'] === value) ? 'selected' : '') + '>' + values[value] + '</option>').appendTo($value);
                                            }
                                            $value.trigger('change');
                                        }
                                    });
                                    break;
                                case 'any_of':
                                    $.post(aza.ajaxurl, {
                                        action: 'aza_get_dimension_values',
                                        dimension: filter_settings['field']
                                    }, function(values) {
                                        if (values) {
                                            $value_col.empty();
                                            $value = $('<select class="aza-value" multiple>').appendTo($value_col).on('change', function() {
                                                filter_settings['value'] = $(this).val();
                                            });
                                            var selected_values = [];
                                            if(filter_settings['value']) {
                                                selected_values = filter_settings['value'].split('|');
                                            }
                                            values = JSON.parse(values);
                                            for (var value in values) {
                                                $('<option value="' + value + '" ' + ((selected_values.indexOf(value) >= 0) ? 'selected' : '') + '>' + values[value] + '</option>').appendTo($value);
                                            }
                                            $value.chosen().change(function() {
                                                if ($(this).val()) {
                                                    filter_settings['value'] = $(this).val().join('|');
                                                } else {
                                                    filter_settings['value'] = '';
                                                }
                                            }).trigger('change');
                                        }
                                    });
                                    break;
                                case 'contain':
                                case 'does_not_contain':
                                    $value = $('<input class="aza-value" type="text" value="' + ('value' in filter_settings ? filter_settings.value : '') + '">').appendTo($value_col).on('change', function() {
                                        filter_settings['value'] = $(this).val();
                                    }).trigger('change');
                                    break;
                            }
                        }
                    }
                    var $filter = $('<div class="aza-filter"></div>').appendTo($filters);
                    var $row = $('<div class="aza-row"></div>').appendTo($filter);
                    var $field_col = $('<div class="aza-col-sm-4"></div>').appendTo($row);
                    var $fields = $('<select class="aza-fields select2"></select>').appendTo($field_col).on('change', function() {
                        filter_settings['field'] = $(this).val();
                        refresh_operators();
                        refresh_value();
                    });
                    for (var dimension in  aza.settings.dimensions) {
                        if (!aza.settings.dimensions[dimension].table) {
                            $('<option value="' + dimension + '" ' + ((filter_settings['field'] === dimension) ? 'selected' : '') + '>' + aza.settings.dimensions[dimension].label + '</option>').appendTo($fields);
                        }
                    }
                    for (var metric in  aza.settings.metrics) {
                        $('<option value="' + metric + '" ' + ((filter_settings['field'] === metric) ? 'selected' : '') + '>' + aza.settings.metrics[metric].label + '</option>').appendTo($fields);
                    }

                    var $operator_col = $('<div class="aza-col-sm-3"></div>').appendTo($row);
                    var $operator = $('<select class="aza-operators"></select>').appendTo($operator_col).on('change', function() {
                        filter_settings['operator'] = $(this).val();
                        refresh_value();
                    });
                    var $value_col = $('<div class="aza-col-sm-4"></div>').appendTo($row);
                    var $value = false;
                    refresh_value();
                    $fields.trigger('change');
                    refresh_operators();
                    var $remove_col = $('<div class="aza-col-sm-1"></div>').appendTo($row);
                    $('<div class="aza-remove-filter"></div>').appendTo($remove_col).on('click', function() {
                        $filter.remove();
                        level_settings.filters = level_settings.filters.filter(function(fs) {
                            return !(fs['field'] === filter_settings['field'] && fs['operator'] === filter_settings['operator'] && fs['value'] === filter_settings['value']);
                        });
                        $.modal.update($('.aza-modal').outerHeight());
                    });
                    $.modal.update($('.aza-modal').outerHeight());
                }
                var $level = $('<div class="aza-level"></div>').appendTo($levels);
                var $row = $('<div class="aza-row"></div>').appendTo($level);
                var $data_col = $('<div class="aza-col-sm-4"></div>').appendTo($row);
                $('<div class="aza-modal-label">' + aza.i18n.grouping_type + '</div>').appendTo($data_col)
                var $dimensions = $('<select class="aza-dimensions select2"></select>').appendTo($data_col).on('change', function() {
                    level_settings['dimension'] = $(this).val();
                });
                for (var dimension in aza.settings.dimensions) {
                    if (!aza.settings.dimensions[dimension].table) {
                        var exists = false;
                        $(settings.dimensions).each(function() {
                            if (this === dimension) {
                                exists = true;
                                return false;
                            }
                        });
                        if (!exists) {
                            $('<option value="' + dimension + '" ' + ((level_settings['dimension'] === dimension) ? 'selected' : '') + '>' + aza.settings.dimensions[dimension].label + '</option>').appendTo($dimensions);
                        }
                    }
                }
                $dimensions.trigger('change');
                var $filters_col = $('<div class="aza-col-sm-7"></div>').appendTo($row);
                $('<div class="aza-modal-label">' + aza.i18n.filters + '</div>').appendTo($filters_col);
                var $filters = $('<div class="aza-filters"></div>').appendTo($filters_col);
                if (!('filters' in level_settings)) {
                    level_settings.filters = [];
                }
                $(level_settings.filters).each(function() {
                    add_filter(this);
                });
                $('<div class="aza-add-filter">' + aza.i18n.add_filter + '</div>').appendTo($filters_col).on('click', function() {
                    var new_filter = {};
                    level_settings.filters.push(new_filter);
                    add_filter(new_filter);
                });
                var $remove_col = $('<div class="aza-col-sm-1"></div>').appendTo($row);
                $('<div class="aza-remove-level"></div>').appendTo($remove_col).on('click', function() {
                    $level.remove();
                    settings.dimensions = settings.dimensions.filter(function(ls) {
                        return !(ls['dimension'] === level_settings['dimension']);
                    });
                    $.modal.update($('.aza-modal').outerHeight());
                });
                $.modal.update($('.aza-modal').outerHeight());
            }
            function add_metric(metric_settings) {
                if (!('metric' in metric_settings)) {
                    metric_settings.metric = Object.keys(aza.settings.metrics)[0];
                }
                if (!('model' in metric_settings)) {
                    metric_settings.model = 'last';
                }
                var $column_row = $('<div class="aza-column aza-row"></div>').appendTo($report_metrics_list);
                var $name_col = $('<div class="aza-col-sm-5"></div>').appendTo($column_row);
                $('<div class="aza-metric" title="' + aza.settings.metrics[metric_settings.metric].desc + '">' + aza.settings.metrics[metric_settings.metric].label + '</div>').appendTo($name_col);
                var $model_col = $('<div class="aza-col-sm-5"></div>').appendTo($column_row);

                var $model = $('<select class="aza-model"></select>').appendTo($model_col).on('change', function() {
                    var old_model = metric_settings.model;
                    metric_settings.model = $(this).val();
                    if ('chart' in settings) {
                        if ((metric_settings['metric'] + '-'+ old_model) in settings.chart) {
                            var m = settings.chart[metric_settings['metric'] + '-'+ old_model];
                            m = jQuery.extend({}, m);
                            settings.chart[metric_settings['metric'] + '-'+ metric_settings['model']] = m;
                            delete settings.chart[metric_settings['metric'] + '-'+ old_model];                            
                        }
                    }
                });
                for (var model in aza.settings.models) {
                    $('<option class="aza-model" value="' + model + '" ' + ((metric_settings.model === model) ? 'selected' : '') + '>' + aza.settings.models[model] + '</option>').appendTo($model);
                }

                var $remove_col = $('<div class="aza-col-sm-2"></div>').appendTo($column_row);
                $('<div class="aza-column-remove"></div>').appendTo($remove_col).on('click', function() {
                    $column_row.remove();
                    settings.metrics = settings.metrics.filter(function(ms) {
                        return !(ms['metric'] === metric_settings['metric']);
                    });
                    if ('chart' in settings) {
                        if ((metric_settings['metric'] + '-'+ metric_settings['model']) in settings.chart) {
                            delete settings.chart[metric_settings['metric'] + '-'+ metric_settings['model']];
                        }
                    }
                });
            }
            var $modal = $('<div class="aza-modal"></div>');
            $('<div class="aza-modal-title">' + aza.i18n.edit_report + '</div>').appendTo($modal);
            $('<div class="aza-modal-desc"></div>').appendTo($modal);
            var $controls = $('<div class="aza-modal-controls"></div>').appendTo($modal);
            var $control = $('<div class="aza-modal-control"></div>').appendTo($controls);
            $('<div class="aza-modal-label">' + aza.i18n.report_name + '</div>').appendTo($control);
            $('<input type="text" value="' + ('name' in settings ? settings.name : '') + '">').appendTo($control).on('change', function() {
                settings['name'] = $(this).val();
            }).trigger('change');
            var $grouping = $('<div class="aza-grouping"></div>').appendTo($modal);
            var $levels = $('<div class="aza-levels"></div>').appendTo($grouping);
            if (!('dimensions' in settings)) {
                settings.dimensions = [];
            }
            $(settings.dimensions).each(function() {
                add_level(this);
            });
            $('<div class="aza-add-level">' + aza.i18n.add_level + '</div>').appendTo($grouping).on('click', function() {
                var new_level = {};
                settings.dimensions.push(new_level);
                add_level(new_level);
            });


            if (!('metrics' in settings)) {
                settings.metrics = [];
            }
            var $metrics = $('<div class="aza-metrics"></div>').appendTo($modal);
            var $row = $('<div class="aza-row"></div>').appendTo($metrics);
            var $metrics_col = $('<div class="aza-col-sm-4"></div>').appendTo($row);
            $('<div class="aza-modal-label">' + aza.i18n.metrics_list + '</div>').appendTo($metrics_col);
            var $metrics_list = $('<div class="aza-metrics-list"></div>').appendTo($metrics_col);
            var $report_col = $('<div class="aza-col-sm-8"></div>').appendTo($row);
            $('<div class="aza-modal-label aza-report-columns-label">' + aza.i18n.columns_in_table + '</div>').appendTo($report_col);
            var $report_metrics = $('<div class="aza-report-columns"></div>').appendTo($report_col);

            Object.keys(aza.settings.metrics).sort().forEach(function(metric, i) {
                var $metric_row = $('<div class="aza-metric"></div>').appendTo($metrics_list);
                $('<div class="aza-metric-add" title="' + aza.settings.metrics[metric].desc + '">' + aza.settings.metrics[metric].label + '</div>').appendTo($metric_row).on('click', function() {

                    var new_metric = {
                        metric: $(this).data('metric')
                    };
                    settings.metrics.push(new_metric);
                    add_metric(new_metric);
                }).data('metric', metric);
            });

            var $column_header = $('<div class="aza-columns-header aza-row"></div>').appendTo($report_metrics);
            var $name_col = $('<div class="aza-col-sm-5"></div>').appendTo($column_header);
            $('<div class="aza-label">' + aza.i18n.metric + '</div>').appendTo($name_col);
            var $model_col = $('<div class="aza-col-sm-5"></div>').appendTo($column_header);
            $('<div class="aza-label">' + aza.i18n.model + '</div>').appendTo($model_col);
            var $remove_col = $('<div class="aza-col-sm-2"></div>').appendTo($column_header);
            var $report_metrics_list = $('<div class="aza-columns-list"></div>').appendTo($report_metrics);
            $(settings.metrics).each(function() {
                add_metric(this);
            });

            var $actions = $('<div class="aza-modal-actions"></div>').appendTo($modal);
            $('<div class="aza-modal-ok">' + aza.i18n.save + '</div>').appendTo($actions).on('click', function() {
                if (settings.dimensions.length === 0) {
                    alert(aza.i18n.zero_levels_warning);
                    return false;
                }
                var duplicate = false;
                $(aza.settings.reports).each(function() {
                    if (this.name === settings.name && !('edit' in settings)) {
                        duplicate = true;
                        return false;
                    }
                });
                if (duplicate) {
                    alert(aza.i18n.duplicate_name_warning);
                    return false;
                }
                if (!('chart' in settings)) {
                    settings.chart = {};
                    $(settings.metrics).each(function(index) {
                        settings.chart[this['metric'] + '-' + this['model']] = {
                            color: metrics_colors[index]
                        };
                    });
                }
                $.modal.close();
                setTimeout(function() {
                    callback(settings);
                }, 0);
                return false;
            });
            $('<div class="aza-modal-cancel">' + aza.i18n.cancel + '</div>').appendTo($actions).on('click', function() {
                $.modal.close();
                return false;
            });
            $('<div class="aza-modal-remove">' + aza.i18n.remove_report + '</div>').appendTo($actions).on('click', function() {
                aza.settings.reports = aza.settings.reports.filter(function(rs) {
                    return !(rs['name'] === settings['name']);
                });
                save_reports(function() {
                    refresh_reports();
                });
                $.modal.close();
                return false;
            });
            $modal.modal({
                autoResize: true,
                overlayClose: true,
                opacity: 0,
                overlayCss: {
                    "background-color": "black"
                },
                closeClass: "aza-close",
                onClose: function() {
                    setTimeout(function() {
                        $.modal.close();
                    }, 0);
                }
            });
        }
        function init_templates() {
            aza.templates = {};
            aza.templates.leads_table = $('table.aza-leads');
            aza.templates.leads_table.detach();
            aza.templates.visits_table = $('table.aza-visits');
            aza.templates.visits_table.detach();
            aza.templates.report_button = $('.aza-reports .aza-report').first();
            aza.templates.report_button.detach();
            $('.aza-reports .aza-report').remove();

            aza.templates.metric_button = $('.aza-chart-metrics .aza-metrics .aza-metric').first();
            aza.templates.metric_button.detach();
            $('.aza-chart-metrics .aza-metrics .aza-metric').remove();
        }
        function save_reports(callback) {
            $.post(aza.ajaxurl, {
                action: 'aza_update_user',
                meta: {
                    'aza-reports': JSON.stringify(aza.settings.reports)
                }
            }, function(data) {
                if (callback) {
                    callback();
                }
            });
        }
        function metrics_data_relative(metrics_data) {
            var relative = false;
            if (Object.keys(metrics_data).length > 1) {
                var integer = false;
                var money = false;
                var percent = false;
                for (var metric in metrics_data) {
                    metric = metric.split('-')[0];
                    if (aza.settings.metrics[metric].type == 'integer') {
                        integer = true;
                    }
                    if (aza.settings.metrics[metric].type == 'money') {
                        money = true;
                    }
                    if (aza.settings.metrics[metric].type == 'percent') {
                        percent = true;
                    }
                }
                if (!percent || money || integer) {
                    relative = true;
                }
            }
            if (relative) {
                var max = {};
                var min = {};
                for (var metric in metrics_data) {
                    max[metric] = metrics_data[metric][0];
                    min[metric] = metrics_data[metric][0];
                    $(metrics_data[metric]).each(function() {
                        if (max[metric] < this) {
                            max[metric] = this;
                        }
                        if (min[metric] > this) {
                            min[metric] = this;
                        }
                    });
                }
                for (var metric in metrics_data) {
                    for (var i = 0; i < metrics_data[metric].length; i++) {
                        metrics_data[metric][i] = (metrics_data[metric][i] - min[metric]) / (max[metric] - min[metric]) * 100;
                    }
                }
            }
            return metrics_data;
        }
        function refresh_chart(report_settings, labels, metrics_data) {
            var options = {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    xAxes: [{
                            gridLines: {
                                drawOnChartArea: false
                            },
                            ticks: {
                            }
                        }],
                    yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                maxTicksLimit: 5
                            }
                        }]
                },
                elements: {
                    point: {
                        radius: 0,
                        hitRadius: 10,
                        hoverRadius: 4,
                        hoverBorderWidth: 3
                    }
                },
                legend: {
                    display: false
                }
            };
            if ($('#aza-chart').data('aza-chart')) {
                $('#aza-chart').data('aza-chart').destroy();
            }
            var datasets = [];

            if (labels.length > 1) {
                metrics_data = metrics_data_relative(metrics_data);
            }

            for (var metric in metrics_data) {
                var color = report_settings.chart[metric].color;
                datasets.push({
                    label: aza.settings.metrics[metric.split('-')[0]].label,
                    backgroundColor: hexToRgbA(color, 0.1),
                    borderColor: color,
                    pointRadius: 3,
                    borderWidth: 2,
                    data: metrics_data[metric]
                });
            }
            var settings = {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: options
            };
            if (labels.length === 1) {
                settings.type = 'bar';
            }
            aza.chart = new Chart($('#aza-chart'), settings);
            $('#aza-chart').data('aza-chart', aza.chart);
        }
        function refresh_reports() {
            $('.aza-reports .aza-report').remove();
            $(aza.settings.reports).each(function() {
                var report_settings = this;
                report_settings.edit = true;
                var $report_button = aza.templates.report_button.clone();
                $report_button.data('report', report_settings['name']);
                $report_button.insertBefore('.aza-reports .aza-add-report');
                $report_button.find('.aza-name').text(report_settings.name);
                $report_button.find('.aza-settings').on('click', function() {
                    open_report_modal(report_settings, function(settings) {
                        save_reports(function() {
                            $report_button.trigger('click');
                            refresh_reports();
                        });
                    });
                    return false;
                });
                $report_button.on('click', function() {
                    aza.current_report_settings = report_settings;
                    load_report_table(report_settings, function() {
                        $report_button.addClass('aza-active');
                        $report_button.siblings().removeClass("aza-active");
                    });
                    load_report_chart(report_settings);
                });
            });
        }
        function load_report_chart(report_settings, levels, callback) {
            function prepare_labels(rows) {
                var labels = [];
                $(rows).each(function() {
                    labels.push(this.day);
                });
                return labels;
            }
            function prepare_metrics_data(rows) {
                var metrics_data = {
                };
                $(rows).each(function() {
                    for (var metric in this.row) {
                        if (!(metric in metrics_data)) {
                            metrics_data[metric] = [];
                        }
                        if (this.row[metric] === null) {
                            metrics_data[metric].push(0);
                        } else {
                            metrics_data[metric].push(parseFloat(this.row[metric]));
                        }
                    }
                });
                return metrics_data;
            }
            if (report_settings) {
                $.post(aza.ajaxurl, {
                    action: 'aza_load_chart',
                    report: report_settings['name'],
                    from: aza.from,
                    to: aza.to,
                    type: aza.type,
                    levels: levels ? levels : []
                }, function(rows) {
                    rows = JSON.parse(rows);
                    var labels = prepare_labels(rows);
                    var metrics_data = prepare_metrics_data(rows);
                    refresh_chart(report_settings, labels, metrics_data);
                    if (aza.compare) {
                        $.post(aza.ajaxurl, {
                            action: 'aza_load_chart',
                            report: report_settings['name'],
                            from: aza.compare_from,
                            to: aza.compare_to
                        }, function(rows) {
                            rows = JSON.parse(rows);
                            var metrics_data = prepare_metrics_data(rows);

                            metrics_data = metrics_data_relative(metrics_data);

                            for (var metric in metrics_data) {                                
                                var color = report_settings.chart[metric].color;
                                aza.chart.data.datasets.push({
                                    label: aza.settings.metrics[metric.split('-')[0]].label,
                                    backgroundColor: hexToRgbA(color, 0.1),
                                    borderColor: color,
                                    pointRadius: 3,
                                    borderWidth: 2,
                                    borderDash: [5, 5],
                                    data: metrics_data[metric]
                                });
                            }
                            aza.chart.update();
                        });
                    }
                    $('.aza-chart-metrics .aza-metrics .aza-metric').remove();
                    $(report_settings.metrics).each(function(index) {
                        var metric = this['metric'];
                        var model = this['model'];
                        var $metric_button = aza.templates.metric_button.clone();
                        if ((metric + '-' + model) in report_settings.chart) {
                            $metric_button.addClass('aza-selected');
                            $metric_button.find('span').css('background-color', metrics_colors[index]);
                        }
                        $metric_button.find('span').text(aza.settings.metrics[metric].label +' ('+ this['model'] + ')');
                        $metric_button.attr('title', aza.settings.metrics[metric].desc);
                        $metric_button.insertBefore('.aza-chart-metrics .aza-metrics .aza-other-metrics').on('click', function() {
                            if ($metric_button.is('.aza-selected')) {
                                $metric_button.removeClass('aza-selected');
                                $metric_button.find('span').css('background-color', '');
                                delete report_settings.chart[metric + '-' + model];
                            } else {
                                $metric_button.addClass('aza-selected');
                                $metric_button.find('span').css('background-color', metrics_colors[index]);
                                report_settings.chart[metric + '-' + model] = {
                                    color: metrics_colors[index]
                                };
                            }
                            save_reports(function() {
                                load_report_chart(report_settings);
                            });
                        });
                    });
                    if (callback) {
                        callback();
                    }
                });
            }
        }
        function number_format(type, value) {
            switch (type) {
                case 'integer':
                    value = Math.floor(value);
                    break;
                case 'money':
                    value = numeral(value).format(aza.settings.money_format);
                    break;
                case 'percent':
                    value = parseInt(value, 10) + '%';
                    break;
            }
            return value;
        }
        function load_report_table(report_settings, callback) {
            if (report_settings) {
                $.post(aza.ajaxurl, {
                    action: 'aza_load_report',
                    report: report_settings['name'],
                    from: aza.from,
                    to: aza.to,
                    levels: []
                }, function(data) {
                    function expand_dimension($row) {
                        $row.addClass('aza-expanded');
                        $row.data('sub-rows').each(function() {
                            $(this).show();
                        });
                        $row.data('sub-rows').last().addClass('aza-expanded-last');
                    }
                    function collapse_dimension($row) {
                        $row.removeClass('aza-expanded');
                        $row.data('sub-rows').each(function() {
                            $(this).hide();
                            if ($(this).data('sub-rows')) {
                                collapse_dimension($(this));
                            }
                        });
                        $row.data('sub-rows').last().removeClass('aza-expanded-last');
                    }
                    function add_dimension(dimension, display_dimension, $row, levels) {
                        var dimension_settings = aza.settings.dimensions[report_settings.dimensions[levels.length].dimension];
                        var new_levels = levels.slice(0);
                        new_levels.push(dimension);
                        var icon = '';
                        if ('icon' in dimension_settings) {
                            if (dimension_settings.icon) {
                                icon = '<img src="' + dimension_settings.icon + '">';
                            }
                        }
                        if (dimension in aza.settings.icons) {
                            icon = '<img src="' + aza.settings.icons[dimension] + '">';
                        }
                        var $d = $('<td>' + icon + display_dimension + '</td>').appendTo($row).on('click', function() {
                            if ($row.is('.aza-has-sublevels')) {
                                if ($row.is('.aza-expanded')) {
                                    collapse_dimension($row);
                                } else {
                                    if ($row.data('sub-rows')) {
                                        expand_dimension($row);
                                    } else {
                                        $.post(aza.ajaxurl, {
                                            action: 'aza_load_report',
                                            report: report_settings['name'],
                                            from: aza.from,
                                            to: aza.to,
                                            levels: new_levels
                                        }, function(data) {
                                            data = JSON.parse(data);
                                            var $current_row = $row;
                                            var $sub_rows = $();
                                            $(data.rows).each(function(index) {
                                                var row = this;
                                                var dimension = report_settings.dimensions[new_levels.length].dimension;
                                                var $sub_row = $('<tr class="aza-level-' + (new_levels.length + 1) + ' ' + (report_settings.dimensions.length > (new_levels.length + 1) ? 'aza-has-sublevels' : '') + '"></tr>').insertAfter($current_row);
                                                $sub_row.data('source', row);
                                                $current_row = $sub_row;
                                                $sub_rows = $sub_rows.add($sub_row);

                                                add_dimension(row[dimension], data.display_rows[index][dimension], $sub_row, new_levels);

                                                $(report_settings.metrics).each(function() {
                                                    $('<td>' + number_format(aza.settings.metrics[this.metric].type, row[this.metric + '-' + this.model]) + '</td>').appendTo($sub_row).data('value', row[this.metric]);
                                                });
                                            });
                                            $row.data('sub-rows', $sub_rows);
                                            expand_dimension($row);
                                        });
                                    }
                                }
                            }
                        });
                        $('<span class="aza-leads"></span>').appendTo($d).on('click', function() {
                            show_leads_datatable(report_settings, new_levels);
                            return false;
                        });
                        $('<span class="aza-chart"></span>').appendTo($d).on('click', function() {
                            $(this).closest('table').find('.aza-chart.aza-active').removeClass('aza-active');
                            $(this).addClass('aza-active');
                            load_report_chart(report_settings, new_levels);
                            return false;
                        });
                    }
                    data = JSON.parse(data);
                    var $table = $('.aza-table table:not(.floatThead-table)');
                    $table.floatThead('destroy');
                    $table.empty();
                    var $thead = $('<thead></thead>').appendTo($table);
                    var $titles = $('<tr></tr>').appendTo($thead);
                    var $means = $('<tr class="aza-means"></tr>').appendTo($thead);
                    $('<th>' + aza.i18n.dimension + '</th>').appendTo($titles);
                    $(report_settings.metrics).each(function() {
                        $('<th title="' + aza.settings.metrics[this.metric].desc + '">' + aza.settings.metrics[this.metric].label + ' <span class="aza-model">(' + this.model + ')</span></th>').appendTo($titles);
                    });
                    var $tbody = $('<tbody></tbody>').appendTo($table);
                    $(data.rows).each(function(index) {
                        var row = this;
                        var $row = $('<tr class="aza-level-1 ' + (report_settings.dimensions.length > 1 ? 'aza-has-sublevels' : '') + '"></tr>').appendTo($tbody);
                        $row.data('source', row);

                        if (report_settings.dimensions.length) {
                            add_dimension(row[report_settings.dimensions[0].dimension], data.display_rows[index][report_settings.dimensions[0].dimension], $row, []);
                        }

                        $(report_settings.metrics).each(function() {
                            $('<td>' + number_format(aza.settings.metrics[this.metric].type, row[this.metric + '-' + this.model]) + '</td>').appendTo($row).data('value', row[this.metric]);
                        });
                    });
                    $table.floatThead({scrollingTop: 50});
                    if (callback) {
                        callback();
                    }
                });
            }
        }
        function load_offline_marketing_costs() {
            var $tbody = $('.aza-marketing-costs table tbody');
            $tbody.empty();
            $.post(aza.ajaxurl, {
                action: 'aza_get_offline_marketing_costs'
            }, function(data) {
                data = JSON.parse(data);
                $(data).each(function() {
                    var marketing_cost = this;
                    var $tr = $('<tr></tr>').appendTo($tbody);
                    $('<td>' + marketing_cost.period + '</td>').appendTo($tr);
                    $('<td>' + marketing_cost.channel + '</td>').appendTo($tr);
                    $('<td>' + marketing_cost.cost + '</td>').appendTo($tr);
                    $('<td class="aza-remove"></td>').appendTo($tr).on('click', function() {
                        $.post(aza.ajaxurl, {
                            action: 'aza_remove_offline_marketing_cost',
                            channel: marketing_cost.channel,
                            from: marketing_cost.from_date,
                            to: marketing_cost.to_date
                        }, function(data) {
                            load_offline_marketing_costs();
                        });
                    });
                });
            });
        }
        function load_phones() {
            var $tbody = $('table.aza-phones tbody');
            $tbody.empty();
            $.post(aza.ajaxurl, {
                action: 'aza_get_calltracking_phones'
            }, function(data) {
                data = JSON.parse(data);
                $(data).each(function() {
                    var phone = this;
                    var $tr = $('<tr></tr>').appendTo($tbody);
                    $('<td>' + phone.phone + '</td>').appendTo($tr);
                    $('<td class="aza-remove"></td>').appendTo($tr).on('click', function() {
                        $.post(aza.ajaxurl, {
                            action: 'aza_remove_calltracking_phone',
                            phone: phone.phone
                        }, function(data) {
                            load_phones();
                        });
                    });
                });
            });
        }
        function load_calls() {
            var $tbody = $('table.aza-calls tbody');
            $tbody.empty();
            $.post(aza.ajaxurl, {
                action: 'aza_get_calltracking_calls'
            }, function(data) {
                data = JSON.parse(data);
                $(data).each(function() {
                    var call = this;
                    var $tr = $('<tr></tr>').appendTo($tbody);
                    $('<td>' + call.call_datetime + '</td>').appendTo($tr);
                    $('<td>' + call.phone + '</td>').appendTo($tr);
                    $('<td>' + call.caller_phone + '</td>').appendTo($tr);
                    $('<td class="aza-remove"></td>').appendTo($tr).on('click', function() {
                        $.post(aza.ajaxurl, {
                            action: 'aza_remove_calltracking_call',
                            promo_code: call.promo_code
                        }, function(data) {
                            calls_datatable();
                        });
                    });
                });
            });
        }
        function calls_datatable(callback) {
            var $table = $('table.aza-calls');
            if ($table.data('dataTable')) {
                $table.data('dataTable').ajax.reload();
            } else {
                var columns = [
                    {sName: "promo_code", sClass: 'aza-id', bVisible: false, orderable: false},
                    {sName: "call_datetime", sClass: '', orderable: false},
                    {sName: "phone", sClass: '', orderable: false},
                    {sName: "caller_phone", sClass: '', orderable: false},
                    {sName: "remove", sClass: 'aza-remove', orderable: false}
                ];
                var columns_index = {};
                columns.map(function(column, i) {
                    columns_index[column.sName] = i;
                });
                var table = $table.DataTable({
                    serverSide: true,
                    ajax: {
                        url: aza.ajaxurl + '?action=aza_calltracking_calls_datatable',
                        type: 'POST'
                    },
                    aoColumns: columns,
                    fnDrawCallback: function(oSettings) {
                        $table.find('tbody tr td.aza-remove').off('click').on('click', function() {
                            var call = {};
                            for (var name in columns_index) {
                                call[name] = table.row(this).data()[columns_index[name]];
                            }
                            $.post(aza.ajaxurl, {
                                action: 'aza_remove_calltracking_call',
                                promo_code: call.promo_code
                            }, function(data) {
                                calls_datatable();
                            });
                        });
                        if (typeof callback === "function") {
                            callback();
                        }
                    },
                    oLanguage: aza.i18n.dataTable
                });
                $table.data('dataTable', table);
            }
        }
        function show_leads_datatable(report_settings, levels, callback) {

            var path = [];
            var dimensions = {};
            $(levels).each(function(index) {
                dimensions[report_settings.dimensions[index].dimension] = this;
                path.push(this);
            });

            var $modal = $('<div class="aza-modal"></div>');
            $('<div class="aza-modal-title">' + aza.i18n.leads + ': ' + path.join(' - ') + '</div>').appendTo($modal);
            var $table = aza.templates.leads_table.clone();
            $table.appendTo($modal);
            var $actions = $('<div class="aza-modal-actions"></div>').appendTo($modal);
            $('<div class="aza-modal-ok">' + aza.i18n.ok + '</div>').appendTo($actions).on('click', function() {
                $.modal.close();
                return false;
            });


            if ($table.data('dataTable')) {
                $table.data('dataTable').ajax.reload();
            } else {
                var columns = [
                    {sName: "lead_id", sClass: 'aza-lead-id', orderable: false},
                    {sName: "type", sClass: '', bVisible: false, orderable: false},
                    {sName: "display_type", sClass: '', orderable: false},
                    {sName: "display_status", sClass: '', orderable: false},
                    {sName: "amount", sClass: '', orderable: false},
                    {sName: "lead_datetime", sClass: '', orderable: false},
                    {sName: "promo_code", sClass: '', orderable: false},
                    {sName: "source", sClass: '', orderable: false},
                    {sName: "first_cost", sClass: '', orderable: false}
                ];
                var columns_index = {};
                columns.map(function(column, i) {
                    columns_index[column.sName] = i;
                });
                var table = $table.DataTable({
                    serverSide: true,
                    ajax: {
                        url: aza.ajaxurl + '?action=aza_leads_datatable',
                        type: 'POST',
                        data: {
                            dimensions: dimensions
                        }
                    },
                    aoColumns: columns,
                    fnDrawCallback: function(oSettings) {

                        $modal.modal({
                            autoResize: true,
                            overlayClose: true,
                            opacity: 0,
                            overlayCss: {
                                "background-color": "black"
                            },
                            closeClass: "aza-close",
                            onClose: function() {
                                setTimeout(function() {
                                    $.modal.close();
                                }, 0);
                            }
                        });
                        $table.find('tbody tr td.aza-lead-id').off('click').on('click', function() {
                            var lead = {};
                            for (var name in columns_index) {
                                lead[name] = table.row(this).data()[columns_index[name]];
                            }
                            $.modal.close();
                            show_lead_visits_history(lead.lead_id, lead.type, lead.display_type);
                        });
                        if (typeof callback === "function") {
                            callback();
                        }
                    },
                    oLanguage: aza.i18n.dataTable
                });
                $table.data('dataTable', table);
            }
        }
        function show_lead_visits_history(lead_id, type, display_type, callback) {
            var $modal = $('<div class="aza-modal"></div>');
            $('<div class="aza-modal-title">' + aza.i18n.user_visits_for + display_type + ' ' + lead_id + '</div>').appendTo($modal);
            var $table = aza.templates.visits_table.clone();
            $table.appendTo($modal);
            var $actions = $('<div class="aza-modal-actions"></div>').appendTo($modal);
            $('<div class="aza-modal-ok">' + aza.i18n.ok + '</div>').appendTo($actions).on('click', function() {
                $.modal.close();
                return false;
            });

            if ($table.data('dataTable')) {
                $table.data('dataTable').ajax.reload();
            } else {
                var columns = [
                    {sName: "promo_code", sClass: '', orderable: false},
                    {sName: "visit_datetime", sClass: '', orderable: false},
                    {sName: "full_source", sClass: '', orderable: false},
                    {sName: "display_landing_page_id", sClass: '', orderable: false}
                ];
                var columns_index = {};
                columns.map(function(column, i) {
                    columns_index[column.sName] = i;
                });
                var table = $table.DataTable({
                    serverSide: true,
                    ajax: {
                        url: aza.ajaxurl + '?action=aza_lead_visits_history_datatable&lead_id=' + lead_id + '&type=' + type,
                        type: 'POST'
                    },
                    aoColumns: columns,
                    fnDrawCallback: function(oSettings) {
                        $modal.modal({
                            autoResize: true,
                            overlayClose: true,
                            opacity: 0,
                            overlayCss: {
                                "background-color": "black"
                            },
                            closeClass: "aza-close",
                            onClose: function() {
                                setTimeout(function() {
                                    $.modal.close();
                                }, 0);
                            }
                        });
                        if (typeof callback === "function") {
                            callback();
                        }
                    },
                    oLanguage: aza.i18n.dataTable
                });
                $table.data('dataTable', table);
            }
        }

        numeral.register('locale', 'custom', {
            delimiters: {
                thousands: aza.settings.thousands_delimiter,
                decimal: aza.settings.decimal_delimiter
            },
            abbreviations: {
                thousand: 'k',
                million: 'm',
                billion: 'b',
                trillion: 't'
            },
            ordinal: function(number) {
                var b = number % 10;
                return (~~(number % 100 / 10) === 1) ? 'th' :
                        (b === 1) ? 'st' :
                        (b === 2) ? 'nd' :
                        (b === 3) ? 'rd' : 'th';
            },
            currency: {
                symbol: aza.settings.currency_symbol
            }
        });
        numeral.locale('custom');
        var metrics_colors = ["#7fdbff", "#0074d9", "#01ff70", "#001f3f", "#39cccc", "#3d9970", "#2ecc40", "#ff4136", "#85144b", "#ff851b", "#b10dc9", "#ffdc00", "#f012be"];
        init_templates();
        if (!aza.settings.reports) {
            aza.settings.reports = [];
        }
        $(aza.settings.reports).each(function(){
            if(this.chart) {
                if($.isArray(this.chart)) {
                    this.chart = {};
                }
            }
        });        
        aza.from = '-30 days';
        aza.to = 'now';
        aza.type = 'day';
        aza.compare_from = '-30 days';
        aza.compare_to = 'now';
        aza.compare = false;
        refresh_reports();
        $('.aza-table table').empty();
        $('.aza-reports .aza-report').first().trigger('click');
        $('.aza-add-report').on('click', function() {
            open_report_modal({name: aza.i18n.new_report}, function(settings) {
                aza.settings.reports.push(settings);
                save_reports(function() {
                    refresh_reports();
                    $('.aza-reports .aza-report').each(function() {
                        if ($(this).data('report') === settings['name']) {
                            $(this).trigger('click');
                            return false;
                        }
                    });
                });
            });
        });
        $.datepicker.setDefaults($.datepicker.regional[ "" ]);
        $('.aza-date-range').each(function() {
            var $field = $(this);
            $field.find('.aza-min').datepicker({
                onClose: function(selectedDate) {
                    $field.find('.aza-max').datepicker("option", "minDate", selectedDate);
                }
            });
            $field.find('.aza-max').datepicker({
                onClose: function(selectedDate) {
                    $field.find('.aza-min').datepicker("option", "maxDate", selectedDate);
                }
            });
        });
        $('input.aza-date').datepicker();
        $('input.aza-time').mask('99:99');
        $('.aza-first-interval .aza-set-range').on('click', function() {
            aza.from = $(this).data('from');
            aza.to = $(this).data('to');
            $(this).addClass('aza-active');
            $(this).siblings().removeClass("aza-active");
            load_report_table(aza.current_report_settings);
            load_report_chart(aza.current_report_settings);
        });
        $('.aza-first-interval .aza-date-range .aza-min, .aza-date-range .aza-max').on('change', function() {
            if ($('.aza-first-interval .aza-date-range .aza-min').datepicker("getDate") && $('.aza-first-interval .aza-date-range .aza-max').datepicker("getDate")) {
                $('.aza-first-interval .aza-set-range.aza-active').removeClass("aza-active");
                aza.from = getGMTtimestamp($('.aza-first-interval .aza-date-range .aza-min').datepicker("getDate"));
                aza.to = getGMTtimestamp($('.aza-first-interval .aza-date-range .aza-max').datepicker("getDate"));
                load_report_table(aza.current_report_settings);
                load_report_chart(aza.current_report_settings);
            }
        });
        $('#aza-compare-to-the-period').on('change', function() {
            aza.compare = $(this).prop('checked');
            load_report_chart(aza.current_report_settings);
        });
        $('.aza-second-interval .aza-set-range').on('click', function() {
            aza.compare_from = $(this).data('from');
            aza.compare_to = $(this).data('to');
            $(this).addClass('aza-active');
            $(this).siblings().removeClass("aza-active");
            if (aza.compare) {
                load_report_chart(aza.current_report_settings);
            }
        });
        $('.aza-second-interval .aza-date-range .aza-min, .aza-date-range .aza-max').on('change', function() {
            if ($('.aza-second-interval .aza-date-range .aza-min').datepicker("getDate") && $('.aza-second-interval .aza-date-range .aza-max').datepicker("getDate")) {
                $('.aza-second-interval .aza-set-range.aza-active').removeClass("aza-active");
                aza.compare_from = getGMTtimestamp($('.aza-second-interval .aza-date-range .aza-min').datepicker("getDate"));
                aza.compare_to = getGMTtimestamp($('.aza-second-interval .aza-date-range .aza-max').datepicker("getDate"));
                if (aza.compare) {
                    load_report_chart(aza.current_report_settings);
                }
            }
        });
        $('.aza-chart-type select').on('change', function() {
            aza.type = $(this).val();
            load_report_chart(aza.current_report_settings);
        });
        $('.aza-menu .aza-item').on('click', function() {
            var $this = $(this);
            $this.addClass("aza-active");
            $this.siblings().removeClass("aza-active");
            var tab = '.' + $this.data("class");
            $('.aza-dialogs .aza-item').not(tab).css("display", "none");
            $(tab).fadeIn();
            switch ($this.data("class")) {
                case 'aza-analytics':
                    $('.aza-reports-management .aza-reports .aza-active').trigger('click');
                    break;
                case 'aza-marketing-costs':
                    load_offline_marketing_costs();
                    break;
                case 'aza-calltracking':
                    load_phones();
                    calls_datatable();
                    break;
            }
        });
        $('form.aza-add-marketing-costs button').on('click', function(event) {
            var $button = $(this);
            var $form = $button.closest('form');
            event.preventDefault();
            if ($form.find('[name="channel"]').val() && $form.find('[name="from"]').val() && $form.find('[name="to"]').val() && $form.find('[name="cost"]').val()) {
                $.post(aza.ajaxurl, {
                    action: 'aza_add_offline_marketing_cost',
                    channel: $form.find('[name="channel"]').val(),
                    from: getGMTtimestamp($form.find('.aza-date-range .aza-min').datepicker("getDate")),
                    to: getGMTtimestamp($form.find('.aza-date-range .aza-max').datepicker("getDate")),
                    cost: $form.find('[name="cost"]').val()
                }, function(data) {
                    load_offline_marketing_costs();
                });
                $form.get(0).reset();
            }
        });
        $('form.aza-add-phone-number button').on('click', function(event) {
            var $button = $(this);
            var $form = $button.closest('form');
            event.preventDefault();
            if ($form.find('[name="phone_number"]').val()) {
                $.post(aza.ajaxurl, {
                    action: 'aza_add_calltracking_phone',
                    phone: $form.find('[name="phone_number"]').val()
                }, function(data) {
                    load_phones();
                });
                $form.get(0).reset();
            }
        });
        $('form.aza-add-call button').on('click', function(event) {
            var $button = $(this);
            var $form = $button.closest('form');
            event.preventDefault();
            if ($form.find('[name="date"]').val() && $form.find('[name="time"]').val() && $form.find('[name="phone"]').val() && $form.find('[name="caller_phone"]').val()) {
                if ($form.get(0).reportValidity()) {
                    var date = $form.find('[name="date"]').datepicker("getDate");
                    var time = $form.find('[name="time"]').val().split(':');
                    date.setHours(time[0]);
                    date.setMinutes(time[1]);
                    $.post(aza.ajaxurl, {
                        action: 'aza_add_calltracking_call',
                        call_timestamp: date.getTime() / 1000,
                        phone: $form.find('[name="phone"]').val(),
                        caller_phone: $form.find('[name="caller_phone"]').val()
                    }, function(data) {
                        if (data === '1') {
                            calls_datatable();
                        } else {
                            alert(data);
                        }
                    });
                    $form.get(0).reset();
                }
            }
        });
        $('form.aza-set-default-phone button').on('click', function(event) {
            var $button = $(this);
            var $form = $button.closest('form');
            event.preventDefault();
            if ($form.find('[name="phone_number"]').val()) {
                if ($form.get(0).reportValidity()) {
                    $.post(aza.ajaxurl, {
                        action: 'aza_set_default_phone',
                        phone: $form.find('[name="phone_number"]').val()
                    }, function(data) {
                        if (data) {
                            alert(data);
                        }
                    });
                }
            }
        });

        if (aza.settings.phone_mask) {
            $('input[type="tel"]').mask(aza.settings.phone_mask);
        }
    });
})(jQuery);