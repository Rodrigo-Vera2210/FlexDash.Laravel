# Feature Specification: Multi-Branch Operational Isolation

**Feature Branch**: `021-multi-branch-isolation`

**Created**: 2026-06-28

**Status**: Draft

**Input**: User description: "Todos las ventas, compras, cobros, caja chica, stock y pagos deben estar por local. Lo que van a compartir, es la informacion de los productos, los clientes, y proveedores..."

## User Scenarios & Testing

### User Story 1 - Session Branch Selector for Premium Plans (Priority: P1)

Como usuario con un plan Premium (multibodega), quiero tener un selector de locales en la barra superior derecha de la aplicación para poder cambiar de local de trabajo activo en cualquier momento.

**Why this priority**: Es la interfaz que determina el contexto de sucursal para todas las transacciones operativas subsiguientes en la sesión del usuario.

**Independent Test**: Iniciar sesión con un usuario que pertenezca a una empresa con plan Premium, hacer clic en el selector de la barra superior, elegir "Sucursal Sur" y confirmar. La página debe recargarse y guardar la preferencia del local activo en la sesión.

**Acceptance Scenarios**:

1. **Given** un usuario de plan Premium con 3 locales creados, **When** accede a la aplicación, **Then** ve un selector en la barra superior con el listado de sus locales.
2. **Given** el selector de local, **When** selecciona un local alternativo, **Then** el sistema actualiza el local activo en la sesión (`session('active_branch_id')`) y recarga la pantalla.
3. **Given** un usuario de plan básico o no premium (límite de 1 local), **When** accede a la aplicación, **Then** el selector de local en el header está oculto y todas las operaciones se registran y consultan bajo su único local por defecto (el local matriz).

---

### User Story 2 - Operational Data Isolation by Active Branch (Priority: P2)

Como administrador o vendedor, quiero que todas las ventas, compras, movimientos de caja chica, cobros y pagos se guarden e identifiquen bajo el local activo seleccionado para evitar mezclar las finanzas y existencias de diferentes locales.

**Why this priority**: Garantiza la integridad operativa y contable individual de cada punto de venta física o bodega.

**Independent Test**: Con el local "Matriz" seleccionado, crear una venta. Con el local "Sucursal Norte" seleccionado, verificar que esa venta no se muestra en el listado de ventas ni afecta el saldo de la caja de esa sucursal, pero los productos, clientes y proveedores siguen estando disponibles para ambos locales.

**Acceptance Scenarios**:

1. **Given** una venta guardada bajo la sucursal "Matriz", **When** el usuario cambia el local activo a "Sucursal Norte", **Then** el listado de ventas y los informes financieros ocultan la venta realizada en la sucursal Matriz.
2. **Given** la creación de una compra o venta, **When** se guarda la transacción, **Then** el sistema asigna automáticamente el `branch_id` activo de la sesión al registro.
3. **Given** el catálogo de productos, clientes y proveedores, **When** el usuario cambia de local activo, **Then** estos catálogos siguen mostrándose y estando disponibles de forma compartida.

---

### User Story 3 - Branch-Scoped Dashboard Metrics (Priority: P3)

Como dueño de la empresa, quiero que el Dashboard muestre las métricas clave (ventas totales, utilidades, transacciones) correspondientes al local activo seleccionado para analizar el rendimiento individual de cada local.

**Why this priority**: Permite tomar decisiones estratégicas de negocio por punto físico de manera independiente.

**Independent Test**: Modificar el local activo en el selector superior y verificar que los gráficos, KPIs de ventas totales y contadores del dashboard principal cambian instantáneamente para reflejar únicamente la información de ese local.

**Acceptance Scenarios**:

1. **Given** el Dashboard principal, **When** el local activo es "Matriz", **Then** las métricas de ingresos, egresos y facturación corresponden exclusivamente a "Matriz".
2. **Given** el Dashboard principal, **When** no hay local seleccionado en sesión (o se selecciona "Todos" si está permitido), **Then** las métricas se consolidan para todos los locales de la empresa.

---

### Edge Cases

- **Usuario de caja chica operando sin local activo**: Si por algún motivo la sesión no tiene un `active_branch_id` definido, el sistema debe tomar como fallback el `branch_id` asignado en el perfil del usuario en la base de datos.
- **Cambio de local a mitad de un formulario**: Si el usuario abre un formulario de venta en el local A, cambia de local en otra pestaña a B, y envía el formulario, la venta debe guardarse bajo el local activo al momento del envío (o el sistema debe validar y alertar del cambio).

## Requirements

### Functional Requirements

- **FR-001**: El sistema DEBE almacenar el local activo del usuario en la sesión (`active_branch_id`).
- **FR-002**: El sistema DEBE inyectar el selector de sucursal en el header principal si `company->max_branches > 1`.
- **FR-003**: El sistema DEBE filtrar todas las consultas operativas (ventas, compras, pagos, cobros, cajas chicas, movimientos de inventario) por el `active_branch_id` de la sesión del usuario.
- **FR-004**: Los catálogos de Productos, Clientes y Proveedores DEBEN omitir el filtrado por sucursal y permanecer filtrados únicamente a nivel de `company_id`.
- **FR-005**: El Dashboard DEBE calcular el resumen financiero aplicando el filtro del local activo.

### Key Entities

- **User**: Posee un `branch_id` por defecto.
- **Branch**: Define los locales físicos de la empresa.
- **Sale / Purchase / CashBox**: Entidades operativas que poseen una clave foránea `branch_id`.

## Success Criteria

### Measurable Outcomes

- **SC-001**: El 100% de las nuevas ventas y compras creadas se asocian de forma automática con el `branch_id` activo de la sesión.
- **SC-002**: Ninguna transacción operativa de la Sucursal A es visible o modificable cuando el local activo de la sesión es la Sucursal B.
