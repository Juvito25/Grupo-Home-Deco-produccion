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

    // --- LÓGICA UNIVERSAL DE EVENTOS EN EL CONTENIDO PRINCIPAL ---
    const mainContent = document.querySelector('.ghd-main-content');
    if (mainContent) {

        // Listener para todos los CLICKS dentro de mainContent
        mainContent.addEventListener('click', function(e) {
            
            const target = e.target;
            const actionButton = target.closest('.action-button');
            const openModalButton = target.closest('.open-complete-task-modal');
            const closeModalButton = target.closest('.close-button');
            const refreshTasksBtn = target.closest('#ghd-refresh-tasks');
            const archiveBtn = target.closest('.archive-order-btn'); // Unificado aquí

            // Lógica para el botón "Refrescar" en el panel de sector
            if (refreshTasksBtn) {
                e.preventDefault();
                const sectorTasksList = document.querySelector('.ghd-sector-tasks-list');
                const campoEstado = mainContent.dataset.campoEstado || '';
                if (!sectorTasksList || !campoEstado) return;

                sectorTasksList.style.opacity = '0.5';
                refreshTasksBtn.disabled = true;

                const params = new URLSearchParams({ action: 'ghd_refresh_sector_tasks', nonce: ghd_ajax.nonce, campo_estado: campoEstado });

                fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.data) {
                            sectorTasksList.innerHTML = data.data.tasks_html;
                            if (data.data.kpi_data) updateSectorKPIs(data.data.kpi_data);
                        }
                    })
                    .finally(() => {
                        sectorTasksList.style.opacity = '1';
                        refreshTasksBtn.disabled = false;
                    });
            }
            
            // Lógica para el botón "Iniciar Tarea" en el Panel de Sector
            if (actionButton && !openModalButton) {
                e.preventDefault();
                const card = actionButton.closest('.ghd-order-card');
                const assigneeSelector = card.querySelector('.ghd-assignee-selector');
                const assigneeId = assigneeSelector ? assigneeSelector.value : '0';
                
                if (actionButton.dataset.value === 'En Progreso' && assigneeId === '0') {
                    alert('Por favor, asigna un operario antes de iniciar la tarea.');
                    return;
                }
                card.style.opacity = '0.5';
                actionButton.disabled = true;
                
                const params = new URLSearchParams({ 
                    action: 'ghd_update_task_status', nonce: ghd_ajax.nonce, order_id: actionButton.dataset.orderId, 
                    field: actionButton.dataset.field, value: actionButton.dataset.value, assignee_id: assigneeId
                });

                fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('ghd-refresh-tasks')?.click();
                        } else { 
                            alert('Error: ' + (data.data?.message || 'Error desconocido')); 
                            card.style.opacity = '1';
                            actionButton.disabled = false;
                        }
                    });
            }

            // Lógica para abrir el modal de registro de detalles
            if (openModalButton) {
                e.preventDefault();
                const modal = document.getElementById(`complete-task-modal-${openModalButton.dataset.orderId}`);
                if (modal) modal.style.display = 'flex';
            }

            // Lógica para cerrar el modal
            if (closeModalButton) {
                e.preventDefault();
                const modal = closeModalButton.closest('.ghd-modal');
                if (modal) modal.style.display = 'none';
            }
            
            // --- CORRECCIÓN: Lógica unificada para el botón "Archivar Pedido" ---
            if (archiveBtn) { 
                e.preventDefault();
                if (!confirm('¿Archivar este pedido? Esta acción es final.')) return;

                const orderId = archiveBtn.dataset.orderId;
                const container = archiveBtn.closest('tr');
                if (!container) return;
                
                container.style.opacity = '0.5';
                archiveBtn.disabled = true;
                archiveBtn.textContent = 'Archivando...';
                
                const params = new URLSearchParams({ action: 'ghd_archive_order', nonce: ghd_ajax.nonce, order_id: orderId });

                fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            container.remove();
                            if (data.data && data.data.kpi_data) {
                                updateAdminClosureKPIs(data.data.kpi_data);
                            }
                        } else {
                            alert('Error: ' + (data.data?.message || 'No se pudo archivar.'));
                            container.style.opacity = '1';
                            archiveBtn.disabled = false;
                            archiveBtn.textContent = 'Archivar Pedido';
                        }
                    });
            }
        }); 

        // Listener para los SUBMITS (envío de formularios)
        mainContent.addEventListener('submit', function(e) {
            const completeTaskForm = e.target.closest('.complete-task-form');
            if (completeTaskForm) {
                e.preventDefault();
                const orderId = completeTaskForm.dataset.orderId;
                const formData = new FormData(completeTaskForm);
                formData.append('action', 'ghd_register_task_details_and_complete');
                formData.append('nonce', ghd_ajax.nonce);

                const submitButton = completeTaskForm.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
                }

                fetch(ghd_ajax.ajax_url, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(data.data.message || 'Tarea completada.');
                        const modal = document.getElementById(`complete-task-modal-${orderId}`);
                        if (modal) modal.style.display = 'none';
                        document.getElementById('ghd-refresh-tasks')?.click();
                    } else {
                        alert('Error: ' + (data.data?.message || 'No se pudo completar la tarea.'));
                    }
                })
                .finally(() => {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="fa-solid fa-check"></i> Completar Tarea';
                    }
                });
            }
        }); 
    } // fin if (mainContent)
///////////////////////////// ////////////////////////////////////////////////////
    // --- LÓGICA PARA ASIGNAR PRIORIDAD EN EL PANEL DE ASIGNACIÓN ---
    const assignationPanel = document.querySelector('.page-template-template-admin-dashboard');

     if (assignationPanel) { // Solo si estamos en el panel de asignación del administrador
        const ordersTableBody = document.getElementById('ghd-orders-table-body');

        if (ordersTableBody) {
            // Función auxiliar para actualizar el estado del botón "Iniciar Producción" y las clases visuales
            const updateStartProductionButtonState = (row) => {
                const prioritySelector = row.querySelector('.ghd-priority-selector');
                const vendedoraSelector = row.querySelector('.ghd-vendedora-selector');
                const startProductionBtn = row.querySelector('.start-production-btn');

                if (prioritySelector && vendedoraSelector && startProductionBtn) {
                    const isPrioritySet = (prioritySelector.value !== 'Seleccionar Prioridad');
                    const isVendedoraSet = (vendedoraSelector.value !== '0'); // '0' es el valor para "Asignar Vendedora"

                    startProductionBtn.disabled = !(isPrioritySet && isVendedoraSet);

                    // Añadir/quitar clases visuales de "no seleccionado" para estilo
                    if (!isPrioritySet) {
                        prioritySelector.classList.add('prioridad-no-seleccionada');
                    } else {
                        prioritySelector.classList.remove('prioridad-no-seleccionada');
                    }
                    if (!isVendedoraSet) {
                        vendedoraSelector.classList.add('vendedora-no-seleccionada');
                    } else {
                        vendedoraSelector.classList.remove('vendedora-no-seleccionada');
                    }
                }
            };

            // Ejecutar la inicialización para cada fila al cargar la página
            ordersTableBody.querySelectorAll('tr').forEach(row => {
                updateStartProductionButtonState(row); // Inicializa el estado del botón y las clases "no-seleccionada"
                
                // --- Aplicar clases de color al selector de PRIORIDAD si ya tiene un valor ---
                const prioritySelector = row.querySelector('.ghd-priority-selector');
                if (prioritySelector && prioritySelector.value !== 'Seleccionar Prioridad') {
                    prioritySelector.classList.remove('prioridad-no-seleccionada'); // Asegurarse de quitarla
                    if (prioritySelector.value === 'Alta') {
                        prioritySelector.classList.add('prioridad-alta');
                    } else if (prioritySelector.value === 'Media') {
                        prioritySelector.classList.add('prioridad-media');
                    } else if (prioritySelector.value === 'Baja') {
                        prioritySelector.classList.add('prioridad-baja');
                    }
                }

                // --- NUEVO BLOQUE: Aplicar clases de color al selector de VENDEDORA si ya tiene un valor ---
                const vendedoraSelector = row.querySelector('.ghd-vendedora-selector');
                if (vendedoraSelector && vendedoraSelector.value !== '0') { // '0' es el valor para "Asignar Vendedora"
                    vendedoraSelector.classList.remove('vendedora-no-seleccionada'); // Asegurarse de quitarla si ya tiene valor
                }
                // --- FIN NUEVO BLOQUE ---
            });

            // Manejar cambios en los selectores (Prioridad y Vendedora)
            ordersTableBody.addEventListener('change', function(e) {
                const target = e.target;
                const row = target.closest('tr'); // Obtener la fila para actualizar el botón

                if (target.classList.contains('ghd-priority-selector')) {
                    const orderId = target.dataset.orderId;
                    const selectedPriority = target.value;
                    
                    updateStartProductionButtonState(row); // Actualiza el estado del botón y clases visuales
                    
                    // Limpiar clases de prioridad existentes y añadir la nueva
                    target.classList.remove('prioridad-alta', 'prioridad-media', 'prioridad-baja');
                    if (selectedPriority === 'Alta') {
                        target.classList.add('prioridad-alta');
                    } else if (selectedPriority === 'Media') {
                        target.classList.add('prioridad-media');
                    } else if (selectedPriority === 'Baja') {
                        target.classList.add('prioridad-baja');
                    }

                    // Enviar la prioridad al backend vía AJAX
                    const params = new URLSearchParams({
                        action: 'ghd_update_priority',
                        nonce: ghd_ajax.nonce,
                        order_id: orderId,
                        priority: selectedPriority
                    });

                    fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                        .then(res => res.json())
                        .then(data => {
                            if (!data.success) {
                                console.error('Error al guardar prioridad:', data.data?.message || 'Error desconocido.');
                            } else {
                                console.log('Prioridad guardada:', data.message);
                            }
                        })
                        .catch(error => {
                            console.error("Error de red al guardar prioridad:", error);
                        });
                } else if (target.classList.contains('ghd-vendedora-selector')) {
                    const orderId = target.dataset.orderId;
                    const selectedVendedoraId = target.value;
                    
                    updateStartProductionButtonState(row); // Actualiza el estado del botón y clases visuales

                    // Enviar la vendedora al backend vía AJAX
                    const params = new URLSearchParams({
                        action: 'ghd_update_vendedora',
                        nonce: ghd_ajax.nonce,
                        order_id: orderId,
                        vendedora_id: selectedVendedoraId
                    });

                    fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                        .then(res => res.json())
                        .then(data => {
                            if (!data.success) {
                                console.error('Error al guardar vendedora:', data.data?.message || 'Error desconocido.');
                            } else {
                                console.log('Vendedora guardada:', data.message);
                            }
                        })
                        .catch(error => {
                            console.error("Error de red al guardar vendedora:", error);
                        });
                }
            });

            // --- NUEVO: Manejar cambios en el selector de Asignación de Operarios ---
            ordersTableBody.addEventListener('change', function(e) {
                const assigneeSelector = e.target.closest('.ghd-assignee-selector');
                if (assigneeSelector) {
                    const orderId = assigneeSelector.dataset.orderId;
                    const fieldPrefix = assigneeSelector.dataset.fieldPrefix; // ej. 'asignado_a_carpinteria'
                    const selectedAssigneeId = assigneeSelector.value;
                    const row = assigneeSelector.closest('tr');

                    // Enviar la asignación al backend vía AJAX
                    const params = new URLSearchParams({
                        action: 'ghd_assign_task_to_member', // <-- AÑADIR ESTE ENDPOINT AJAX EN functions.php
                        nonce: ghd_ajax.nonce,
                        order_id: orderId,
                        field_prefix: fieldPrefix,
                        assignee_id: selectedAssigneeId
                    });

                    fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                        .then(res => res.json())
                        .then(data => {
                            if (!data.success) {
                                console.error('Error al asignar operario:', data.data?.message || 'Error desconocido.');
                            } else {
                                console.log('Operario asignado:', data.message);
                                // Opcional: Refrescar la sección de producción del admin para ver el cambio
                                const refreshProdBtn = document.getElementById('ghd-refresh-production-tasks');
                                if (refreshProdBtn) { refreshProdBtn.click(); }
                            }
                        })
                        .catch(error => {
                            console.error("Error de red al asignar operario:", error);
                        });
                }
            });
            // --- FIN NUEVO ---
        }
    }

    // --- LÓGICA PARA EL BOTÓN "REFRESCAR" EN PANELES DE SECTOR ---
    const sectorDashboard = document.querySelector('.is-sector-dashboard-panel');
    if (sectorDashboard) {
        const refreshTasksBtn = document.getElementById('ghd-refresh-tasks');
        
        if (refreshTasksBtn) {
            refreshTasksBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const sectorTasksList = document.querySelector('.ghd-sector-tasks-list');
                const mainContentEl = document.querySelector('.ghd-main-content');
                const campoEstado = mainContentEl ? mainContentEl.dataset.campoEstado : '';

                if (!sectorTasksList || !campoEstado) {
                    console.error("Error: No se encontró la lista de tareas o el campo de estado para refrescar.");
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
                            // --- CORRECCIÓN: Acceder directamente a los datos de la respuesta ---
                            sectorTasksList.innerHTML = data.tasks_html;
                            if (data.kpi_data) {
                                updateSectorKPIs(data.kpi_data);
                            }
                        } else {
                            alert('Error al refrescar tareas: ' + (data.data?.message || 'Error desconocido.'));
                            sectorTasksList.innerHTML = '<p class="no-tasks-message">Error al cargar tareas.</p>';
                        }
                    })
                    .catch(error => {
                        console.error("Error en la petición AJAX de refresco:", error);
                        alert('Error de red al refrescar tareas.');
                        sectorTasksList.innerHTML = '<p class="no-tasks-message">Error de red. Inténtalo de nuevo.</p>';
                    })
                    .finally(() => {
                        sectorTasksList.style.opacity = '1';
                        refreshTasksBtn.disabled = false;
                    });
            });
        }
    }// fin botón refrescar sector

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
                    productionTableBody.innerHTML = '<tr><td colspan="9">Error de red. Inténtalo de nuevo.</td></tr>'; // Colspan ajustado
                })
                .finally(() => {
                    productionTasksContainer.style.opacity = '1';
                    refreshProductionTasksBtn.disabled = false;
                    // Si el panel de producción se refresca, también refrescar el de cierre.
                    const refreshClosureBtn = document.getElementById('ghd-refresh-closure-tasks');
                    if (refreshClosureBtn) { refreshClosureBtn.click(); }
                });
        });
    }

    // --- LÓGICA PARA EL BOTÓN "REFRESCAR" EN LA PÁGINA DE PEDIDOS ARCHIVADOS ---
    const refreshArchivedOrdersBtn = document.getElementById('ghd-refresh-archived-orders');
    if (refreshArchivedOrdersBtn) {
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

    // --- LÓGICA PARA LOS MODALES DE COMPLETAR TAREA ---
    // Event delegation para los botones que abren modales
    mainContent.addEventListener('click', function(e) { // Asegurarse que mainContent está definido
        const openModalButton = e.target.closest('.open-complete-task-modal');
        if (openModalButton) {
            e.preventDefault();
            const orderId = openModalButton.dataset.orderId;
            const modal = document.getElementById(`complete-task-modal-${orderId}`);
            if (modal) {
                modal.style.display = 'flex'; // Mostrar el modal
                // Detener la propagación del evento para que no active otros listeners
                e.stopPropagation(); 
            }
        }

        // Event delegation para los botones que cierran modales
        const closeModalButton = e.target.closest('.close-button');
        if (closeModalButton && closeModalButton.dataset.modalId) {
            e.preventDefault();
            const modal = document.getElementById(closeModalButton.dataset.modalId);
            if (modal) {
                modal.style.display = 'none'; // Ocultar el modal
                // Opcional: Resetear el formulario del modal al cerrarlo
                const form = modal.querySelector('.complete-task-form');
                if (form) {
                    form.reset(); // Limpia campos y estado del botón
                    const submitButton = form.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="fa-solid fa-check"></i> Completar y Guardar';
                    }
                    form.style.opacity = '1'; // Restaurar opacidad
                }
            }
            // Detener la propagación del evento para que no active otros listeners
            e.stopPropagation(); 
        }

        // Lógica para enviar el formulario de registro de detalles de tarea
        const completeTaskForm = e.target.closest('.complete-task-form');
        // Asegurarse que el evento es un click en el botón submit y que el formulario existe
        if (completeTaskForm && e.target.type === 'submit') { 
            e.preventDefault(); // Prevenir el envío por defecto del formulario
            const orderId = completeTaskForm.dataset.orderId;
            const fieldEstadoSector = completeTaskForm.dataset.field; // ej. 'estado_carpinteria'
            const formData = new FormData(completeTaskForm); // Capturar todos los datos del formulario (incluyendo archivos)

            // Añadir datos adicionales que no están en el formulario
            formData.append('action', 'ghd_register_task_details_and_complete');
            formData.append('nonce', ghd_ajax.nonce);
            formData.append('order_id', orderId);
            formData.append('field', fieldEstadoSector);
            // Obtener el ID del usuario logueado si no está ya en formData (aunque en este caso, el backend lo obtiene)
            // formData.append('logged_in_user_id', ghd_ajax.user_id); // Si estuviera disponible en ghd_ajax

            // Deshabilitar formulario y mostrar indicador de carga
            const submitButton = completeTaskForm.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
            }
            completeTaskForm.style.opacity = '0.5';

            fetch(ghd_ajax.ajax_url, {
                method: 'POST',
                body: formData // FormData se envía directamente, manejando archivos correctamente
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.data.message); // Mostrar mensaje de éxito
                    const modal = document.getElementById(`complete-task-modal-${orderId}`);
                    if (modal) {
                        modal.style.display = 'none'; // Cerrar modal
                    }
                    
                    // Refrescar el panel del sector para ver que la tarea ha desaparecido o cambiado
                    const refreshTasksBtn = document.getElementById('ghd-refresh-tasks');
                    if (refreshTasksBtn) { refreshTasksBtn.click(); }
                    
                    // Si el Admin está viendo un panel de sector, refrescar también sus paneles generales
                    if (document.body.classList.contains('is-admin-dashboard-panel')) {
                        const refreshProdBtn = document.getElementById('ghd-refresh-production-tasks');
                        if (refreshProdBtn) { refreshProdBtn.click(); }
                        const refreshClosureBtn = document.getElementById('ghd-refresh-closure-tasks');
                        if (refreshClosureBtn) { refreshClosureBtn.click(); }
                    }

                } else {
                    alert('Error al completar tarea: ' + (data.data?.message || 'Error desconocido.'));
                }
            })
            .catch(error => {
                console.error("Error de red al completar tarea:", error);
                alert('Error de red al completar tarea.');
            })
            .finally(() => {
                // Restaurar el estado del botón y del formulario
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fa-solid fa-check"></i> Completar y Guardar';
                }
                completeTaskForm.style.opacity = '1';
            });
        }
    }); // Fin de la delegación de eventos en mainContent


    // --- LÓGICA PARA EL FILTRO DESDE LA URL AL CARGAR LA PÁGINA (EN ADMIN DASHBOARD) ---
    const adminDashboard = document.querySelector('.page-template-template-admin-dashboard');
    if (adminDashboard) {
        const searchFilter = document.getElementById('ghd-search-filter');
        const statusFilter = document.getElementById('ghd-status-filter');
        const priorityFilter = document.getElementById('ghd-priority-filter');
        const resetFiltersBtn = document.getElementById('ghd-reset-filters');
        const tableBody = document.getElementById('ghd-orders-table-body');

        // Función para aplicar los filtros
        const applyFilters = () => {
            if (!tableBody) return;
            
            tableBody.style.opacity = '0.5';
            
            const params = new URLSearchParams({ 
                action: 'ghd_filter_orders', // <-- NECESITA ESTE ENDPOINT AJAX EN functions.php
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
                        alert('Error al filtrar tareas: ' + (data.data?.message || ''));
                        tableBody.innerHTML = '<tr><td colspan="9">Ocurrió un error al cargar los datos.</td></tr>';
                    }
                })
                .catch(error => {
                    console.error("Error en la petición AJAX de filtros:", error);
                    tableBody.innerHTML = '<tr><td colspan="9">Error de red. Inténtalo de nuevo.</td></tr>';
                })
                .finally(() => {
                    tableBody.style.opacity = '1';
                });
        };

        let searchTimeout;
        if (searchFilter) { 
            searchFilter.addEventListener('keyup', () => { 
                clearTimeout(searchTimeout); 
                searchTimeout = setTimeout(applyFilters, 500); // Esperar 500ms después de dejar de teclear
            });
        }
        
        if (statusFilter) { 
            statusFilter.addEventListener('change', applyFilters);
        }
        if (priorityFilter) { 
            priorityFilter.addEventListener('change', applyFilters);
        }
        
        if (resetFiltersBtn) { 
            resetFiltersBtn.addEventListener('click', () => { 
                searchFilter.value = ''; 
                statusFilter.value = ''; 
                priorityFilter.value = ''; 
                applyFilters(); 
            });
        }

        // Lógica para cargar filtros desde la URL si existen (ej. después de una búsqueda)
        if (window.location.hash && window.location.hash.startsWith('#buscar=')) {
            let searchTerm = decodeURIComponent(window.location.hash.substring(8)).replace(/\+/g, ' ');
            if (searchFilter) searchFilter.value = searchTerm;
            applyFilters(); // Aplicar el filtro de búsqueda
            // Limpiar el hash de la URL después de usarlo
            history.pushState("", document.title, window.location.pathname + window.location.search);
        }
    }

    // --- LÓGICA PARA LOS GRÁFICOS DE LA PÁGINA DE REPORTES ---
    // (Asegurarse de que el objeto ghd_reports_data esté disponible globalmente)
    if (typeof ghd_reports_data !== 'undefined' && document.querySelector('.ghd-reports-grid')) {
        // Código para inicializar Chart.js...
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
        // ... (código para otros gráficos: cargaPorSectorChart, pedidosPorPrioridadChart) ...
    }

    // --- LÓGICA PARA EL BOTÓN "EXPORTAR" PEDIDOS (EN TABLAS DEL ADMIN) ---
    const exportAssignationOrdersBtn = document.getElementById('ghd-export-assignation-orders');
    if (exportAssignationOrdersBtn) {
        exportAssignationOrdersBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const exportType = exportAssignationOrdersBtn.dataset.exportType || 'assignation';

            exportAssignationOrdersBtn.disabled = true;
            exportAssignationOrdersBtn.textContent = 'Exportando...';

            const params = new URLSearchParams({
                action: 'ghd_export_orders_csv', // <-- NECESITA ESTE ENDPOINT AJAX EN functions.php
                nonce: ghd_ajax.nonce,
                export_type: exportType
            });

            fetch(ghd_ajax.ajax_url, {
                method: 'POST',
                body: params
            })
            .then(response => {
                // Manejar el caso de que la respuesta sea un JSON de error
                const contentType = response.headers.get('Content-Type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.data?.message || 'Error desconocido al exportar.');
                    });
                }
                // Si no es JSON, asumimos que es un archivo y procedemos
                return response.blob();
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                
                // Intentar obtener el nombre del archivo del header Content-Disposition
                const contentDisposition = response.headers.get('Content-Disposition'); // Acceder a la respuesta original
                const filenameMatch = contentDisposition && contentDisposition.match(/filename="(.+)"/);
                a.download = filenameMatch ? filenameMatch[1] : `export_${Date.now()}.csv`;
                
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url); // Liberar el objeto URL
                alert('Exportación completada con éxito.');
            })
            .catch(error => {
                console.error("Error al exportar pedidos:", error);
                alert('Error al exportar pedidos: ' + error.message);
            })
            .finally(() => {
                exportAssignationOrdersBtn.disabled = false;
                exportAssignationOrdersBtn.innerHTML = '<i class="fa-solid fa-download"></i> <span>Exportar</span>';
            });
        });
    }

        // --- LÓGICA PARA EL POPUP DE NUEVO PEDIDO ---
    const abrirModalBtn = document.getElementById('abrir-nuevo-pedido-modal');
    const nuevoPedidoModal = document.getElementById('nuevo-pedido-modal');
    const nuevoPedidoForm = document.getElementById('nuevo-pedido-form');

    if (abrirModalBtn && nuevoPedidoModal) {
        // Abrir el modal
        abrirModalBtn.addEventListener('click', function() {
            nuevoPedidoModal.style.display = 'flex';
        });

        // Cerrar el modal con el botón de la 'X'
        const closeModalBtn = nuevoPedidoModal.querySelector('.close-button');
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', function() {
                nuevoPedidoModal.style.display = 'none';
            });
        }
    }

        // Reemplaza el listener 'submit' para nuevoPedidoForm con este
    if (nuevoPedidoForm) {
        nuevoPedidoForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creando...';

            const formData = new FormData(this);
            formData.append('action', 'ghd_crear_nuevo_pedido');
            formData.append('nonce', ghd_ajax.nonce);

            fetch(ghd_ajax.ajax_url, {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.data.message);
                    nuevoPedidoModal.style.display = 'none';
                    this.reset();

                    // --- CORRECCIÓN: Lógica para insertar la nueva fila con AJAX ---
                    const tableBody = document.getElementById('ghd-orders-table-body');
                    if (tableBody) {
                        // Buscar si la tabla tiene el mensaje de "No hay pedidos"
                        const noOrdersRow = tableBody.querySelector('td[colspan="6"]');
                        if (noOrdersRow) {
                            // Si la tabla estaba vacía, reemplazar el mensaje con la nueva fila
                            tableBody.innerHTML = data.data.new_row_html;
                        } else {
                            // Si ya hay pedidos, añadir el nuevo al principio
                            tableBody.insertAdjacentHTML('afterbegin', data.data.new_row_html);
                        }
                    }
                    
                } else {
                    alert('Error: ' + (data.data?.message || 'No se pudo crear el pedido.'));
                }
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fa-solid fa-plus"></i> Crear Pedido';
            });
        });
    }
}); // Cierre Correcto del DOMContentLoaded listener

// LÓGICA PARA ACTIVAR EL FILTRO DESDE LA URL AL CARGAR LA PÁGINA (EXISTENTE)
// Se mantiene aquí por si acaso, pero la lógica principal de filtros está en DOMContentLoaded.
// La llamada a applyFilters desde aquí podría no funcionar si el DOM no está listo.
window.addEventListener('load', function() {
    // Asegurarse de que getElementById existe antes de usarlo
    const searchFilterInput = document.getElementById('ghd-search-filter');
    if (!searchFilterInput) return;

    const urlParams = new URLSearchParams(window.location.search);
    const searchTerm = urlParams.get('buscar');

    if (searchTerm) {
        searchFilterInput.value = searchTerm;
        // Simular el evento keyup para activar la lógica applyFilters
        const event = new Event('keyup');
        searchFilterInput.dispatchEvent(event);
        // Limpiar el parámetro de búsqueda de la URL después de aplicarlo
        history.pushState("", document.title, window.location.pathname + window.location.search);
    }
});


// --- LÓGICA PARA EL BOTÓN "REFRESCAR" EN LA PÁGINA DE PEDIDOS ARCHIVADOS ---
const refreshArchivedBtn = document.getElementById('ghd-refresh-archived-orders');
if (refreshArchivedBtn) {
    refreshArchivedBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        const tableBody = document.getElementById('ghd-archived-orders-table-body');
        if (!tableBody) return;

        tableBody.style.opacity = '0.5';
        refreshArchivedBtn.disabled = true;

        const params = new URLSearchParams({
            action: 'ghd_refresh_archived_orders',
            nonce: ghd_ajax.nonce
        });
        // ...listener de "submit" para nuevoPedidoForm ...
            fetch(ghd_ajax.ajax_url, {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(res => {
                if (!res.ok) {
                    // Si el servidor responde con un error (ej. 500), lo capturamos aquí
                    throw new Error('Error del servidor. Revisa el debug.log de WordPress.');
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    alert(data.data.message);
                    window.location.reload();
                } else {
                    // Esto mostrará el error específico enviado desde PHP (ej. "Campo obligatorio")
                    alert('Error: ' + (data.data?.message || 'No se pudo crear el pedido.'));
                }
            })
            .catch(error => {
                // Esto captura errores de red o errores lanzados en el .then() anterior
                console.error("Error al crear pedido:", error);
                alert("Ocurrió un error inesperado. Revisa la consola para más detalles.");
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fa-solid fa-plus"></i> Crear Pedido';
            });
    });
}
