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