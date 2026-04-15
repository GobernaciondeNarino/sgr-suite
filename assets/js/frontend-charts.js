/**
 * SGR Suite - Frontend Charts JS v2.1.0
 *
 * D3Plus v2 method-chaining API. Supports: bar, barH, stacked_bar,
 * grouped_bar, line, area, pie, donut, treemap, pack.
 * Toolbar: Detalle, Compartir, Datos, Imagen, Descarga.
 *
 * @package SGR_Suite
 * @since   2.1.0
 */
(function () {
    'use strict';

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
            case 'colombiano': return num.toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 2});
            case 'millones':
                if (Math.abs(num) >= 1e9) return (num / 1e9).toFixed(1) + ' MMll';
                if (Math.abs(num) >= 1e6) return (num / 1e6).toFixed(1) + 'M';
                if (Math.abs(num) >= 1e3) return (num / 1e3).toFixed(1) + 'K';
                return num.toLocaleString('es-CO');
            case 'internacional': return num.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 2});
            default: return String(num);
        }
    }

    function makeColorFn(colors) {
        var palette = (colors && colors.length) ? colors : ['#348afb','#1e40af','#059669','#d97706','#dc2626','#7c3aed','#0891b2','#be185d','#65a30d','#ea580c','#4f46e5','#0d9488'];
        var map = {};
        var idx = 0;
        return function (d) {
            var key = (d && (d.series || d.label)) || 'default';
            if (!(key in map)) { map[key] = palette[idx % palette.length]; idx++; }
            return map[key];
        };
    }

    var ChartManager = {
        charts: {},

        init: function () {
            var wrappers = document.querySelectorAll('.sgr-chart-wrapper');
            if (!wrappers.length) return;

            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) { ChartManager.loadChart(entry.target); observer.unobserve(entry.target); }
                    });
                }, {rootMargin: '200px'});
                wrappers.forEach(function (w) { observer.observe(w); });
            } else {
                wrappers.forEach(function (w) { ChartManager.loadChart(w); });
            }

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.sgr-chart-toolbar-btn');
                if (!btn) return;
                var wrapper = btn.closest('.sgr-chart-wrapper');
                if (!wrapper) return;
                var action = btn.dataset.action;
                var uid = wrapper.id;

                switch (action) {
                    case 'detail': ChartManager.showModal(uid + '-detail-modal'); break;
                    case 'share': ChartManager.showModal(uid + '-share-modal'); break;
                    case 'data': ChartManager.showDataModal(uid); break;
                    case 'image': ChartManager.exportImage(uid); break;
                    case 'download': ChartManager.downloadCSV(uid); break;
                    case 'copy-link': ChartManager.copyLink(); break;
                    case 'fullscreen': ChartManager.toggleFullscreen(wrapper); break;
                }
            });

            // Share buttons
            document.addEventListener('click', function (e) {
                var shareBtn = e.target.closest('.sgr-share-btn');
                if (!shareBtn) return;
                e.preventDefault();
                var url = encodeURIComponent(window.location.href);
                var title = encodeURIComponent(document.title);
                var network = shareBtn.dataset.network;
                var shareUrl = '';
                switch (network) {
                    case 'facebook': shareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + url; break;
                    case 'twitter': shareUrl = 'https://twitter.com/intent/tweet?url=' + url + '&text=' + title; break;
                    case 'linkedin': shareUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' + url; break;
                    case 'whatsapp': shareUrl = 'https://wa.me/?text=' + title + '%20' + url; break;
                }
                if (shareUrl) window.open(shareUrl, '_blank', 'width=600,height=400');
            });

            // Close modals
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

        showModal: function (id) {
            var el = document.getElementById(id);
            if (el) el.style.display = 'flex';
        },

        copyLink: function () {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(window.location.href);
            }
        },

        loadChart: function (wrapper) {
            var uid = wrapper.id;
            var configEl = document.getElementById(uid + '-config');
            if (!configEl) return;

            var parsed;
            try { parsed = JSON.parse(configEl.textContent); } catch (e) { this.showError(uid, 'Error al leer configuración.'); return; }

            var formData = new FormData();
            formData.append('action', 'sgr_suite_get_chart_data');
            formData.append('chart_id', parsed.chartId);
            formData.append('nonce', parsed.nonce);

            var ajaxUrl = (typeof window.sgrCharts !== 'undefined') ? window.sgrCharts.ajaxUrl : '/wp-admin/admin-ajax.php';

            fetch(ajaxUrl, {method: 'POST', body: formData, credentials: 'same-origin'})
                .then(function (r) { return r.json(); })
                .then(function (response) {
                    if (!response.success) { ChartManager.showError(uid, (response.data && response.data.message) || 'Error'); return; }
                    ChartManager.charts[uid] = {data: response.data.data, config: response.data.config};
                    ChartManager.renderChart(uid, response.data.data, response.data.config);
                })
                .catch(function () { ChartManager.showError(uid, 'Error de conexión.'); });
        },

        renderChart: function (uid, data, config) {
            var container = document.getElementById(uid + '-container');
            if (!container || !data || !data.length) { this.showError(uid, 'No hay datos disponibles.'); return; }
            container.innerHTML = '';

            var d3p = window.d3plus;
            if (!d3p) { this.showError(uid, 'D3Plus no cargado.'); return; }

            var chartType = config.chart_type || 'bar';
            var colorFn = makeColorFn(config.colors);
            var numFormat = config.number_format || 'colombiano';
            var selector = '#' + uid + '-container';
            var hasSeries = data.length > 0 && data[0].series !== undefined;
            var hasXY = data.length > 0 && data[0].x !== undefined && data[0].y !== undefined;

            // Cast numeric values
            data.forEach(function (d) {
                if (d.value !== undefined) d.value = parseFloat(d.value) || 0;
                if (d.count !== undefined) d.count = parseInt(d.count) || 0;
                if (d.x !== undefined) d.x = parseFloat(d.x) || 0;
                if (d.y !== undefined) d.y = parseFloat(d.y) || 0;
            });

            try {
                var chart;
                var tooltipCfg = {body: function (d) { return formatNumber(d.value, numFormat); }};

                switch (chartType) {

                    case 'bar':
                        chart = new d3p.BarChart()
                            .select(selector).data(data)
                            .x('label').y('value')
                            .groupBy(hasSeries ? 'series' : 'label')
                            .tooltipConfig(tooltipCfg)
                            .shapeConfig({fill: colorFn});
                        break;

                    case 'barH':
                        chart = new d3p.BarChart()
                            .select(selector).data(data)
                            .x('value').y('label').discrete('y')
                            .groupBy(hasSeries ? 'series' : 'label')
                            .tooltipConfig(tooltipCfg)
                            .shapeConfig({fill: colorFn});
                        break;

                    case 'stacked_bar':
                        chart = new d3p.BarChart()
                            .select(selector).data(data)
                            .x('label').y('value')
                            .stacked(true)
                            .groupBy(hasSeries ? 'series' : 'label')
                            .tooltipConfig(tooltipCfg)
                            .shapeConfig({fill: colorFn});
                        break;

                    case 'grouped_bar':
                        chart = new d3p.BarChart()
                            .select(selector).data(data)
                            .x('label').y('value')
                            .groupBy(hasSeries ? 'series' : 'label')
                            .tooltipConfig(tooltipCfg)
                            .shapeConfig({fill: colorFn});
                        break;

                    case 'line':
                        chart = new d3p.LinePlot()
                            .select(selector).data(data)
                            .x('label').y('value')
                            .groupBy(hasSeries ? 'series' : function () { return 'Valor'; });
                        break;

                    case 'area':
                        chart = new d3p.StackedArea()
                            .select(selector).data(data)
                            .x('label').y('value')
                            .groupBy(hasSeries ? 'series' : function () { return 'Valor'; })
                            .shapeConfig({fill: colorFn});
                        break;

                    case 'pie':
                        chart = new d3p.Pie()
                            .select(selector).data(data)
                            .groupBy('label')
                            .value(function (d) { return d.value; })
                            .tooltipConfig(tooltipCfg)
                            .shapeConfig({fill: colorFn});
                        break;

                    case 'donut':
                        chart = new d3p.Pie()
                            .select(selector).data(data)
                            .groupBy('label')
                            .value(function (d) { return d.value; })
                            .innerRadius(function () { return 80; })
                            .tooltipConfig(tooltipCfg)
                            .shapeConfig({fill: colorFn});
                        break;

                    case 'treemap':
                        chart = new d3p.Treemap()
                            .select(selector).data(data)
                            .groupBy('label').sum('value')
                            .tooltipConfig(tooltipCfg)
                            .shapeConfig({fill: colorFn});
                        break;

                    case 'pack':
                        chart = new d3p.Pack()
                            .select(selector).data(data)
                            .groupBy('label').sum('value')
                            .tooltipConfig(tooltipCfg)
                            .shapeConfig({fill: colorFn});
                        break;

                    case 'scatter':
                        // D3plus v2 usa Plot para scatter: necesita x/y numéricos.
                        // Si la vista no trae columnas x/y, degradamos a barra.
                        if (!hasXY) {
                            chart = new d3p.BarChart()
                                .select(selector).data(data)
                                .x('label').y('value')
                                .groupBy(hasSeries ? 'series' : 'label')
                                .tooltipConfig(tooltipCfg)
                                .shapeConfig({fill: colorFn});
                            break;
                        }
                        chart = new d3p.Plot()
                            .select(selector).data(data)
                            .x('x').y('y')
                            .groupBy(hasSeries ? 'series' : 'label')
                            .size(function (d) {
                                var v = (d && d.value !== undefined) ? parseFloat(d.value) : 0;
                                return v > 0 ? Math.sqrt(v) : 6;
                            })
                            .tooltipConfig({
                                title: function (d) { return d && d.label ? String(d.label) : ''; },
                                body: function (d) {
                                    if (!d) return '';
                                    var xv = formatNumber(d.x, numFormat);
                                    var yv = (d.y != null ? parseFloat(d.y).toFixed(2) : '0') + '%';
                                    return 'Valor: ' + xv + '<br/>Avance: ' + yv;
                                }
                            })
                            .shapeConfig({fill: colorFn});
                        break;

                    default:
                        chart = new d3p.BarChart()
                            .select(selector).data(data)
                            .x('label').y('value').groupBy('label')
                            .shapeConfig({fill: colorFn});
                }

                if (config.show_legend === false && chart.legend) {
                    chart.legend(false);
                }

                chart.render();

            } catch (e) {
                console.error('SGR Chart error:', e);
                this.showError(uid, 'Error al renderizar: ' + e.message);
            }
        },

        showError: function (uid, message) {
            var c = document.getElementById(uid + '-container');
            if (c) c.innerHTML = '<div class="sgr-chart-error"><p>' + escapeHtml(message) + '</p></div>';
        },

        toggleFullscreen: function (wrapper) {
            if (!document.fullscreenElement) { wrapper.requestFullscreen().catch(function () {}); }
            else { document.exitFullscreen(); }
        },

        exportImage: function (uid) {
            var container = document.getElementById(uid + '-container');
            if (!container) return;
            var svg = container.querySelector('svg');
            if (!svg) return;

            var svgData = new XMLSerializer().serializeToString(svg);
            var canvas = document.createElement('canvas');
            var ctx = canvas.getContext('2d');
            var img = new Image();

            canvas.width = svg.clientWidth * 2;
            canvas.height = svg.clientHeight * 2;
            ctx.scale(2, 2);
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            img.onload = function () {
                ctx.drawImage(img, 0, 0);
                var a = document.createElement('a');
                a.download = 'sgr-chart-' + uid + '.png';
                a.href = canvas.toDataURL('image/png');
                a.click();
            };
            img.src = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgData)));
        },

        showDataModal: function (uid) {
            var modal = document.getElementById(uid + '-data-modal');
            var chartData = this.charts[uid];
            if (!modal || !chartData || !chartData.data.length) return;

            var data = chartData.data;
            var keys = Object.keys(data[0]);
            var numFormat = chartData.config.number_format || 'colombiano';

            var thead = modal.querySelector('thead');
            var tbody = modal.querySelector('tbody');

            thead.innerHTML = '<tr>' + keys.map(function (k) { return '<th>' + escapeHtml(k) + '</th>'; }).join('') + '</tr>';
            tbody.innerHTML = data.map(function (row) {
                return '<tr>' + keys.map(function (k) {
                    var val = row[k];
                    if ((k === 'value' || k === 'count' || k === 'total_valor') && !isNaN(val)) val = formatNumber(val, numFormat);
                    return '<td>' + escapeHtml(String(val != null ? val : '')) + '</td>';
                }).join('') + '</tr>';
            }).join('');

            modal.style.display = 'flex';
        },

        downloadCSV: function (uid) {
            var chartData = this.charts[uid];
            if (!chartData || !chartData.data.length) return;
            var data = chartData.data;
            var keys = Object.keys(data[0]);
            var csv = '\uFEFF' + keys.join(',') + '\n';
            data.forEach(function (row) {
                csv += keys.map(function (k) { return '"' + String(row[k] != null ? row[k] : '').replace(/"/g, '""') + '"'; }).join(',') + '\n';
            });
            var a = document.createElement('a');
            a.href = URL.createObjectURL(new Blob([csv], {type: 'text/csv;charset=utf-8;'}));
            a.download = 'sgr-chart-' + uid + '.csv';
            a.click();
            URL.revokeObjectURL(a.href);
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { ChartManager.init(); });
    } else {
        ChartManager.init();
    }
})();
