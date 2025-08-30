document.addEventListener('DOMContentLoaded', function() {
    // --- LÓGICA DEL MENÚ MÓVIL (Estable) ---
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.querySelector('.ghd-sidebar');
    if (menuToggle && sidebar) {
        const overlay = document.createElement('div');
        overlay.style.cssText = "position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:999;display:none;";
        document.body.appendChild(overlay);
        const closeMenu = () => { sidebar.classList.remove('sidebar-visible'); overlay.style.display = 'none'; };
        menuToggle.addEventListener('click', (e) => { e.stopPropagation(); sidebar.classList.add('sidebar-visible'); overlay.style.display = 'block'; });
        overlay.addEventListener('click', closeMenu);
    }

    // --- Función para actualizar los KPIs en los paneles de Sector (Carpintería, Corte, etc.) ---
    const updateSectorKPIs = (kpiData) => {
        const activasEl = document.getElementById('kpi-activas');
        const prioridadEl = document.getElementById('kpi-prioridad-alta');
        const completadasHoyEl = document.getElementById('kpi-completadas-hoy');
        const tiempoEl = document.getElementById('kpi-tiempo-promedio');

        if (activasEl) activasEl.textContent = kpiData.total_pedidos;
        if (prioridadEl) prioridadEl.textContent = kpiData.total_prioridad_alta;
        if (completadasHoyEl) completadasHoyEl.textContent = kpiData.completadas_hoy;
        if (tiempoEl) tiempoEl.textContent = kpiData.tiempo_promedio_str;
    };

    // --- Función para actualizar los KPIs en la sección de Cierre del Admin principal ---
    const updateAdminClosureKPIs = (kpiData) => {
        const activasEl = document.getElementById('kpi-cierre-activas');
        const prioridadEl = document.getElementById('kpi-cierre-prioridad-alta');
        const completadasHoyEl = document.getElementById('kpi-cierre-completadas-hoy');
        const tiempoEl = document.getElementById('kpi-cierre-tiempo-promedio');

        if (activasEl) activasEl.textContent = kpiData.total_pedidos_cierre;
        if (prioridadEl) prioridadEl.textContent = kpiData.total_prioridad_alta_cierre;
        if (completadasHoyEl) completadasHoyEl.textContent = kpiData.completadas_hoy_cierre;
        if (tiempoEl) tiempoEl.textContent = kpiData.tiempo_promedio_str_cierre;
    };


    // --- LÓGICA UNIVERSAL DE CLICS EN EL CONTENIDO PRINCIPAL ---
    const mainContent = document.querySelector('.ghd-main-content');
    if (mainContent) {
        mainContent.addEventListener('click', function(e) {
            
<<<<<<< HEAD
            // Lógica para el botón "Archivar Pedido"
            // Esta lógica DEBE evitar ser el controlador principal del panel administrativo
            // ya que el panel administrativo tiene su propia lógica de archivo con actualización de KPIs.
            // Usamos la nueva clase 'is-admin-sector-panel' para diferenciarlo.
            const archiveBtn = e.target.closest('.archive-order-btn');
            if (archiveBtn && !document.body.classList.contains('is-admin-sector-panel')) { 
=======
            // Lógica para el botón "Archivar Pedido" (versión NO-ADMIN-DASHBOARD)
            const archiveBtnGeneral = e.target.closest('.archive-order-btn');
            if (archiveBtnGeneral && !document.body.classList.contains('is-admin-dashboard-panel')) { 
>>>>>>> 2dac4e9 (Feat: completado del flujo de trabajo)
                e.preventDefault();
                if (!confirm('¿Archivar este pedido? Esta acción es final.')) return;

                const orderId = archiveBtnGeneral.dataset.orderId;
                const container = archiveBtnGeneral.closest('tr') || archiveBtnGeneral.closest('.ghd-order-card');
                
                container.style.opacity = '0.5';
                archiveBtnGeneral.disabled = true;
                archiveBtnGeneral.textContent = 'Archivando...';

                const params = new URLSearchParams({ action: 'ghd_archive_order', nonce: ghd_ajax.nonce, order_id: orderId });
                fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            container.remove();
                        } else {
                            alert('Error: ' + (data.data?.message || 'No se pudo archivar.'));
                            container.style.opacity = '1';
                            archiveBtnGeneral.disabled = false;
                            archiveBtnGeneral.textContent = 'Archivar Pedido';
                        }
                    });
            }

            // Lógica para los botones de estado del panel de sector (.action-button)
            const actionButton = e.target.closest('.action-button');
            if (actionButton) {
                e.preventDefault();
                const card = actionButton.closest('.ghd-order-card');
                card.style.opacity = '0.5';
                
                const params = new URLSearchParams({ 
                    action: 'ghd_update_task_status', 
                    nonce: ghd_ajax.nonce, 
                    order_id: actionButton.dataset.orderId, 
                    field: actionButton.dataset.field, 
                    value: actionButton.dataset.value 
                });

                fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            if (actionButton.dataset.value === 'Completado') { 
                                card.remove(); 
                            } else { 
                                card.outerHTML = data.data.html; 
                            }
                            // ¡Actualizamos los KPIs del sector después de la acción!
                            if (data.data.kpi_data) {
                                updateSectorKPIs(data.data.kpi_data);
                            }
                        } else { 
                            alert('Error al actualizar: ' + (data.data?.message || '')); 
                        }
                    })
                    .finally(() => {
                        // Si la tarjeta no fue removida, restaurar opacidad
                        const finalCard = document.getElementById(card.id);
                        if (finalCard) { finalCard.style.opacity = '1'; }
                    });
            }
            
            // Lógica para el botón "Iniciar Producción" en el Panel de Administrador principal
            const startProductionBtn = e.target.closest('.start-production-btn');
            if (startProductionBtn && document.body.classList.contains('is-admin-dashboard-panel')) {
                e.preventDefault();
                if (!confirm('¿Deseas iniciar la producción de este pedido?')) return;

                const orderId = startProductionBtn.dataset.orderId;
                const rowToRemove = startProductionBtn.closest('tr');
                
                rowToRemove.style.opacity = '0.5';
                startProductionBtn.disabled = true;
                startProductionBtn.textContent = 'Iniciando...';

                const params = new URLSearchParams({ 
                    action: 'ghd_admin_action', 
                    nonce: ghd_ajax.nonce, 
                    order_id: orderId,
                    type: 'start_production' 
                });

                fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            rowToRemove.remove(); 
                        } else {
                            alert('Error: ' + (data.data?.message || 'No se pudo iniciar la producción.'));
                            rowToRemove.style.opacity = '1';
                            startProductionBtn.disabled = false;
                            startProductionBtn.textContent = 'Iniciar Producción';
                        }
                    })
                    .catch(error => {
                        console.error("Error en la petición AJAX:", error);
                        alert('Error de red. No se pudo iniciar la producción.');
                        rowToRemove.style.opacity = '1';
                        startProductionBtn.disabled = false;
                        startProductionBtn.textContent = 'Iniciar Producción';
                    });
            }

            // --- LÓGICA: BOTÓN "Archivar Pedido" en la sección de Cierre del Admin principal ---
            const archiveClosureBtn = e.target.closest('.archive-order-btn');
            const isWithinClosureTable = e.target.closest('#ghd-closure-table-body');
            
            if (archiveClosureBtn && isWithinClosureTable && document.body.classList.contains('is-admin-dashboard-panel')) {
                e.preventDefault();
                if (!confirm('¿Archivar este pedido? Esta acción es final.')) return;

                const orderId = archiveClosureBtn.dataset.orderId;
                const rowToArchive = archiveClosureBtn.closest('tr');
                
                rowToArchive.style.opacity = '0.5';
                archiveClosureBtn.disabled = true;
                archiveClosureBtn.textContent = 'Archivando...';

                const params = new URLSearchParams({ 
                    action: 'ghd_archive_order', 
                    nonce: ghd_ajax.nonce, 
                    order_id: orderId 
                });

                fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            rowToArchive.remove();
                            if (data.data.kpi_data) {
                                updateAdminClosureKPIs(data.data.kpi_data);
                            }
                        } else {
                            alert('Error: ' + (data.data.message || 'No se pudo archivar.'));
                            rowToArchive.style.opacity = '1';
                            archiveClosureBtn.disabled = false;
                            archiveClosureBtn.textContent = 'Archivar Pedido';
                        }
                    })
                    .catch(error => {
                        console.error("Error en la petición AJAX:", error);
                        alert('Error de red. No se pudo archivar el pedido.');
                        rowToArchive.style.opacity = '1';
                        archiveClosureBtn.disabled = false;
                        archiveClosureBtn.textContent = 'Archivar Pedido';
                    });
            }

        }); // Fin del mainContent.addEventListener('click')
    } // Fin del if(mainContent)


    // --- LÓGICA PARA EL BOTÓN "REFRESCAR" EN PANELES DE SECTOR ---
    const refreshTasksBtn = document.getElementById('ghd-refresh-tasks');
    if (refreshTasksBtn) {
        refreshTasksBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const sectorTasksList = document.querySelector('.ghd-sector-tasks-list');
            const mainContentEl = document.querySelector('.ghd-main-content'); // Obtener el elemento main
            const campoEstado = mainContentEl ? mainContentEl.dataset.campoEstado : ''; // Obtener campo_estado del data attribute

            if (!sectorTasksList || !campoEstado) {
                console.error("No se encontró la lista de tareas del sector o el campo_estado.");
                return;
            }

            sectorTasksList.style.opacity = '0.5'; // Efecto visual de carga
            refreshTasksBtn.disabled = true;

            const params = new URLSearchParams({
                action: 'ghd_refresh_sector_tasks',
                nonce: ghd_ajax.nonce,
                campo_estado: campoEstado // Pasar el campo_estado del sector actual
            });

            fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        sectorTasksList.innerHTML = data.data.tasks_html;
                        if (data.data.kpi_data) {
                            updateSectorKPIs(data.data.kpi_data); // Actualizar los KPIs
                        }
                    } else {
                        alert('Error al refrescar tareas: ' + (data.data?.message || ''));
                        sectorTasksList.innerHTML = '<p class="no-tasks-message">Error al cargar tareas.</p>';
                    }
                })
                .catch(error => {
                    console.error("Error en la petición AJAX de refresco de sector:", error);
                    alert('Error de red al refrescar tareas.');
                    sectorTasksList.innerHTML = '<p class="no-tasks-message">Error de red. Inténtalo de nuevo.</p>';
                })
                .finally(() => {
                    sectorTasksList.style.opacity = '1';
                    refreshTasksBtn.disabled = false;
                });
        });
    }

    // --- LÓGICA PARA EL BOTÓN "REFRESCAR" EN LA SECCIÓN DE CIERRE DEL ADMIN PRINCIPAL ---
    const refreshClosureTasksBtn = document.getElementById('ghd-refresh-closure-tasks');
    if (refreshClosureTasksBtn && document.body.classList.contains('is-admin-dashboard-panel')) {
        refreshClosureTasksBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const closureTasksContainer = document.getElementById('admin-closure-tasks-container');
            const closureTableBody = document.getElementById('ghd-closure-table-body');

            if (!closureTasksContainer || !closureTableBody) {
                console.error("No se encontró el contenedor de tareas de cierre.");
                return;
            }
            
            closureTasksContainer.style.opacity = '0.5'; // Efecto visual de carga
            refreshClosureTasksBtn.disabled = true;

            const params = new URLSearchParams({
                action: 'ghd_refresh_admin_closure_section',
                nonce: ghd_ajax.nonce
            });

            fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        closureTableBody.innerHTML = data.data.table_html;
                        if (data.data.kpi_data) {
                            updateAdminClosureKPIs(data.data.kpi_data); // Actualizar los KPIs de cierre
                        }
                    } else {
                        alert('Error al refrescar pedidos de cierre: ' + (data.data?.message || ''));
                        closureTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Error al cargar pedidos de cierre.</td></tr>';
                    }
                })
                .catch(error => {
                    console.error("Error en la petición AJAX de refresco de cierre:", error);
                    alert('Error de red al refrescar pedidos de cierre.');
                    closureTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Error de red. Inténtalo de nuevo.</td></tr>';
                })
                .finally(() => {
                    closureTasksContainer.style.opacity = '1';
                    refreshClosureTasksBtn.disabled = false;
                });
        });
    }


    // --- LÓGICA DE FILTROS Y BÚSQUEDA PARA EL PANEL DE ADMINISTRADOR (EXISTENTE) ---
    // (Este bloque no tiene cambios, se mantiene como estaba)
    const adminDashboard = document.querySelector('.page-template-template-admin-dashboard');

    if (adminDashboard) {
        const searchFilter = document.getElementById('ghd-search-filter');
        const statusFilter = document.getElementById('ghd-status-filter');
        const priorityFilter = document.getElementById('ghd-priority-filter');
        const resetFiltersBtn = document.getElementById('ghd-reset-filters');
        const tableBody = document.getElementById('ghd-orders-table-body');

        const applyFilters = () => {
            if (!tableBody) return;
            
            tableBody.style.opacity = '0.5';
            
            const params = new URLSearchParams({ 
                action: 'ghd_filter_orders', 
                nonce: ghd_ajax.nonce, 
                search: searchFilter.value, 
                status: statusFilter.value, 
                priority: priorityFilter.value 
            });
            
            fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                .then(res => res.json())
                .then(data => { 
                    if (data.success) {
                        tableBody.innerHTML = data.data.html;
                    } else {
                        tableBody.innerHTML = '<tr><td colspan="9">Ocurrió un error al cargar los datos.</td></tr>';
                    }
                })
                .catch(error => {
                    console.error("Error en la petición AJAX:", error);
                    tableBody.innerHTML = '<tr><td colspan="9">Error de red. Inténtalo de nuevo.</td></tr>';
                })
                .finally(() => {
                    tableBody.style.opacity = '1';
                });
        };

        let searchTimeout;
        searchFilter.addEventListener('keyup', () => { 
            clearTimeout(searchTimeout); 
            searchTimeout = setTimeout(applyFilters, 500); 
        });
        
        statusFilter.addEventListener('change', applyFilters);
        priorityFilter.addEventListener('change', applyFilters);
        
        resetFiltersBtn.addEventListener('click', () => { 
            searchFilter.value = ''; 
            statusFilter.value = ''; 
            priorityFilter.value = ''; 
            applyFilters(); 
        });

        if (window.location.hash && window.location.hash.startsWith('#buscar=')) {
            let searchTerm = decodeURIComponent(window.location.hash.substring(8)).replace(/\+/g, ' ');
            searchFilter.value = searchTerm;
            applyFilters();
            history.pushState("", document.title, window.location.pathname + window.location.search);
        }
    }
<<<<<<< HEAD
}
// --- LÓGICA PARA LOS GRÁFICOS DE LA PÁGINA DE REPORTES ---
// Este bloque fue movido para estar dentro del DOMContentLoaded principal
if (typeof ghd_reports_data !== 'undefined' && document.querySelector('.ghd-reports-grid')) {
    
    // GRÁFICO 1: PEDIDOS POR ESTADO (BARRAS)
    const pedidosCtx = document.getElementById('pedidosPorEstadoChart');
    if (pedidosCtx) {
        new Chart(pedidosCtx, {
            type: 'bar',
            data: {
                labels: ghd_reports_data.sector.labels,
                datasets: [{
                    label: 'Pedidos Activos',
                    data: ghd_reports_data.sector.data,
                    backgroundColor: 'rgba(74, 124, 89, 0.7)' // Verde corporativo
                }]
            },
            options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
    }
    
    // GRÁFICO 2: CARGA POR SECTOR (DONA)
    const cargaCtx = document.getElementById('cargaPorSectorChart');
    if (cargaCtx) {
        new Chart(cargaCtx, {
            type: 'doughnut',
            data: {
                labels: ghd_reports_data.sector.labels,
                datasets: [{
                    data: ghd_reports_data.sector.data,
                    backgroundColor: ['#4A7C59', '#B34A49', '#F59E0B', '#6B7280', '#3E3E3E']
                }]
            }
        });
    }
    
    // GRÁFICO 3: PEDIDOS POR PRIORIDAD (POLAR)
    const prioridadCtx = document.getElementById('pedidosPorPrioridadChart');
    if (prioridadCtx) {
        new Chart(prioridadCtx, {
            type: 'polarArea',
            data: {
                labels: ghd_reports_data.prioridad.labels,
                datasets: [{
                    data: ghd_reports_data.prioridad.data,
                    backgroundColor: ['rgba(179, 74, 73, 0.7)', 'rgba(245, 158, 11, 0.7)', 'rgba(74, 124, 89, 0.7)']
                }]
            }
        });
    }
}


// --- LÓGICA DEL PANEL ADMINISTRATIVO (ARCHIVAR CON KPIs) ---
// Este bloque debe estar dentro del DOMContentLoaded principal y usar el selector correcto.
const adminPanel = document.querySelector('.is-admin-sector-panel'); // <--- SELECTOR CORREGIDO
if (adminPanel) {
    // Si la tabla usa .ghd-table tbody, o si usa .ghd-sector-tasks-list (para las tarjetas)
    const containerForEvents = adminPanel.querySelector('.ghd-table tbody') || adminPanel.querySelector('.ghd-sector-tasks-list'); 
    
    // Función para actualizar los KPIs en la UI
    const updateAdminKPIs = (kpiData) => {
        const activasEl = document.getElementById('kpi-activas');
        const prioridadEl = document.getElementById('kpi-prioridad-alta');
        const completadasHoyEl = document.getElementById('kpi-completadas-hoy'); 
        const tiempoEl = document.getElementById('kpi-tiempo-promedio');

        if (activasEl) activasEl.textContent = kpiData.total_pedidos;
        if (prioridadEl) prioridadEl.textContent = kpiData.total_prioridad_alta;
        if (completadasHoyEl) completadasHoyEl.textContent = kpiData.completadas_hoy;
        if (tiempoEl) tiempoEl.textContent = kpiData.tiempo_promedio_str;
    };

    if (containerForEvents) { 
        containerForEvents.addEventListener('click', function(e) {
            const archiveBtn = e.target.closest('.archive-order-btn');
            if (archiveBtn) {
                e.preventDefault();
                if (!confirm('¿Archivar este pedido? Esta acción es final.')) return;

                const orderId = archiveBtn.dataset.orderId;
                // El elemento a remover es la tarjeta completa en el ghd-sector-tasks-list
                const containerToRemove = archiveBtn.closest('.ghd-order-card');
                
                containerToRemove.style.opacity = '0.5';
                archiveBtn.disabled = true;
                archiveBtn.textContent = 'Archivando...';

                const params = new URLSearchParams({ action: 'ghd_archive_order', nonce: ghd_ajax.nonce, order_id: orderId });
                fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            containerToRemove.remove();
                            // Actualizamos los KPIs con los datos que nos devuelve el servidor
                            if (data.data.kpi_data) {
                                updateAdminKPIs(data.data.kpi_data);
                            }
                        } else {
                            alert('Error: ' + (data.data.message || 'No se pudo archivar.'));
                            containerToRemove.style.opacity = '1';
                            archiveBtn.disabled = false;
                            archiveBtn.textContent = 'Archivar Pedido';
                        }
                    })
                    .catch(error => { // Añadir manejo de errores de red
                        console.error("Error en la petición AJAX:", error);
                        alert('Error de red. No se pudo archivar el pedido.');
                        containerToRemove.style.opacity = '1';
                        archiveBtn.disabled = false;
                        archiveBtn.textContent = 'Archivar Pedido';
                    });
            }
        });
    }
}
}); // Cierre del document.addEventListener('DOMContentLoaded', function() original
=======

    // --- LÓGICA PARA LOS GRÁFICOS DE LA PÁGINA DE REPORTES (EXISTENTE) ---
    // (Este bloque no tiene cambios)
    if (typeof ghd_reports_data !== 'undefined' && document.querySelector('.ghd-reports-grid')) {
        const pedidosCtx = document.getElementById('pedidosPorEstadoChart');
        if (pedidosCtx) { new Chart(pedidosCtx, { type: 'bar', data: { labels: ghd_reports_data.sector.labels, datasets: [{ label: 'Pedidos Activos', data: ghd_reports_data.sector.data, backgroundColor: 'rgba(74, 124, 89, 0.7)' }] }, options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } } }); }
        const cargaCtx = document.getElementById('cargaPorSectorChart');
        if (cargaCtx) { new Chart(cargaCtx, { type: 'doughnut', data: { labels: ghd_reports_data.sector.labels, datasets: [{ data: ghd_reports_data.sector.data, backgroundColor: ['#4A7C59', '#B34A49', '#F59E0B', '#6B7280', '#3E3E3E'] }] } }); }
        const prioridadCtx = document.getElementById('pedidosPorPrioridadChart');
        if (prioridadCtx) { new Chart(prioridadCtx, { type: 'polarArea', data: { labels: ghd_reports_data.prioridad.labels, datasets: [{ data: ghd_reports_data.prioridad.data, backgroundColor: ['rgba(179, 74, 73, 0.7)', 'rgba(245, 158, 11, 0.7)', 'rgba(74, 124, 89, 0.7)'] }] } }); }
    }
>>>>>>> 2dac4e9 (Feat: completado del flujo de trabajo)

}); // Cierre del document.addEventListener('DOMContentLoaded', function() original

// LÓGICA PARA ACTIVAR EL FILTRO DESDE LA URL AL CARGAR LA PÁGINA (EXISTENTE)
// (Este bloque no tiene cambios, aunque applyFilters necesita estar en un scope accesible)
window.addEventListener('load', function() {
    const searchFilterInput = document.getElementById('ghd-search-filter');
    if (!searchFilterInput) return;

    const urlParams = new URLSearchParams(window.location.search);
    const searchTerm = urlParams.get('buscar');

    if (searchTerm) {
        searchFilterInput.value = searchTerm;
<<<<<<< HEAD
        // Para que applyFilters funcione aquí, debería estar definida en un scope más amplio o
        // se debe replicar la lógica. Para evitar duplicación, asumo que applyFilters puede ser llamada
        // si el adminDashboard (que la contiene) existe. Si no, necesitarías mover applyFilters a un scope global
        // o pasar los parámetros necesarios para re-ejecutar el filtro.
        // Por la estructura actual, la forma más simple es re-trigger el evento keyup o un "click" en un botón de filtro si existe.
        const event = new Event('keyup');
        searchFilterInput.dispatchEvent(event); // Esto simulará el keyup y disparará applyFilters vía setTimeout
=======
        const adminDashboardEl = document.querySelector('.page-template-template-admin-dashboard');
        if (adminDashboardEl) {
            const event = new Event('keyup');
            searchFilterInput.dispatchEvent(event);
        }
>>>>>>> 2dac4e9 (Feat: completado del flujo de trabajo)
        history.pushState("", document.title, window.location.pathname + window.location.search);
    }
});