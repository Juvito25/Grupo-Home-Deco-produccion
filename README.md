# Gestor de Flujo de Producción - Grupo Home Deco

<img src="./img/sello.png" alt="Texto alternativo" width="100" /> 
<img src="./img/2.png" alt="Texto alternativo" width="200"/>

Sistema de gestión interna diseñado a medida para **Grupo Home Deco**, una fábrica de sillones. La aplicación centraliza y supervisa el ciclo de vida completo de los pedidos, desde su recepción hasta la entrega final, optimizando la comunicación entre los diferentes sectores de la fábrica.

Este proyecto está construido como un tema hijo de WordPress, utilizando una arquitectura de aplicación para proporcionar una experiencia de panel de control (dashboard) robusta y a medida.

---

## 🚀 Características Principales

*   **Panel de Administrador Centralizado:**
    *   Visualización de todos los pedidos entrantes.
    *   Asignación de **prioridad** (Alta, Media, Baja) a cada pedido.
    *   Asignación del **primer sector** de producción (Carpintería, Costura, etc.) con un solo clic.
    *   Interfaz responsive para gestión desde cualquier dispositivo.

*   **Paneles Dedicados por Sector:**
    *   Vistas personalizadas para **Carpintería, Costura, Tapicería y Logística**.
    *   Cada trabajador solo ve las tareas que le han sido asignadas.
    *   Funcionalidad para marcar una tarea como completada y mover el pedido automáticamente al **siguiente sector** en la línea de producción.

*   **Integración con WooCommerce:**
    *   (Próximamente) Captura automática de pedidos desde las tiendas online `emberhomedeco.com` y `santinointeriores.com` a través de webhooks y n8n.

*   **Automatización y Notificaciones:**
    *   (Próximamente) Notificaciones automáticas por Email/WhatsApp al cliente en cada cambio de estado importante (ej: "Tu pedido ha entrado en Tapicería").

---

## 🛠️ Pila Tecnológica (Stack)

*   **Backend:** WordPress 6.x, PHP 8.x
*   **Frontend:** HTML5, CSS3 (con variables CSS), JavaScript (Vanilla JS)
*   **Base de Datos:** MySQL (gestionada por WordPress)
*   **Plugins Esenciales:**
    *   [Advanced Custom Fields (ACF)](https://wordpress.org/plugins/advanced-custom-fields/): Para la creación de campos personalizados.
    *   [Custom Post Type UI (CPT UI)](https://wordpress.org/plugins/custom-post-type-ui/): Para la gestión de Tipos de Contenido Personalizado (`orden_produccion`).
    *   [User Role Editor](https://wordpress.org/plugins/user-role-editor/): Para la creación de perfiles de usuario por sector.
*   **Tema Padre:** [Hello Elementor](https://wordpress.org/themes/hello-elementor/) (utilizado como un lienzo en blanco).
*   **Librerías Externas:**
    *   [Font Awesome](https://fontawesome.com/): Para la iconografía de la interfaz.

---

## ⚙️ Instalación y Configuración

Este proyecto es un tema hijo de WordPress y requiere un entorno WordPress funcional.

1.  **Prerrequisitos:**
    *   Un servidor web local (ej: [WordPress Studio](https://developer.wordpress.org/playground/), LocalWP, XAMPP) o en producción.
    *   Una instalación limpia de WordPress.
    *   El tema padre **"Hello Elementor"** instalado (no necesita estar activo).
    *   Los plugins esenciales mencionados arriba instalados y activados.

2.  **Instalación del Tema:**
    *   Clona este repositorio o descarga el archivo ZIP.
    *   Sube la carpeta del tema a tu directorio `wp-content/themes/`.
    *   Ve a **Apariencia > Temas** en tu panel de WordPress y activa el tema **"Grupo Home Deco - Producción"**.

3.  **Configuración Post-Instalación:**
    *   Crea las páginas necesarias en WordPress y asígnales las plantillas correspondientes:
        *   **Página "Panel de Control"**: Asignar la plantilla `GHD - Panel de Administrador`.
        *   **Página "Mi Puesto"**: Asignar la plantilla `GHD - Panel de Sector`.
    *   Crea los roles de usuario para cada sector (`rol_carpinteria`, `rol_costura`, etc.) usando el plugin User Role Editor.
    *   Crea usuarios de prueba y asígnales los roles correspondientes para probar las vistas de sector.

---

##  flowchart LR
    subgraph Flujo de un Pedido
        A[WooCommerce] -->|Nuevo Pedido| B(Panel de Admin);
        B -->|Asigna Sector| C{Producción};
        subgraph Producción
            C1[Carpintería] --> C2[Costura];
            C2 --> C3[Tapicería];
            C3 --> C4[Logística];
        end
        C --> D[Entrega al Cliente];
    end


---
*Desarrollado por [Juvi Web | Automatizaciones y Desarrollo web](https://instagram.com/juviweb)*