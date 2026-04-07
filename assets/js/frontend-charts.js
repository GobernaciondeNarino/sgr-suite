/**
 * SGR Suite - Frontend Charts JS v2.0.0
 *
 * Renders charts with D3Plus v2 using the correct method-chaining API.
 * Features: lazy loading via IntersectionObserver, toolbar actions
 * (fullscreen, data modal, CSV export).
 *
 * D3Plus v2 classes used:
 *   d3plus.BarChart, d3plus.LinePlot, d3plus.Pie,
 *   d3plus.Treemap, d3plus.Pack
 *
 * Data format from AJAX: [{label: "...", value: 123}, ...]
 *
 * @package SGR_Suite
 * @since   2.0.0
 */
(function () {
    'use strict';

    /* =========================================
       Utilities
       ========================================= */

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    function formatNumber(value, format) {
        if (value == null || isNaN(value)) return '0';
        var num = parseFloat(value);

        switch (format) {
            case 'colombiano':
                return num.toLocaleString('es-CO', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });
            case 'millones':
                if (Math.abs(num) >= 1e9) return (num / 1e9).toFixed(1) + ' MMll';
                if (Math.abs(num) >= 1e6) return (num / 1e6).toFixed(1) + 'M';
                if (Math.abs(num) >= 1e3) return (num / 1e3).toFixed(1) + 'K';
                return num.toLocaleString('es-CO');
            case 'internacional':
                return num.toLocaleString('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });
            case 'sin_formato':
                return String(num);
            default:
                return num.toLocaleString('es-CO');
        }
    }

    /**
     * Build a stable color assignment function from a palette.
     * Maps each unique label/series to a color, reusing across renders.
     */
    function makeColorFn(colors) {
        var palette = (colors && colors.length)
            ? colors
            : ['#348afb', '#1e40af', '#059669', '#d97706', '#dc2626', '#7c3aed', '#0891b2', '#ca8a04'];
        var map = {};
        var idx = 0;
        return function (d) {
            var key = d.label || d.series || 'default';
            if (!(key in map)) {
                map[key] = palette[idx % palette.length];
                idx++;
            }
            return map[key];
        };
    }

    /* =========================================
       ChartManager
       ========================================= */

    var ChartManager = {
        charts: {},

        /**
         * Find all .sgr-chart-wrapper elements and lazy-load them.
         * Set up event delegation for toolbar and modal close.
         */
        init: function () {
            var wrappers = document.querySelectorAll('.sgr-chart-wrapper');
            if (!wrappers.length) return;

            // Lazy load with IntersectionObserver
            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            ChartManager.loadChart(entry.target);
                            observer.unobserve(entry.target);
                        }
                    });
                }, { rootMargin: '200px' });

                wrappers.forEach(function (w) {
                    observer.observe(w);
                });
            } else {
                // Fallback: load all immediately
                wrappers.forEach(function (w) {
                    ChartManager.loadChart(w);
                });
            }

            // Toolbar button delegation
            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.sgr-chart-toolbar-btn');
                if (!btn) return;

                var wrapper = btn.closest('.sgr-chart-wrapper');
                if (!wrapper) return;

                var action = btn.dataset.action;
                var uid = wrapper.id;

                if (action === 'fullscreen') ChartManager.toggleFullscreen(wrapper);
                if (action === 'data') ChartManager.showDataModal(uid);
                if (action === 'download') ChartManager.downloadCSV(uid);
            });

            // Close data modals via close button or backdrop click
            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('sgr-chart-data-modal-close')) {
                    var modal = e.target.closest('.sgr-chart-data-modal');
                    if (modal) modal.classList.remove('show');
                }
                if (e.target.classList.contains('sgr-chart-data-modal')) {
                    e.target.classList.remove('show');
                }
            });
        },

        /**
         * Read JSON config from script tag, fetch data via AJAX POST, then render.
         */
        loadChart: function (wrapper) {
            var uid = wrapper.id;
            var configEl = document.getElementById(uid + '-config');
            if (!configEl) return;

            var parsed;
            try {
                parsed = JSON.parse(configEl.textContent);
            } catch (e) {
                this.showError(uid, 'Error al leer la configuracion del grafico.');
                return;
            }

            var chartId = parsed.chartId;
            var nonce = parsed.nonce;

            var formData = new FormData();
            formData.append('action', 'sgr_suite_get_chart_data');
            formData.append('chart_id', chartId);
            formData.append('nonce', nonce);

            var ajaxUrl = (typeof window.sgrCharts !== 'undefined' && window.sgrCharts.ajaxUrl)
                ? window.sgrCharts.ajaxUrl
                : '/wp-admin/admin-ajax.php';

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function (resp) { return resp.json(); })
            .then(function (response) {
                if (!response.success) {
                    ChartManager.showError(
                        uid,
                        (response.data && response.data.message)
                            ? response.data.message
                            : 'Error al cargar datos.'
                    );
                    return;
                }

                var data = response.data.data;
                var serverConfig = response.data.config;

                ChartManager.charts[uid] = {
                    data: data,
                    config: serverConfig
                };

                ChartManager.renderChart(uid, data, serverConfig);
            })
            .catch(function () {
                ChartManager.showError(uid, 'Error de conexion al cargar el grafico.');
            });
        },

        /**
         * Render chart using D3Plus v2 method-chaining API.
         *
         * Supported chart_type values:
         *   bar, barH, stacked, line, pie, donut, treemap, pack
         */
        renderChart: function (uid, data, config) {
            var container = document.getElementById(uid + '-container');
            if (!container || !data || !data.length) {
                this.showError(uid, 'No hay datos disponibles para este grafico.');
                return;
            }

            // Clear loading spinner
            container.innerHTML = '';

            var d3p = window.d3plus;
            if (!d3p) {
                this.showError(uid, 'D3Plus no esta cargado.');
                return;
            }

            var chartType = config.chart_type || 'bar';
            var numFormat = config.number_format || 'colombiano';
            var colors = config.colors || ['#348afb', '#1e40af', '#059669', '#d97706', '#dc2626', '#7c3aed'];
            var colorFn = makeColorFn(colors);
            var selector = '#' + uid + '-container';

            // Ensure numeric values are numbers, not strings from JSON
            data.forEach(function (d) {
                if (d.value !== undefined) d.value = parseFloat(d.value) || 0;
            });

            var tooltipCfg = {
                body: function (d) {
                    return formatNumber(d.value, numFormat);
                }
            };

            try {
                var chart;

                switch (chartType) {

                    // ── Vertical bar ──
                    case 'bar':
                        chart = new d3p.BarChart()
                            .select(selector)
                            .data(data)
                            .x('label')
                            .y('value')
                            .groupBy('label')
                            .shapeConfig({ fill: colorFn })
                            .tooltipConfig(tooltipCfg);
                        break;

                    // ── Horizontal bar ──
                    case 'barH':
                        chart = new d3p.BarChart()
                            .select(selector)
                            .data(data)
                            .x('value')
                            .y('label')
                            .discrete('y')
                            .groupBy('label')
                            .shapeConfig({ fill: colorFn })
                            .tooltipConfig(tooltipCfg);
                        break;

                    // ── Stacked bar (requires series field) ──
                    case 'stacked':
                        chart = new d3p.BarChart()
                            .select(selector)
                            .data(data)
                            .x('label')
                            .y('value')
                            .groupBy('series')
                            .stacked(true)
                            .shapeConfig({ fill: colorFn })
                            .tooltipConfig(tooltipCfg);
                        break;

                    // ── Line plot ──
                    case 'line':
                        chart = new d3p.LinePlot()
                            .select(selector)
                            .data(data)
                            .x('label')
                            .y('value')
                            .groupBy(function () { return 'Valor'; })
                            .shapeConfig({
                                Line: { stroke: colors[0] || '#348afb', strokeWidth: 3 }
                            })
                            .tooltipConfig(tooltipCfg);
                        break;

                    // ── Pie ──
                    case 'pie':
                        chart = new d3p.Pie()
                            .select(selector)
                            .data(data)
                            .groupBy('label')
                            .value(function (d) { return d.value; })
                            .shapeConfig({ fill: colorFn })
                            .tooltipConfig(tooltipCfg);
                        break;

                    // ── Donut (Pie with innerRadius) ──
                    case 'donut':
                        chart = new d3p.Pie()
                            .select(selector)
                            .data(data)
                            .groupBy('label')
                            .value(function (d) { return d.value; })
                            .innerRadius(function () {
                                var el = document.querySelector(selector);
                                if (!el) return 80;
                                var size = Math.min(el.clientWidth, el.clientHeight);
                                return Math.max(40, size * 0.2);
                            })
                            .shapeConfig({ fill: colorFn })
                            .tooltipConfig(tooltipCfg);
                        break;

                    // ── Treemap ──
                    case 'treemap':
                        chart = new d3p.Treemap()
                            .select(selector)
                            .data(data)
                            .groupBy('label')
                            .sum('value')
                            .shapeConfig({ fill: colorFn })
                            .tooltipConfig(tooltipCfg);
                        break;

                    // ── Pack / Bubble ──
                    case 'pack':
                        chart = new d3p.Pack()
                            .select(selector)
                            .data(data)
                            .groupBy('label')
                            .sum('value')
                            .shapeConfig({ fill: colorFn })
                            .tooltipConfig(tooltipCfg);
                        break;

                    // ── Fallback ──
                    default:
                        chart = new d3p.BarChart()
                            .select(selector)
                            .data(data)
                            .x('label')
                            .y('value')
                            .groupBy('label')
                            .shapeConfig({ fill: colorFn })
                            .tooltipConfig(tooltipCfg);
                        break;
                }

                // Legend toggle
                if (config.show_legend === false && typeof chart.legend === 'function') {
                    chart.legend(false);
                }

                // Axis titles (only on chart types that support them)
                if (config.x_axis_title && typeof chart.xConfig === 'function') {
                    chart.xConfig({ title: config.x_axis_title });
                }
                if (config.y_axis_title && typeof chart.yConfig === 'function') {
                    chart.yConfig({ title: config.y_axis_title });
                }

                // Custom height
                if (config.chart_height && typeof chart.height === 'function') {
                    chart.height(parseInt(config.chart_height, 10));
                }

                chart.render();

            } catch (e) {
                console.error('SGR Chart render error:', e);
                this.showError(uid, 'Error al renderizar el grafico: ' + e.message);
            }
        },

        /**
         * Display error message inside chart container.
         */
        showError: function (uid, message) {
            var container = document.getElementById(uid + '-container');
            if (container) {
                container.innerHTML = '<div class="sgr-chart-error"><p>' +
                    escapeHtml(message) + '</p></div>';
            }
        },

        /**
         * Toggle Fullscreen API on the chart wrapper element.
         */
        toggleFullscreen: function (wrapper) {
            if (!document.fullscreenElement) {
                if (wrapper.requestFullscreen) {
                    wrapper.requestFullscreen().catch(function () {});
                } else if (wrapper.webkitRequestFullscreen) {
                    wrapper.webkitRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }
            }
        },

        /**
         * Populate the data table inside the modal and show it.
         */
        showDataModal: function (uid) {
            var modal = document.getElementById(uid + '-data-modal');
            var chartData = this.charts[uid];
            if (!modal || !chartData || !chartData.data || !chartData.data.length) return;

            var data = chartData.data;
            var keys = Object.keys(data[0]);
            var numFormat = chartData.config.number_format || 'colombiano';

            var thead = modal.querySelector('thead');
            var tbody = modal.querySelector('tbody');

            thead.innerHTML = '<tr>' + keys.map(function (k) {
                return '<th>' + escapeHtml(k) + '</th>';
            }).join('') + '</tr>';

            tbody.innerHTML = data.map(function (row) {
                return '<tr>' + keys.map(function (k) {
                    var val = row[k];
                    if (k === 'value' && !isNaN(val)) {
                        val = formatNumber(val, numFormat);
                    }
                    return '<td>' + escapeHtml(String(val != null ? val : '')) + '</td>';
                }).join('') + '</tr>';
            }).join('');

            modal.classList.add('show');
        },

        /**
         * Generate a CSV blob from chart data and trigger a download.
         */
        downloadCSV: function (uid) {
            var chartData = this.charts[uid];
            if (!chartData || !chartData.data || !chartData.data.length) return;

            var data = chartData.data;
            var keys = Object.keys(data[0]);

            // UTF-8 BOM for Excel compatibility
            var csv = '\uFEFF';
            csv += keys.join(',') + '\n';

            data.forEach(function (row) {
                csv += keys.map(function (k) {
                    var val = String(row[k] != null ? row[k] : '');
                    return '"' + val.replace(/"/g, '""') + '"';
                }).join(',') + '\n';
            });

            var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'sgr-chart-' + uid + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    };

    /* =========================================
       Initialize on DOM ready
       ========================================= */

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            ChartManager.init();
        });
    } else {
        ChartManager.init();
    }

})();
