# Especificación 005: Sistema Centralizado de Gestión de Catálogos (Taxes, Categories, etc.)

## 1. Objetivo
Diseñar e implementar un sistema unificado y escalable para la administración de tablas auxiliares o catálogos maestros del sistema (ej. Impuestos, Categorías de Productos, Métodos de Pago y en el futuro Ciudades, Países, etc.) siguiendo las mejores prácticas de experiencia de usuario (UX/UI).

El flujo de trabajo propuesto combina un **Panel Central de Configuración** para mantenimiento masivo, junto con **Modales de Creación Rápida en Contexto** para agilizar los flujos de trabajo principales.

---

## 2. Propuesta de Experiencia de Usuario (UX/UI Flow)

De acuerdo a las mejores prácticas de diseño de interfaces empresariales, utilizaremos un **diseño híbrido**:

### A. Panel Central de Catálogos (Configuración)
- **Ruta Única**: `/settings/catalogs` (para no saturar el menú lateral principal con una sección por cada pequeña tabla).
- **Interfaz Multitesta (Tabs)**: Una barra de navegación horizontal de pestañas para cambiar rápidamente entre catálogos:
  - Pestaña 1: **Impuestos** (Taxes)
  - Pestaña 2: **Categorías** (Categories)
  - Pestaña 3: **Métodos de Pago** (Payment Methods)
  - Pestañas Futuras: **Ciudades**, **Países**, etc.
- **Acciones Estándar (CRUD)**:
  - Tabla limpia con buscador y paginación.
  - Botón "Nuevo Item" que abre un modal adaptativo para ese catálogo.
  - Botón "Editar" (abre el modal con datos cargados).
  - **Toggle Activo/Inactivo (`is_active`)**: En lugar de eliminar directamente (lo cual causaría fallos de integridad referencial si el impuesto o categoría ya está asociado a un producto o venta), se prioriza el desactivar el registro para ocultarlo de los formularios futuros, preservando los datos históricos.
  - Botón "Eliminar": Permitido únicamente si el registro no tiene referencias asociadas en la base de datos.

### B. Modales de Creación Rápida en Contexto (Quick Add)
- En los formularios principales de creación/edición (ej. Crear Producto, Nueva Venta, Nueva Compra):
  - Junto a los selects (`select.input-solid`) de Categoría, Impuesto o Método de Pago, se añade un botón de acción rápida `+` estilizado.
  - Al hacer clic, abre un modal flotante rápido que solicita los datos mínimos necesarios del catálogo.
  - Tras guardar mediante AJAX, el modal se cierra, se inyecta el nuevo registro en el dropdown y se autoselecciona automáticamente para continuar el flujo de trabajo sin recargar la página.

---

## 3. Arquitectura y Modelos Relacionados

### A. Catálogos Existentes a Soportar

1. **Impuestos (`taxes`)**
   - Atributos: `name` (string), `code` (string, unique), `rate` (decimal: 2), `is_active` (boolean).
   - Referenciado en: `products`, `sales`, `purchases`.

2. **Categorías de Productos (`categories`)**
   - Atributos: `name` (string, unique), `description` (string, nullable), `is_active` (boolean).
   - Referenciado en: `products`.

3. **Métodos de Pago (`payment_methods`)**
   - Atributos: `name` (string, unique), `description` (string, nullable), `is_active` (boolean).
   - Referenciado en: `payments`.

---

## 4. Rutas y Controladores

Crearemos un módulo de configuración `App\Modules\Settings`:

- **Controlador**: `App\Modules\Settings\Controllers\CatalogController.php`
- **Rutas (`routes/web.php` o `routes/settings.php`)**:
  - `GET /settings/catalogs` -> `index()` (carga la vista base con las pestañas).
  - `POST /settings/catalogs/taxes` -> `storeTax(Request)` (API/AJAX + Sincrónico).
  - `PUT /settings/catalogs/taxes/{tax}` -> `updateTax(Request)`
  - `POST /settings/catalogs/categories` -> `storeCategory(Request)` (API/AJAX + Sincrónico).
  - `PUT /settings/catalogs/categories/{category}` -> `updateCategory(Request)`
  - `POST /settings/catalogs/payment-methods` -> `storePaymentMethod(Request)`
  - `PUT /settings/catalogs/payment-methods/{method}` -> `updatePaymentMethod(Request)`
  - `POST /settings/catalogs/toggle-status` -> `toggleStatus(Request)` (cambio dinámico de `is_active` para cualquier modelo de catálogo).
  - `DELETE /settings/catalogs/{type}/{id}` -> `destroy(Request)` (eliminación física si no hay relaciones).

---

## 5. Diseño de Interfaz de Usuario (UI Premium)

- **Estilos**: Siguiendo el rediseño visual de FlexDash, utilizaremos esquinas redondeadas (`rounded-xl` y `rounded-2xl`), sombras suaves (`var(--shadow-sm)`), y inputs estilizados con íconos absolutos (`.input-icon-wrapper` y `.input-solid`).
- **Modales**: Diseñados con AlpineJS y Tailwind CSS para transiciones animadas de entrada y salida (`transition: opacity, transform 0.2s`).
- **Estados de Hover**: Efecto de hover mejorado en los botones secundarios y primarios con cambios de fondo suaves para una apariencia de alta gama.
