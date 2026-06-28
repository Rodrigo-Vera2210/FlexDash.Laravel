# Feature Specification: Inventory & Branch Transfer Management

**Feature Branch**: `020-inventory-management`

**Created**: 2026-06-28

**Status**: Draft

**Input**: User description: "Vamos a crear la seccion de inventario. La seccion de Kardex, esta bien, ya que muestra los movimientos. Pero debe haber una seccion donde se vea el inventario, con el total de producto, por cada local. Igual debe estar habilitado la opcion de traslado entre bodegas..."

## User Scenarios & Testing

### User Story 1 - Branch Stock Inventory View (Priority: P1)

Como usuario de la sucursal o dueño de la empresa, quiero ver una sección de inventario consolidado que muestre la cantidad total de stock de cada producto por local para conocer las existencias reales en cada punto.

**Why this priority**: Es la necesidad básica para controlar y auditar las existencias actuales por bodega de forma independiente al Kardex de movimientos.

**Independent Test**: Acceder a `/inventory/stock` (o la ruta respectiva de inventario), seleccionar un local en el filtro superior, y ver el listado de productos con sus cantidades de stock específicas en ese local.

**Acceptance Scenarios**:

1. **Given** un usuario autenticado con múltiples locales, **When** accede a la sección de Inventario, **Then** ve un listado de productos con columnas mostrando el stock correspondiente a cada local.
2. **Given** la vista de Inventario, **When** utiliza el filtro por local, **Then** la tabla se actualiza mostrando únicamente los productos y existencias del local seleccionado.

---

### User Story 2 - Inter-branch Stock Transfer (Priority: P2)

Como administrador o personal autorizado, quiero poder realizar un traslado de mercancía entre bodegas/locales para mover existencias de forma controlada y segura.

**Why this priority**: Permite la distribución interna de inventario y genera los movimientos de egreso e ingreso correspondientes de forma automática.

**Independent Test**: Ir al formulario de traslado de stock, seleccionar el local origen (con stock suficiente), el local destino, ingresar los productos y cantidades, y procesar el traslado. Los niveles de stock de ambos locales deben actualizarse y el Kardex debe reflejar los egresos e ingresos.

**Acceptance Scenarios**:

1. **Given** existencias suficientes en el local origen, **When** el usuario realiza un traslado de 10 unidades del Producto A al local destino, **Then** el stock del local origen disminuye en 10, el stock del local destino aumenta en 10, y se registran dos movimientos en el Kardex (egreso por traslado e ingreso por traslado).
2. **Given** una solicitud de traslado, **When** la cantidad ingresada supera el stock disponible en el local origen, **Then** el sistema arroja un error de validación y cancela la operación sin modificar los inventarios.

---

### User Story 3 - Multi-branch Plan Restriction (Priority: P3)

Como administrador del sistema, quiero que la opción de traslado entre bodegas esté restringida según el plan de suscripción del cliente.

**Why this priority**: Fomenta el upgrade a planes superiores (Premium/Enterprise) que permiten operar con múltiples bodegas.

**Independent Test**: Registrar una empresa con un plan que no admita multibodegas (por ejemplo, plan básico con límite de 1 local), verificar que no se muestra el botón de traslado y que si se intenta acceder por URL directa a la ruta de traslados, retorna un código de acceso denegado (403).

**Acceptance Scenarios**:

1. **Given** una empresa con un plan que admite múltiples sucursales/bodegas, **When** accede a Inventario, **Then** ve habilitada la opción de "Traslado entre Bodegas".
2. **Given** una empresa con plan básico (límite de 1 bodega), **When** intenta acceder a la sección de traslados, **Then** el sistema retorna una pantalla de error 403 (Acceso Denegado / Requiere Plan Multibodega).

---

### Edge Cases

- **Traslado al mismo local**: El sistema debe impedir que el local de origen y el local de destino sean el mismo.
- **Stock negativo**: Controlar condiciones de carrera concurrentes donde dos usuarios intentan trasladar el mismo stock al mismo tiempo.

## Requirements

### Functional Requirements

- **FR-001**: El sistema DEBE proveer una pantalla de consulta de inventario por local.
- **FR-002**: El sistema DEBE registrar movimientos de Kardex con tipo `egreso_traslado` e `ingreso_traslado` para auditar los traslados.
- **FR-003**: El sistema DEBE validar que el local de origen tenga suficiente stock disponible antes de autorizar un traslado.
- **FR-004**: El sistema DEBE validar los límites de locales del plan contratado antes de permitir el acceso a la funcionalidad de traslados.
- **FR-005**: El traslado DEBE ejecutarse en una transacción de base de datos única para evitar inconsistencias de stock en caso de fallos.

### Key Entities

- **BranchProduct**: Pivote que asocia un producto con una sucursal (`branch_id`, `product_id`, `stock`).
- **InventoryMovement**: Registro de movimiento de Kardex (`branch_id`, `product_id`, `quantity`, `type` [ingreso_traslado, egreso_traslado], `created_at`).
- **StockTransfer**: Registro del traslado consolidado (`id`, `origin_branch_id`, `destination_branch_id`, `user_id`, `created_at`).
- **StockTransferDetail**: Detalle de los productos trasladados (`stock_transfer_id`, `product_id`, `quantity`).

## Success Criteria

### Measurable Outcomes

- **SC-001**: El 100% de los traslados exitosos descuentan del origen e incrementan el destino de manera exacta.
- **SC-002**: El sistema bloquea accesos a la ruta de traslado para usuarios con plan básico con 100% de efectividad.
