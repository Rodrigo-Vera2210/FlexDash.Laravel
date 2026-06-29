# Feature Specification: Subscription Upgrade Proration and Superadmin Bank Accounts Management

**Feature Branch**: `023-subscription-upgrade-proration`

**Created**: 2026-06-29

**Status**: Draft

**Input**: User description: "Cambiar la pantalla de suscripcion/ para que se asemeje a la de registration/billing/. El objetivo es el mismo seleccionar el periodo de meses y el plan al que se quiere cambiar. Toca aumentar una validacion del tiempo y valor que tiene de la suscripcion actual para disminuirle a la suscripcion nueva. Por ejemplo si yo ya pague 3 meses del basico a $30 y me faltan 2 meses y medios, y voy a pasar a 3 meses del standard a $50, toca descontarle $25 por los 2 meses y medios que no termino. Entonces pagaría $25 al nuevo plan. Tambien para el superadministrador toca aumentar una seccion para administrar las cuentas bancarias que van a ser usadas para cobrar las suscripciones."

## User Scenarios & Testing

### User Story 1 - Advanced Plan Selector & Prorated Discount Calculation (Priority: P1)

Como dueño de empresa, quiero cambiar o renovar mi suscripción usando un selector de periodo de meses y planes dinámicos (igual que en el registro/billing). Si decido cambiar a un plan superior (Upgrade), quiero que el valor restante no consumido de mi suscripción actual se calcule y se aplique como descuento (prorrateo) al total de mi nuevo plan para pagar únicamente la diferencia.

**Why this priority**: Es el flujo principal de facturación y retención de clientes que desean escalar sus capacidades operativas.

**Independent Test**:
1. Iniciar sesión como empresa con plan `basic` activo y 75 días restantes de vigencia (de un pago previo de 3 meses a $30.00).
2. Ir a la pantalla de Suscripción en `/settings/subscription`.
3. Seleccionar 3 meses del plan `standard` ($50.00 base).
4. Verificar que la pantalla muestre un descuento por prorrateo de $25.00 y un total neto a pagar de $25.00.
5. Registrar la transferencia bancaria con el comprobante de pago por el valor de $25.00.

**Acceptance Scenarios**:
1. **Given** un cliente con plan activo y vigencia restante, **When** accede a la pantalla de suscripción, **Then** visualiza las opciones de planes y periodos con su costo mensual, descuentos por volumen de meses y el descuento prorrateado correspondiente a los días restantes.
2. **Given** la selección de un nuevo plan, **When** el usuario confirma la solicitud de cambio, **Then** el sistema calcula el importe neto restando el saldo a favor (`new_total_amount - unused_credit`) y registra el pago con estado `pending`.
3. **Given** una suscripción suspendida o vencida, **When** se solicita renovación o cambio de plan, **Then** el descuento por prorrateo es $0.00 ya que no tiene saldo remanente de vigencia activa.

---

### User Story 2 - Superadmin Bank Accounts Administration (Priority: P2)

Como Superadministrador del sistema, quiero administrar (crear con carga de logotipo del banco, editar, eliminar, activar/desactivar) las cuentas bancarias de la plataforma en las que las empresas depositan el dinero de las suscripciones, para que los clientes siempre visualicen la información bancaria y los logotipos actualizados al realizar sus pagos.

**Why this priority**: Evita que los datos de las cuentas bancarias estén hardcodeados en el código de las vistas, permitiendo cambios dinámicos desde el panel del Superadministrador.

**Independent Test**:
1. Iniciar sesión como Superadministrador.
2. Ir al panel de administración de cuentas bancarias.
3. Crear una cuenta: "Banco Pichincha - Corriente #987654321" y subir una imagen para el logotipo del banco.
4. Modificarla o desactivarla.
5. Verificar que los cambios (incluido el logotipo de la cuenta) se reflejan inmediatamente en las tarjetas de información de cuentas en las pantallas de registro y de renovación de suscripciones de las empresas.

**Acceptance Scenarios**:
1. **Given** el panel de Superadministrador, **When** se agrega una nueva cuenta bancaria con nombre de banco, tipo de cuenta, número, beneficiario, RUC y un archivo de imagen de logotipo, **Then** esta se almacena en la base de datos y la imagen se guarda en el storage público.
2. **Given** los formularios de cobro en Registro y Suscripción, **When** se listan las cuentas destino, **Then** se obtienen dinámicamente de las cuentas bancarias activas registradas por el Superadministrador mostrando sus respectivos logotipos/iconos.

---

### Edge Cases

- **Saldo a favor mayor al costo del nuevo plan**: Si el saldo remanente es mayor al total del nuevo plan (por ejemplo, le quedan 11 meses de básico y cambia a 1 mes de estándar), el neto a pagar es $0.00. El pago se registra por $0.00 y pasa a aprobación del superadministrador.
- **Sin pagos previos registrados**: Si la empresa fue creada manualmente o no tiene pagos aprobados registrados, el descuento prorrateado es $0.00.
- **Banco sin logotipo**: Si una cuenta bancaria no tiene logotipo cargado, se debe mostrar un icono o marcador de posición genérico de banco.

## Requirements

### Functional Requirements

- **FR-001**: El sistema DEBE calcular el saldo no consumido de la suscripción actual usando la fórmula: `unused_credit = (last_approved_payment->amount / last_approved_payment->duration_months / 30) * remaining_days`.
- **FR-002**: El formulario de suscripción `/settings/subscription` DEBE renderizar el selector de periodos (1, 3, 6, 12, 24, 36 meses) y tarjetas de planes en el mismo formato estético que `/register/billing`.
- **FR-003**: El sistema DEBE proveer un CRUD completo para cuentas bancarias en el panel del Superadministrador que permita subir una imagen de logotipo (`logo_path`).
- **FR-004**: Los datos bancarios y logotipos mostrados en los flujos de `/register/billing`, `/subscription-suspended` y `/settings/subscription` DEBEN consultarse dinámicamente de la base de datos de cuentas bancarias activas.

## Success Criteria

### Measurable Outcomes

- **SC-001**: El descuento prorrateado se calcula de manera exacta con un margen de error menor al 1% comparado con la fórmula diaria teórica.
- **SC-002**: El superadministrador puede añadir o retirar cuentas de cobro sin modificar líneas de código ni requerir despliegues adicionales.
