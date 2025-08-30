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
            
            // Lógica para el botón "Archivar Pedido" (versión NO-ADMIN-DASHBOARD)
            // Esta lógica se aplica a botones .archive-order-btn que NO están en el is-admin-dashboard-panel
            // (ej., si por alguna razón un admin ve un sector ajeno y archiva desde allí, aunque ya no debería haber botón)
            const archiveBtnGeneral = e.target.closest('.archive-order-btn');
            // La condición se asegura de que no se ejecute si estamos en el panel principal del Admin
            if (archiveBtnGeneral && !document.body.classList.contains('is-admin-dashboard-panel')) { 
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
            const mainContentEl = document.querySelector('.ghd-main-content');
            const campoEstado = mainContentEl ? mainContentEl.dataset.campoEstado : '';

            if (!sectorTasksList || !campoEstado) {
                console.error("No se encontró la lista de tareas del sector o el campo_estado.");
                return;
            }

            sectorTasksList.style.opacity = '0.5';
            refreshTasksBtn.disabled = true;

            const params = new URLSearchParams({
                action: 'ghd_refresh_sector_tasks',
                nonce: ghd_ajax.nonce,
                campo_estado: campoEstado
            });

            fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        sectorTasksList.innerHTML = data.data.tasks_html;
                        if (data.data.kpi_data) {
                            updateSectorKPIs(data.data.kpi_data);
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
            
            closureTasksContainer.style.opacity = '0.5';
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
                            updateAdminClosureKPIs(data.data.kpi_data);
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
}); // Cierre del document.addEventListener('DOMContentLoaded', function() original

// LÓGICA PARA ACTIVAR EL FILTRO DESDE LA URL AL CARGAR LA PÁGINA (EXISTENTE)
window.addEventListener('load', function() {
    const searchFilterInput = document.getElementById('ghd-search-filter');
    if (!searchFilterInput) return;

    const urlParams = new URLSearchParams(window.location.search);
    const searchTerm = urlParams.get('buscar');

    if (searchTerm) {
        searchFilterInput.value = searchTerm;
        const adminDashboardEl = document.querySelector('.page-template-template-admin-dashboard');
        if (adminDashboardEl) {
            const event = new Event('keyup');
            searchFilterInput.dispatchEvent(event);
        }
        history.pushState("", document.title, window.location.pathname + window.location.search);
    }
});