# Feature Specification: Multi-Enterprise User Registration & Company Setup

**Feature Branch**: `001-multi-enterprise-registration`

**Created**: June 2, 2026

**Status**: Draft

**Input**: Multi-enterprise user registration system with company type selection, email validation, and brand-compliant UI

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Legal Entity Company Registration (Priority: P1)

A company administrator registers their organization as a legal entity in the FlexDash POS system. The admin provides company legal information (company name, tax ID, legal address) and becomes the company representative. The system creates a company record flagged as a legal entity and creates an admin user with the company representative role, enabling the admin to configure the POS system.

**Why this priority**: This is the primary use case for enterprise customers registering their businesses. It's the core workflow that enables multi-tenant operation and serves the majority of use cases.

**Independent Test**: This can be fully tested by completing the legal entity registration flow end-to-end—submitting company legal data and verifying that both company and admin user records are created with correct roles and flags—without requiring the natural person flow.

**Acceptance Scenarios**:

1. **Given** an unregistered user on the company type selection page, **When** they select "Legal Entity" and proceed, **Then** the legal entity registration form displays with fields for company name, tax ID, legal address, city, state/province, postal code, and country
2. **Given** a user on the legal entity registration form with all required fields, **When** they complete the form and click "Register", **Then** a company record is created with legal_entity_flag = true and the admin user is created with company_representative role
3. **Given** successful company creation, **When** the company is created, **Then** an email verification link is sent to the provided email address
4. **Given** a newly registered user, **When** they attempt to login before verifying their email, **Then** the system displays a message requiring email verification
5. **Given** a user with an unverified account, **When** they click the email verification link, **Then** the account is marked as verified and they can login

---

### User Story 2 - Natural Person Self-Registration (Priority: P1)

A self-employed individual or sole proprietor registers themselves in the FlexDash POS system using their personal information. Their personal data is used to create both the company record (for POS accounting) and their admin user account. The system creates a company record flagged as a natural person and assigns the admin an owner role.

**Why this priority**: Small business owners and solo entrepreneurs represent a significant market segment. This independent flow is essential for the system to serve both enterprise and small business use cases effectively.

**Independent Test**: This can be fully tested by completing the natural person registration flow—submitting personal data and verifying that both company and user records are created with correct natural person flagging and owner role—without requiring the legal entity flow.

**Acceptance Scenarios**:

1. **Given** an unregistered user on the company type selection page, **When** they select "Natural Person" and proceed, **Then** the natural person registration form displays with fields for full name, ID number, email, password, address, city, state/province, postal code, and country
2. **Given** a user on the natural person registration form with all required fields, **When** they complete the form and click "Register", **Then** a company record is created with natural_entity_flag = true and the admin user is created with owner role
3. **Given** a natural person registration, **When** registration completes, **Then** the personal information is used identically for both company and user records (no data duplication or discrepancy)
4. **Given** successful natural person registration, **When** the registration completes, **Then** an email verification link is sent to the provided email address
5. **Given** a newly registered natural person, **When** they attempt to login, **Then** they see a message prompting email verification

---

### User Story 3 - Email Validation Workflow (Priority: P1)

Users receive an email with a verification link after registration. Clicking the link validates their account, enabling login access. Without email verification, users cannot access the POS system, ensuring data integrity and valid contact information.

**Why this priority**: Email validation is a critical security and operational requirement. It ensures valid contact information for multi-tenant systems and prevents account abuse. It must work reliably for both registration paths.

**Independent Test**: This can be tested by creating a user (via either registration path) and verifying the email validation flow independently—sending and verifying the email token without requiring specific registration path details.

**Acceptance Scenarios**:

1. **Given** a user completes registration (legal entity or natural person), **When** the registration is successful, **Then** an email is sent with a unique verification token and a link to validate the account
2. **Given** a user receives the verification email, **When** they click the verification link, **Then** the system validates the token and marks the email_validated field as true
3. **Given** a user with an invalid or expired token, **When** they click a verification link, **Then** the system displays a message explaining the token is invalid or expired and provides an option to resend the verification email
4. **Given** a user with an unverified email, **When** they attempt to login, **Then** the authentication is denied and a message prompts them to verify their email
5. **Given** a user with a verified email, **When** they login with correct credentials, **Then** they are granted access to the POS system

---

### User Story 4 - Brand-Compliant Registration UI (Priority: P2)

Registration pages are styled with FlexDash brand colors (blue, teal/cyan gradient with yellow/orange accents) and designed to be mobile-responsive. Users see a cohesive, professional brand experience from their first interaction with the system.

**Why this priority**: Brand consistency creates a professional impression and sets expectations for the POS system quality. Mobile responsiveness is essential for modern web applications, though less critical than core registration functionality.

**Independent Test**: This can be tested by opening registration pages on desktop and mobile devices and verifying visual styling and responsiveness meet brand guidelines—without requiring registration completion.

**Acceptance Scenarios**:

1. **Given** the registration home page, **When** the page loads, **Then** the layout, buttons, and form elements use FlexDash brand colors (primary blue, teal/cyan gradient, yellow/orange accents)
2. **Given** a user on any registration page, **When** they view the page on a mobile device (375px - 480px width), **Then** the layout is responsive and all form fields are accessible without horizontal scrolling
3. **Given** a user on any registration page, **When** they view the page on a tablet device (768px width), **Then** the layout is responsive and properly centered
4. **Given** a user on any registration page, **When** they view the page on a desktop device (1024px+ width), **Then** the layout is properly formatted with appropriate spacing and readability
5. **Given** all form pages in the registration flow, **When** a user views them, **Then** brand colors are consistently applied across all pages and elements (buttons, links, headers, accents)

---

### Edge Cases

- What happens when a user submits registration but an email send fails? (Retry mechanism or manual verification option)
- What happens when a user attempts to register with an email that already exists in the system?
- What happens if a user's browser session expires during the registration process?
- How does the system handle rapid sequential registration attempts from the same IP address? (Rate limiting)
- What happens when a user tries to register with invalid data (malformed email, invalid tax ID format, etc.)?

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: System MUST allow users to select between "Legal Entity" and "Natural Person" as the company type at the start of registration
- **FR-002**: System MUST display a legal entity registration form with fields: company name, tax ID, legal address, city, state/province, postal code, country, email, and password when legal entity is selected
- **FR-003**: System MUST display a natural person registration form with fields: full name, ID number, email, password, address, city, state/province, postal code, and country when natural person is selected
- **FR-004**: System MUST validate email format during registration and prevent registration with invalid email addresses
- **FR-005**: System MUST validate that the email is not already registered in the system and display an error if it is
- **FR-006**: System MUST validate password strength (minimum 8 characters, mix of uppercase/lowercase/numbers/special characters) and guide users to create secure passwords
- **FR-007**: System MUST create a Company record with legal_entity_flag = true when legal entity registration completes
- **FR-008**: System MUST create a Company record with natural_entity_flag = true when natural person registration completes
- **FR-009**: System MUST create an Admin User with company_representative role when legal entity registration completes
- **FR-010**: System MUST create an Admin User with owner role when natural person registration completes
- **FR-011**: System MUST copy all personal information identically to both Company and User records for natural person registrations (name, address, etc.)
- **FR-012**: System MUST send a confirmation email with a unique verification token and verification link immediately after successful registration
- **FR-013**: System MUST generate unique, time-limited verification tokens (valid for 24 hours) that cannot be reused
- **FR-014**: System MUST validate verification tokens and mark email_validated = true when user clicks the verification link
- **FR-015**: System MUST prevent login for users with email_validated = false and display a message directing them to verify their email
- **FR-016**: System MUST allow users to request a new verification email if the original token expires or is lost
- **FR-017**: System MUST use FlexDash brand colors (primary blue, teal/cyan gradient, yellow/orange accents) consistently across all registration UI pages
- **FR-018**: System MUST implement mobile-responsive design using Tailwind CSS that works on devices from 375px (mobile) to 1920px (desktop) width
- **FR-019**: System MUST log all registration attempts, email verifications, and authentication failures for security audit trails
- **FR-020**: System MUST validate all input data server-side to prevent SQL injection, XSS, and other security vulnerabilities

### Key Entities _(include if feature involves data)_

- **Company**: Represents a business entity (legal or natural person). Attributes: id, name, tax_id (nullable for natural persons), legal_address, city, state_province, postal_code, country, legal_entity_flag, natural_entity_flag, created_at, updated_at. Relationships: One-to-Many with User.
- **User**: Represents a system user account. Attributes: id, company_id (FK), email, password_hash, name, role (company_representative, owner, staff, etc.), email_validated, email_validated_at, created_at, updated_at. Relationships: Many-to-One with Company.
- **EmailValidation**: Represents pending email verification. Attributes: id, user_id (FK), verification_token, token_expires_at, created_at. Used to track email verification status and token lifecycle.
- **CompanyType**: Enum or lookup table representing company type classification. Values: legal_entity, natural_person.
- **Role**: Enum or lookup table representing user roles. Values: company_representative (for legal entity admins), owner (for natural person admins), staff (for other staff users).

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: Users can complete legal entity registration in under 3 minutes from page load to email verification link reception
- **SC-002**: Users can complete natural person registration in under 2 minutes from page load to email verification link reception
- **SC-003**: Email verification links are delivered within 1 minute of registration in 99.5% of cases
- **SC-004**: Email verification tokens are validated and account activation completes in under 5 seconds
- **SC-005**: System successfully prevents login for unverified users in 100% of tested cases
- **SC-006**: Registration pages render and respond to user input in under 2 seconds on 3G mobile connections
- **SC-007**: 95% of first-time users can complete the appropriate registration flow on the first attempt without errors
- **SC-008**: Both registration paths (legal entity and natural person) operate independently with no cross-contamination of data
- **SC-009**: Brand color implementation achieves 100% visual consistency across all registration pages as verified by design review
- **SC-010**: Mobile responsiveness works correctly on devices from 375px to 1920px width with no layout breaking or overflow issues

## Assumptions

- **Authentication System**: An existing authentication system (password hashing, session management) is available and will be reused for user authentication
- **Email Service**: An SMTP or email service provider (e.g., SendGrid, AWS SES) is available and configured for sending verification emails
- **Database**: A relational database system supporting transactions is available for storing Company, User, EmailValidation, and related records
- **Module-Based Architecture**: The system follows a module-based design pattern (as per FlexDash project constitution), so registration functionality will be organized as a self-contained module with clear interfaces to other modules (auth, company management, user management)
- **Tailwind CSS**: Tailwind CSS is already configured in the project and available for styling
- **FlexDash Logo Colors**: The exact brand colors (blue, teal/cyan gradient, yellow/orange) are defined in a shared color palette/variables file that can be referenced across the application
- **Mobile-First Design**: The design approach prioritizes mobile devices first, then scales up to larger screens
- **User Verification**: Email verification is required before login; there is no bypass for unverified accounts
- **Unique Email Constraint**: Each email address in the system is unique and can only be associated with one user account
- **Role-Based Access**: The system implements role-based access control (RBAC) where company_representative and owner roles grant admin privileges to their respective company instances
- **Security Standards**: Password requirements (minimum 8 characters, mixed case, numbers, special characters) follow industry best practices for password security
- **TDD Approach**: Implementation will follow Test-Driven Development (TDD) principles with unit tests, integration tests, and end-to-end tests written before and alongside feature code
- **Multi-Tenant Isolation**: Each company's data is logically isolated; registration creates appropriate company scoping for subsequent POS operations
