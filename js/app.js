document.addEventListener('DOMContentLoaded', function() {
    
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.querySelector('.ghd-sidebar');
    const closeSidebarBtn = document.getElementById('mobile-menu-close'); // <-- NUEVO: Botón de cierre dentro del sidebar

    if (menuToggle && sidebar) {
        const overlay = document.createElement('div');
        overlay.style.cssText = "position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:999;display:none;";
        document.body.appendChild(overlay);

        const closeMenu = () => { 
            sidebar.classList.remove('sidebar-visible'); 
            overlay.style.display = 'none'; 
            document.body.classList.remove('no-scroll'); // Opcional: remover clase para deshabilitar scroll del body
        };
        const openMenu = (e) => { 
            e.stopPropagation(); 
            sidebar.classList.add('sidebar-visible'); 
            overlay.style.display = 'block'; 
            document.body.classList.add('no-scroll'); // Opcional: añadir clase para deshabilitar scroll del body
        };

        menuToggle.addEventListener('click', openMenu);
        overlay.addEventListener('click', closeMenu);
        
        if (closeSidebarBtn) { // <-- Si el botón de cierre existe, añadir listener
            closeSidebarBtn.addEventListener('click', closeMenu);
        }
    } // fin if menuToggle
////////////////////////////// ////////////////////////////////////////////////////
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

    // --- Función para refrescar la lista de tareas del Fletero ---
    const refreshFleteroTasksList = () => {
        const fleteroTasksList = document.querySelector('.ghd-fletero-tasks-list');
        const refreshFleteroTasksBtn = document.getElementById('ghd-refresh-fletero-tasks');

        if (!fleteroTasksList || !refreshFleteroTasksBtn) {
            console.warn('refreshFleteroTasksList: Elementos DOM (.ghd-fletero-tasks-list o #ghd-refresh-fletero-tasks) no encontrados. Posiblemente en otra página.');
            return;
        }

        fleteroTasksList.style.opacity = '0.5';
        refreshFleteroTasksBtn.disabled = true;

        const params = new URLSearchParams({
            action: 'ghd_refresh_fletero_tasks',
            nonce: ghd_ajax.nonce
        });

        fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
            .then(res => res.json())
            .then(response => { 
                console.log('Respuesta completa de ghd_refresh_fletero_tasks:', response);

                if (response.success) { 
                    if (typeof response.data.tasks_html === 'string') {
                        fleteroTasksList.innerHTML = response.data.tasks_html;
                        console.log('Refresco de tareas de fletero exitoso. Contenido actualizado.');
                    } else {
                        const errorMessage = response.data.message || 'La respuesta de refresco del servidor es incompleta (falta HTML).';
                        fleteroTasksList.innerHTML = '<p class="no-tasks-message" style="text-align: center; padding: 20px;">' + errorMessage + '</p>';
                        console.error('Error de refresco (success=true, pero tasks_html inválido):', response);
                    }
                } else {
                    const errorMessage = response.data?.message || 'Error desconocido del servidor al refrescar.';
                    fleteroTasksList.innerHTML = '<p class="no-tasks-message" style="text-align: center; padding: 20px;">' + errorMessage + '</p>';
                    console.error('Error de refresco (success=false):', response);
                }
            })
            .catch(error => {
                console.error("Error de red al refrescar tareas de fletero:", error);
                fleteroTasksList.innerHTML = '<p class="no-tasks-message" style="text-align: center; padding: 20px;">Error de red. Inténtalo de nuevo.</p>';
            })
            .finally(() => {
                fleteroTasksList.style.opacity = '1';
                refreshFleteroTasksBtn.disabled = false;
            });
    }; // fin función refreshFleteroTasksList

    // --- LÓGICA UNIVERSAL DE EVENTOS EN EL CONTENIDO PRINCIPAL ---
    const mainContent = document.querySelector('.ghd-main-content');
    if (mainContent) {

        // Listener para todos los CLICKS dentro de mainContent
        mainContent.addEventListener('click', function(e) {
            
            const target = e.target;
            // Botones de paneles de Administrador/Sector
            const actionButton = target.closest('.action-button');
            const openModalButton = target.closest('.open-complete-task-modal');
            const closeModalButton = target.closest('.close-button');
            const refreshTasksBtn = target.closest('#ghd-refresh-tasks'); // Refrescar panel de sector general
            const archiveBtn = target.closest('.archive-order-btn'); // Archivar pedido (Admin/Macarena)

            // Botones de panel de Fletero
            const fleteroActionButton = target.closest('.fletero-action-btn'); // Marcar Recogido / Marcar Entregado
            const openUploadDeliveryProofModalBtn = target.closest('.open-upload-delivery-proof-modal'); // Abre modal de comprobante de fletero
            const refreshFleteroTasksBtn = target.closest('#ghd-refresh-fletero-tasks'); // Refrescar panel de fletero


            // --- MANEJADORES DE CLICKS ---

            // Lógica para el botón "Refrescar" en el panel de sector (NO fletero)
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
                    .then(response => { // Renombrado a 'response' para consistencia
                        if (response.success && response.data) { // Acceso correcto a response.data
                            sectorTasksList.innerHTML = response.data.tasks_html;
                            if (response.data.kpi_data) updateSectorKPIs(response.data.kpi_data);
                        } else {
                            // Mostrar mensaje de error en consola, no alerta
                            console.error('Error al refrescar tareas de sector: ' + (response.data?.message || 'Error desconocido.'));
                            sectorTasksList.innerHTML = '<p class="no-tasks-message">Error al cargar tareas.</p>';
                        }
                    })
                    .catch(error => {
                        console.error("Error en la petición AJAX de refresco de sector:", error);
                        sectorTasksList.innerHTML = '<p class="no-tasks-message">Error de red. Inténtalo de nuevo.</p>';
                    })
                    .finally(() => {
                        sectorTasksList.style.opacity = '1';
                        refreshTasksBtn.disabled = false;
                    });
            }
            
            // Lógica para el botón "Iniciar Tarea" en el Panel de Sector
            if (actionButton && !openModalButton) { // Solo si es un botón de acción simple (no abre modal)
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
                    .then(response => { // Renombrado a 'response'
                        if (response.success) {
                            document.getElementById('ghd-refresh-tasks')?.click();
                            // Mostrar mensaje de éxito en consola si no hay alerta
                            console.log('Estado de tarea actualizado: ' + (response.data?.message || ''));
                        } else { 
                            alert('Error: ' + (response.data?.message || 'Error desconocido')); 
                            card.style.opacity = '1';
                            actionButton.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error("Error de red al iniciar tarea:", error);
                        alert('Error de red. Inténtalo de nuevo.'); 
                        card.style.opacity = '1';
                        actionButton.disabled = false;
                    });
            }

            // Lógica para abrir el modal de registro de detalles (general de sector)
            if (openModalButton) {
                e.preventDefault();
                const modal = document.getElementById(`complete-task-modal-${openModalButton.dataset.orderId}`);
                if (modal) {
                    modal.style.display = 'flex';
                    modal.dispatchEvent(new Event('ghdModalOpened'));
                    e.stopPropagation(); 
                }
            }

            // Lógica para cerrar CUALQUIER modal (botón X)
            if (closeModalButton) {
                e.preventDefault();
                const modal = closeModalButton.closest('.ghd-modal');
                if (modal) {
                    modal.style.display = 'none';
                    const form = modal.querySelector('form');
                    if (form) form.reset();
                }
                e.stopPropagation(); 
            }
            
            // Lógica unificada para el botón "Archivar Pedido" (Admin/Macarena)
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
                    .then(response => { // Renombrado a 'response'
                        if (response.success) {
                            container.remove();
                            if (response.data && response.data.kpi_data) {
                                updateAdminClosureKPIs(response.data.kpi_data);
                            }
                            console.log('Pedido archivado: ' + (response.data?.message || ''));
                        } else {
                            alert('Error: ' + (response.data?.message || 'No se pudo archivar.'));
                            container.style.opacity = '1';
                            archiveBtn.disabled = false;
                            archiveBtn.textContent = 'Archivar Pedido';
                        }
                    })
                    .catch(error => {
                        console.error("Error de red al archivar pedido:", error);
                        alert('Error de red. Inténtalo de nuevo.');
                        container.style.opacity = '1';
                        archiveBtn.disabled = false;
                        archiveBtn.textContent = 'Archivar Pedido';
                    });
            }

            // --- Lógica para el botón "Refrescar" en el panel de fletero ---
            if (refreshFleteroTasksBtn) {
                e.preventDefault();
                refreshFleteroTasksList(); // Llama a la función centralizada de refresco
            }

            // --- Lógica para el botón "Marcar como Recogido" del Fletero ---
            if (fleteroActionButton && !openUploadDeliveryProofModalBtn) {
                e.preventDefault();
                const orderId = fleteroActionButton.dataset.orderId;
                const newStatus = fleteroActionButton.dataset.newStatus; // 'Recogido' o 'Entregado'
                const card = fleteroActionButton.closest('.ghd-order-card');

                if (!confirm(`¿Estás seguro de que quieres marcar este pedido como "${newStatus}"?`)) {
                    return;
                }

                card.style.opacity = '0.5';
                fleteroActionButton.disabled = true;
                fleteroActionButton.textContent = 'Actualizando...';

                const params = new URLSearchParams({
                    action: 'ghd_fletero_mark_recogido',
                    nonce: ghd_ajax.nonce,
                    order_id: orderId
                });

                fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                .then(res => res.json())
                .then(response => {
                    if (response.success) {
                        refreshFleteroTasksList();
                        // Alerta solo si es un mensaje de éxito, pero generalmente el refresh es silencioso
                        console.log('Pedido marcado como recogido: ' + (response.data?.message || '')); 
                    } else {
                        alert('Error: ' + (response.data?.message || 'Error desconocido al marcar como recogido.'));
                    }
                })
                .catch(error => {
                    console.error("Error de red al marcar como recogido:", error);
                    alert('Error de red. Inténtalo de nuevo.');
                })
                .finally(() => {
                    card.style.opacity = '1'; 
                    fleteroActionButton.disabled = false;
                    fleteroActionButton.textContent = 'Marcar como Recogido';
                });
            } // fin fleteroActionButton

            // Lógica para abrir el modal de subida de comprobante del Fletero
            if (openUploadDeliveryProofModalBtn) {
                e.preventDefault();
                const orderId = openUploadDeliveryProofModalBtn.dataset.orderId;
                const modal = document.getElementById(`upload-delivery-proof-modal-${orderId}`);
                if (modal) {
                    modal.style.display = 'flex';
                    modal.dispatchEvent(new Event('ghdModalOpened'));
                    e.stopPropagation();
                }
            }

        }); // fin listener clicks 

        // Listener para los SUBMITS (envío de formularios)
        mainContent.addEventListener('submit', function(e) {
            const completeTaskForm = e.target.closest('.complete-task-form'); // Formulario de tareas de sector (general)
            const completeDeliveryForm = e.target.closest('.complete-delivery-form'); // Formulario de entrega del Fletero

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
                .then(response => { // Renombrado a 'response'
                    if (response.success) {
                        alert(response.data.message || 'Tarea completada.');
                        const modal = document.getElementById(`complete-task-modal-${orderId}`);
                        if (modal) modal.style.display = 'none';
                        document.getElementById('ghd-refresh-tasks')?.click();
                    } else {
                        alert('Error: ' + (response.data?.message || 'No se pudo completar la tarea.'));
                    }
                })
                .catch(error => {
                    console.error("Error de red al completar tarea:", error);
                    alert('Error de red al completar tarea.');
                })
                .finally(() => {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="fa-solid fa-check"></i> Completar Tarea';
                    }
                });
            }

            // --- Lógica para el SUBMIT del formulario de COMPROBANTE DE ENTREGA del Fletero ---
            if (completeDeliveryForm) {
                e.preventDefault();
                const orderId = completeDeliveryForm.dataset.orderId;
                const formData = new FormData(completeDeliveryForm);
                formData.append('action', 'ghd_fletero_complete_delivery');
                formData.append('nonce', ghd_ajax.nonce);
                formData.append('order_id', orderId);

                const submitButton = completeDeliveryForm.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Entregando...';
                }
                completeDeliveryForm.style.opacity = '0.5';

                fetch(ghd_ajax.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(response => { // Renombramos a 'response' para claridad
                    if (response.success) {
                        const modal = document.getElementById(`upload-delivery-proof-modal-${orderId}`);
                        if (modal) {
                            modal.style.display = 'none';
                            completeDeliveryForm.reset();
                        }
                        console.log('Entrega completada: ' + (response.data?.message || ''));
                        refreshFleteroTasksList();
                    } else {
                        alert('Error al completar entrega: ' + (response.data?.message || 'Error desconocido.'));
                    }
                })
                .catch(error => {
                    console.error("Error de red al completar entrega:", error);
                    alert('Error de red al completar entrega.');
                })
                .finally(() => {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="fa-solid fa-check"></i> Marcar como Entregado';
                    }
                    completeDeliveryForm.style.opacity = '1';
                });// fin fetch
            } // fin if (completeDeliveryForm)
        }); // fin listener submits
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
                    // const isPrioritySet = (prioritySelector.value !== 'Seleccionar Prioridad');
                    const isPrioritySet = (prioritySelector.value !== '');
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
                        .then(response => { // Renombrado a 'response'
                            if (!response.success) {
                                console.error('Error al guardar prioridad:', response.data?.message || 'Error desconocido.');
                            } else {
                                console.log('Prioridad guardada:', response.data.message);
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
                        .then(response => { // Renombrado a 'response'
                            if (!response.success) {
                                console.error('Error al guardar vendedora:', response.data?.message || 'Error desconocido.');
                            } else {
                                console.log('Vendedora guardada:', response.data.message);
                            }
                        })
                        .catch(error => {
                            console.error("Error de red al guardar vendedora:", error);
                        });
                }
            });

        // --- NUEVO BLOQUE: Manejar cambios en el selector de Asignación de Operarios usando delegación de eventos ---
        mainContent.addEventListener('change', function(e) { // Escuchar 'change' en el contenedor principal
            const assigneeSelector = e.target.closest('.ghd-assignee-selector');
            if (assigneeSelector) {
                const orderId = assigneeSelector.dataset.orderId;
                const fieldPrefix = assigneeSelector.dataset.fieldPrefix; // ej. 'asignado_a_carpinteria'
                const selectedAssigneeId = assigneeSelector.value;
                
                // Opcional: Mostrar un indicador de carga en la tarjeta
                const card = assigneeSelector.closest('.ghd-order-card');
                if (card) card.style.opacity = '0.5';

                // Enviar la asignación al backend vía AJAX
                const params = new URLSearchParams({
                    action: 'ghd_assign_task_to_member',
                    nonce: ghd_ajax.nonce,
                    order_id: orderId,
                    field_prefix: fieldPrefix,
                    assignee_id: selectedAssigneeId
                });

                fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                    .then(res => res.json())
                    .then(response => { // Renombrado a 'response'
                        if (response.success) {
                            console.log('Operario asignado:', response.data.message);
                            // Después de asignar, refrescar las tareas para ver el cambio reflejado (nombre asignado)
                            document.getElementById('ghd-refresh-tasks')?.click();
                        } else {
                            console.error('Error al asignar operario:', response.data?.message || 'Error desconocido.');
                            alert('Error al asignar operario: ' + (response.data?.message || 'Error desconocido.'));
                        }
                    })
                    .catch(error => {
                        console.error("Error de red al asignar operario:", error);
                        alert('Error de red al asignar operario.');
                    })
                    .finally(() => {
                        if (card) card.style.opacity = '1'; // Restaurar opacidad
                    });
            }
        }); // fin listener delegación de eventos
        // --- FIN NUEVO BLOQUE ---

            // --- NUEVO BLOQUE: Manejar click en "Iniciar Producción" ---
            ordersTableBody.addEventListener('click', function(e) {
                const startProductionBtn = e.target.closest('.start-production-btn');
                // Asegurarse que el botón fue clicado y que no esté deshabilitado
                if (startProductionBtn && !startProductionBtn.disabled) {
                    e.preventDefault(); // Prevenir el comportamiento por defecto del botón

                    const orderId = startProductionBtn.dataset.orderId;
                    const row = startProductionBtn.closest('tr'); // Obtener la fila para efectos visuales

                    if (!confirm('¿Estás seguro de que quieres iniciar la producción de este pedido? Esta acción moverá el pedido a producción.')) {
                        return; // Si el usuario cancela, no hacer nada
                    }

                    // 1. Indicador visual de carga
                    row.style.opacity = '0.5';
                    startProductionBtn.disabled = true;
                    startProductionBtn.textContent = 'Iniciando...';

                    // 2. Preparar los parámetros para la llamada AJAX
                    const params = new URLSearchParams({
                        action: 'ghd_start_production', // La nueva acción AJAX definida en functions.php
                        nonce: ghd_ajax.nonce,
                        order_id: orderId
                    });

                    // 3. Realizar la petición AJAX
                    fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                        .then(res => res.json())
                        .then(response => { // Renombrado a 'response'
                            if (response.success) {
                                alert(response.data.message);
                                row.remove(); // Eliminar la fila de la tabla de asignación (ya no está pendiente)

                                // 4. Refrescar la sección de "Pedidos en Producción" y sus KPIs
                                const productionTableBody = document.getElementById('ghd-production-table-body');
                                const refreshProductionTasksBtn = document.getElementById('ghd-refresh-production-tasks');
                                
                                if (productionTableBody && response.data.production_tasks_html) {
                                     // Reemplazar el contenido de la tabla de producción con el HTML actualizado
                                    productionTableBody.innerHTML = response.data.production_tasks_html;
                                    // Actualizar los KPIs de producción
                                    if (response.data.kpi_data) {
                                        updateAdminProductionKPIs(response.data.kpi_data);
                                    }
                                } else if (refreshProductionTasksBtn) {
                                    refreshProductionTasksBtn.click();
                                }
                            } else {
                                alert('Error al iniciar producción: ' + (response.data?.message || 'Error desconocido.'));
                                row.style.opacity = '1'; 
                                startProductionBtn.disabled = false;
                                startProductionBtn.textContent = 'Iniciar Producción';
                            }
                        })
                        .catch(error => {
                            console.error("Error de red al iniciar producción:", error);
                            alert('Error de red al iniciar producción. Por favor, revisa tu conexión.');
                            row.style.opacity = '1';
                            startProductionBtn.disabled = false;
                            startProductionBtn.textContent = 'Iniciar Producción';
                        });
                }
            });
            // --- FIN NUEVO BLOQUE ---
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
                const campoEstado = mainContent.dataset.campoEstado || '';
                if (!sectorTasksList || !campoEstado) return;

                sectorTasksList.style.opacity = '0.5';
                refreshTasksBtn.disabled = true;

                const params = new URLSearchParams({
                    action: 'ghd_refresh_sector_tasks',
                    nonce: ghd_ajax.nonce,
                    campo_estado: campoEstado
                });

                fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                    .then(res => res.json())
                    .then(response => { // Renombrado a 'response'
                        if (response.success) { // Aquí no hay response.data.data, es response.data.tasks_html
                            sectorTasksList.innerHTML = response.data.tasks_html; // <-- Usar response.data.tasks_html
                            if (response.data.kpi_data) {
                                updateSectorKPIs(response.data.kpi_data);
                            }
                            console.log('Refresco de tareas de sector exitoso: ' + (response.data?.message || ''));
                        } else {
                            // Mostrar mensaje de error en consola, no alerta
                            console.error('Error al refrescar tareas de sector: ' + (response.data?.message || 'Error desconocido.'));
                            sectorTasksList.innerHTML = '<p class="no-tasks-message">Error al cargar tareas.</p>';
                        }
                    })
                    .catch(error => {
                        console.error("Error en la petición AJAX de refresco de sector:", error);
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
                .then(response => { // Renombrado a 'response'
                    if (response.success) {
                        closureTableBody.innerHTML = response.data.table_html;
                        if (response.data.kpi_data) {
                            updateAdminClosureKPIs(response.data.kpi_data);
                        }
                        console.log('Refresco de cierre exitoso: ' + (response.data?.message || ''));
                    } else {
                        console.error('Error al refrescar pedidos de cierre: ' + (response.data?.message || ''));
                        closureTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Error al cargar pedidos de cierre.</td></tr>';
                    }
                })
                .catch(error => {
                    console.error("Error en la petición AJAX de refresco de cierre:", error);
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
                .then(response => { // Renombrado a 'response'
                    if (response.success) {
                        productionTableBody.innerHTML = response.data.tasks_html;
                        if (response.data.kpi_data) {
                            updateAdminProductionKPIs(response.data.kpi_data);
                        }
                        console.log('Refresco de producción exitoso: ' + (response.data?.message || ''));
                    } else {
                        console.error('Error al refrescar pedidos en producción: ' + (response.data?.message || ''));
                        productionTableBody.innerHTML = '<tr><td colspan="9" style="text-align:center;">Error al cargar pedidos en producción.</td></tr>';
                    }
                })
                .catch(error => {
                    console.error("Error en la petición AJAX de refresco de producción:", error);
                    productionTableBody.innerHTML = '<tr><td colspan="9">Error de red. Inténtalo de nuevo.</td></tr>';
                })
                .finally(() => {
                    productionTasksContainer.style.opacity = '1';
                    refreshProductionTasksBtn.disabled = false;
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

            fetch(ghd_ajax.ajax_url, {
                method: 'POST',
                body: params
            })
            .then(response => {
                const contentType = response.headers.get('Content-Type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.data?.message || 'Error desconocido al exportar.');
                    });
                }
                return response.blob();
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                
                const contentDisposition = response.headers.get('Content-Disposition');
                const filenameMatch = contentDisposition && contentDisposition.match(/filename="(.+)"/);
                a.download = filenameMatch ? filenameMatch[1] : `export_${Date.now()}.csv`;
                
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                console.log('Exportación completada con éxito.');
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
        abrirModalBtn.addEventListener('click', function() {
            nuevoPedidoModal.style.display = 'flex';
        });

        const closeModalBtn = nuevoPedidoModal.querySelector('.close-button');
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', function() {
                nuevoPedidoModal.style.display = 'none';
            });
        }
    }

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
            .then(response => { // Renombrado a 'response'
                if (response.success) {
                    alert(response.data.message);
                    nuevoPedidoModal.style.display = 'none';
                    this.reset();

                    const tableBody = document.getElementById('ghd-orders-table-body');
                    if (tableBody) {
                        const noOrdersRow = tableBody.querySelector('td[colspan="6"]');
                        if (noOrdersRow) {
                            tableBody.innerHTML = response.data.new_row_html;
                        } else {
                            tableBody.insertAdjacentHTML('afterbegin', response.data.new_row_html);
                        }
                    } 
                } else {
                    alert('Error: ' + (response.data?.message || 'No se pudo crear el pedido.'));
                }
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fa-solid fa-plus"></i> Crear Pedido';
            });
        });
    }
}); // Cierre Correcto del DOMContentLoaded listener

window.addEventListener('load', function() {
    const searchFilterInput = document.getElementById('ghd-search-filter');
    if (!searchFilterInput) return;

    const urlParams = new URLSearchParams(window.location.search);
    const searchTerm = urlParams.get('buscar');

    if (searchTerm) {
        searchFilterInput.value = searchTerm;
        const event = new Event('keyup');
        searchFilterInput.dispatchEvent(event);
        history.pushState("", document.title, window.location.pathname + window.location.search);
    }
});


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
        
            fetch(ghd_ajax.ajax_url, {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error('Error del servidor. Revisa el debug.log de WordPress.');
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    alert(data.data.message);
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.data?.message || 'No se pudo crear el pedido.'));
                }
            })
            .catch(error => {
                console.error("Error al crear pedido:", error);
                alert("Ocurrió un error inesperado. Revisa la consola para más detalles.");
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fa-solid fa-plus"></i> Crear Pedido';
            });
    });
}