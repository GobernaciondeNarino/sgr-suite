/**
 * SGR Suite - Admin Charts JS
 *
 * Gestiona la interfaz de configuración de gráficos:
 * selección de tipo, carga dinámica de columnas, filtros
 * dinámicos y preview de colores.
 *
 * @package SGR_Suite
 * @since   1.0.1
 */
(function ($) {
    'use strict';

    if (typeof sgrChartsAdmin === 'undefined') {
        return;
    }

    var SGRChartAdmin = {

        filterIndex: 100,

        init: function () {
            this.bindChartTypeSelection();
            this.bindDataSourceChange();
            this.bindFilterControls();
            this.bindColorPreview();
        },

        /**
         * Selección de tipo de gráfico con highlight visual.
         */
        bindChartTypeSelection: function () {
            $(document).on('change', '.sgr-chart-type-option input[type="radio"]', function () {
                $('.sgr-chart-type-option').removeClass('selected');
                $(this).closest('.sgr-chart-type-option').addClass('selected');
            });
        },

        /**
         * Cuando cambia la fuente de datos, recargar columnas vía AJAX.
         */
        bindDataSourceChange: function () {
            var self = this;
            $('#sgr-data-source').on('change', function () {
                var table = $(this).val();
                self.loadColumns(table);
            });
        },

        /**
         * Cargar columnas de una tabla vía AJAX.
         */
        loadColumns: function (table) {
            var selects = ['#sgr-x-field', '#sgr-y-field', '#sgr-group-by', '#sgr-order-by'];

            $.ajax({
                url: sgrChartsAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'sgr_suite_get_table_columns',
                    nonce: sgrChartsAdmin.nonce,
                    table: table
                },
                success: function (response) {
                    if (!response.success) return;

                    var columns = response.data.columns;

                    selects.forEach(function (sel) {
                        var $select = $(sel);
                        var currentVal = $select.val();

                        // Preservar primera opción
                        var firstOption = $select.find('option:first').clone();
                        $select.empty().append(firstOption);

                        columns.forEach(function (col) {
                            var opt = $('<option></option>')
                                .val(col.name)
                                .text(col.name + ' (' + col.type + ')');

                            if (col.name === currentVal) {
                                opt.prop('selected', true);
                            }

                            $select.append(opt);
                        });
                    });

                    // También actualizar selects de filtros
                    $('.sgr-filter-field').each(function () {
                        var $fSelect = $(this);
                        var fVal = $fSelect.val();
                        $fSelect.empty();

                        columns.forEach(function (col) {
                            var opt = $('<option></option>')
                                .val(col.name)
                                .text(col.name);
                            if (col.name === fVal) opt.prop('selected', true);
                            $fSelect.append(opt);
                        });
                    });
                }
            });
        },

        /**
         * Controles de filtros dinámicos.
         */
        bindFilterControls: function () {
            var self = this;

            // Agregar filtro
            $('#sgr-add-filter').on('click', function () {
                var idx = self.filterIndex++;
                var operators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE'];
                var opOptions = operators.map(function (op) {
                    return '<option value="' + op + '">' + op + '</option>';
                }).join('');

                // Tomar columnas del select actual
                var colOptions = '';
                $('#sgr-x-field option').each(function () {
                    if ($(this).val()) {
                        colOptions += '<option value="' + $(this).val() + '">' + $(this).text().split(' (')[0] + '</option>';
                    }
                });

                var row = '<div class="sgr-filter-row">' +
                    '<select name="sgr_chart[filters][' + idx + '][field]" class="sgr-filter-field">' + colOptions + '</select>' +
                    '<select name="sgr_chart[filters][' + idx + '][operator]">' + opOptions + '</select>' +
                    '<input type="text" name="sgr_chart[filters][' + idx + '][value]" class="regular-text" placeholder="Valor...">' +
                    '<button type="button" class="button sgr-remove-filter">&times;</button>' +
                    '</div>';

                $('#sgr-filters-container').append(row);
            });

            // Remover filtro
            $(document).on('click', '.sgr-remove-filter', function () {
                $(this).closest('.sgr-filter-row').remove();
            });
        },

        /**
         * Preview de colores en tiempo real.
         */
        bindColorPreview: function () {
            $('#sgr-colors').on('input', function () {
                var colors = $(this).val().split(',').map(function (c) { return c.trim(); });
                var preview = $('#sgr-color-preview');
                preview.empty();

                colors.forEach(function (color) {
                    if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
                        preview.append('<span class="sgr-color-swatch" style="background: ' + color + ';"></span>');
                    }
                });
            });
        }
    };

    $(document).ready(function () {
        SGRChartAdmin.init();
    });

})(jQuery);
