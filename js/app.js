document.addEventListener('DOMContentLoaded', function() {
    
    // --- LÓGICA DEL MENÚ MÓVIL (PARA AMBOS PANELES) ---
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.querySelector('.ghd-sidebar');
    
    // Comprobamos que ambos elementos existan antes de añadir listeners
    if (menuToggle && sidebar) {
        const overlay = document.createElement('div');
        overlay.classList.add('ghd-sidebar-overlay');

        function openSidebar() {
            sidebar.classList.add('sidebar-visible');
            document.body.appendChild(overlay);
            setTimeout(() => overlay.classList.add('is-visible'), 10);
        }

        function closeSidebar() {
            sidebar.classList.remove('sidebar-visible');
            overlay.classList.remove('is-visible');
            setTimeout(() => {
                if (document.body.contains(overlay)) {
                    document.body.removeChild(overlay);
                }
            }, 300);
        }

        menuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.contains('sidebar-visible') ? closeSidebar() : openSidebar();
        });

        overlay.addEventListener('click', closeSidebar);
    }
    
    // --- LÓGICA PARA EL PANEL DE ADMINISTRADOR ---
    const adminActionLinks = document.querySelectorAll('.ghd-table .action-link');
    
    // Solo ejecutamos este bloque si estamos en el panel de admin
    if (adminActionLinks.length > 0) {
        const actionToggles = document.querySelectorAll('.actions-toggle');

        function closeAllActionMenus() {
            document.querySelectorAll('.actions-menu.is-open').forEach(menu => menu.classList.remove('is-open'));
        }

        actionToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                const currentMenu = this.nextElementSibling;
                const wasOpen = currentMenu.classList.contains('is-open');
                closeAllActionMenus();
                if (!wasOpen) currentMenu.classList.add('is-open');
            });
        });

        document.addEventListener('click', closeAllActionMenus);

        adminActionLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const orderId = this.closest('.actions-dropdown').querySelector('.actions-toggle').dataset.orderId;
                const action = this.dataset.action;
                const value = this.dataset.value;
                const row = this.closest('tr');
                row.style.opacity = '0.5';

                fetch(ghd_ajax.ajax_url, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'ghd_update_order',
                        nonce: ghd_ajax.nonce,
                        order_id: orderId,
                        field: action === 'change_priority' ? 'prioridad_pedido' : 'sector_actual',
                        value: value
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizamos la UI con los nuevos datos SIN eliminar la fila
                        if (action === 'change_priority') {
                            const priorityCell = row.querySelector('td:nth-child(6) .ghd-tag'); // Columna 6 ahora
                            priorityCell.textContent = value;
                            priorityCell.className = 'ghd-tag ' + data.data.new_class;
                        } else if (action === 'change_sector') {
                            const sectorCell = row.querySelector('td:nth-child(7)'); // Columna 7 ahora
                            sectorCell.textContent = value;
                            const stateCell = row.querySelector('td:nth-child(5) .ghd-tag'); // Columna 5 ahora
                            stateCell.textContent = value;
                            stateCell.className = 'ghd-tag tag-blue';
                        }
                    } else {
                        alert('Error: ' + (data.data.message || 'Ocurrió un error.'));
                    }
                }) 
                .catch(error => console.error('Error:', error))
                .finally(closeAllActionMenus);
            });
        });
    }

    // --- LÓGICA PARA EL PANEL DE SECTOR ---
    const moveButtons = document.querySelectorAll('.move-to-next-sector-btn');
    
    // Solo ejecutamos este bloque si estamos en el panel de sector
    if (moveButtons.length > 0) {
        moveButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                if (!confirm('¿Mover este pedido al siguiente sector?')) return;

                const orderId = this.dataset.orderId;
                const nonce = this.dataset.nonce;
                const card = this.closest('.ghd-task-card');

                card.style.opacity = '0.5';
                this.textContent = 'Moviendo...';
                this.disabled = true;

                fetch(ghd_ajax.ajax_url, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'ghd_move_to_next_sector',
                        nonce: nonce,
                        order_id: orderId,
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';
                        setTimeout(() => card.remove(), 500);
                    } else {
                        alert('Error: ' + (data.data.message || 'Ocurrió un error.'));
                        card.style.opacity = '1';
                        this.textContent = 'Mover a Siguiente Sector';
                        this.disabled = false;
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });
    }
});

// --- LÓGICA DE FILTROS PARA EL PANEL DE ADMIN ---
const searchFilter = document.getElementById('ghd-search-filter');
const statusFilter = document.getElementById('ghd-status-filter');
const priorityFilter = document.getElementById('ghd-priority-filter');
const resetFiltersBtn = document.getElementById('ghd-reset-filters');
const tableBody = document.querySelector('.ghd-table tbody');

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
    searchFilter.addEventListener('keyup', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    priorityFilter.addEventListener('change', applyFilters);
    resetFiltersBtn.addEventListener('click', () => {
        searchFilter.value = '';
        statusFilter.value = '';
        priorityFilter.value = '';
        applyFilters();
    });
}