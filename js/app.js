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

    // --- LÓGICA UNIVERSAL DE CLICS EN EL CONTENIDO PRINCIPAL ---
    const mainContent = document.querySelector('.ghd-main-content');
    if (mainContent) {
        mainContent.addEventListener('click', function(e) {
            
            // Lógica para el botón "Archivar Pedido"
            // Esta lógica DEBE evitar ser el controlador principal del panel administrativo
            // ya que el panel administrativo tiene su propia lógica de archivo con actualización de KPIs.
            // Usamos la nueva clase 'is-admin-sector-panel' para diferenciarlo.
            const archiveBtn = e.target.closest('.archive-order-btn');
            if (archiveBtn && !document.body.classList.contains('is-admin-sector-panel')) { 
                e.preventDefault();
                if (!confirm('¿Archivar este pedido? Esta acción es final.')) return;

                const orderId = archiveBtn.dataset.orderId;
                const container = archiveBtn.closest('tr') || archiveBtn.closest('.ghd-order-card');
                
                container.style.opacity = '0.5';
                archiveBtn.disabled = true;
                archiveBtn.textContent = 'Archivando...';

                const params = new URLSearchParams({ action: 'ghd_archive_order', nonce: ghd_ajax.nonce, order_id: orderId });
                fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            container.remove();
                            // Aquí no actualizamos KPIs porque este bloque es para paneles de sector no administrativos
                            // o si el admin ve otro sector, los KPIs que se ven no son los de su propio panel.
                        } else {
                            alert('Error: ' + (data.data?.message || 'No se pudo archivar.'));
                            container.style.opacity = '1';
                            archiveBtn.disabled = false;
                            archiveBtn.textContent = 'Archivar Pedido';
                        }
                    });
            }

            // Lógica para los botones de estado del panel de sector
            const actionButton = e.target.closest('.action-button');
            if (actionButton) {
                e.preventDefault();
                const card = actionButton.closest('.ghd-order-card');
                card.style.opacity = '0.5';
                const params = new URLSearchParams({ action: 'ghd_update_task_status', nonce: ghd_ajax.nonce, order_id: actionButton.dataset.orderId, field: actionButton.dataset.field, value: actionButton.dataset.value });
                fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            if (actionButton.dataset.value === 'Completado') { card.remove(); } 
                            else { card.outerHTML = data.data.html; }
                        } else { alert('Error al actualizar.'); }
                    })
                    .finally(() => {
                        const finalCard = document.getElementById(card.id);
                        if (finalCard) { finalCard.style.opacity = '1'; }
                    });
            }
        });
    }

// --- LÓGICA DE FILTROS Y BÚSQUEDA PARA EL PANEL DE ADMINISTRADOR ---
const adminDashboard = document.querySelector('.page-template-template-admin-dashboard');

if (adminDashboard) {
    const searchFilter = document.getElementById('ghd-search-filter');
    const statusFilter = document.getElementById('ghd-status-filter');
    const priorityFilter = document.getElementById('ghd-priority-filter');
    const resetFiltersBtn = document.getElementById('ghd-reset-filters');
    const tableBody = document.getElementById('ghd-orders-table-body'); // Usamos el ID para más seguridad

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

    // Lógica para leer el filtro desde la URL al cargar la página
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

// LÓGICA PARA ACTIVAR EL FILTRO DESDE LA URL AL CARGAR LA PÁGINA
window.addEventListener('load', function() {
    const searchFilterInput = document.getElementById('ghd-search-filter');
    if (!searchFilterInput) return; // Solo se ejecuta en el panel de admin

    const urlParams = new URLSearchParams(window.location.search);
    const searchTerm = urlParams.get('buscar');

    if (searchTerm) {
        searchFilterInput.value = searchTerm;
        // Para que applyFilters funcione aquí, debería estar definida en un scope más amplio o
        // se debe replicar la lógica. Para evitar duplicación, asumo que applyFilters puede ser llamada
        // si el adminDashboard (que la contiene) existe. Si no, necesitarías mover applyFilters a un scope global
        // o pasar los parámetros necesarios para re-ejecutar el filtro.
        // Por la estructura actual, la forma más simple es re-trigger el evento keyup o un "click" en un botón de filtro si existe.
        const event = new Event('keyup');
        searchFilterInput.dispatchEvent(event); // Esto simulará el keyup y disparará applyFilters vía setTimeout
        history.pushState("", document.title, window.location.pathname + window.location.search);
    }
});