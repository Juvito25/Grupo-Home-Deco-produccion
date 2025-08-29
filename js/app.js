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

    // --- LÓGICA DEL PANEL DE ADMINISTRADOR ---
    const adminTableBody = document.querySelector('.ghd-table tbody');
    if (adminTableBody) {
        adminTableBody.addEventListener('click', function(e) {
            const startBtn = e.target.closest('.start-production-btn');
            if (startBtn) {
                e.preventDefault();
                if (!confirm('¿Iniciar la producción de este pedido?')) return;
                
                const row = startBtn.closest('tr');
                const orderId = startBtn.dataset.orderId;
                
                row.style.opacity = '0.5';
                startBtn.disabled = true;
                startBtn.textContent = 'Iniciando...';
                
                const params = new URLSearchParams({
                    action: 'ghd_admin_action', nonce: ghd_ajax.nonce,
                    order_id: orderId, type: 'start_production'
                });
                
                fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            row.remove();
                        } else {
                            alert('Error: ' + (data.data?.message || 'No se pudo iniciar la producción.'));
                            row.style.opacity = '1';
                            startBtn.disabled = false;
                            startBtn.textContent = 'Iniciar Producción';
                        }
                    });
            }
        });
    }

// --- REEMPLAZA ESTE BLOQUE COMPLETO EN js/app.js ---

// --- LÓGICA DEL PANEL DE SECTOR (CON ACTUALIZACIÓN DE UI EN EL CLIENTE) ---
const tasksList = document.querySelector('.ghd-sector-tasks-list');
if (tasksList) {
    tasksList.addEventListener('click', function(e) {
        const actionButton = e.target.closest('.action-button');
        if (actionButton) {
            e.preventDefault();
            
            const card = actionButton.closest('.ghd-order-card');
            card.style.opacity = '0.5';
            
            const params = new URLSearchParams({
                action: 'ghd_update_task_status', nonce: ghd_ajax.nonce,
                order_id: actionButton.dataset.orderId,
                field: actionButton.dataset.field,
                value: actionButton.dataset.value
            });

            fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Si la acción fue 'Completado', eliminamos la tarjeta.
                        if (actionButton.dataset.value === 'Completado') {
                            card.remove();
                        } 
                        // SI LA ACCIÓN FUE 'EN PROGRESO', ACTUALIZAMOS LA TARJETA MANUALMENTE.
                        else if (actionButton.dataset.value === 'En Progreso') {
                            // 1. Cambiamos el botón
                            actionButton.textContent = 'Marcar Completa';
                            actionButton.dataset.value = 'Completado';

                            // 2. Añadimos la etiqueta "En Progreso"
                            const tagsContainer = card.querySelector('.order-tags');
                            if (tagsContainer) {
                                const newTag = document.createElement('span');
                                newTag.className = 'ghd-tag tag-blue';
                                newTag.textContent = 'En Progreso';
                                tagsContainer.appendChild(newTag);
                            }
                        }
                    } else {
                        alert('Error al actualizar el estado.');
                    }
                })
                .catch(error => console.error('Error:', error))
                .finally(() => {
                    // Nos aseguramos de que la tarjeta vuelva a ser visible si no se eliminó.
                    if (document.body.contains(card)) {
                        card.style.opacity = '1';
                    }
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
document.addEventListener('DOMContentLoaded', function() {
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
})
});

// --- AÑADE ESTE BLOQUE COMPLETO AL FINAL DE js/app.js ---

// LÓGICA PARA ACTIVAR EL FILTRO DESDE LA URL AL CARGAR LA PÁGINA
window.addEventListener('load', function() {
    const searchFilterInput = document.getElementById('ghd-search-filter');
    if (!searchFilterInput) return; // Solo se ejecuta en el panel de admin

    // Usamos URLSearchParams para leer los parámetros de la URL (ej: ?buscar=Jorge+Garcia)
    const urlParams = new URLSearchParams(window.location.search);
    const searchTerm = urlParams.get('buscar');

    // Si encontramos el parámetro 'buscar' en la URL...
    if (searchTerm) {
        // ...lo ponemos en el campo de búsqueda...
        searchFilterInput.value = searchTerm;
        
        // ...y disparamos la función de filtrado que ya existe.
        if (typeof applyFilters === 'function') {
            applyFilters();
        }
    }
});

// --- LÓGICA PARA EL BOTÓN "ARCHIVAR PEDIDO" (PANEL ADMINISTRATIVO) ---
const adminPanel = document.querySelector('.page-template-template-administrativo');

if (adminPanel) {
    const tableBody = adminPanel.querySelector('.ghd-table tbody');
    
    tableBody.addEventListener('click', function(e) {
        const archiveBtn = e.target.closest('.archive-order-btn');
        
        if (archiveBtn) {
            e.preventDefault();
            
            if (!confirm('¿Estás seguro de que quieres archivar este pedido? Esta acción es final.')) {
                return;
            }

            const orderId = archiveBtn.dataset.orderId;
            const row = archiveBtn.closest('tr');

            // Feedback visual
            row.style.opacity = '0.5';
            archiveBtn.disabled = true;
            archiveBtn.textContent = 'Archivando...';

            const params = new URLSearchParams({
                action: 'ghd_archive_order',
                nonce: ghd_ajax.nonce,
                order_id: orderId,
            });

            fetch(ghd_ajax.ajax_url, {
                method: 'POST',
                body: params
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Si tiene éxito, eliminamos la fila de la vista
                    row.remove();
                } else {
                    alert('Error: ' + (data.data.message || 'No se pudo archivar el pedido.'));
                    row.style.opacity = '1';
                    archiveBtn.disabled = false;
                    archiveBtn.textContent = 'Archivar Pedido';
                }
            })
            .catch(error => {
                console.error('Error de red:', error);
                alert('Ocurrió un error de red.');
                row.style.opacity = '1';
                archiveBtn.disabled = false;
                archiveBtn.textContent = 'Archivar Pedido';
            });
        }
    });
}