document.addEventListener('DOMContentLoaded', function() {
    // Lógica del menú móvil (igual que antes)
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
        // Usamos delegación de eventos para manejar todos los clics dentro de la tabla
        adminTableBody.addEventListener('click', function(e) {
            
            // --- NUEVO: LÓGICA PARA EL BOTÓN "INICIAR PRODUCCIÓN" ---
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
                    action: 'ghd_admin_action',
                    nonce: ghd_ajax.nonce,
                    order_id: orderId,
                    type: 'start_production'
                });
                
                fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // Si tiene éxito, eliminamos la fila de la vista de pendientes
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

    const tasksList = document.querySelector('.ghd-sector-tasks-list');
    if (tasksList) {
        tasksList.addEventListener('click', function(e) {
            const actionButton = e.target.closest('.action-button');
            if (actionButton) {
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
                    .then(data => { if (data.success) card.remove(); else { card.style.opacity = '1'; alert('Error.'); } });
            }
        });
    }
});