# Feature Specification: PDF Reports & Cash Box Excel Export

**Feature Branch**: `008-pdf-reports-and-cash-excel`

**Created**: 2026-06-17

**Status**: Draft

**Input**: User description: "Vamos a crear reportes en pdf para las ventas y compras, con un formato como se ve en la imagen adjuntada, con los colores de nuestra paleta. Debe haber la facilidad para descargar los pdf a lo que se cree las ordenes, en los listados, y en todos los lados que aparezcan las ordenes de compras y ventas. Y para el reporte de caja chica vamos a exportarlo en excel."

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Sales Invoice PDF Report (Priority: P1)

As a sales cashier or manager, I want to download a beautifully designed PDF invoice for any sales order so that I can print it or share it with the customer.

**Why this priority**: Customers require physical or digital invoices. It represents the primary customer-facing document.

**Independent Test**: Can be tested by clicking the "Descargar PDF" button on a sale's show page or index table row, verifying that a PDF downloads and contains correct header wave design, details, totals, and matching primary blue palette.

**Acceptance Scenarios**:
1. **Given** I am on the details page of an approved sale, **When** I click the "Descargar PDF" button, **Then** a PDF is generated and downloaded immediately.
2. **Given** the generated PDF invoice, **When** I open it, **Then** I see the top banner styled in Primary Blue (`#0054a6`) with a wavy visual flow, text matching "INVOICE" and the enterprise brand name, billing details, a detailed items table, and a footer featuring payment info, terms, signature block, and bottom waves.

---

### User Story 2 - Purchase Order PDF Report (Priority: P1)

As a purchasing manager, I want to download a PDF document of any purchase order so that I can send it to the supplier for record-keeping.

**Why this priority**: Supplier purchases require purchase order verification documents.

**Independent Test**: Can be tested by navigating to a purchase's show page or index row, downloading the PDF, and verifying that the PDF matches the purchase details and uses the Teal/Cyan (`#00a2e8`) brand color accent.

**Acceptance Scenarios**:
1. **Given** I am on the purchases index, **When** I click the download PDF icon for a purchase, **Then** the purchase order PDF is downloaded.
2. **Given** the purchase PDF, **When** I view its styling, **Then** it mirrors the waves header layout of the sales invoice but uses the Teal/Cyan (`#00a2e8`) color palette.

---

### User Story 3 - Cash Box Session Excel Export (Priority: P1)

As an accountant or administrator, I want to export the transaction log of the petty cash box session to Excel so that I can do further analysis or auditing.

**Why this priority**: Auditing cash flows requires spreadsheet compatibility to filter, sort, and reconcile accounts.

**Independent Test**: Can be tested by clicking "Exportar Excel" on the active cash box screen and verifying that a `.xlsx` spreadsheet downloads, opens successfully in Microsoft Excel, and lists all session transactions with proper headers and formatting.

**Acceptance Scenarios**:
1. **Given** I am on the Cash Box view with an open session, **When** I click the "Exportar Excel" button in the header actions, **Then** a file named `reporte-caja-chica-SESION-[ID].xlsx` is downloaded.
2. **Given** the downloaded Excel spreadsheet, **When** I open it, **Then** I see a stylized top header with session info, a table column layout (Date, Concept, User, Type, Amount), styled headers using the brand's primary blue, and correctly formatted numeric columns.

---

## Edge Cases

- **Unicode/Accents**: Standard PDF render engines (like Dompdf) can crash or show question marks for accented Spanish letters (á, é, í, ó, ú, ñ) or the currency symbol (S/). The PDF templates MUST be configured to use UTF-8 charset and standard system fonts (Helvetica/Arial) supporting Spanish characters natively.
- **Empty Cash Box**: If the active cash box has no transactions, the Excel export must still generate a valid spreadsheet containing the initial opening balance row and clean headers.
- **Long Invoice Tables**: If an invoice has dozens of line items, the PDF must break onto multiple pages gracefully, keeping headers and footers intact.
- **Borrador/Draft Status**: PDFs should show a prominent "BORRADOR / DRAFT" watermark or text if the sale/purchase is still in draft state, to prevent official usage before approval.

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: PDF invoices for Sales MUST show: series & number, issue date, due date, customer business name, document ID (RUC/DNI), items list, subtotal, tax name & amount, discount, total general, paid amount, pending balance, and notes.
- **FR-002**: Sales PDF invoices MUST use the Primary Blue (`#0054a6`) styling with the wavy header/footer layout.
- **FR-003**: Purchases PDF reports MUST show details matching purchases, using the Teal/Cyan (`#00a2e8`) waves design layout.
- **FR-004**: Download PDF buttons/icons MUST be placed on:
  - Sales and Purchases index lists (action columns).
  - Sales and Purchases details/show views.
  - Dashboard's recent sales table.
- **FR-005**: Cash Box Excel export MUST generate a `.xlsx` spreadsheet using the brand palette for styling (blue `#0054a6` header row, white bold text, auto-fit column widths, proper currency columns).
- **FR-006**: Cash Box Excel report MUST list session details (Opening balance, active user, opened date, status) and list transactions chronologically.

### Key Entities

- **Sale**: The sales order invoice data source.
- **Purchase**: The purchase order data source.
- **CashBox**: The petty cash box session.
- **CashBoxTransaction**: Single transaction details (Manual inflows, outflows, payments).

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: PDF documents render and download in under 3 seconds.
- **SC-002**: Generated PDFs are perfectly formatted standard A4 sheets with no overlapping blocks.
- **SC-003**: Excel files open in Microsoft Excel or LibreOffice without "Corrupted file" warnings.
- **SC-004**: All numeric columns in Excel are formatted as actual numbers/currency, not strings.

---

## Assumptions

- We will install `barryvdh/laravel-dompdf` for PDF generation, which uses Dompdf.
- We will install `phpoffice/phpspreadsheet` for Excel generation.
- All PDF views will be written in standard Blade views, converting CSS to Dompdf-compatible inline styles.
- Wave graphics will be generated using inline SVGs or optimized base64 background-images in the PDF CSS.
