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

    // --- LÓGICA DEL PANEL DE SECTOR ---
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
                            if (actionButton.dataset.value === 'Completado') {
                                card.remove();
                            } else {
                                card.outerHTML = data.data.html;
                            }
                        } else {
                            alert('Error al actualizar el estado.');
                        }
                    })
                    .catch(error => console.error('Error:', error))
                    .finally(() => {
                        const finalCard = document.getElementById(card.id);
                        if (finalCard) {
                            finalCard.style.opacity = '1';
                        }
                    });
            }
        });
    }

    // --- BLOQUE AÑADIDO: LÓGICA DE FILTROS (PANEL ADMIN) ---
    const searchFilter = document.getElementById('ghd-search-filter');
    if (searchFilter) {
        const statusFilter = document.getElementById('ghd-status-filter');
        const priorityFilter = document.getElementById('ghd-priority-filter');
        const resetFiltersBtn = document.getElementById('ghd-reset-filters');
        
        const applyFilters = () => {
            if (!adminTableBody) return;
            adminTableBody.style.opacity = '0.5';
            const params = new URLSearchParams({ 
                action: 'ghd_filter_orders', 
                nonce: ghd_ajax.nonce, 
                search: searchFilter.value, 
                status: statusFilter.value, 
                priority: priorityFilter.value 
            });
            fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                .then(res => res.json())
                .then(data => { if (data.success) adminTableBody.innerHTML = data.data.html; })
                .finally(() => adminTableBody.style.opacity = '1');
        };

        let searchTimeout;
        searchFilter.addEventListener('keyup', () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(applyFilters, 500); });
        statusFilter.addEventListener('change', applyFilters);
        priorityFilter.addEventListener('change', applyFilters);
        resetFiltersBtn.addEventListener('click', () => { 
            searchFilter.value = ''; statusFilter.value = ''; priorityFilter.value = ''; 
            applyFilters(); 
        });
    }

    // --- BLOQUE AÑADIDO: LÓGICA PARA LEER EL FILTRO DESDE LA URL ---
    window.addEventListener('load', function() {
        const searchFilterInput = document.getElementById('ghd-search-filter');
        if (!searchFilterInput) return;

        if (window.location.hash && window.location.hash.startsWith('#buscar=')) {
            let searchTerm = decodeURIComponent(window.location.hash.substring(8));
            searchTerm = searchTerm.replace(/\+/g, ' ');

            searchFilterInput.value = searchTerm;
            
            // Reutilizamos la función que ya existe para aplicar los filtros
            if (typeof applyFilters === 'function') {
                applyFilters();
            }

            history.pushState("", document.title, window.location.pathname + window.location.search);
        }
    });

});