/**
 * SGR Suite - Admin Charts JS v2.0.0
 *
 * Chart configuration interface for the views-based config system.
 * Features: chart type radio selection with visual highlight,
 * color input preview with swatches, data preview via AJAX
 * using data_view selects.
 *
 * Expects sgrChartsAdmin to be localized with:
 *   { ajaxUrl: '...', nonce: '...' }
 *
 * @package SGR_Suite
 * @since   2.0.0
 */
(function ($) {
    'use strict';

    if (typeof sgrChartsAdmin === 'undefined') {
        return;
    }

    var SGRChartAdmin = {

        init: function () {
            this.bindChartTypeSelection();
            this.bindColorPreview();
            this.bindPreviewButton();
            this.bindCompatibilityFilter();

            // Trigger initial color swatch render if colors already set
            var $colors = $('#sgr-colors');
            if ($colors.length && $colors.val()) {
                $colors.trigger('input');
            }

            // Aplicar filtro inicial según el tipo de gráfico seleccionado.
            this.applyCompatibilityFilter();
        },

        /* ===========================================
           Chart Type Radio Selection
           =========================================== */

        /**
         * When a chart type radio is selected, add .selected class
         * to the parent option label and remove it from siblings.
         * Además, re-aplica el filtro de compatibilidad sobre el selector
         * de vistas de datos.
         */
        bindChartTypeSelection: function () {
            var self = this;
            $(document).on('change', '.sgr-chart-type-option input[type="radio"]', function () {
                $('.sgr-chart-type-option').removeClass('selected');
                $(this).closest('.sgr-chart-type-option').addClass('selected');
                self.applyCompatibilityFilter();
            });

            // Ensure the initially checked option is highlighted on page load
            $('.sgr-chart-type-option input[type="radio"]:checked').each(function () {
                $(this).closest('.sgr-chart-type-option').addClass('selected');
            });
        },

        /* ===========================================
           Compatibility Filter (chart type ↔ data view) — BIDIRECCIONAL
           =========================================== */

        /**
         * Matriz de compatibilidad vista → tipos compatibles (llega desde
         * PHP via sgrChartsAdmin.compatibility). En v2.5.0 la matriz es
         * exhaustiva: toda vista declara sus tipos, sin fallback permisivo.
         *
         * Filtro bidireccional:
         *   1) Al cambiar el tipo de gráfico se ocultan las vistas
         *      incompatibles y se salta a la primera válida si la actual
         *      dejó de serlo.
         *   2) Al cambiar la vista se deshabilitan los radios de tipos
         *      incompatibles y se salta al primer tipo válido si el actual
         *      dejó de serlo (lo cual también re-dispara (1) sobre el nuevo
         *      tipo — la lógica es idempotente).
         */
        bindCompatibilityFilter: function () {
            var self = this;
            $(document).on('change', '#sgr-data-view', function () {
                self.applyChartTypeFilter();
                self.applyCompatibilityFilter();
            });
        },

        /**
         * Aplica (tipo → vistas): oculta las vistas incompatibles con
         * el tipo actualmente seleccionado.
         */
        applyCompatibilityFilter: function () {
            var matrix = (sgrChartsAdmin && sgrChartsAdmin.compatibility) || {};
            var $chartTypeChecked = $('.sgr-chart-type-option input[type="radio"]:checked');
            if (!$chartTypeChecked.length) {
                return;
            }
            var currentType = $chartTypeChecked.val();

            var $select = $('#sgr-data-view');
            if (!$select.length) {
                return;
            }

            var $options = $select.find('option');
            var currentlySelected = $select.val();
            var firstValidOption = null;
            var selectedStillValid = false;

            $options.each(function () {
                var opt = this;
                var $opt = $(opt);
                var key = $opt.val();
                var allowed = matrix[key];
                // Sin entrada en la matriz ⇒ legacy permisivo para no romper
                // configs guardadas antes del exhaustive mapping.
                var isCompatible = !allowed || allowed.indexOf(currentType) !== -1;

                if (isCompatible) {
                    opt.disabled = false;
                    opt.hidden = false;
                    if (firstValidOption === null) {
                        firstValidOption = key;
                    }
                    if (key === currentlySelected) {
                        selectedStillValid = true;
                    }
                } else {
                    opt.disabled = true;
                    opt.hidden = true;
                }
            });

            // Ocultar también los optgroup que quedaron totalmente vacíos.
            $select.find('optgroup').each(function () {
                var group = this;
                var visibleCount = $(group).find('option').filter(function () { return !this.hidden; }).length;
                group.disabled = visibleCount === 0;
                group.hidden = visibleCount === 0;
            });

            if (!selectedStillValid && firstValidOption !== null) {
                $select.val(firstValidOption).trigger('change');
                if (window.console) {
                    window.console.info('[SGR] Vista cambiada a la primera compatible con ' + currentType + ': ' + firstValidOption);
                }
            }
        },

        /**
         * Aplica (vista → tipos): deshabilita los radios de chart_type
         * incompatibles con la vista actualmente seleccionada. Si el tipo
         * seleccionado deja de ser válido, salta al primer tipo permitido.
         */
        applyChartTypeFilter: function () {
            var matrix = (sgrChartsAdmin && sgrChartsAdmin.compatibility) || {};
            var currentView = $('#sgr-data-view').val();
            if (!currentView) return;

            var allowed = matrix[currentView];
            // Sin entrada en la matriz ⇒ permisivo (legacy).
            if (!allowed || !allowed.length) {
                $('.sgr-chart-type-option').removeClass('sgr-chart-type-disabled')
                    .find('input[type="radio"]').prop('disabled', false);
                return;
            }

            var $labels = $('.sgr-chart-type-option');
            var selectedStillValid = false;
            var firstValidType = null;
            var currentType = $('.sgr-chart-type-option input[type="radio"]:checked').val();

            $labels.each(function () {
                var $label = $(this);
                var $radio = $label.find('input[type="radio"]');
                var type = $radio.val();
                var isAllowed = allowed.indexOf(type) !== -1;

                if (isAllowed) {
                    $label.removeClass('sgr-chart-type-disabled');
                    $radio.prop('disabled', false);
                    if (firstValidType === null) firstValidType = type;
                    if (type === currentType) selectedStillValid = true;
                } else {
                    $label.addClass('sgr-chart-type-disabled');
                    $radio.prop('disabled', true);
                }
            });

            if (!selectedStillValid && firstValidType !== null) {
                // Cambiar selección al primer tipo válido y disparar el
                // mismo evento que un click humano para que el resto de la
                // UI (highlight, refreshPreview) reaccione.
                var $nextRadio = $('.sgr-chart-type-option input[type="radio"][value="' + firstValidType + '"]');
                $nextRadio.prop('checked', true).trigger('change');
                if (window.console) {
                    window.console.info('[SGR] Tipo de gráfico cambiado al primero compatible con ' + currentView + ': ' + firstValidType);
                }
            }
        },

        /* ===========================================
           Color Input Preview
           =========================================== */

        /**
         * Parse comma-separated hex colors from the input field and
         * render color swatches in the preview container.
         */
        bindColorPreview: function () {
            $('#sgr-colors').on('input', function () {
                var rawValue = $(this).val();
                var parts = rawValue.split(',');
                var $preview = $('#sgr-color-preview');
                $preview.empty();

                parts.forEach(function (part) {
                    var color = part.trim();
                    // Validate 3-char or 6-char hex
                    if (/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(color)) {
                        $preview.append(
                            '<span class="sgr-color-swatch" style="background:' + color + ';" title="' + color + '"></span>'
                        );
                    }
                });
            });
        },

        /* ===========================================
           Preview Button (AJAX)
           =========================================== */

        /**
         * Preview button sends AJAX POST to sgr_suite_preview_chart_data
         * with data_view, limit, and order_dir. Displays the result count
         * and a sample record in the sidebar preview box.
         */
        bindPreviewButton: function () {
            var self = this;

            // Si el usuario cambia cualquier control relevante, rehacemos
            // automáticamente la vista previa. Debounced para evitar spam.
            var debouncedRefresh = self._debounce(function () {
                self.refreshPreview();
            }, 350);
            $(document).on(
                'change input',
                '#sgr-data-view, #sgr-limit, #sgr-order-dir, #sgr-chart-height, ' +
                '#sgr-number-format, #sgr-colors, #sgr-legend-mode, ' +
                '#sgr-x-labels-rotate, #sgr-x-labels-size, ' +
                '#sgr-x-title, #sgr-y-title, ' +
                'input[name="sgr_chart[chart_type]"], ' +
                'input[name="sgr_chart[x_labels_visible]"], ' +
                'input[name="sgr_chart[show_legend]"]',
                debouncedRefresh
            );

            $('#sgr-btn-preview').on('click', function (e) {
                e.preventDefault();
                self.refreshPreview(true);
            });

            // Auto-ejecutar un primer render al cargar la página si ya
            // existe una vista seleccionada (edición de un chart ya guardado).
            if ($('#sgr-data-view').length && $('#sgr-data-view').val()) {
                setTimeout(function () { self.refreshPreview(); }, 250);
            }
        },

        /**
         * Pequeño debouncer sin dependencias externas.
         */
        _debounce: function (fn, ms) {
            var t;
            return function () {
                var ctx = this, args = arguments;
                clearTimeout(t);
                t = setTimeout(function () { fn.apply(ctx, args); }, ms);
            };
        },

        /**
         * Reúne la configuración actual del formulario, llama al endpoint
         * AJAX de preview y dibuja el gráfico real con window.SGRChart.render().
         */
        refreshPreview: function (explicit) {
            var self = this;
            var $btn = $('#sgr-btn-preview');
            var $area = $('#sgr-chart-preview-area');
            if (!$area.length) return;

            var formData = self._collectConfig();
            if (!formData.data_view) {
                $area.html(
                    '<p style="text-align:center;color:#d63638;padding:15px;">Selecciona una vista de datos primero.</p>'
                );
                return;
            }

            // Mostrar un mini spinner sin destruir el área (para evitar
            // flicker cuando el render es rápido).
            if (explicit) {
                $btn.prop('disabled', true).text('Cargando...');
            }
            if (!$area.find('.sgr-preview-loading').length) {
                $area.html('<div class="sgr-preview-loading" style="padding:40px;text-align:center;color:#64748b;">' +
                    '<span class="spinner is-active" style="float:none;margin:0 8px 0 0;"></span>' +
                    'Generando vista previa…</div>');
            }

            $.ajax({
                url: sgrChartsAdmin.ajaxUrl,
                method: 'POST',
                data: $.extend({
                    action: 'sgr_suite_preview_chart_data',
                    nonce:  sgrChartsAdmin.nonce
                }, formData),
                success: function (response) {
                    $btn.prop('disabled', false).text('Actualizar Vista Previa');

                    if (!response.success) {
                        var errMsg = (response.data && response.data.message)
                            ? response.data.message
                            : 'Error al obtener datos';
                        $area.html(
                            '<p style="text-align:center;color:#d63638;padding:15px;">' +
                            self.escapeHtml(errMsg) + '</p>'
                        );
                        return;
                    }

                    var payload  = response.data || {};
                    var chartData = payload.data || [];
                    var cfg       = payload.config || formData;

                    // Siempre actualizamos el widget lateral con la tabla
                    // de datos, incluso cuando el chart no se puede dibujar.
                    self.renderDataWidget(chartData);

                    if (!chartData.length) {
                        $area.html(
                            '<p style="text-align:center;color:#64748b;padding:30px;">' +
                            'No hay datos para mostrar con la configuración actual.</p>'
                        );
                        return;
                    }

                    // Construir un wrapper con la misma estructura que usa
                    // el frontend para que la leyenda de iconos se ubique
                    // correctamente debajo del chart.
                    var wrapperId = 'sgr-chart-preview-wrapper';
                    var containerId = wrapperId + '-container';
                    $area.html(
                        '<div class="sgr-chart-wrapper" id="' + wrapperId + '" style="margin:0;">' +
                            '<div class="sgr-chart-container" id="' + containerId + '" ' +
                                 'style="height:' + (parseInt(cfg.chart_height, 10) || 360) + 'px;position:relative;"></div>' +
                        '</div>'
                    );

                    var containerEl = document.getElementById(containerId);
                    if (containerEl && window.SGRChart && typeof window.SGRChart.render === 'function') {
                        window.SGRChart.render(containerEl, chartData, cfg);
                    } else {
                        $area.html(
                            '<p style="text-align:center;color:#d63638;padding:15px;">' +
                            'SGRChart.render no está disponible (¿se cargó frontend-charts.js?).</p>'
                        );
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).text('Actualizar Vista Previa');
                    $area.html(
                        '<p style="text-align:center;color:#d63638;padding:15px;">Error de conexión con el servidor.</p>'
                    );
                }
            });
        },

        /**
         * Pintar la tabla de datos (widget lateral) con los registros
         * devueltos por el AJAX de preview. Mantiene la columna lateral
         * sincronizada con la vista seleccionada en el formulario.
         */
        renderDataWidget: function (rows) {
            var self = this;
            var $widget = $('#sgr-chart-data-widget-area');
            if (!$widget.length) return;

            if (!rows || !rows.length) {
                $widget.html(
                    '<p class="description sgr-data-widget-empty" style="text-align:center;padding:24px 6px;">' +
                    'No hay registros para la vista seleccionada.</p>'
                );
                return;
            }

            var total = rows.length;
            var preview = rows.slice(0, 10);
            var keys = Object.keys(preview[0]);

            var html = '';
            html += '<div class="sgr-data-widget-summary">';
            html += '  <span class="sgr-data-widget-count">' + total + '</span>';
            html += '  <span class="sgr-data-widget-label">registros</span>';
            html += '</div>';

            html += '<div class="sgr-data-widget-scroll"><table class="sgr-data-widget-table">';
            html += '<thead><tr>';
            keys.forEach(function (k) {
                html += '<th>' + self.escapeHtml(k) + '</th>';
            });
            html += '</tr></thead><tbody>';

            preview.forEach(function (row) {
                html += '<tr>';
                keys.forEach(function (k) {
                    var val = row[k];
                    var str;
                    if (val == null) {
                        str = '';
                    } else if (typeof val === 'object') {
                        try { str = JSON.stringify(val); } catch (e) { str = String(val); }
                    } else {
                        str = String(val);
                    }
                    if (str.length > 42) str = str.substring(0, 39) + '…';
                    html += '<td>' + self.escapeHtml(str) + '</td>';
                });
                html += '</tr>';
            });

            html += '</tbody></table></div>';

            if (total > preview.length) {
                html += '<p class="sgr-data-widget-more">' +
                    'Mostrando ' + preview.length + ' de ' + total + ' registros' +
                '</p>';
            }

            $widget.html(html);
        },

        /**
         * Recopilar la configuración actual del formulario para enviarla
         * al endpoint de preview. Devuelve un objeto aplanado con los
         * mismos nombres de campo que el AJAX handler PHP espera.
         */
        _collectConfig: function () {
            var formData = {
                chart_type:       $('input[name="sgr_chart[chart_type]"]:checked').val() || 'bar',
                data_view:        $('#sgr-data-view').val() || '',
                limit:            $('#sgr-limit').val() || 20,
                order_dir:        $('#sgr-order-dir').val() || $('select[name="sgr_chart[order_dir]"]').val() || 'DESC',
                chart_height:     $('#sgr-chart-height').val() || 400,
                number_format:    $('#sgr-number-format').val() || 'colombiano',
                colors:           $('#sgr-colors').val() || '',
                legend_mode:      $('#sgr-legend-mode').val() || 'auto',
                x_labels_rotate:  $('#sgr-x-labels-rotate').val() || 0,
                x_labels_size:    $('#sgr-x-labels-size').val() || 12,
                x_labels_visible: $('input[name="sgr_chart[x_labels_visible]"]').is(':checked') ? 1 : 0,
                x_title:          $('#sgr-x-title').val() || '',
                y_title:          $('#sgr-y-title').val() || '',
                show_legend:      $('input[name="sgr_chart[show_legend]"]').is(':checked') ? 1 : 0
            };
            return formData;
        },

        /* ===========================================
           Utility
           =========================================== */

        escapeHtml: function (text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = String(text);
            return div.innerHTML;
        }
    };

    $(document).ready(function () {
        SGRChartAdmin.init();
    });

})(jQuery);
