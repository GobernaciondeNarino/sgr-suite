/**
 * SGR Suite - Frontend JS
 *
 * Modal de detalle, filtros, búsqueda y accordeones para
 * la grilla de proyectos del SGR.
 *
 * @package SGR_Suite
 * @since   1.0.0
 */
(function () {
    'use strict';

    // --- Utilidades ---

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatMoney(value) {
        if (!value || isNaN(value)) return '$ 0,00';
        var num = parseFloat(value);
        return '$ ' + num.toLocaleString('es-CO', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatNumber(value) {
        if (!value || isNaN(value)) return '0';
        return parseInt(value).toLocaleString('es-CO');
    }

    // --- Modal ---

    function generarContenidoModal(proyecto) {
        var html = '<div style="padding: 20px;">';

        // Titulo y BPIN
        html += '<h2 style="color: #334155; margin-bottom: 10px; font-size: 24px;">' + escapeHtml(proyecto.nombreProyecto) + '</h2>';
        html += '<p style="color: #348afb; font-weight: bold; margin-bottom: 20px;">BPIN: ' + escapeHtml(proyecto.numeroProyecto) + '</p>';

        // Grid de Información
        html += '<div class="regalias-modal-info-grid">';

        html += '<div class="regalias-modal-info-card">';
        html += '<div class="regalias-modal-info-label">Valor Proyecto</div>';
        html += '<div style="font-size: 18px; color: #334155; font-weight: bold;">' + formatMoney(proyecto.valorProyecto) + '</div>';
        html += '</div>';

        html += '<div class="regalias-modal-info-card">';
        html += '<div class="regalias-modal-info-label">Entidad Ejecutora</div>';
        html += '<div style="font-size: 14px; color: #334155;">' + escapeHtml(proyecto.entidadEjecutoraProyecto || 'N/A') + '</div>';
        html += '</div>';

        html += '<div class="regalias-modal-info-card">';
        html += '<div class="regalias-modal-info-label">Dependencia</div>';
        html += '<div style="font-size: 14px; color: #334155;">' + escapeHtml(proyecto.dependenciaProyecto || 'N/A') + '</div>';
        html += '</div>';

        var numContratos = proyecto.contratosProyecto ? proyecto.contratosProyecto.length : 0;
        html += '<div class="regalias-modal-info-card">';
        html += '<div class="regalias-modal-info-label">Contratos</div>';
        html += '<div style="font-size: 18px; color: #348afb; font-weight: bold;">' + numContratos + '</div>';
        html += '</div>';

        html += '</div>';

        // Metas
        if (proyecto.metasProyecto && proyecto.metasProyecto.length > 0) {
            html += '<div class="regalias-modal-accordion-item" style="margin-top: 20px;">';
            html += '<div class="regalias-modal-accordion-header" onclick="sgrToggleAccordion(this)">';
            html += '<span>Metas del Proyecto (' + proyecto.metasProyecto.length + ')</span>';
            html += '<span class="accordion-icon">&#9660;</span>';
            html += '</div>';
            html += '<div class="regalias-modal-accordion-content">';
            html += '<div class="regalias-modal-accordion-body">';
            html += '<ul style="list-style: disc; padding-left: 20px; line-height: 1.8;">';
            proyecto.metasProyecto.forEach(function (meta) {
                html += '<li style="margin-bottom: 8px;">' + escapeHtml(meta) + '</li>';
            });
            html += '</ul></div></div></div>';
        }

        // Contratos
        if (proyecto.contratosProyecto && proyecto.contratosProyecto.length > 0) {
            html += '<h3 style="margin-top: 30px; margin-bottom: 15px; color: #334155; font-size: 20px;">Contratos del Proyecto</h3>';

            proyecto.contratosProyecto.forEach(function (contrato, idx) {
                html += '<div class="regalias-modal-accordion-item">';
                html += '<div class="regalias-modal-accordion-header" onclick="sgrToggleAccordion(this)">';
                html += '<span>Contrato #' + escapeHtml(contrato.numeroContrato || String(idx + 1)) + '</span>';
                html += '<span class="accordion-icon">&#9660;</span>';
                html += '</div>';
                html += '<div class="regalias-modal-accordion-content">';
                html += '<div class="regalias-modal-accordion-body">';

                // Info del contrato
                html += '<div style="background: #f8fafc; padding: 15px; border-radius: 4px; margin-bottom: 15px;">';
                html += '<p style="margin-bottom: 10px;"><strong>Valor:</strong> ' + formatMoney(contrato.valorContrato) + '</p>';

                if (contrato.objetoContrato && contrato.objetoContrato.trim()) {
                    html += '<p style="margin-bottom: 10px;"><strong>Objeto:</strong> ' + escapeHtml(contrato.objetoContrato) + '</p>';
                }

                if (contrato.esOpsEjecContractual) {
                    html += '<p style="margin-bottom: 10px;"><strong>Es OPS:</strong> ' + escapeHtml(contrato.esOpsEjecContractual) + '</p>';
                }

                // Avance Físico
                if (contrato.procentajeAvanceFisico) {
                    var avance = parseFloat(contrato.procentajeAvanceFisico) || 0;
                    html += '<p style="margin-bottom: 5px;"><strong>Avance Físico:</strong></p>';
                    html += '<div class="regalias-modal-progress-bar">';
                    html += '<div class="regalias-modal-progress-fill" style="width: ' + avance + '%;">' + avance.toFixed(2) + '%</div>';
                    html += '</div>';
                }
                html += '</div>';

                // Municipios
                if (contrato.municipiosEjecContractual && contrato.municipiosEjecContractual.length > 0) {
                    html += '<div style="margin-bottom: 15px;">';
                    html += '<strong>Municipios Beneficiados:</strong>';
                    html += '<ul style="list-style: disc; padding-left: 20px; margin-top: 8px;">';
                    contrato.municipiosEjecContractual.forEach(function (mun) {
                        html += '<li>' + escapeHtml(mun.nombre);
                        if (mun.poblacion_beneficiada) {
                            html += ' - Población: ' + formatNumber(mun.poblacion_beneficiada);
                        }
                        html += '</li>';
                    });
                    html += '</ul></div>';
                }

                // Descripción ejecución
                if (contrato.descripcionEjecContractual && contrato.descripcionEjecContractual.trim()) {
                    html += '<div style="margin-bottom: 15px;">';
                    html += '<strong>Seguimiento:</strong>';
                    html += '<div style="background: #fff; padding: 15px; border: 1px solid #e5e7eb; border-radius: 4px; margin-top: 8px; max-height: 300px; overflow-y: auto; white-space: pre-wrap; font-size: 13px; line-height: 1.6;">';
                    html += escapeHtml(contrato.descripcionEjecContractual);
                    html += '</div></div>';
                }

                // Galería
                if (contrato.imagenesEjecContractual && contrato.imagenesEjecContractual.length > 0) {
                    html += '<div style="margin-top: 15px;">';
                    html += '<strong>Galería de Imágenes (' + contrato.imagenesEjecContractual.length + '):</strong>';
                    html += '<div class="regalias-modal-images-grid" style="margin-top: 10px;">';

                    contrato.imagenesEjecContractual.forEach(function (img, imgIdx) {
                        // Sólo se emite una URL http(s) válida para evitar javascript: u otros esquemas.
                        var rawUrl = typeof img === 'string' ? img.trim() : '';
                        if (!/^https?:\/\//i.test(rawUrl)) {
                            return;
                        }
                        var safeUrl = escapeHtml(rawUrl);
                        html += '<div class="regalias-modal-image-item">';
                        // Sin onclick inline; se delega el click más abajo tras insertar el HTML.
                        html += '<img src="' + safeUrl + '" alt="Imagen ' + (imgIdx + 1) + '" data-sgr-full="' + safeUrl + '" loading="lazy">';
                        html += '</div>';
                    });

                    html += '</div></div>';
                }

                html += '</div></div></div>';
            });
        } else {
            html += '<p style="text-align: center; color: #666; padding: 20px;">Este proyecto aún no tiene contratos registrados.</p>';
        }

        html += '</div>';
        return html;
    }

    // --- Funciones globales ---

    window.sgrAbrirModal = function (index) {
        if (typeof sgrProyectosData === 'undefined') return;
        var proyecto = sgrProyectosData[index];
        if (!proyecto) return;

        var modal = document.getElementById('regalias-grid-modal');
        var modalBody = document.getElementById('regalias-grid-modal-body');

        modalBody.innerHTML = generarContenidoModal(proyecto);

        // Enlazar apertura segura de imágenes sin onclick inline.
        modalBody.querySelectorAll('img[data-sgr-full]').forEach(function (imgEl) {
            imgEl.addEventListener('click', function () {
                var href = imgEl.getAttribute('data-sgr-full') || '';
                if (/^https?:\/\//i.test(href)) {
                    window.open(href, '_blank', 'noopener,noreferrer');
                }
            });
        });

        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    window.sgrCerrarModal = function () {
        var modal = document.getElementById('regalias-grid-modal');
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    };

    window.sgrToggleAccordion = function (header) {
        var content = header.nextElementSibling;
        var icon = header.querySelector('.accordion-icon');

        if (content.classList.contains('active')) {
            content.classList.remove('active');
            icon.innerHTML = '&#9660;';
        } else {
            content.classList.add('active');
            icon.innerHTML = '&#9650;';
        }
    };

    // --- Cerrar modal con ESC y click fuera ---

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') window.sgrCerrarModal();
    });

    document.addEventListener('click', function (e) {
        var modal = document.getElementById('regalias-grid-modal');
        if (modal && e.target === modal) {
            window.sgrCerrarModal();
        }
    });

    // --- Filtros y Búsqueda ---

    document.addEventListener('DOMContentLoaded', function () {
        var searchInput = document.getElementById('regalias-grid-search-general');
        var filterMunicipio = document.getElementById('regalias-grid-filter-municipio');
        var filterDependencia = document.getElementById('regalias-grid-filter-dependencia');
        var filterEntidad = document.getElementById('regalias-grid-filter-entidad');
        var proyectosContainer = document.getElementById('regalias-grid-proyectos');
        var noResultsMsg = document.getElementById('regalias-grid-no-results-message');

        if (!searchInput || !proyectosContainer) return;

        function aplicarFiltros() {
            var searchTerm = searchInput.value.toLowerCase().trim();
            var municipioValue = filterMunicipio ? filterMunicipio.value.toLowerCase() : '';
            var dependenciaValue = filterDependencia ? filterDependencia.value.toLowerCase() : '';
            var entidadValue = filterEntidad ? filterEntidad.value.toLowerCase() : '';

            var cards = proyectosContainer.querySelectorAll('.regalias-grid-card');
            var visibleCount = 0;

            cards.forEach(function (card) {
                var index = parseInt(card.dataset.index);
                var proyecto = (typeof sgrProyectosData !== 'undefined') ? sgrProyectosData[index] : null;

                // Búsqueda general
                var matchSearch = true;
                if (searchTerm && proyecto) {
                    var nombre = (proyecto.nombreProyecto || '').toLowerCase();
                    var numero = (proyecto.numeroProyecto || '').toLowerCase();
                    matchSearch = nombre.indexOf(searchTerm) !== -1 || numero.indexOf(searchTerm) !== -1;
                }

                // Filtros por data attributes
                var municipios = card.dataset.municipios || '';
                var dependencia = card.dataset.dependencia || '';
                var entidad = card.dataset.entidad || '';

                var matchMunicipio = !municipioValue || municipios.indexOf(municipioValue) !== -1;
                var matchDependencia = !dependenciaValue || dependencia === dependenciaValue;
                var matchEntidad = !entidadValue || entidad === entidadValue;

                var isVisible = matchSearch && matchMunicipio && matchDependencia && matchEntidad;

                card.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleCount++;
            });

            if (noResultsMsg) {
                noResultsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        }

        // Debounce para búsqueda
        var searchTimeout;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(aplicarFiltros, 300);
        });

        if (filterMunicipio) filterMunicipio.addEventListener('change', aplicarFiltros);
        if (filterDependencia) filterDependencia.addEventListener('change', aplicarFiltros);
        if (filterEntidad) filterEntidad.addEventListener('change', aplicarFiltros);
    });

})();
