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
           Compatibility Filter (chart type ↔ data view)
           =========================================== */

        /**
         * Cuando se elige un tipo de gráfico (p.ej., geomap), se deben
         * ocultar las vistas del selector que no son compatibles con ese
         * tipo. La matriz de compatibilidad llega desde PHP via
         * sgrChartsAdmin.compatibility: { view_key: [chart_type,...] }.
         * Las vistas que no aparecen en el mapa se consideran compatibles
         * con todos los tipos (fallback permisivo).
         */
        bindCompatibilityFilter: function () {
            var self = this;
            $(document).on('change', '#sgr-data-view', function () {
                self.applyCompatibilityFilter();
            });
        },

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

            // Si la vista actualmente seleccionada es incompatible, saltar
            // automáticamente a la primera opción válida.
            if (!selectedStillValid && firstValidOption !== null) {
                $select.val(firstValidOption).trigger('change');
                if (window.console) {
                    window.console.info('[SGR] Vista cambiada a la primera compatible con ' + currentType + ': ' + firstValidOption);
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

            $('#sgr-btn-preview').on('click', function () {
                var $btn = $(this);
                var dataView = $('#sgr-data-view').val();
                var limit = $('#sgr-limit').val() || 20;
                var orderDir = $('select[name="sgr_chart[order_dir]"]').val()
                    || $('#sgr-order-dir').val()
                    || 'DESC';

                if (!dataView) {
                    $('#sgr-chart-preview-area').html(
                        '<p style="text-align:center;color:#d63638;padding:15px;">Selecciona una vista de datos primero.</p>'
                    );
                    return;
                }

                $btn.prop('disabled', true).text('Cargando...');

                $.ajax({
                    url: sgrChartsAdmin.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'sgr_suite_preview_chart_data',
                        nonce: sgrChartsAdmin.nonce,
                        data_view: dataView,
                        limit: limit,
                        order_dir: orderDir
                    },
                    success: function (response) {
                        $btn.prop('disabled', false).text('Actualizar Vista Previa');

                        if (response.success) {
                            var count = response.data.count || 0;
                            var records = response.data.data || [];
                            var html = '';

                            html += '<div style="text-align:center;padding:15px;">';
                            html += '<div style="font-size:28px;font-weight:900;color:#334155;">' + count + '</div>';
                            html += '<div style="font-size:12px;color:#666;text-transform:uppercase;font-weight:600;">Registros encontrados</div>';
                            html += '</div>';

                            if (count > 0 && records.length > 0) {
                                // Show a mini table preview of first 5 records
                                var keys = Object.keys(records[0]);
                                var previewRows = records.slice(0, 5);

                                html += '<div style="overflow-x:auto;border-top:1px solid #e5e7eb;">';
                                html += '<table style="width:100%;border-collapse:collapse;font-size:12px;">';
                                html += '<thead><tr>';
                                keys.forEach(function (k) {
                                    html += '<th style="padding:6px 8px;background:#f1f5f9;text-align:left;font-weight:600;color:#334155;border-bottom:1px solid #e5e7eb;">';
                                    html += self.escapeHtml(k) + '</th>';
                                });
                                html += '</tr></thead><tbody>';

                                previewRows.forEach(function (row) {
                                    html += '<tr>';
                                    keys.forEach(function (k) {
                                        var val = row[k] != null ? String(row[k]) : '';
                                        // Truncate long values
                                        if (val.length > 40) val = val.substring(0, 37) + '...';
                                        html += '<td style="padding:5px 8px;border-bottom:1px solid #f0f0f1;color:#444;">';
                                        html += self.escapeHtml(val) + '</td>';
                                    });
                                    html += '</tr>';
                                });

                                html += '</tbody></table></div>';

                                if (count > 5) {
                                    html += '<p style="text-align:center;font-size:11px;color:#999;padding:5px 0;">Mostrando 5 de ' + count + ' registros</p>';
                                }
                            }

                            $('#sgr-chart-preview-area').html(html);
                        } else {
                            var errMsg = (response.data && response.data.message)
                                ? response.data.message
                                : 'Error al obtener datos';
                            $('#sgr-chart-preview-area').html(
                                '<p style="text-align:center;color:#d63638;padding:15px;">' +
                                self.escapeHtml(errMsg) + '</p>'
                            );
                        }
                    },
                    error: function () {
                        $btn.prop('disabled', false).text('Actualizar Vista Previa');
                        $('#sgr-chart-preview-area').html(
                            '<p style="text-align:center;color:#d63638;padding:15px;">Error de conexion con el servidor.</p>'
                        );
                    }
                });
            });
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
