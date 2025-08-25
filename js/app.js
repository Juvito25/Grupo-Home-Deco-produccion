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

    // --- LÓGICA DEL PANEL DE ADMINISTRADOR (Corregida y Robusta) ---
    const adminTableBody = document.querySelector('.ghd-table tbody');
    if (adminTableBody) {
        adminTableBody.addEventListener('click', function(e) {
            const toggle = e.target.closest('.actions-toggle');
            if (toggle) {
                e.stopPropagation();
                const menu = toggle.nextElementSibling;
                const wasOpen = menu.classList.contains('is-open');
                document.querySelectorAll('.actions-menu.is-open').forEach(m => m.classList.remove('is-open'));
                if (!wasOpen) menu.classList.add('is-open');
            }

            const actionLink = e.target.closest('.action-link');
            if (actionLink) {
                e.preventDefault();
                const row = actionLink.closest('tr');
                row.style.opacity = '0.5';
                const params = new URLSearchParams({
                    action: 'ghd_update_order', nonce: ghd_ajax.nonce, order_id: actionLink.closest('.actions-dropdown').querySelector('.actions-toggle').dataset.orderId,
                    field: actionLink.dataset.action === 'change_priority' ? 'prioridad_pedido' : 'sector_actual',
                    value: actionLink.dataset.value
                });
                fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            row.outerHTML = data.data.html;
                        } else {
                            alert('Error al actualizar.');
                        }
                    })
                    .catch(error => console.error('Error:', error))
                    .finally(() => {
                        // CORRECCIÓN CLAVE: Siempre restauramos la opacidad.
                        if (row) { // Comprobamos si la fila todavía existe
                           row.style.opacity = '1';
                        }
                    });
            }
        });
        document.addEventListener('click', () => document.querySelectorAll('.actions-menu.is-open').forEach(m => m.classList.remove('is-open')));
    }

    // --- LÓGICA DEL PANEL DE SECTOR (Estable) ---
    const sectorGrid = document.querySelector('.ghd-sector-tasks-grid');
    if (sectorGrid) {
        // ... (código del panel de sector que ya funcionaba) ...
    }
    
    // --- LÓGICA PARA EL BOTÓN DE REFRESCAR (Estable) ---
    const refreshBtn = document.getElementById('ghd-refresh-tasks');
    if (refreshBtn && sectorGrid) {
        refreshBtn.addEventListener('click', function() {
            const icon = this.querySelector('i');
            if (icon) icon.classList.add('fa-spin');
            sectorGrid.style.opacity = '0.5';
            const params = new URLSearchParams({ action: 'ghd_refresh_tasks', nonce: ghd_ajax.nonce });
            fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                .then(res => res.json())
                .then(data => { if (data.success) sectorGrid.innerHTML = data.data.html; })
                .finally(() => {
                    if (icon) icon.classList.remove('fa-spin');
                    sectorGrid.style.opacity = '1';
                });
        });
    }
});

// --- LÓGICA PARA EL BOTÓN "MOVER A SIGUIENTE SECTOR" (UNIVERSAL) ---
// Esta lógica ahora funcionará tanto en el panel de sector como en la página de detalles.
const mainContent = document.querySelector('.ghd-main-content');

if (mainContent) {
    mainContent.addEventListener('click', function(e) {
        const moveBtn = e.target.closest('.move-to-next-sector-btn');
        
        if (moveBtn) {
            e.preventDefault();
            
            if (!confirm('¿Estás seguro de que quieres mover este pedido al siguiente sector?')) {
                return;
            }

            const orderId = moveBtn.dataset.orderId;
            const nonce = moveBtn.dataset.nonce;
            const card = moveBtn.closest('.ghd-task-card') || moveBtn.closest('.ghd-card'); // Busca el contenedor padre

            // Feedback visual
            if (card) card.style.opacity = '0.5';
            moveBtn.disabled = true;
            moveBtn.textContent = 'Moviendo...';

            const params = new URLSearchParams({
                action: 'ghd_move_to_next_sector',
                nonce: nonce,
                order_id: orderId,
            });

            fetch(ghd_ajax.ajax_url, {
                method: 'POST',
                body: params
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Si tiene éxito, redirigimos o eliminamos la tarjeta
                    if (card && card.classList.contains('ghd-task-card')) {
                        card.remove(); // Si estamos en el panel de sector, eliminamos la tarjeta
                    } else {
                        alert('¡Pedido movido con éxito!');
                        location.reload(); // Si estamos en la página de detalles, la recargamos
                    }
                } else {
                    alert('Error: ' + (data.data.message || 'No se pudo mover el pedido.'));
                    if (card) card.style.opacity = '1';
                    moveBtn.disabled = false;
                    // (Aquí podrías restaurar el texto original del botón si quisieras)
                }
            })
            .catch(error => {
                console.error('Error de red:', error);
                alert('Ocurrió un error de red. Inténtalo de nuevo.');
                if (card) card.style.opacity = '1';
                moveBtn.disabled = false;
            });
        }
    });
}

// --- LÓGICA DE FILTROS PARA EL PANEL DE ADMIN ---
const searchFilter = document.getElementById('ghd-search-filter');
const statusFilter = document.getElementById('ghd-status-filter');
const priorityFilter = document.getElementById('ghd-priority-filter');
const resetFiltersBtn = document.getElementById('ghd-reset-filters');
const tableBody = document.getElementById('ghd-orders-table-body');

function applyFilters() {
    if (!tableBody) return; // Salir si no estamos en la página del admin
    
    tableBody.style.opacity = '0.5';

    const params = new URLSearchParams({
        action: 'ghd_filter_orders',
        nonce: ghd_ajax.nonce,
        search: searchFilter.value,
        status: statusFilter.value,
        priority: priorityFilter.value,
    });

    fetch(ghd_ajax.ajax_url, {
        method: 'POST',
        body: params
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            tableBody.innerHTML = data.data.html;
        }
    })
    .finally(() => {
        tableBody.style.opacity = '1';
    });
}

if (searchFilter) { // Si los filtros existen, añadimos los listeners
    let searchTimeout;
    searchFilter.addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500); // Espera 500ms después de teclear
    });
    
    statusFilter.addEventListener('change', applyFilters);
    priorityFilter.addEventListener('change', applyFilters);
    
    resetFiltersBtn.addEventListener('click', () => {
        searchFilter.value = '';
        statusFilter.value = '';
        priorityFilter.value = '';
        applyFilters();
    });
}