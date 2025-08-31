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

    // --- Funciones para actualizar los KPIs ---
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

    const updateAdminProductionKPIs = (kpiData) => {
        const activasEl = document.getElementById('kpi-produccion-activas');
        const prioridadEl = document.getElementById('kpi-produccion-prioridad-alta');
        const completadasHoyEl = document.getElementById('kpi-produccion-completadas-hoy');
        const tiempoEl = document.getElementById('kpi-produccion-tiempo-promedio');

        if (activasEl) activasEl.textContent = kpiData.total_pedidos_produccion;
        if (prioridadEl) prioridadEl.textContent = kpiData.total_prioridad_alta_produccion;
        if (completadasHoyEl) completadasHoyEl.textContent = kpiData.completadas_hoy_produccion;
        if (tiempoEl) tiempoEl.textContent = kpiData.tiempo_promedio_str_produccion;
    };


    // --- LÓGICA UNIVERSAL DE CLICS EN EL CONTENIDO PRINCIPAL ---
    const mainContent = document.querySelector('.ghd-main-content');
    if (mainContent) {
        mainContent.addEventListener('click', function(e) {
            
            // Lógica para el botón "Archivar Pedido" (versión NO-ADMIN-DASHBOARD)
            const archiveBtnGeneral = e.target.closest('.archive-order-btn');
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
                            if (data.data.kpi_data) {
                                updateSectorKPIs(data.data.kpi_data);
                            }
                            if (document.body.classList.contains('is-admin-dashboard-panel')) {
                                const refreshProdBtn = document.getElementById('ghd-refresh-production-tasks');
                                if (refreshProdBtn) { refreshProdBtn.click(); }
                                const refreshClosureBtn = document.getElementById('ghd-refresh-closure-tasks');
                                if (refreshClosureBtn) { refreshClosureBtn.click(); }
                            }
                        } else { 
                            alert('Error al actualizar: ' + (data.data?.message || '')); 
                        }
                    })
                    .finally(() => {
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
                            const refreshProdBtn = document.getElementById('ghd-refresh-production-tasks');
                            if (refreshProdBtn) { refreshProdBtn.click(); }
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
                            const refreshArchivedBtn = document.getElementById('ghd-refresh-archived-orders'); 
                            if (refreshArchivedBtn) { refreshArchivedBtn.click(); }
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

    // --- LÓGICA PARA EL BOTÓN "REFRESCAR" EN LA SECCIÓN DE PEDIDOS EN PRODUCCIÓN DEL ADMIN PRINCIPAL ---
    const refreshProductionTasksBtn = document.getElementById('ghd-refresh-production-tasks');
    if (refreshProductionTasksBtn && document.body.classList.contains('is-admin-dashboard-panel')) {
        refreshProductionTasksBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const productionTasksContainer = document.getElementById('admin-production-tasks-container');
            const productionTableBody = document.getElementById('ghd-production-table-body');

            if (!productionTasksContainer || !productionTableBody) {
                console.error("No se encontró el contenedor de tareas en producción.");
                return;
            }
            
            productionTasksContainer.style.opacity = '0.5';
            refreshProductionTasksBtn.disabled = true;

            const params = new URLSearchParams({
                action: 'ghd_refresh_production_tasks',
                nonce: ghd_ajax.nonce
            });

            fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        productionTableBody.innerHTML = data.data.tasks_html;
                        if (data.data.kpi_data) {
                            updateAdminProductionKPIs(data.data.kpi_data);
                        }
                    } else {
                        alert('Error al refrescar pedidos en producción: ' + (data.data?.message || ''));
                        productionTableBody.innerHTML = '<tr><td colspan="9" style="text-align:center;">Error al cargar pedidos en producción.</td></tr>'; // Colspan ajustado
                    }
                })
                .catch(error => {
                    console.error("Error en la petición AJAX de refresco de producción:", error);
                    alert('Error de red al refrescar pedidos en producción.');
                    productionTableBody.innerHTML = '<tr><td colspan="9" style="text-align:center;">Error de red. Inténtalo de nuevo.</td></tr>'; // Colspan ajustado
                })
                .finally(() => {
                    productionTasksContainer.style.opacity = '1';
                    refreshProductionTasksBtn.disabled = false;
                    // --- Refrescar también Pedidos Pendientes de Cierre ---
                    const refreshClosureBtn = document.getElementById('ghd-refresh-closure-tasks');
                    if (refreshClosureBtn) { refreshClosureBtn.click(); }
                });
        });
    }

    // --- LÓGICA PARA EL BOTÓN "REFRESCAR" EN LA PÁGINA DE PEDIDOS ARCHIVADOS ---
    const refreshArchivedOrdersBtn = document.getElementById('ghd-refresh-archived-orders');
    if (refreshArchivedOrdersBtn) { // <-- Condición simplificada
        refreshArchivedOrdersBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const archivedOrdersTableBody = document.getElementById('ghd-archived-orders-table-body');

            if (!archivedOrdersTableBody) {
                console.error("No se encontró el cuerpo de la tabla de pedidos archivados.");
                return;
            }
            
            archivedOrdersTableBody.style.opacity = '0.5';
            refreshArchivedOrdersBtn.disabled = true;

            const params = new URLSearchParams({
                action: 'ghd_refresh_archived_orders',
                nonce: ghd_ajax.nonce
            });

            fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        archivedOrdersTableBody.innerHTML = data.data.table_html;
                    } else {
                        alert('Error al refrescar pedidos archivados: ' + (data.data?.message || ''));
                        archivedOrdersTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Error al cargar pedidos archivados.</td></tr>';
                    }
                })
                .catch(error => {
                    console.error("Error en la petición AJAX de refresco de archivados:", error);
                    alert('Error de red al refrescar pedidos archivados.');
                    archivedOrdersTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Error de red. Inténtalo de nuevo.</td></tr>';
                })
                .finally(() => {
                    archivedOrdersTableBody.style.opacity = '1';
                    refreshArchivedOrdersBtn.disabled = false;
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
                        alert('Error al refrescar tareas: ' + (data.data?.message || ''));
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
    if (typeof ghd_reports_data !== 'undefined' && document.querySelector('.ghd-reports-grid')) {
        console.log("Datos de Reportes Recibidos:", ghd_reports_data)
        const pedidosCtx = document.getElementById('pedidosPorEstadoChart');
        if (pedidosCtx) {
            new Chart(pedidosCtx, {
                type: 'bar',
                data: {
                    labels: ghd_reports_data.pedidos_por_estado.labels,
                    datasets: [{
                        label: 'Pedidos por Estado',
                        data: ghd_reports_data.pedidos_por_estado.data,
                        backgroundColor: ghd_reports_data.pedidos_por_estado.labels.map(label => 
                            ghd_reports_data.pedidos_por_estado.backgroundColors[label] || 'rgba(74, 124, 89, 0.7)'
                        ),
                        borderColor: 'rgba(255, 255, 255, 0.8)',
                        borderWidth: 1
                    }]
                },
                options: { 
                    responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                    plugins: { legend: { display: false }, title: { display: false } }
                }
            });
        }
        const cargaCtx = document.getElementById('cargaPorSectorChart');
        if (cargaCtx) {
            new Chart(cargaCtx, {
                type: 'doughnut',
                data: {
                    labels: ghd_reports_data.carga_por_sector.labels,
                    datasets: [{
                        data: ghd_reports_data.carga_por_sector.data,
                        backgroundColor: ghd_reports_data.carga_por_sector.backgroundColors || ['#4A7C59', '#B34A49', '#F59E0B', '#6B7280', '#3E3E3E'],
                        borderColor: '#fff', borderWidth: 2
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' }, title: { display: false } } }
            });
        }
        const prioridadCtx = document.getElementById('pedidosPorPrioridadChart');
        if (prioridadCtx) {
            new Chart(prioridadCtx, {
                type: 'polarArea',
                data: {
                    labels: ghd_reports_data.pedidos_por_prioridad.labels,
                    datasets: [{
                        data: ghd_reports_data.pedidos_por_prioridad.data,
                        backgroundColor: ghd_reports_data.pedidos_por_prioridad.labels.map(label => 
                            ghd_reports_data.pedidos_por_prioridad.backgroundColors[label] || 'rgba(179, 74, 73, 0.7)'
                        ),
                        borderColor: '#fff', borderWidth: 2
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' }, title: { display: false } },
                    scale: { ticks: { beginAtZero: true, stepSize: 1 } }
                }
            });
        }
    }
}); // Cierre ÚNICO y CORRECTO del document.addEventListener('DOMContentLoaded', function() principal

// LÓGICA PARA ACTIVAR EL FILTRO DESDE LA URL AL CARGAR LA PÁGINA (EXISTENTE)
// Este listener de window.load queda como un bloque independiente al final del archivo.
// Se ha mantenido aquí por compatibilidad, aunque su funcionalidad de applyFilters ya está en DOMContentLoaded.
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