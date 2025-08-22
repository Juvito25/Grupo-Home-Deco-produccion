document.addEventListener('DOMContentLoaded', function() {
    
    // --- Lógica del menú móvil (ya la teníamos) ---
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.querySelector('.ghd-sidebar');
    const overlay = document.createElement('div');
    overlay.classList.add('ghd-sidebar-overlay');

    if (menuToggle && sidebar) {
        // ... (el código del menú móvil que ya tenías sigue aquí) ...
        // No lo pego de nuevo para no alargar, pero debe estar aquí.
    }
    
    // --- NUEVA LÓGICA PARA LOS MENÚS DE ACCIONES DE LA TABLA ---
    const actionToggles = document.querySelectorAll('.actions-toggle');

    // Función para cerrar todos los menús
    function closeAllActionMenus() {
        document.querySelectorAll('.actions-menu.is-open').forEach(menu => {
            menu.classList.remove('is-open');
        });
    }

    // Abrir/cerrar el menú al hacer clic en el botón de "..."
    actionToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const currentMenu = this.nextElementSibling;
            const wasOpen = currentMenu.classList.contains('is-open');
            closeAllActionMenus(); // Primero cerramos todos
            if (!wasOpen) {
                currentMenu.classList.add('is-open'); // Abrimos el actual si estaba cerrado
            }
        });
    });

    // Cerrar los menús si se hace clic en cualquier otro lugar de la página
    document.addEventListener('click', closeAllActionMenus);


    // --- LÓGICA AJAX PARA ACTUALIZAR DATOS ---
    const actionLinks = document.querySelectorAll('.action-link');
    actionLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const orderId = this.closest('.actions-dropdown').querySelector('.actions-toggle').dataset.orderId;
            const action = this.dataset.action;
            const value = this.dataset.value;

            // Bloqueamos la UI para feedback visual
            const row = this.closest('tr');
            row.style.opacity = '0.5';

            fetch(ghd_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
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
                    // Actualizamos la UI con los nuevos datos
                    if (action === 'change_priority') {
                        const priorityCell = row.querySelector('td:nth-child(5) .ghd-tag');
                        priorityCell.textContent = value;
                        priorityCell.className = 'ghd-tag ' + data.data.new_class;
                    } else if (action === 'change_sector') {
                        const sectorCell = row.querySelector('td:nth-child(6)');
                        sectorCell.textContent = value;
                    }
                } else {
                    alert('Error: ' + data.data.message);
                }
            })
            .catch(error => console.error('Error:', error))
            .finally(() => {
                row.style.opacity = '1'; // Restauramos la UI
                closeAllActionMenus();
            });
        });
    });
});