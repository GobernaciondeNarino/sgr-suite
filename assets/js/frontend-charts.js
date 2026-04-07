/**
 * SGR Suite - Frontend Charts JS
 *
 * Renderizado de gráficos con D3Plus, lazy loading via
 * IntersectionObserver, toolbar, modal de datos y export CSV.
 *
 * @package SGR_Suite
 * @since   1.0.1
 */
(function () {
    'use strict';

    // --- Utilidades ---

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
                return num.toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
            case 'millones':
                if (Math.abs(num) >= 1e9) return (num / 1e9).toFixed(1) + ' MMll';
                if (Math.abs(num) >= 1e6) return (num / 1e6).toFixed(1) + 'M';
                if (Math.abs(num) >= 1e3) return (num / 1e3).toFixed(1) + 'K';
                return num.toLocaleString('es-CO');
            case 'internacional':
                return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
            case 'sin_formato':
                return String(num);
            default:
                return num.toLocaleString('es-CO');
        }
    }

    /**
     * Resolver clase D3Plus por tipo de gráfico.
     */
    function getD3PlusClass(chartType) {
        var d3p = window.d3plus || {};

        var mapping = {
            'bar':         d3p.BarChart,
            'stacked_bar': d3p.StackedArea, // fallback, D3Plus v2
            'grouped_bar': d3p.BarChart,
            'line':        d3p.LinePlot,
            'area':        d3p.StackedArea,
            'pie':         d3p.Pie,
            'donut':       d3p.Donut,
            'treemap':     d3p.Treemap,
            'pack':        d3p.Pack
        };

        return mapping[chartType] || d3p.BarChart;
    }

    // --- ChartManager ---

    var ChartManager = {
        charts: {},

        init: function () {
            var wrappers = document.querySelectorAll('.sgr-chart-wrapper');
            if (!wrappers.length) return;

            // Lazy load con IntersectionObserver
            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            ChartManager.loadChart(entry.target);
                            observer.unobserve(entry.target);
                        }
                    });
                }, { rootMargin: '200px' });

                wrappers.forEach(function (wrapper) {
                    observer.observe(wrapper);
                });
            } else {
                // Fallback sin IO
                wrappers.forEach(function (wrapper) {
                    ChartManager.loadChart(wrapper);
                });
            }

            // Event delegation para toolbar
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

            // Cerrar modales
            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('sgr-chart-data-modal-close')) {
                    var modal = e.target.closest('.sgr-chart-data-modal');
                    if (modal) modal.style.display = 'none';
                }
                if (e.target.classList.contains('sgr-chart-data-modal')) {
                    e.target.style.display = 'none';
                }
            });
        },

        loadChart: function (wrapper) {
            var uid = wrapper.id;
            var configEl = document.getElementById(uid + '-config');
            if (!configEl) return;

            var parsed;
            try {
                parsed = JSON.parse(configEl.textContent);
            } catch (e) {
                this.showError(uid, 'Error al leer la configuración del gráfico.');
                return;
            }

            var chartId = parsed.chartId;
            var nonce = parsed.nonce;
            var config = parsed.config;

            // AJAX para obtener datos
            var formData = new FormData();
            formData.append('action', 'sgr_suite_get_chart_data');
            formData.append('chart_id', chartId);
            formData.append('nonce', nonce);

            var ajaxUrl = (typeof sgrCharts !== 'undefined') ? sgrCharts.ajaxUrl : '/wp-admin/admin-ajax.php';

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function (resp) { return resp.json(); })
            .then(function (response) {
                if (!response.success) {
                    ChartManager.showError(uid, response.data?.message || 'Error al cargar datos.');
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
            .catch(function (err) {
                ChartManager.showError(uid, 'Error de conexión al cargar el gráfico.');
            });
        },

        renderChart: function (uid, data, config) {
            var container = document.getElementById(uid + '-container');
            if (!container || !data || !data.length) {
                this.showError(uid, 'No hay datos disponibles para este gráfico.');
                return;
            }

            // Limpiar loading
            container.innerHTML = '';

            var chartType = config.chart_type || 'bar';
            var ChartClass = getD3PlusClass(chartType);

            if (!ChartClass) {
                this.showError(uid, 'Tipo de gráfico no soportado o D3Plus no cargado.');
                return;
            }

            var numFormat = config.number_format || 'colombiano';
            var colors = config.colors || ['#348afb', '#1e40af', '#059669', '#d97706'];

            // Preparar datos - verificar si hay series
            var hasSeries = data.length > 0 && data[0].series !== undefined;

            try {
                var chartConfig = {
                    select: '#' + uid + '-container',
                    data: data,
                    groupBy: hasSeries ? 'series' : 'label',
                    tooltipConfig: {
                        body: function (d) {
                            return formatNumber(d.value, numFormat);
                        }
                    },
                    shapeConfig: {
                        fill: function (d, i) {
                            return colors[i % colors.length];
                        }
                    }
                };

                // Configurar según tipo
                if (['bar', 'stacked_bar', 'grouped_bar'].indexOf(chartType) !== -1) {
                    chartConfig.x = 'label';
                    chartConfig.y = 'value';
                    if (chartType === 'grouped_bar') {
                        chartConfig.grouped = true;
                    }
                    if (chartType === 'stacked_bar') {
                        chartConfig.stacked = true;
                    }
                } else if (chartType === 'line' || chartType === 'area') {
                    chartConfig.x = 'label';
                    chartConfig.y = 'value';
                    chartConfig.groupBy = hasSeries ? 'series' : undefined;
                } else if (chartType === 'pie' || chartType === 'donut') {
                    chartConfig.value = 'value';
                    chartConfig.groupBy = 'label';
                } else if (chartType === 'treemap') {
                    chartConfig.sum = 'value';
                    chartConfig.groupBy = 'label';
                } else if (chartType === 'pack') {
                    chartConfig.sum = 'value';
                    chartConfig.groupBy = 'label';
                }

                // Leyenda
                if (config.show_legend === false) {
                    chartConfig.legend = false;
                }

                // Títulos de ejes
                if (config.x_axis_title) {
                    chartConfig.xConfig = chartConfig.xConfig || {};
                    chartConfig.xConfig.title = config.x_axis_title;
                }
                if (config.y_axis_title) {
                    chartConfig.yConfig = chartConfig.yConfig || {};
                    chartConfig.yConfig.title = config.y_axis_title;
                }

                new ChartClass(chartConfig).render();

            } catch (e) {
                console.error('SGR Chart render error:', e);
                this.showError(uid, 'Error al renderizar el gráfico: ' + e.message);
            }
        },

        showError: function (uid, message) {
            var container = document.getElementById(uid + '-container');
            if (container) {
                container.innerHTML = '<div class="sgr-chart-error">' +
                    '<p>' + escapeHtml(message) + '</p></div>';
            }
        },

        toggleFullscreen: function (wrapper) {
            if (!document.fullscreenElement) {
                wrapper.requestFullscreen().catch(function () {});
            } else {
                document.exitFullscreen();
            }
        },

        showDataModal: function (uid) {
            var modal = document.getElementById(uid + '-data-modal');
            var chartData = this.charts[uid];
            if (!modal || !chartData) return;

            var thead = modal.querySelector('thead');
            var tbody = modal.querySelector('tbody');

            // Construir tabla
            var data = chartData.data;
            if (!data.length) return;

            var keys = Object.keys(data[0]);
            var numFormat = chartData.config.number_format || 'colombiano';

            thead.innerHTML = '<tr>' + keys.map(function (k) {
                return '<th>' + escapeHtml(k) + '</th>';
            }).join('') + '</tr>';

            tbody.innerHTML = data.map(function (row) {
                return '<tr>' + keys.map(function (k) {
                    var val = row[k];
                    if (k === 'value' && !isNaN(val)) {
                        val = formatNumber(val, numFormat);
                    }
                    return '<td>' + escapeHtml(String(val ?? '')) + '</td>';
                }).join('') + '</tr>';
            }).join('');

            modal.style.display = 'flex';
        },

        downloadCSV: function (uid) {
            var chartData = this.charts[uid];
            if (!chartData || !chartData.data.length) return;

            var data = chartData.data;
            var keys = Object.keys(data[0]);

            var csv = '\uFEFF'; // BOM
            csv += keys.join(',') + '\n';
            data.forEach(function (row) {
                csv += keys.map(function (k) {
                    var val = String(row[k] ?? '');
                    return '"' + val.replace(/"/g, '""') + '"';
                }).join(',') + '\n';
            });

            var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'sgr-chart-' + uid + '.csv';
            a.click();
            URL.revokeObjectURL(url);
        }
    };

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            ChartManager.init();
        });
    } else {
        ChartManager.init();
    }

})();
