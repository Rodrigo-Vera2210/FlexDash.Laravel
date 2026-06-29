# Feature Specification: Autocomplete Inputs for Catalogs & Documents

**Feature Branch**: `022-autocomplete-inputs`

**Created**: 2026-06-29

**Status**: Draft

**Input**: User description: "Implementar input autocomplete para los clientes/proveedores, documentos, productos, en compras, ventas, inventario, facturas electronicas, caja chica."

## User Scenarios & Testing

### User Story 1 - Autocomplete for Partners (Clients/Suppliers) (Priority: P1)

Como usuario del sistema, quiero que al escribir en los campos de Cliente o Proveedor (en Ventas, Compras y Caja Chica) se muestre un menú desplegable con coincidencias en tiempo real basadas en el nombre comercial o número de identificación (cédula/RUC) para agilizar el registro sin tener que buscar en un dropdown gigante.

**Why this priority**: Es el catálogo con mayor recurrencia y volumen de datos que ralentiza la carga si se renderizan cientos de opciones estáticas en el HTML.

**Independent Test**: Ir a la pantalla de crear venta o compra, escribir las primeras letras de un cliente registrado o parte de su RUC, y verificar que se despliega una lista de sugerencias. Al hacer clic en una sugerencia, se autocompleta el campo y se selecciona el ID interno correspondiente.

**Acceptance Scenarios**:

1. **Given** la pantalla de creación de venta, **When** el usuario escribe 3 caracteres en el buscador de clientes, **Then** el sistema realiza una consulta asíncrona y muestra los resultados coincidentes (por nombre comercial o RUC/CI).
2. **Given** el desplegable de autocompletado, **When** el usuario selecciona un cliente de la lista, **Then** se asigna el `partner_id` al formulario y se muestra visualmente el nombre comercial seleccionado.
3. **Given** un término de búsqueda sin coincidencias, **When** el usuario escribe en el input, **Then** se muestra un mensaje indicando "No se encontraron resultados".

---

### User Story 2 - Autocomplete for Products (Priority: P2)

Como administrador o vendedor, quiero que al agregar ítems en las facturas de venta, compras o traslados de inventario pueda buscar productos usando un buscador asíncrono con autocompletado para seleccionar rápidamente los productos y sus precios/impuestos asociados.

**Why this priority**: Los catálogos de productos suelen ser extensos, y un buscador predictivo evita sobrecargar el DOM de la página.

**Independent Test**: En la pantalla de crear venta o traslado de bodega, comenzar a escribir el nombre o código de un producto en la fila de ítems y verificar que se sugieren productos en tiempo real con su precio/costo y tasa de IVA correspondiente.

**Acceptance Scenarios**:

1. **Given** el formulario de detalle de ítems en ventas o compras, **When** el usuario escribe en el campo del producto, **Then** el sistema muestra sugerencias predictivas filtrando por nombre o código del producto.
2. **Given** la selección de un producto en la sugerencia, **When** el usuario hace clic en el producto, **Then** se autocompletan los campos de precio unitario, código e impuesto (IVA) de la línea de detalle correspondiente.

---

### User Story 3 - Autocomplete for Invoices/Documents (Priority: P3)

Como facturador o cajero, quiero que al registrar cobros asimilados, pagos de caja chica o emitir notas de crédito en el módulo de facturación electrónica, pueda autocompletar la factura o documento de referencia escribiendo su número secuencial de comprobante.

**Why this priority**: Evita errores manuales de tipografía al asociar cobros, pagos de facturas o anulaciones electrónicas con sus documentos originales.

**Independent Test**: En la sección de cobros masivos de caja chica o al asociar un pago, buscar una factura escribiendo el número secuencial (ej: `000000001` o `F001-00000001`) y verificar que se despliegan sugerencias con el saldo pendiente y datos del cliente.

**Acceptance Scenarios**:

1. **Given** el formulario de cobro masivo de caja chica o facturación electrónica, **When** el usuario busca una factura por número, **Then** el sistema muestra los comprobantes activos que coincidan con la búsqueda.
2. **Given** un documento seleccionado, **When** el usuario lo confirma, **Then** se carga el saldo pendiente y se asocia el ID del documento correspondiente.

---

### Edge Cases

- **Entrada vacía**: Si el input se vacía, la lista de sugerencias debe ocultarse y restablecerse el valor de selección.
- **Petición con retardo (Debouncing)**: Si el usuario escribe rápido, no deben dispararse decenas de peticiones HTTP en paralelo. Se debe implementar un retardo (debouncing) de mínimo 300ms.
- **Selecciones inválidas**: Si el usuario escribe un texto libre que no corresponde a una sugerencia y sale del input, se debe restablecer el valor al último válido seleccionado o borrar el ID oculto para evitar enviar datos inválidos.

## Requirements

### Functional Requirements

- **FR-001**: El sistema DEBE proveer endpoints asíncronos en formato JSON para buscar clientes/proveedores, productos y documentos de facturación que soporten filtros por términos de búsqueda (`q`).
- **FR-002**: Las llamadas de búsqueda DEBEN estar protegidas bajo los mismos middlewares de seguridad (`auth.jwt`, `initialize.branch`).
- **FR-003**: Los buscadores de clientes/proveedores y productos en ventas, compras y traslados de inventario DEBEN implementar autocompletado en la interfaz de usuario.
- **FR-004**: Los buscadores de documentos en cobros masivos de caja chica y facturación electrónica DEBEN implementar autocompletado en la interfaz de usuario.
- **FR-005**: El componente de autocompletado DEBE implementar "debouncing" de peticiones de al menos 300ms para evitar sobrecarga del servidor.

## Success Criteria

### Measurable Outcomes

- **SC-001**: Los usuarios pueden seleccionar un cliente o producto en menos de 3 segundos utilizando el buscador predictivo.
- **SC-002**: Las peticiones de autocompletado no superan el tiempo de respuesta promedio de 150ms bajo condiciones normales de red local.
- **SC-003**: El DOM de las páginas de creación de transacciones reduce su peso en un 60% al no renderizar listados estáticos gigantescos de catálogos en el HTML inicial.
