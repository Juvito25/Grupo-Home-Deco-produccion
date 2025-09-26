// --- NUEVO: Función global para inicializar puntos estimados en modales de Embalaje ---
const initEmbalajeModalPoints = (modalElement, postId) => {
    // Evitar re-inicializar si ya se hizo o si los elementos no existen
    if (!modalElement || modalElement.dataset.pointsInitialized === 'true') {
        return;
    }

    const modeloSelector = modalElement.querySelector('#modelo_embalado_' + postId);
    const cantidadInput = modalElement.querySelector('#cantidad_embalada_' + postId);
    const puntosEstimadosSpan = modalElement.querySelector('#puntos_estimados_' + postId);

    const updateEstimatedPoints = () => {
        if (!modeloSelector || !cantidadInput || modeloSelector.selectedIndex === -1 || modeloSelector.value === '') {
            puntosEstimadosSpan.textContent = '0';
            return;
        }
        const selectedOption = modeloSelector.options[modeloSelector.selectedIndex];
        const modelPoints = parseInt(selectedOption.dataset.points || '0');
        const quantity = parseInt(cantidadInput.value || '0');
        const totalPoints = modelPoints * quantity;
        puntosEstimadosSpan.textContent = totalPoints;
    };

    // Escuchar cambios en los selectores y el input de cantidad
    if (modeloSelector) modeloSelector.addEventListener('change', updateEstimatedPoints);
    if (cantidadInput) cantidadInput.addEventListener('input', updateEstimatedPoints);

    // Disparar updateEstimatedPoints al abrir el modal (evento principal)
    modalElement.addEventListener('ghdModalOpened', updateEstimatedPoints); 
    
    // Forzar una inicialización inmediata si el selector de modelo existe y tiene un valor inicial
    if (modeloSelector && modeloSelector.value !== '') { 
        updateEstimatedPoints(); 
    } else { // Si el selector existe pero no tiene un valor (ej. "Selecciona un modelo")
        puntosEstimadosSpan.textContent = '0'; // Asegurar que muestre 0
    }

    // Marcar el modal como inicializado para evitar re-inicializaciones
    modalElement.dataset.pointsInitialized = 'true';
};
// --- FIN NUEVO ---

document.addEventListener('DOMContentLoaded', function() {
    
    // --- LÓGICA DEL MENÚ MÓVIL (Estable) ---
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.querySelector('.ghd-sidebar');
    const closeSidebarBtn = document.getElementById('mobile-menu-close');

    if (menuToggle && sidebar) {
        const overlay = document.createElement('div');
        overlay.style.cssText = "position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:999;display:none;";
        document.body.appendChild(overlay);

        const closeMenu = () => { 
            sidebar.classList.remove('sidebar-visible'); 
            overlay.style.display = 'none'; 
            document.body.classList.remove('no-scroll');
        };
        const openMenu = (e) => { 
            e.stopPropagation(); 
            sidebar.classList.add('sidebar-visible'); 
            overlay.style.display = 'block'; 
            document.body.classList.add('no-scroll');
        };

        menuToggle.addEventListener('click', openMenu);
        overlay.addEventListener('click', closeMenu);
        
        if (closeSidebarBtn) {
            closeSidebarBtn.addEventListener('click', closeMenu);
        }
    }

    // --- Declaración de elementos de contadores de pestañas en un ámbito más amplio ---
    const tabCounterAssignation = document.getElementById('tab-counter-assignation');
    const tabCounterProduction = document.getElementById('tab-counter-production');
    const tabCounterClosure = document.getElementById('tab-counter-closure');

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

    const updateAdminAssignationKPIs = (totalPedidos) => {
        if (tabCounterAssignation) {
            tabCounterAssignation.textContent = totalPedidos;
        }
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
        if (tabCounterClosure) tabCounterClosure.textContent = kpiData.total_pedidos_cierre; // Actualizar contador de pestaña
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
        if (tabCounterProduction) tabCounterProduction.textContent = kpiData.total_pedidos_produccion; // Actualizar contador de pestaña
    };

    // --- Función para refrescar la lista de tareas del Fletero ---
    const refreshFleteroTasksList = () => {
        const fleteroTasksList = document.querySelector('.ghd-fletero-tasks-list');
        const refreshFleteroTasksBtn = document.getElementById('ghd-refresh-fletero-tasks');

        if (!fleteroTasksList || !refreshFleteroTasksBtn) {
            console.warn('refreshFleteroTasksList: Elementos DOM no encontrados. Posiblemente en otra página.');
            return;
        }

        fleteroTasksList.style.opacity = '0.5';
        refreshFleteroTasksBtn.disabled = true;

        const params = new URLSearchParams({
            action: 'ghd_refresh_fletero_tasks',
            nonce: ghd_ajax.nonce
        });

        fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
            .then(res => {
                if (!res.ok) { 
                    return res.text().then(text => { 
                        const errorMsg = `Server responded with ${res.status}: ${text || 'No response body'}`;
                        console.error("Error de carga HTTP en refresco de fletero:", errorMsg);
                        fleteroTasksList.innerHTML = `<p class="no-tasks-message" style="text-align: center; padding: 20px;">Error de carga: ${res.status}. Por favor, intente de nuevo.</p>`;
                        throw new Error(errorMsg); // Propagar el error para el .catch()
                    });
                }
                return res.json();
            })
            .then(response => { 
                if (response.success && typeof response.data.tasks_html === 'string') {
                    fleteroTasksList.innerHTML = response.data.tasks_html; 
                } else {
                    const errorMessage = response.data.message || 'La respuesta del servidor para refresco es incompleta.';
                    console.error('Error de refresco (respuesta inválida):', errorMessage, response);
                    fleteroTasksList.innerHTML = '<p class="no-tasks-message" style="text-align: center; padding: 20px;">Error al cargar tareas. Inténtalo de nuevo.</p>';
                }
            })
            .catch(error => {
                console.error("Error de red en refresco de fletero:", error);
                fleteroTasksList.innerHTML = `<p class="no-tasks-message" style="text-align: center; padding: 20px;">Error de red. Inténtalo de nuevo.</p>`;
            })
            .finally(() => {
                fleteroTasksList.style.opacity = '1';
                refreshFleteroTasksBtn.disabled = false;
            });
    }; // fin función refreshFleteroTasksList

    // --- Función para refrescar la tabla de Pedidos Pendientes de Asignación ---
    const refreshAssignationTable = () => {
        const assignationTableBody = document.getElementById('ghd-orders-table-body');
        const refreshAssignationBtn = document.getElementById('ghd-refresh-assignation-tasks');

        if (!assignationTableBody || !refreshAssignationBtn) {
            console.warn('refreshAssignationTable: Elementos DOM no encontrados.');
            return;
        }

        assignationTableBody.style.opacity = '0.5';
        refreshAssignationBtn.disabled = true;

        const params = new URLSearchParams({
            action: 'ghd_refresh_assignation_section', // Este es el nuevo endpoint AJAX en PHP
            nonce: ghd_ajax.nonce
        });

        fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
            .then(res => res.json())
            .then(response => {
                if (response.success && typeof response.data.table_html === 'string') {
                    assignationTableBody.innerHTML = response.data.table_html;
                    if (response.data.total_pedidos_asignacion !== undefined) {
                        updateAdminAssignationKPIs(response.data.total_pedidos_asignacion);
                    }
                    console.log('Refresco de asignación exitoso: ' + (response.data?.message || ''));
                } else {
                    console.error('Error al refrescar pedidos de asignación: ' + (response.data?.message || 'Error desconocido.'));
                    assignationTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Error al cargar pedidos de asignación.</td></tr>';
                }
            })
            .catch(error => {
                console.error("Error de red al refrescar asignación:", error);
                assignationTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Error de red. Inténtalo de nuevo.</td></tr>';
            })
            .finally(() => {
                assignationTableBody.style.opacity = '1';
                refreshAssignationBtn.disabled = false;
            });
    };

    // --------------lógica para buscadores --------------   
      // --- LÓGICA PARA EL BUSCADOR EN LA PESTAÑA DE ASIGNACIÓN ---
    const searchAssignationInput = document.getElementById('ghd-search-assignation');
    // const applySearchAssignationBtn = document.getElementById('ghd-apply-search-assignation'); // ELIMINAR ESTA LÍNEA
    const resetSearchAssignationBtn = document.getElementById('ghd-reset-search-assignation');
    const assignationTableBody = document.getElementById('ghd-orders-table-body');

    let searchAssignationTimeout; // Variable para el debounce

    // Función para realizar la búsqueda AJAX
    const performAssignationSearch = () => {
        if (!assignationTableBody) return;

        const searchTerm = searchAssignationInput.value.trim();
        assignationTableBody.style.opacity = '0.5';
        // applySearchAssignationBtn.disabled = true; // ELIMINAR ESTA LÍNEA
        resetSearchAssignationBtn.disabled = true;

        const params = new URLSearchParams({
            action: 'ghd_search_assignation_orders', // Endpoint AJAX
            nonce: ghd_ajax.nonce,
            search_term: searchTerm
        });

        fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
            .then(res => res.json())
            .then(response => {
                if (response.success && typeof response.data.table_html === 'string') {
                    assignationTableBody.innerHTML = response.data.table_html;
                    console.log('Búsqueda de asignación exitosa: ' + (response.data?.message || ''));
                } else {
                    console.error('Error al buscar pedidos de asignación: ' + (response.data?.message || 'Error desconocido.'));
                    assignationTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No se encontraron pedidos con el término de búsqueda.</td></tr>';
                }
            })
            .catch(error => {
                console.error("Error de red al buscar asignación:", error);
                assignationTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Error de red. Inténtalo de nuevo.</td></tr>';
            })
            .finally(() => {
                assignationTableBody.style.opacity = '1';
                // applySearchAssignationBtn.disabled = false; // ELIMINAR ESTA LÍNEA
                resetSearchAssignationBtn.disabled = false;
            });
    };

    // Event Listeners para el buscador
    // ELIMINAR ESTE BLOQUE:
    // if (applySearchAssignationBtn) {
    //     applySearchAssignationBtn.addEventListener('click', performAssignationSearch);
    // }

    if (searchAssignationInput) {
        searchAssignationInput.addEventListener('keyup', function(e) {
            clearTimeout(searchAssignationTimeout); // Limpiar el timeout anterior
            searchAssignationTimeout = setTimeout(() => {
                performAssignationSearch();
            }, 300); // Esperar 300ms después de la última pulsación
        });
    }

    if (resetSearchAssignationBtn) {
        resetSearchAssignationBtn.addEventListener('click', function() {
            searchAssignationInput.value = ''; // Limpiar el campo de búsqueda
            clearTimeout(searchAssignationTimeout); // Limpiar cualquier búsqueda pendiente
            performAssignationSearch(); // Realizar una búsqueda vacía para mostrar todos los pedidos
        });
    }
    // --- FIN LÓGICA PARA EL BUSCADOR EN LA PESTAÑA DE ASIGNACIÓN ---
     
    // --- LÓGICA PARA EL BUSCADOR EN LA PESTAÑA DE PRODUCCIÓN ---
    const searchProductionInput = document.getElementById('ghd-search-production');
    const applySearchProductionBtn = document.getElementById('ghd-apply-search-production');
    const resetSearchProductionBtn = document.getElementById('ghd-reset-search-production');
    const productionTableBody = document.getElementById('ghd-production-table-body');
    const productionKPIsContainer = document.querySelector('#tab-production .ghd-kpi-grid'); // Contenedor de KPIs de producción

    let searchProductionTimeout; // Variable para el debounce

    // Función para realizar la búsqueda AJAX en Producción
    const performProductionSearch = () => {
        if (!productionTableBody || !productionKPIsContainer) return;

        const searchTerm = searchProductionInput.value.trim();
        productionTableBody.style.opacity = '0.5';
        productionKPIsContainer.style.opacity = '0.5'; // También atenuar KPIs
        applySearchProductionBtn.disabled = true;
        resetSearchProductionBtn.disabled = true;

        const params = new URLSearchParams({
            action: 'ghd_search_production_orders', // Nuevo endpoint AJAX
            nonce: ghd_ajax.nonce,
            search_term: searchTerm
        });

        fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
            .then(res => res.json())
            .then(response => {
                if (response.success && typeof response.data.table_html === 'string') {
                    productionTableBody.innerHTML = response.data.table_html;
                    if (response.data.kpi_data) {
                        updateAdminProductionKPIs(response.data.kpi_data); // Actualizar KPIs
                    }
                    console.log('Búsqueda de producción exitosa: ' + (response.data?.message || ''));
                } else {
                    console.error('Error al buscar pedidos en producción: ' + (response.data?.message || 'Error desconocido.'));
                    productionTableBody.innerHTML = '<tr><td colspan="11" style="text-align:center;">No se encontraron pedidos con el término de búsqueda.</td></tr>';
                    // Resetear KPIs si no hay resultados
                    updateAdminProductionKPIs({
                        total_pedidos_produccion: 0,
                        total_prioridad_alta_produccion: 0,
                        completadas_hoy_produccion: 0,
                        tiempo_promedio_str_produccion: '0.0h'
                    });
                }
            })
            .catch(error => {
                console.error("Error de red al buscar producción:", error);
                productionTableBody.innerHTML = '<tr><td colspan="11" style="text-align:center;">Error de red. Inténtalo de nuevo.</td></tr>';
                // Resetear KPIs en caso de error de red
                updateAdminProductionKPIs({
                    total_pedidos_produccion: 0,
                    total_prioridad_alta_produccion: 0,
                    completadas_hoy_produccion: 0,
                    tiempo_promedio_str_produccion: '0.0h'
                });
            })
            .finally(() => {
                productionTableBody.style.opacity = '1';
                productionKPIsContainer.style.opacity = '1';
                applySearchProductionBtn.disabled = false;
                resetSearchProductionBtn.disabled = false;
            });
    };

    // Event Listeners para el buscador de Producción
    if (applySearchProductionBtn) {
        applySearchProductionBtn.addEventListener('click', performProductionSearch);
    }

    if (searchProductionInput) {
        searchProductionInput.addEventListener('keyup', function(e) {
            clearTimeout(searchProductionTimeout); // Limpiar el timeout anterior
            searchProductionTimeout = setTimeout(() => {
                performProductionSearch();
            }, 300); // Esperar 300ms después de la última pulsación
        });
    }

    if (resetSearchProductionBtn) {
        resetSearchProductionBtn.addEventListener('click', function() {
            searchProductionInput.value = ''; // Limpiar el campo de búsqueda
            clearTimeout(searchProductionTimeout); // Limpiar cualquier búsqueda pendiente
            // Llamar a la función de refresco existente para recargar la tabla completa y KPIs
            const refreshProductionTasksBtn = document.getElementById('ghd-refresh-production-tasks');
            if (refreshProductionTasksBtn) {
                refreshProductionTasksBtn.click();
            } else {
                performProductionSearch(); // Fallback si el botón de refresco no existe
            }
        });
    }
    // --- FIN LÓGICA PARA EL BUSCADOR EN LA PESTAÑA DE PRODUCCIÓN ---

    // Bloque a reemplazar en app.js
// Ubicación: Dentro de document.addEventListener('DOMContentLoaded', function() { ... });
//            En la sección de lógica del buscador de la pestaña de cierre.

    // --- LÓGICA PARA EL BUSCADOR EN LA PESTAÑA DE CIERRE ---
    const searchClosureInput = document.getElementById('ghd-search-closure');
    // const applySearchClosureBtn = document.getElementById('ghd-apply-search-closure'); // ELIMINAR ESTA LÍNEA
    const resetSearchClosureBtn = document.getElementById('ghd-reset-search-closure');
    const closureTableBody = document.getElementById('ghd-closure-table-body');
    const closureKPIsContainer = document.querySelector('#tab-closure .ghd-kpi-grid'); // Contenedor de KPIs de cierre

    let searchClosureTimeout; // Variable para el debounce

    // Función para realizar la búsqueda AJAX en Cierre
    const performClosureSearch = () => {
        if (!closureTableBody || !closureKPIsContainer) return;

        const searchTerm = searchClosureInput.value.trim();
        closureTableBody.style.opacity = '0.5';
        closureKPIsContainer.style.opacity = '0.5'; // También atenuar KPIs
        // applySearchClosureBtn.disabled = true; // ELIMINAR ESTA LÍNEA
        resetSearchClosureBtn.disabled = true; // Solo deshabilitar el botón Limpiar

        const params = new URLSearchParams({
            action: 'ghd_search_closure_orders', // Endpoint AJAX
            nonce: ghd_ajax.nonce,
            search_term: searchTerm
        });

        fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
            .then(res => res.json())
            .then(response => {
                if (response.success && typeof response.data.table_html === 'string') {
                    closureTableBody.innerHTML = response.data.table_html;
                    if (response.data.kpi_data) {
                        updateAdminClosureKPIs(response.data.kpi_data); // Actualizar KPIs
                    }
                    console.log('Búsqueda de cierre exitosa: ' + (response.data?.message || ''));
                } else {
                    console.error('Error al buscar pedidos de cierre: ' + (response.data?.message || 'Error desconocido.'));
                    closureTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No se encontraron pedidos con el término de búsqueda.</td></tr>';
                    // Resetear KPIs si no hay resultados
                    updateAdminClosureKPIs({
                        total_pedidos_cierre: 0,
                        total_prioridad_alta_cierre: 0,
                        completadas_hoy_cierre: 0,
                        tiempo_promedio_str_cierre: '0.0h'
                    });
                }
            })
            .catch(error => {
                console.error("Error de red al buscar cierre:", error);
                closureTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Error de red. Inténtalo de nuevo.</td></tr>';
                // Resetear KPIs en caso de error de red
                updateAdminClosureKPIs({
                    total_pedidos_cierre: 0,
                    total_prioridad_alta_cierre: 0,
                    completadas_hoy_cierre: 0,
                    tiempo_promedio_str_cierre: '0.0h'
                });
            })
            .finally(() => {
                closureTableBody.style.opacity = '1';
                closureKPIsContainer.style.opacity = '1';
                // applySearchClosureBtn.disabled = false; // ELIMINAR ESTA LÍNEA
                resetSearchClosureBtn.disabled = false; // Solo habilitar el botón Limpiar
            });
    };

    // Event Listeners para el buscador de Cierre
    // ELIMINAR ESTE BLOQUE:
    // if (applySearchClosureBtn) {
    //     applySearchClosureBtn.addEventListener('click', performClosureSearch);
    // }

    if (searchClosureInput) {
        searchClosureInput.addEventListener('keyup', function(e) {
            clearTimeout(searchClosureTimeout); // Limpiar el timeout anterior
            searchClosureTimeout = setTimeout(() => {
                performClosureSearch();
            }, 300); // Esperar 300ms después de la última pulsación
        });
    }

    if (resetSearchClosureBtn) {
        resetSearchClosureBtn.addEventListener('click', function() {
            searchClosureInput.value = ''; // Limpiar el campo de búsqueda
            clearTimeout(searchClosureTimeout); // Limpiar cualquier búsqueda pendiente
            // Llamar a la función de refresco existente para recargar la tabla completa y KPIs
            const refreshClosureTasksBtn = document.getElementById('ghd-refresh-closure-tasks');
            if (refreshClosureTasksBtn) {
                refreshClosureTasksBtn.click();
            } else {
                performClosureSearch(); // Fallback si el botón de refresco no existe
            }
        });
    }
    // --- FIN LÓGICA PARA EL BUSCADOR EN LA PESTAÑA DE CIERRE ---
    
     // --- LÓGICA PARA EL BUSCADOR EN PANELES DE SECTOR ---
    const searchSectorInput = document.getElementById('ghd-search-sector');
    const resetSectorSearchBtn = document.getElementById('ghd-reset-search-sector');
    const sectorTasksList = document.querySelector('.ghd-sector-tasks-list');
    const sectorKPIsContainer = document.querySelector('.ghd-kpi-grid'); // Contenedor de KPIs del sector

    let searchSectorTimeout; // Variable para el debounce

    // Función para realizar la búsqueda AJAX en Paneles de Sector
    const performSectorSearch = () => {
        if (!sectorTasksList || !sectorKPIsContainer) return;

        const searchTerm = searchSectorInput.value.trim();
        const campoEstado = mainContent.dataset.campoEstado || ''; // Obtener el campo de estado del sector
        if (!campoEstado) {
            console.error('performSectorSearch: campoEstado no definido para el sector.');
            return;
        }

        sectorTasksList.style.opacity = '0.5';
        sectorKPIsContainer.style.opacity = '0.5'; // También atenuar KPIs
        resetSectorSearchBtn.disabled = true;

        const params = new URLSearchParams({
            action: 'ghd_search_sector_tasks', // Nuevo endpoint AJAX
            nonce: ghd_ajax.nonce,
            search_term: searchTerm,
            campo_estado: campoEstado // Enviar el campo de estado para filtrar por sector
        });

        fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
            .then(res => res.json())
            .then(response => {
                if (response.success && typeof response.data.tasks_html === 'string') {
                    sectorTasksList.innerHTML = response.data.tasks_html;
                    if (response.data.kpi_data) {
                        updateSectorKPIs(response.data.kpi_data); // Actualizar KPIs
                    }
                    console.log('Búsqueda de sector exitosa: ' + (response.data?.message || ''));
                } else {
                    console.error('Error al buscar tareas de sector: ' + (response.data?.message || 'Error desconocido.'));
                    sectorTasksList.innerHTML = '<p class="no-tasks-message">No se encontraron tareas con el término de búsqueda.</p>';
                    // Resetear KPIs si no hay resultados
                    updateSectorKPIs({
                        total_pedidos: 0,
                        total_prioridad_alta: 0,
                        completadas_hoy: 0,
                        tiempo_promedio_str: '0.0h'
                    });
                }
            })
            .catch(error => {
                console.error("Error de red al buscar sector:", error);
                sectorTasksList.innerHTML = '<p class="no-tasks-message">Error de red. Inténtalo de nuevo.</p>';
                // Resetear KPIs en caso de error de red
                updateSectorKPIs({
                    total_pedidos: 0,
                    total_prioridad_alta: 0,
                    completadas_hoy: 0,
                    tiempo_promedio_str: '0.0h'
                });
            })
            .finally(() => {
                sectorTasksList.style.opacity = '1';
                sectorKPIsContainer.style.opacity = '1';
                resetSectorSearchBtn.disabled = false;
            });
    };

    // Event Listeners para el buscador de Sector
    if (searchSectorInput) {
        searchSectorInput.addEventListener('keyup', function(e) {
            clearTimeout(searchSectorTimeout); // Limpiar el timeout anterior
            searchSectorTimeout = setTimeout(() => {
                performSectorSearch();
            }, 300); // Esperar 300ms después de la última pulsación
        });
    }

    if (resetSectorSearchBtn) {
        resetSectorSearchBtn.addEventListener('click', function() {
            searchSectorInput.value = ''; // Limpiar el campo de búsqueda
            clearTimeout(searchSectorTimeout); // Limpiar cualquier búsqueda pendiente
            // Llamar a la función de refresco existente para recargar la tabla completa y KPIs
            const refreshTasksBtn = document.getElementById('ghd-refresh-tasks');
            if (refreshTasksBtn) {
                refreshTasksBtn.click();
            } else {
                performSectorSearch(); // Fallback si el botón de refresco no existe
            }
        });
    }
    // --- FIN LÓGICA PARA EL BUSCADOR EN PANELES DE SECTOR ---
    //------------------- fin lógica buscadores -------------------------------

    // --- LÓGICA UNIVERSAL DE EVENTOS EN EL CONTENIDO PRINCIPAL ---
    const mainContent = document.querySelector('.ghd-main-content');
    if (mainContent) {

        const fleteroTasksList = document.querySelector('.ghd-fletero-tasks-list');
        // const refreshFleteroTasksBtn = document.getElementById('ghd-refresh-fletero-tasks'); // Ya declarado arriba

        // --- Lógica para Pestañas (Tabs) usando delegación de eventos ---
        mainContent.addEventListener('click', function(e) {
            const tabButton = e.target.closest('.ghd-tab-button');
            if (tabButton) {
                e.preventDefault();
                const container = tabButton.closest('.ghd-tabs-container');
                if (!container) return;

                const tabButtons = container.querySelectorAll('.ghd-tab-button');
                const tabContents = container.querySelectorAll('.ghd-tab-content');

                // Remover clase 'active' de todos los botones y contenidos
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));

                // Añadir clase 'active' al botón clickeado
                tabButton.classList.add('active');

                // Mostrar el contenido correspondiente
                const targetTabId = tabButton.dataset.tab;
                const targetContent = container.querySelector(`#tab-${targetTabId}`);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
            }
        });
        // --- FIN Lógica para Pestañas (Tabs) usando delegación de eventos ---
        
        // Listener para todos los CLICKS dentro de mainContent
        mainContent.addEventListener('click', function(e) {
            
            const target = e.target;
            const actionButton = target.closest('.action-button');
            const openModalButton = target.closest('.open-complete-task-modal');
            const closeModalButton = target.closest('.close-button');
            const refreshTasksBtn = target.closest('#ghd-refresh-tasks'); // Refrescar panel de sector general
            const archiveBtn = target.closest('.archive-order-btn'); // Archivar pedido (Admin/Macarena)

            // Botones de panel de Fletero
            const fleteroActionButton = target.closest('.fletero-action-btn'); // Marcar Recogido / Marcar Entregado
            const openUploadDeliveryProofModalBtn = target.closest('.open-upload-delivery-proof-modal'); // Abre modal de comprobante de fletero

            // Otros botones
            const exportAssignationOrdersBtn = target.closest('#ghd-export-assignation-orders'); // Botón de exportar
            const abrirModalBtn = target.closest('#abrir-nuevo-pedido-modal'); // Botón "Nuevo Pedido"
            const refreshAssignationBtn = target.closest('#ghd-refresh-assignation-tasks'); // Botón "Refrescar" de asignación

            // --- MANEJADORES DE CLICKS ---

            // Lógica para abrir el modal de Nuevo Pedido
            if (abrirModalBtn) {
                e.preventDefault();
                const nuevoPedidoModal = document.getElementById('nuevo-pedido-modal');
                if (nuevoPedidoModal) {
                    nuevoPedidoModal.style.display = 'flex';
                }
            }

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
                    .then(response => { 
                        if (response.success && response.data && typeof response.data.tasks_html === 'string') {
                            sectorTasksList.innerHTML = response.data.tasks_html; 
                            if (response.data.kpi_data) updateSectorKPIs(response.data.kpi_data);
                            console.log('Refresco de tareas de sector exitoso: ' + (response.data?.message || ''));

                            const openEmbalajeModals = sectorTasksList.querySelectorAll('.open-complete-task-modal[data-field="estado_embalaje"]');
                            openEmbalajeModals.forEach(btn => {
                                const orderId = btn.dataset.orderId;
                                const modal = document.getElementById(`complete-task-modal-${orderId}`);
                                if (modal && typeof initEmbalajeModalPoints === 'function') {
                                    initEmbalajeModalPoints(modal, orderId);
                                }
                            });

                        } else {
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
                    .then(response => { 
                        if (response.success) {
                            document.getElementById('ghd-refresh-tasks')?.click();
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
                    
                    const orderId = openModalButton.dataset.orderId;
                    const fieldEstado = openModalButton.dataset.field; 
                    if (fieldEstado === 'estado_embalaje' && typeof initEmbalajeModalPoints === 'function') {
                        initEmbalajeModalPoints(modal, orderId);
                    }
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
                    .then(response => { 
                        if (response.success) {
                            container.remove();
                            if (response.data && response.data.kpi_data) {
                                updateAdminClosureKPIs(response.data.kpi_data);
                            }
                            // Actualizar contador de la pestaña de cierre
                            // const currentClosureCount = parseInt(tabCounterClosure?.textContent || '0');
                            // updateAdminClosureKPIs({ 
                            //     total_pedidos_cierre: currentClosureCount - 1,
                            //     total_prioridad_alta_cierre: 0, 
                            //     completadas_hoy_cierre: 0,
                            //     tiempo_promedio_str_cierre: '0h'
                            // });
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
            const refreshFleteroTasksBtn = document.getElementById('ghd-refresh-fletero-tasks');
            if (refreshFleteroTasksBtn && target.closest('#ghd-refresh-fletero-tasks')) { 
                e.preventDefault();
                refreshFleteroTasksList(); 
            }

            // --- Lógica para el botón "Marcar como Recogido" del Fletero ---
            if (fleteroActionButton && !openUploadDeliveryProofModalBtn) {
                e.preventDefault();
                const orderId = fleteroActionButton.dataset.orderId;
                const newStatus = fleteroActionButton.dataset.newStatus;
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
                        console.log('Pedido marcado como recogido: ' + (response.data?.message || '')); 
                    } else {
                        alert('Error: ' + (response.data?.message || 'Error desconocido al marcar como recogido.'));
                    }
                })
                .catch(error => {
                    console.error("Error de red al marcar como recogido:" + error);
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
            
            // --- Lógica para el botón "Refrescar" en la sección de pedidos en asignación del admin ---
            const refreshAssignationBtnElement = document.getElementById('ghd-refresh-assignation-tasks'); 
            if (refreshAssignationBtnElement && target.closest('#ghd-refresh-assignation-tasks')) { 
                e.preventDefault();
                refreshAssignationTable(); // Llama a la nueva función de refresco
            }

        }); // fin listener clicks 

        // Listener para los SUBMITS (envío de formularios)
        mainContent.addEventListener('submit', function(e) {
            const completeTaskForm = e.target.closest('.complete-task-form'); 
            const completeDeliveryForm = e.target.closest('.complete-delivery-form'); 
            const nuevoPedidoForm = e.target.closest('#nuevo-pedido-form'); 


            if (completeTaskForm) {
                e.preventDefault();
                const orderId = completeTaskForm.dataset.orderId;
                const fieldEstadoSector = completeTaskForm.dataset.field; 

                const formData = new FormData(completeTaskForm);
                formData.append('action', 'ghd_register_task_details_and_complete');
                formData.append('nonce', ghd_ajax.nonce);
                formData.append('order_id', orderId); 
                formData.append('field', fieldEstadoSector); 

                const submitButton = completeTaskForm.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
                } 

                fetch(ghd_ajax.ajax_url, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(response => { 
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
                .then(res => {
                    if (!res.ok) { 
                        return res.text().then(text => { 
                            const errorMsg = `Server responded with ${res.status}: ${text || 'No response body'}`;
                            console.error("Error de carga HTTP al completar entrega:", errorMsg);
                            throw new Error(errorMsg); 
                        });
                    }
                    return res.json();
                })
                .then(response => { 
                    if (response.success) {
                        const modal = document.getElementById(`upload-delivery-proof-modal-${orderId}`);
                        if (modal) {
                            modal.style.display = 'none';
                            completeDeliveryForm.reset();
                        }
                        console.log('Entrega completada (éxito): ' + (response.data?.message || ''));
                        refreshFleteroTasksList(); 
                        
                        const refreshClosureBtn = document.getElementById('ghd-refresh-closure-tasks');
                        if (refreshClosureBtn && document.body.classList.contains('is-admin-dashboard-panel')) {
                            refreshClosureBtn.click(); 
                        }
                    } else {
                        console.error('Error al completar entrega (server success=false): ' + (response.data?.message || 'Error desconocido del servidor.'));
                        alert('Error: ' + (response.data?.message || 'No se pudo completar la entrega.')); 
                    }
                })
                .catch(error => {
                    console.error("Error de red al completar entrega:", error); 
                    alert('Error de red al completar entrega. Por favor, intente de nuevo.'); 
                })
                .finally(() => {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="fa-solid fa-check"></i> Marcar como Entregado';
                    }
                    completeDeliveryForm.style.opacity = '1';
                });
            } // fin if (completeDeliveryForm)


            // --- Lógica para el SUBMIT del formulario de NUEVO PEDIDO (Admin) ---
            if (nuevoPedidoForm) {
                e.preventDefault();

                const submitButton = nuevoPedidoForm.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creando...';

                const formData = new FormData(nuevoPedidoForm);
                formData.append('action', 'ghd_crear_nuevo_pedido');
                formData.append('nonce', ghd_ajax.nonce);

                fetch(ghd_ajax.ajax_url, {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                })
                .then(res => res.json())
                .then(response => { 
                    if (response.success) {
                        alert(response.data.message);
                        const nuevoPedidoModal = document.getElementById('nuevo-pedido-modal'); 
                        if (nuevoPedidoModal) nuevoPedidoModal.style.display = 'none';
                        this.reset();

                        const tableBody = document.getElementById('ghd-orders-table-body');
                        if (tableBody) {
                            const noOrdersRow = tableBody.querySelector('td[colspan="6"]');
                            if (noOrdersRow) {
                                tableBody.innerHTML = response.data.new_row_html;
                            } else {
                                tableBody.insertAdjacentHTML('afterbegin', response.data.new_row_html);
                            }
                            // Actualizar contador de la pestaña de asignación
                            const currentAssignationCount = parseInt(tabCounterAssignation?.textContent || '0');
                            updateAdminAssignationKPIs(currentAssignationCount + 1);
                        } 
                    } else {
                        alert('Error: ' + (response.data?.message || 'No se pudo crear el pedido.'));
                    }
                })
                .finally(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fa-solid fa-plus"></i> Crear Pedido';
                });
            } // fin if (nuevoPedidoForm)

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
                updateStartProductionButtonState(row); 
                
                const prioritySelector = row.querySelector('.ghd-priority-selector');
                if (prioritySelector && prioritySelector.value !== 'Seleccionar Prioridad') {
                    prioritySelector.classList.remove('prioridad-no-seleccionada');
                    if (prioritySelector.value === 'Alta') {
                        prioritySelector.classList.add('prioridad-alta');
                    } else if (prioritySelector.value === 'Media') {
                        prioritySelector.classList.add('prioridad-media');
                    } else if (prioritySelector.value === 'Baja') {
                        prioritySelector.classList.add('prioridad-baja');
                    }
                }

                const vendedoraSelector = row.querySelector('.ghd-vendedora-selector');
                if (vendedoraSelector && vendedoraSelector.value !== '0') { 
                    vendedoraSelector.classList.remove('vendedora-no-seleccionada');
                }
            });

            // Manejar cambios en los selectores (Prioridad y Vendedora)
            ordersTableBody.addEventListener('change', function(e) {
                const target = e.target;
                const row = target.closest('tr');

                if (target.classList.contains('ghd-priority-selector')) {
                    const orderId = target.dataset.orderId;
                    const selectedPriority = target.value;
                    
                    updateStartProductionButtonState(row);
                    
                    target.classList.remove('prioridad-alta', 'prioridad-media', 'prioridad-baja');
                    if (selectedPriority === 'Alta') {
                        target.classList.add('prioridad-alta');
                    } else if (selectedPriority === 'Media') {
                        target.classList.add('prioridad-media');
                    } else if (selectedPriority === 'Baja') {
                        target.classList.add('prioridad-baja');
                    }

                    const params = new URLSearchParams({
                        action: 'ghd_update_priority',
                        nonce: ghd_ajax.nonce,
                        order_id: orderId,
                        priority: selectedPriority
                    });

                    fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                        .then(res => res.json())
                        .then(response => { 
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
                    
                    updateStartProductionButtonState(row);

                    const params = new URLSearchParams({
                        action: 'ghd_update_vendedora',
                        nonce: ghd_ajax.nonce,
                        order_id: orderId,
                        vendedora_id: selectedVendedoraId
                    });

                    fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                        .then(res => res.json())
                        .then(response => { 
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

        mainContent.addEventListener('change', function(e) { 
            const assigneeSelector = e.target.closest('.ghd-assignee-selector');
            if (assigneeSelector) {
                const orderId = assigneeSelector.dataset.orderId;
                const fieldPrefix = assigneeSelector.dataset.fieldPrefix;
                const selectedAssigneeId = assigneeSelector.value;
                
                const card = assigneeSelector.closest('.ghd-order-card');
                if (card) card.style.opacity = '0.5';

                const params = new URLSearchParams({
                    action: 'ghd_assign_task_to_member',
                    nonce: ghd_ajax.nonce,
                    order_id: orderId,
                    field_prefix: fieldPrefix,
                    assignee_id: selectedAssigneeId
                });

                fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                    .then(res => res.json())
                    .then(response => { 
                        if (response.success) {
                            console.log('Operario asignado:', response.data.message);
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
                        if (card) card.style.opacity = '1';
                    });
            }
        }); 

            // --- Manejar click en "Iniciar Producción" ---
            ordersTableBody.addEventListener('click', function(e) {
                const startProductionBtn = e.target.closest('.start-production-btn');
                if (startProductionBtn && !startProductionBtn.disabled) {
                    e.preventDefault();

                    const orderId = startProductionBtn.dataset.orderId;
                    const row = startProductionBtn.closest('tr');

                    if (!confirm('¿Estás seguro de que quieres iniciar la producción de este pedido? Esta acción moverá el pedido a producción.')) {
                        return;
                    }

                    row.style.opacity = '0.5';
                    startProductionBtn.disabled = true;
                    startProductionBtn.textContent = 'Iniciando...';

                    const params = new URLSearchParams({
                        action: 'ghd_start_production',
                        nonce: ghd_ajax.nonce,
                        order_id: orderId
                    });

                    fetch(ghd_ajax.ajax_url, { method: 'POST', body: params })
                        .then(res => res.json())
                        .then(response => { 
                            if (response.success) {
                                alert(response.data.message);
                                row.remove();

                                // Disminuir contador de asignación
                                const currentAssignationCount = parseInt(tabCounterAssignation?.textContent || '0');
                                updateAdminAssignationKPIs(currentAssignationCount - 1);

                                // Refrescar la tabla de producción y sus KPIs (que ya actualiza el contador de producción)
                                const refreshProductionTasksBtn = document.getElementById('ghd-refresh-production-tasks');
                                if (refreshProductionTasksBtn) {
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
                    .then(response => { 
                        if (response.success) { 
                            sectorTasksList.innerHTML = response.data.tasks_html;
                            if (response.data.kpi_data) {
                                updateSectorKPIs(response.data.kpi_data);
                            }
                            console.log('Refresco de tareas de sector exitoso: ' + (response.data?.message || ''));
                        } else {
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
                .then(response => { 
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
                .then(response => { 
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
            .then(res => res.json()) 
            .then(response => { 
                if (response.success) {
                    archivedOrdersTableBody.innerHTML = response.data.table_html;
                    console.log('Refresco de pedidos archivados exitoso: ' + (response.data?.message || ''));
                } else {
                    console.error('Error al refrescar pedidos archivados: ' + (response.data?.message || ''));
                    archivedOrdersTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Error al cargar pedidos archivados.</td></tr>';
                }
            })
            .catch(error => {
                console.error("Error de red al refrescar pedidos archivados:", error);
                archivedOrdersTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Error de red. Inténtalo de nuevo.</td></tr>';
            })
            .finally(() => {
                archivedOrdersTableBody.style.opacity = '1';
                refreshArchivedOrdersBtn.disabled = false;
            });
        });
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
                action: 'ghd_export_orders_csv',
                nonce: ghd_ajax.nonce,
                export_type: exportType
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
    const nuevoPedidoModal = document.getElementById('nuevo-pedido-modal');
    const nuevoPedidoForm = document.getElementById('nuevo-pedido-form');

    if (nuevoPedidoModal) { 
        const closeModalBtn = nuevoPedidoModal.querySelector('.close-button');
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', function() {
                nuevoPedidoModal.style.display = 'none';
                if (nuevoPedidoForm) nuevoPedidoForm.reset(); 
            });
        }
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