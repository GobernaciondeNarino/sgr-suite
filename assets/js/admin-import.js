/**
 * SGR Suite - Admin Import JS
 *
 * Gestiona la importación de datos desde el panel de administración:
 * inicio, progreso, cancelación y limpieza de datos.
 *
 * @package SGR_Suite
 * @since   1.0.0
 */
(function ($) {
    'use strict';

    if (typeof sgrSuiteAdmin === 'undefined') {
        return;
    }

    var SGRImport = {
        polling: null,
        isRunning: false,

        init: function () {
            $('#sgr-btn-import').on('click', this.startImport.bind(this));
            $('#sgr-btn-cancel').on('click', this.cancelImport.bind(this));
            $('#sgr-btn-truncate').on('click', this.truncateData.bind(this));
        },

        startImport: function () {
            if (this.isRunning) return;

            this.isRunning = true;
            $('#sgr-btn-import').prop('disabled', true).text(sgrSuiteAdmin.i18n.importando);
            $('#sgr-btn-cancel').show();
            $('#sgr-import-progress').show();
            $('#sgr-import-result').hide();

            $.ajax({
                url: sgrSuiteAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'sgr_suite_start_import',
                    nonce: sgrSuiteAdmin.nonce
                },
                success: function () {
                    SGRImport.startPolling();
                },
                error: function (xhr) {
                    SGRImport.showError('Error al iniciar: ' + (xhr.responseJSON?.data?.message || 'Error desconocido'));
                    SGRImport.resetUI();
                }
            });
        },

        startPolling: function () {
            this.polling = setInterval(function () {
                SGRImport.checkProgress();
            }, 2000);
        },

        checkProgress: function () {
            $.ajax({
                url: sgrSuiteAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'sgr_suite_check_progress',
                    nonce: sgrSuiteAdmin.nonce
                },
                success: function (response) {
                    if (!response.success) return;

                    var data = response.data;
                    var status = data.status || 'idle';

                    if (status === 'running') {
                        SGRImport.updateProgress(data);
                    } else if (status === 'complete') {
                        SGRImport.stopPolling();
                        SGRImport.updateProgress(data);
                        SGRImport.showSuccess(data.message || sgrSuiteAdmin.i18n.completado);
                        SGRImport.resetUI();
                        // Recargar después de 2 segundos
                        setTimeout(function () { location.reload(); }, 2000);
                    } else if (status === 'error' || status === 'cancelled') {
                        SGRImport.stopPolling();
                        SGRImport.showError(data.message || sgrSuiteAdmin.i18n.error);
                        SGRImport.resetUI();
                    }
                },
                error: function () {
                    // No detener polling por un error de red aislado
                }
            });
        },

        updateProgress: function (data) {
            var total = parseInt(data.total) || 0;
            var current = parseInt(data.current) || 0;
            var percent = total > 0 ? Math.round((current / total) * 100) : 0;

            $('#sgr-progress-bar').css('width', percent + '%').text(percent + '%');
            $('#sgr-progress-text').text(data.message || '');
        },

        cancelImport: function () {
            $.ajax({
                url: sgrSuiteAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'sgr_suite_cancel_import',
                    nonce: sgrSuiteAdmin.nonce
                },
                success: function () {
                    SGRImport.stopPolling();
                    SGRImport.showError(sgrSuiteAdmin.i18n.cancelado);
                    SGRImport.resetUI();
                }
            });
        },

        truncateData: function () {
            if (!confirm(sgrSuiteAdmin.i18n.confirmarLimpiar)) {
                return;
            }

            $.ajax({
                url: sgrSuiteAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'sgr_suite_truncate_data',
                    nonce: sgrSuiteAdmin.nonce
                },
                success: function (response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data?.message || 'Error al eliminar datos.');
                    }
                },
                error: function () {
                    alert('Error de conexión.');
                }
            });
        },

        stopPolling: function () {
            if (this.polling) {
                clearInterval(this.polling);
                this.polling = null;
            }
        },

        resetUI: function () {
            this.isRunning = false;
            $('#sgr-btn-import').prop('disabled', false).text('Iniciar Importación');
            $('#sgr-btn-cancel').hide();
        },

        showSuccess: function (message) {
            $('#sgr-import-result')
                .removeClass('sgr-import-error')
                .addClass('sgr-import-success')
                .html('<strong>' + this.escapeHtml(message) + '</strong>')
                .show();
        },

        showError: function (message) {
            $('#sgr-import-result')
                .removeClass('sgr-import-success')
                .addClass('sgr-import-error')
                .html('<strong>' + this.escapeHtml(message) + '</strong>')
                .show();
        },

        escapeHtml: function (text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    $(document).ready(function () {
        SGRImport.init();
    });

})(jQuery);
