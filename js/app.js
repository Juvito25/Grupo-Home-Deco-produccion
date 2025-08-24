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

// --- LÓGICA PARA EL BOTÓN "MOVER A SIGUIENTE SECTOR" (PANEL SECTOR) ---
const sectorGrid = document.querySelector('.ghd-sector-tasks-grid');

// Usamos delegación de eventos en el contenedor por si la lista se refresca con AJAX.
if (sectorGrid) {
    sectorGrid.addEventListener('click', function(e) {
        const moveBtn = e.target.closest('.move-to-next-sector-btn');
        
        if (moveBtn) {
            e.preventDefault();
            
            if (!confirm('¿Estás seguro de que quieres mover este pedido al siguiente sector?')) {
                return;
            }

            const orderId = moveBtn.dataset.orderId;
            const nonce = moveBtn.dataset.nonce;
            const card = moveBtn.closest('.ghd-task-card');

            // Feedback visual inmediato
            card.style.opacity = '0.5';
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
                    // Si tiene éxito, eliminamos la tarjeta con una animación
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        card.remove();
                    }, 500);
                } else {
                    // Si falla, mostramos un error y restauramos la tarjeta
                    alert('Error: ' + (data.data.message || 'No se pudo mover el pedido.'));
                    card.style.opacity = '1';
                    moveBtn.disabled = false;
                    moveBtn.textContent = 'Mover a Siguiente Sector';
                }
            })
            .catch(error => {
                console.error('Error de red:', error);
                alert('Ocurrió un error de red. Inténtalo de nuevo.');
                card.style.opacity = '1';
                moveBtn.disabled = false;
                moveBtn.textContent = 'Mover a Siguiente Sector';
            });
        }
    });
}