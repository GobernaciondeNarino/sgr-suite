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

    /**
     * Construir un tooltip enriquecido y adaptable al esquema de los datos.
     *
     * Muestra como título el label (o "label → series" cuando aplica) y
     * como cuerpo todas las métricas disponibles en la fila: valor
     * principal, cantidad, valor promedio, valor total y participación
     * porcentual (sólo para vistas sin series, donde la suma es
     * comparable).
     *
     * Se sobrescribe después en los cases scatter/geomap que tienen sus
     * propios tooltips específicos.
     */
    function buildTooltipConfig(chartType, data, config, numFormat) {
        // Total absoluto de la serie principal para calcular %.
        var total = 0;
        var hasSeries = data.length && data[0].series !== undefined;
        for (var i = 0; i < data.length; i++) {
            var v = parseFloat(data[i].value);
            if (!isNaN(v)) total += Math.abs(v);
        }

        // Etiqueta principal del valor según la intención de la vista.
        // Para conteos (p. ej. proyectos_por_*) preferimos "Cantidad" y
        // omitimos "Valor" para evitar redundancia. Lo detectamos por la
        // ausencia de `count` (si la vista ya tiene count es que value es
        // una métrica distinta).
        var viewKey = (config && config.data_view) || '';
        // Vistas donde el value principal es una cantidad (no un valor
        // monetario): el label del tooltip muestra "Cantidad" en vez de
        // "Valor" y se omite la participación % (no tiene sentido
        // porcentualizar individuos o avance).
        var isCount = /^(?:contratos_por_|proyectos_vigencia|proyectos_dependencia|matrix_municipio|distribucion_|avance_por_entidad)/.test(viewKey);
        var valueLabel = isCount && !('valor_total' in (data[0] || {})) ? 'Cantidad' : 'Valor';

        // Para poblacion_por_municipio el valor ES el número de
        // beneficiados: mostramos "Población beneficiada" y omitimos
        // la participación % (es más impactante el absoluto).
        var isPoblacion = /^poblacion_/.test(viewKey);
        if (isPoblacion) valueLabel = 'Población beneficiada';

        // Para avance: añadir % al valor principal.
        var isAvance = /avance/.test(viewKey);
        var formatValue = function (v) {
            if (v == null || isNaN(v)) return '';
            if (isAvance) return parseFloat(v).toFixed(2) + '%';
            return formatNumber(v, numFormat);
        };

        return {
            title: function (d) {
                if (!d) return '';
                var lbl = d.label != null ? String(d.label) : '';
                var ser = d.series != null ? String(d.series) : '';
                if (lbl && ser && ser !== lbl) {
                    return lbl + ' → ' + ser;
                }
                return lbl || ser || '';
            },
            body: function (d) {
                if (!d) return '';
                var lines = [];
                var val = parseFloat(d.value);

                if (!isNaN(val)) {
                    lines.push('<strong>' + valueLabel + ':</strong> ' + formatValue(val));
                }

                // Cantidad de registros asociados (count).
                if (d.count != null) {
                    var cnt = parseInt(d.count, 10);
                    if (!isNaN(cnt)) {
                        lines.push('<strong>Proyectos:</strong> ' + cnt.toLocaleString('es-CO'));
                    }
                }

                // Valor promedio por grupo.
                if (d.valor_promedio != null) {
                    var avg = parseFloat(d.valor_promedio);
                    if (!isNaN(avg) && avg > 0) {
                        lines.push('<strong>Promedio:</strong> ' + formatNumber(avg, numFormat));
                    }
                }

                // Valor total (cuando la métrica principal es conteo).
                if (d.total_valor != null) {
                    var tot = parseFloat(d.total_valor);
                    if (!isNaN(tot) && tot > 0) {
                        lines.push('<strong>Valor total:</strong> ' + formatNumber(tot, numFormat));
                    }
                }

                // Número de contrato para distribuciones de avance.
                if (d.detalle) {
                    lines.push('<strong>Contrato:</strong> ' + String(d.detalle));
                }

                // Participación % (sólo para vistas sin series donde sumar
                // tiene sentido — en stacked/grouped el 'total' suele
                // mezclar categorías distintas).
                if (!hasSeries && total > 0 && !isNaN(val) && !isAvance && !isPoblacion && !isCount) {
                    var pct = (Math.abs(val) / total * 100).toFixed(1);
                    lines.push('<strong>Participación:</strong> ' + pct + '%');
                }

                return lines.join('<br/>');
            }
        };
    }

    /**
     * Aplicar la configuración de etiquetas del eje X sobre un chart d3plus.
     *
     * Sólo tiene sentido para charts con ejes (bar/barH/line/area/scatter y
     * sus variantes). Para tipos categóricos (pie/donut/treemap/pack/geomap)
     * se ignora silenciosamente.
     */
    function applyXAxisConfig(chart, chartType, config) {
        if (!chart) { return; }

        // Tipos donde los ejes no aplican.
        var skip = ['pie', 'donut', 'treemap', 'pack', 'geomap'];
        if (skip.indexOf(chartType) !== -1) { return; }

        var size    = parseInt(config.x_labels_size || 12, 10) || 12;
        var xTitle  = String(config.x_title || '').trim();
        var yTitle  = String(config.y_title || '').trim();

        // NOTA v2.5.3: la rotación y la visibilidad de etiquetas de tick
        // se aplican vía DOM post-render (applyAxisDomOverrides) porque
        // d3plus v2 ignora silenciosamente `shapeConfig.labelConfig.rotate`
        // cuando el auto-layout decide que "no hay crowding".
        //
        // IMPORTANTE v2.5.4: mantenemos la estructura EXACTA de v2.5.1 que
        // sabemos que no dispara ".slice is not a function":
        //   - xConfig siempre con sólo shapeConfig.labelConfig.fontSize + title opcional.
        //   - yConfig SÓLO cuando es barH (categorías en Y) o hay y_title.
        //   - Sin `titleConfig` ni `fontWeight` (dejar defaults de d3plus).
        if (typeof chart.xConfig === 'function') {
            var xConf = {
                shapeConfig: {
                    labelConfig: { fontSize: size }
                }
            };
            if (xTitle) {
                xConf.title = xTitle;
            }
            try { chart.xConfig(xConf); } catch (err) {
                console.warn('SGR Chart: xConfig no aplicado:', err && err.message);
            }
        }

        // yConfig sólo cuando aporta algo: barH (rota la axis) o título Y.
        // Llamarlo innecesariamente puede disparar edge-cases internos de
        // d3plus v2 cuando el chart no se construye aún completamente.
        var yNeedsUpdate = yTitle || chartType === 'barH';
        if (yNeedsUpdate && typeof chart.yConfig === 'function') {
            var yConf = {};
            if (chartType === 'barH') {
                yConf.shapeConfig = { labelConfig: { fontSize: size } };
            }
            if (yTitle) {
                yConf.title = yTitle;
            }
            try { chart.yConfig(yConf); } catch (_) { /* ignore */ }
        }
    }

    /**
     * Post-procesador DOM de los ejes tras chart.render().
     *
     * D3plus v2 a menudo ignora `shapeConfig.labelConfig.rotate` cuando
     * el auto-layout decide que no hace falta rotar, y no expone una
     * forma directa de ocultar las etiquetas de tick sin romper el
     * layout. Para que los controles del admin funcionen de verdad,
     * buscamos los `<text>` del eje correspondiente y aplicamos:
     *   - `display:none` si el usuario desmarcó "Mostrar etiquetas".
     *   - `transform: rotate(N, x, y)` + text-anchor cuando rotate > 0.
     *
     * La heurística localiza el eje por posición en el SVG (banda
     * inferior para X, izquierda para barH). Se ejecuta en dos pasadas
     * (rápida + tardía) para cubrir la animación de entrada.
     */
    function applyAxisDomOverrides(container, config, chartType) {
        var skip = ['pie', 'donut', 'treemap', 'pack', 'geomap'];
        if (skip.indexOf(chartType) !== -1) { return; }
        if (!container) { return; }

        var visible = config.x_labels_visible !== false;
        var rotate  = parseInt(config.x_labels_rotate || 0, 10) || 0;

        // Si no hay nada que aplicar (visibilidad por defecto + sin rotación)
        // nos ahorramos el DOM poke: d3plus ya posicionó las etiquetas bien.
        if (visible && rotate === 0) { return; }

        [120, 600].forEach(function (delay) {
            setTimeout(function () {
                // Cualquier excepción aquí NO debe propagar — el chart ya
                // se renderizó; esto es un post-proceso cosmético.
                try {
                    applyAxisOverridesOnce(container, visible, rotate, chartType);
                } catch (err) {
                    if (window.console && console.warn) {
                        console.warn('SGR Chart: DOM axis override falló:', err && err.message);
                    }
                }
            }, delay);
        });
    }

    function applyAxisOverridesOnce(container, visible, rotate, chartType) {
        var svg = container.querySelector('svg');
        if (!svg) { return; }

        // Para barH las categorías están en el eje Y; para el resto en X.
        var categoricalAxisIsX = (chartType !== 'barH');

        var svgBox = svg.getBoundingClientRect();
        if (!svgBox.width || !svgBox.height) { return; }

        var textNodes = svg.querySelectorAll('text');
        if (!textNodes.length) { return; }

        Array.prototype.forEach.call(textNodes, function (t) {
            // Excluir textos dentro de grupos marcados como título.
            var parent = t.parentNode;
            while (parent && parent !== svg) {
                var cls = (parent.getAttribute && parent.getAttribute('class')) || '';
                if (/title/i.test(cls)) { return; }
                parent = parent.parentNode;
            }

            var r = t.getBoundingClientRect();
            if (!r.width && !r.height) { return; }

            var inXBand = ( r.top > svgBox.top + svgBox.height * 0.78 );
            var inYBand = ( r.left < svgBox.left + svgBox.width * 0.22 );

            var isCategoricalTick = categoricalAxisIsX ? inXBand : inYBand;
            if (!isCategoricalTick) { return; }

            // Visibilidad
            t.style.display = visible ? '' : 'none';

            // Rotación sólo cuando el eje categórico es el X (para barH
            // las etiquetas van horizontales en Y, rotarlas sería raro).
            if (visible && categoricalAxisIsX) {
                if (rotate > 0) {
                    var x = t.getAttribute('x') || 0;
                    var y = t.getAttribute('y') || 0;
                    t.setAttribute('transform', 'rotate(' + rotate + ' ' + x + ' ' + y + ')');
                    // Anchor: para rotaciones pronunciadas "end" cuelga
                    // el texto bajo el tick; "middle" lo centra.
                    t.setAttribute('text-anchor', rotate >= 30 ? 'end' : 'middle');
                } else {
                    // Restaurar layout nativo si el usuario bajó la rotación.
                    t.removeAttribute('transform');
                    t.setAttribute('text-anchor', 'middle');
                }
            }
        });
    }

    /**
     * Tabla compacta de iconos SVG para fallback/JS cuando el servidor
     * no provee config.legend_icons (p.ej., renders que no pasan por
     * PHP). Los mismos patrones que el catálogo PHP en
     * class-visualizer.php::get_icon_catalog().
     */
    var ICON_CATALOG = {
        health:    { svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 3h4v5h5v4h-5v5h-4v-5H5V8h5V3z"/></svg>', match: ['idsn','salud','instituto departamental'] },
        water:     { svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.5c-4.5 6.5-7 9.5-7 13a7 7 0 0 0 14 0c0-3.5-2.5-6.5-7-13z"/></svg>', match: ['pda','agua','plan departamental'] },
        road:      { svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21 L8 3 M20 21 L16 3 M12 3 v3 m0 4 v3 m0 4 v4"/></svg>', match: ['infra','obra','vias','via '] },
        coins:     { svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6 v6 c0 1.7 3.6 3 8 3 s8-1.3 8-3 V6"/><path d="M4 12 v6 c0 1.7 3.6 3 8 3 s8-1.3 8-3 v-6"/></svg>', match: ['regalia','regalía','sgr'] },
        building:  { svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M4 21V7l8-4 8 4v14"/><path d="M9 21V12h6v9"/><path d="M4 21h16"/></svg>', match: ['municipio'] },
        star:      { svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 L14.7 8.6 L22 9.3 L16.4 14.1 L18.2 21.3 L12 17.6 L5.8 21.3 L7.6 14.1 L2 9.3 L9.3 8.6 Z"/></svg>', match: ['departamento','gobernacion'] },
        briefcase: { svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><rect x="2" y="7" width="20" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/><path d="M2 13h20"/></svg>', match: ['otro','especial','entidad'] },
        calendar:  { svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>', match: ['2023','2024','2025','2026','2027','vigencia','idsn*','infra*'] },
        target:    { svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/></svg>', match: ['meta','objetivo'] },
        document:  { svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="14 3 14 9 20 9"/><path d="M8 13h8M8 17h5"/></svg>', match: ['contrato'] },
        alert:     { svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"><path d="M10.3 3.9 1.9 18a2 2 0 0 0 1.7 3h16.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>', match: ['alto','riesgo alto'] },
        warning:   { svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>', match: ['medio','riesgo medio'] },
        check:     { svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M7 12l4 4 6-8"/></svg>', match: ['bajo','riesgo bajo'] },
        mappin:    { svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>', match: ['mapa','geomap','mun.','mpio'] },
        'default': { svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 1 1 5.8 1c0 2-3 2.5-3 4.5"/><path d="M12 18h.01"/></svg>', match: [] }
    };

    function normalizeKey(v) {
        if (v == null) return '';
        var s = String(v);
        // Quitar tildes y pasar a minúsculas.
        try { s = s.normalize('NFD').replace(/[\u0300-\u036f]/g, ''); } catch (_) { /* IE */ }
        return s.toLowerCase();
    }

    function resolveIconForLabel(label) {
        var needle = normalizeKey(label);
        var keys = Object.keys(ICON_CATALOG);
        for (var i = 0; i < keys.length; i++) {
            var k = keys[i];
            if (k === 'default') continue;
            var patterns = ICON_CATALOG[k].match || [];
            for (var j = 0; j < patterns.length; j++) {
                if (patterns[j] && needle.indexOf(patterns[j]) !== -1) {
                    return ICON_CATALOG[k];
                }
            }
        }
        return ICON_CATALOG['default'];
    }

    /**
     * Dibujar una leyenda HTML personalizada debajo del wrapper del gráfico.
     *
     * Modos soportados:
     *   - 'icons' → sólo los cuadros con icono SVG (sin texto al lado).
     *               El título se expone vía tooltip nativo del navegador.
     *   - 'text'  → sólo chips con punto de color + label (sin icono SVG).
     *
     * Usa config.legend_icons si viene pre-computado desde PHP, o lo
     * calcula sobre la marcha a partir de las filas únicas del dataset.
     */
    function renderIconLegend(wrapperEl, data, config, mode) {
        if (!wrapperEl) return;
        mode = mode === 'text' ? 'text' : 'icons';

        var items = Array.isArray(config.legend_icons) ? config.legend_icons.slice() : null;
        if (!items || !items.length) {
            // Fallback: construir en cliente.
            items = [];
            var seen = {};
            var field = data.length && data[0].series !== undefined ? 'series' : 'label';
            var palette = (config.colors && config.colors.length) ? config.colors : ['#348afb','#1e40af','#059669','#d97706','#dc2626','#7c3aed','#0891b2','#be185d'];
            var idx = 0;
            for (var i = 0; i < data.length; i++) {
                var lbl = data[i][field];
                if (lbl == null || seen[lbl]) continue;
                seen[lbl] = true;
                var icon = resolveIconForLabel(lbl);
                items.push({
                    label: String(lbl),
                    color: palette[idx % palette.length],
                    svg:   icon.svg
                });
                idx++;
            }
        }

        if (!items.length) return;

        var legend = document.createElement('div');
        legend.className = 'sgr-chart-icon-legend sgr-chart-icon-legend--' + mode;

        items.forEach(function (item) {
            var chip = document.createElement('div');
            chip.className = 'sgr-chart-icon-legend-item';
            // El título (tooltip nativo) siempre expone el nombre aunque
            // el modo 'icons' no lo muestre en pantalla.
            chip.setAttribute('title', item.label);

            if (mode === 'icons') {
                // Sólo cuadro con icono, sin label textual al lado.
                var iconBox = document.createElement('span');
                iconBox.className = 'sgr-chart-icon-legend-icon';
                iconBox.style.backgroundColor = item.color || '#94a3b8';
                iconBox.style.color = '#ffffff';
                iconBox.innerHTML = item.svg || '';
                chip.appendChild(iconBox);
            } else {
                // Modo 'text': punto de color + label, sin icono SVG.
                var dot = document.createElement('span');
                dot.className = 'sgr-chart-icon-legend-dot';
                dot.style.backgroundColor = item.color || '#94a3b8';
                chip.appendChild(dot);

                var labelBox = document.createElement('span');
                labelBox.className = 'sgr-chart-icon-legend-label';
                labelBox.textContent = item.label;
                chip.appendChild(labelBox);
            }

            legend.appendChild(chip);
        });

        wrapperEl.appendChild(legend);
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
            if (!container) { this.showError(uid, 'Contenedor no encontrado.'); return; }
            try {
                this._doRender(container, data, config);
            } catch (e) {
                console.error('SGR Chart error:', e);
                this.showError(uid, 'Error al renderizar: ' + e.message);
            }
        },

        /**
         * Núcleo del renderer. Se comparte con ChartManager.renderChart()
         * y con la API pública window.SGRChart.render(), que el admin usa
         * para dibujar la vista previa en tiempo real.
         *
         * Recibe un elemento DOM (no un uid), data y config. No captura
         * errores: el caller decide cómo mostrarlos.
         *
         * @private
         */
        _doRender: function (container, data, config) {
            if (!container) {
                return;
            }
            if (!data || !data.length) {
                container.innerHTML = '<div class="sgr-chart-error"><p>' + escapeHtml('No hay datos disponibles.') + '</p></div>';
                return;
            }
            // Limpiar restos previos (loader, error, gráfico anterior).
            container.innerHTML = '';
            // Limpiar cualquier leyenda de iconos previa en el wrapper padre.
            var wrapperEl = container.closest ? container.closest('.sgr-chart-wrapper') : null;
            if (wrapperEl) {
                var oldLegend = wrapperEl.querySelector('.sgr-chart-icon-legend');
                if (oldLegend) { oldLegend.parentNode.removeChild(oldLegend); }
            }

            var d3p = window.d3plus;
            if (!d3p) {
                container.innerHTML = '<div class="sgr-chart-error"><p>' + escapeHtml('D3Plus no cargado.') + '</p></div>';
                return;
            }

            var chartType = config.chart_type || 'bar';
            var colorFn = makeColorFn(config.colors);
            var numFormat = config.number_format || 'colombiano';
            var hasSeries = data.length > 0 && data[0].series !== undefined;
            var hasXY = data.length > 0 && data[0].x !== undefined && data[0].y !== undefined;

            // Normalizar los valores numéricos antes de entregar el
            // array a d3plus. wpdb los devuelve como strings (p.ej.
            // "4791599566189.55"); parseFloat + Math.round elimina la
            // cola de decimales que dispara edge-cases numéricos en el
            // axis (BarChart/StackedArea con valores COP ~10^12).
            //
            // Se clona cada fila para no mutar el array original — el
            // widget lateral de datos lo consume en paralelo y debe
            // conservar los valores crudos.
            data = data.map(function (row) {
                var d = {};
                for (var k in row) {
                    if (Object.prototype.hasOwnProperty.call(row, k)) {
                        d[k] = row[k];
                    }
                }
                if (d.value !== undefined) {
                    d.value = Math.round(parseFloat(d.value) || 0);
                }
                if (d.count !== undefined) {
                    d.count = parseInt(d.count, 10) || 0;
                }
                if (d.x !== undefined) {
                    var nx = parseFloat(d.x) || 0;
                    d.x = Math.round(nx * 100) / 100;
                }
                if (d.y !== undefined) {
                    var ny = parseFloat(d.y) || 0;
                    d.y = Math.round(ny * 100) / 100;
                }
                if (d.valor_total !== undefined) {
                    d.valor_total = Math.round(parseFloat(d.valor_total) || 0);
                }
                // Asegurar que label/series sean strings (nunca undefined
                // ni numéricos) para evitar que d3plus llame .slice() o
                // similar sobre un tipo inesperado.
                if (d.label !== undefined && d.label !== null) {
                    d.label = String(d.label);
                }
                if (d.series !== undefined && d.series !== null) {
                    d.series = String(d.series);
                }
                return d;
            });

            // Asegurar que el contenedor tenga un id para que d3plus pueda
            // seleccionarlo. Si es un elemento temporal del admin, sintetizamos
            // uno estable derivado del timestamp.
            if (!container.id) {
                container.id = 'sgr-chart-anon-' + Math.random().toString(36).slice(2, 9);
            }
            var selector = '#' + container.id;

            var chart;
            // Tooltip enriquecido que muestra label + series + métricas
            // disponibles. Los cases scatter y geomap lo sobrescriben con
            // su propia configuración específica más abajo.
            var tooltipCfg = buildTooltipConfig(chartType, data, config, numFormat);

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
                            .groupBy(hasSeries ? 'series' : function () { return 'Valor'; })
                            .tooltipConfig(tooltipCfg)
                            .shapeConfig({stroke: colorFn, strokeWidth: 2});
                        break;

                    case 'area':
                        chart = new d3p.StackedArea()
                            .select(selector).data(data)
                            .x('label').y('value')
                            .groupBy(hasSeries ? 'series' : function () { return 'Valor'; })
                            .tooltipConfig(tooltipCfg)
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

                    case 'geomap':
                        // Geomap Nariño: requiere topojson local + data con
                        // columna `id` = DIVIPOLA (la agregación la hace PHP).
                        //
                        // Preparación del topojson: cada geometría tiene ya
                        // feature.id = DIVIPOLA (normalizado offline), así que
                        // podemos usar la configuración por defecto de
                        // d3plus-geomap sin topojsonId/topojsonFilter (evitando
                        // bugs de short-circuit y diferencias entre versiones).
                        if (!d3p.Geomap) {
                            this.showError(uid, 'Geomap no disponible en esta versión de D3plus.');
                            return;
                        }
                        var topoUrl = (typeof window.sgrCharts !== 'undefined' && window.sgrCharts.topojsonUrl)
                            ? window.sgrCharts.topojsonUrl
                            : '';
                        if (!topoUrl) {
                            this.showError(uid, 'No se encontró el topojson de municipios.');
                            return;
                        }

                        // Asegurar que data[i].id sea string (los IDs del
                        // topojson son strings; un int no hace match).
                        data.forEach(function (d) {
                            if (d && d.id != null) {
                                d.id = String(d.id);
                            }
                        });

                        // Paleta secuencial por defecto (sin #FFFCF3 — los
                        // municipios sin datos no se incluyen en la data,
                        // se pintan con el fill por defecto más abajo).
                        var geomapPalette = (config.colors && config.colors.length >= 3)
                            ? config.colors
                            : ['#bfdbfe', '#60a5fa', '#2563eb', '#1e3a8a'];

                        // Construcción defensiva: algunos métodos (fitFilter,
                        // topojsonId, ocean, tiles) pueden no estar expuestos
                        // en todas las variantes del bundle. Se aplican con
                        // detección de tipo para no romper la cadena.
                        // v2.5.6: sólo se pasan filas con datos reales.
                        // Los polígonos sin data reciben fill #FFFCF3 vía
                        // shapeConfig. El tooltip nativo de d3plus sólo
                        // dispara en features con data-match — así no hay
                        // confusión con "sin contratos".
                        chart = new d3p.Geomap()
                            .select(selector)
                            .data(data)
                            .groupBy('id')
                            .colorScale('value')
                            .colorScaleConfig({
                                color: geomapPalette,
                                axisConfig: {
                                    tickFormat: function (n) {
                                        return formatNumber(n, numFormat);
                                    }
                                }
                            })
                            .colorScalePosition('bottom')
                            .tooltipConfig({
                                title: function (d) {
                                    return d && d.label ? String(d.label) : '';
                                },
                                body: function (d) {
                                    if (!d) return '';
                                    var metric  = (config.data_view && config.data_view.indexOf('contratos') !== -1)
                                        ? 'Contratos'
                                        : 'Valor';
                                    var lines = [];
                                    if (d.value != null) {
                                        lines.push('<strong>' + metric + ':</strong> ' + formatNumber(d.value, numFormat));
                                    }
                                    if (d.contratos != null && metric !== 'Contratos') {
                                        lines.push('<strong>Contratos:</strong> ' + d.contratos);
                                    }
                                    if (d.valor_total != null && metric === 'Contratos') {
                                        lines.push('<strong>Valor total:</strong> ' + formatNumber(d.valor_total, numFormat));
                                    }
                                    if (d.poblacion != null && d.poblacion > 0) {
                                        lines.push('<strong>Población:</strong> ' + formatNumber(d.poblacion, 'colombiano'));
                                    }
                                    if (d.avance_promedio != null && d.avance_promedio > 0) {
                                        lines.push('<strong>Avance:</strong> ' + d.avance_promedio + '%');
                                    }
                                    if (d.dependencias && d.dependencias.length) {
                                        lines.push('<strong>Dep.:</strong> ' + d.dependencias.join(', '));
                                    }
                                    return lines.join('<br/>');
                                }
                            })
                            .topojson(topoUrl);

                        if (typeof chart.topojsonId === 'function') {
                            chart.topojsonId('id');
                        }
                        if (typeof chart.tiles === 'function') {
                            chart.tiles(true);
                        }
                        if (typeof chart.ocean === 'function') {
                            chart.ocean('transparent');
                        }
                        if (typeof chart.fitFilter === 'function') {
                            chart.fitFilter(function (d) {
                                var fid = d && d.id != null ? String(d.id) : '';
                                return fid.length === 5 && fid.substring(0, 2) === '52';
                            });
                        }
                        break;

                    default:
                        chart = new d3p.BarChart()
                            .select(selector).data(data)
                            .x('label').y('value').groupBy('label')
                            .shapeConfig({fill: colorFn});
                }

            // Ocultar la leyenda nativa de d3plus cuando el usuario
            // eligió cualquiera de los modos personalizados (icons/text)
            // o la ocultó totalmente. 'auto' = leyenda d3plus por defecto.
            var legendMode = config.legend_mode || 'auto';
            var hideNativeLegend =
                legendMode === 'icons' ||
                legendMode === 'text'  ||
                legendMode === 'hidden' ||
                config.show_legend === false;
            if (hideNativeLegend && chart.legend) {
                try { chart.legend(false); } catch (_) { /* ignore */ }
            }

            // Aplicar configuración base de los ejes (title, fontSize).
            applyXAxisConfig(chart, chartType, config);

            chart.render();

            // Post-procesamiento DOM de los ejes: rotación forzada y
            // ocultamiento real de las etiquetas de tick.
            applyAxisDomOverrides(container, config, chartType);

            // Leyenda HTML personalizada (iconos o texto) se construye
            // después del render para insertarse en el wrapper. El admin
            // puede sobre-escribir config.legend_icons en runtime.
            if ((legendMode === 'icons' || legendMode === 'text') && wrapperEl) {
                renderIconLegend(wrapperEl, data, config, legendMode);
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

    // API pública reutilizable por el admin (vista previa) y por terceros.
    //
    //   window.SGRChart.render(containerEl, data, config)
    //     containerEl: elemento DOM donde dibujar (se vacía antes).
    //     data:        array devuelto por el AJAX de preview/get_chart_data.
    //     config:      objeto config devuelto junto con los datos.
    //
    // Si containerEl pertenece a un `.sgr-chart-wrapper`, la leyenda de
    // iconos se inserta automáticamente en el wrapper padre.
    window.SGRChart = {
        render: function (containerEl, data, config) {
            if (!containerEl) return;
            try {
                ChartManager._doRender(containerEl, data, config || {});
            } catch (e) {
                console.error('SGR Chart error:', e);
                containerEl.innerHTML = '<div class="sgr-chart-error"><p>' +
                    escapeHtml('Error al renderizar: ' + e.message) + '</p></div>';
            }
        },
        resolveIconForLabel: resolveIconForLabel
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { ChartManager.init(); });
    } else {
        ChartManager.init();
    }
})();
