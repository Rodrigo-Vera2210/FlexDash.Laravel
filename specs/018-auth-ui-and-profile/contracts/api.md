# API Contracts: Auth UI & Profile Management

**Phase**: 1 (Design) | **Date**: 2026-06-25

This document defines the RESTful API contracts for profile management and password change workflows. All endpoints require JWT authentication (Bearer token) except where noted.

---

## Profile Endpoints

### GET /api/profile

**Purpose**: Retrieve authenticated user's profile information.

**Authentication**: Required (JWT)

**Request**:

```http
GET /api/profile HTTP/1.1
Authorization: Bearer {jwt_token}
```

**Response** (200 OK):

```json
{
    "success": true,
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "name": "Juan Pérez",
        "email": "juan@example.com",
        "phone": "+593987654321",
        "company_id": "550e8400-e29b-41d4-a716-446655440001",
        "company_name": "Mi Empresa S.A.",
        "theme_preference": "dark",
        "language": "es",
        "timezone": "America/Guayaquil",
        "notifications_enabled": true,
        "email_verified_at": "2026-01-15T10:30:00Z",
        "created_at": "2026-01-15T10:30:00Z",
        "updated_at": "2026-06-25T14:20:00Z"
    }
}
```

**Error Responses**:

- 401 Unauthorized: Invalid or missing JWT token
- 404 Not Found: User not found (rare; indicates data integrity issue)

---

### PUT /api/profile

**Purpose**: Update authenticated user's personal information (name, phone, language, timezone, notifications).

**Authentication**: Required (JWT)

**Request**:

```http
PUT /api/profile HTTP/1.1
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "name": "Juan Carlos Pérez",
  "phone": "+593998765432",
  "language": "es",
  "timezone": "America/New_York",
  "notifications_enabled": false
}
```

**Validation Rules**:

- `name`: required, string, max:255
- `phone`: nullable, regex:/^\+?593\d{9}$/
- `language`: in:es,en
- `timezone`: in:America/Guayaquil,America/New_York,... (IANA timezone list)
- `notifications_enabled`: boolean

**Response** (200 OK):

```json
{
    "success": true,
    "message": "Perfil actualizado exitosamente",
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "name": "Juan Carlos Pérez",
        "email": "juan@example.com",
        "phone": "+593998765432",
        "language": "es",
        "timezone": "America/New_York",
        "notifications_enabled": false,
        "updated_at": "2026-06-25T14:25:00Z"
    }
}
```

**Error Responses**:

- 400 Bad Request:
    ```json
    {
        "success": false,
        "message": "Validation failed",
        "errors": {
            "phone": ["El formato del teléfono es inválido"],
            "timezone": ["La zona horaria seleccionada no es válida"]
        }
    }
    ```
- 401 Unauthorized: Invalid token
- 422 Unprocessable Entity: Validation failure (same as 400; alternative response)

---

### PATCH /api/profile/theme

**Purpose**: Update user's theme preference (light/dark/system).

**Authentication**: Required (JWT)

**Request**:

```http
PATCH /api/profile/theme HTTP/1.1
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "theme_preference": "dark"
}
```

**Validation Rules**:

- `theme_preference`: required, in:light,dark,system

**Response** (200 OK):

```json
{
    "success": true,
    "message": "Tema actualizado exitosamente",
    "data": {
        "theme_preference": "dark",
        "updated_at": "2026-06-25T14:30:00Z"
    }
}
```

**Error Responses**:

- 400 Bad Request: Invalid theme value
- 401 Unauthorized: Invalid token

---

## Password Change Endpoints

### POST /api/password/request-otp

**Purpose**: Initiate password change by requesting an OTP code.

**Authentication**: Required (JWT)

**Request**:

```http
POST /api/password/request-otp HTTP/1.1
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "current_password": "MyCurrentP@ssw0rd"
}
```

**Validation Rules**:

- `current_password`: required, string; must match user's current password hash

**Response** (200 OK):

```json
{
    "success": true,
    "message": "Se envió un código de verificación a tu correo electrónico",
    "data": {
        "otp_request_id": "550e8400-e29b-41d4-a716-446655440002",
        "expires_in_seconds": 600,
        "email_masked": "j***@example.com"
    }
}
```

**Error Responses**:

- 400 Bad Request:
    ```json
    {
        "success": false,
        "message": "La contraseña actual es incorrecta",
        "code": "INVALID_CURRENT_PASSWORD"
    }
    ```
- 401 Unauthorized: Invalid JWT token
- 429 Too Many Requests: User has requested too many OTPs (rate limit 3 per 10 minutes)
    ```json
    {
        "success": false,
        "message": "Has solicitado demasiados códigos. Intenta en 10 minutos",
        "retry_after_seconds": 600
    }
    ```

---

### POST /api/password/verify-otp

**Purpose**: Verify OTP code before allowing password reset.

**Authentication**: Required (JWT)

**Request**:

```http
POST /api/password/verify-otp HTTP/1.1
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "otp_code": "123456"
}
```

**Validation Rules**:

- `otp_code`: required, string, length:6, numeric; token must not be expired, must not exceed 3 attempts

**Response** (200 OK):

```json
{
    "success": true,
    "message": "Código de verificación válido. Procede a cambiar tu contraseña",
    "data": {
        "verified": true,
        "otp_session_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "next_step": "set_new_password"
    }
}
```

**Error Responses**:

- 400 Bad Request (Invalid OTP):
    ```json
    {
        "success": false,
        "message": "El código de verificación es incorrecto",
        "code": "INVALID_OTP",
        "attempts_remaining": 2
    }
    ```
- 400 Bad Request (Expired OTP):
    ```json
    {
        "success": false,
        "message": "El código de verificación ha expirado. Solicita uno nuevo",
        "code": "OTP_EXPIRED"
    }
    ```
- 400 Bad Request (Max attempts exceeded):
    ```json
    {
        "success": false,
        "message": "Se excedió el número máximo de intentos. Solicita un nuevo código",
        "code": "MAX_ATTEMPTS_EXCEEDED",
        "request_new_otp": true
    }
    ```
- 401 Unauthorized: Invalid JWT token

---

### POST /api/password/reset

**Purpose**: Reset password after OTP verification.

**Authentication**: Required (JWT)

**Request**:

```http
POST /api/password/reset HTTP/1.1
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "otp_session_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "new_password": "MyNewP@ssw0rd123",
  "new_password_confirmation": "MyNewP@ssw0rd123"
}
```

**Validation Rules**:

- `new_password`: required, min:8, confirmed (must match `new_password_confirmation`), strong password (uppercase, lowercase, number, special char recommended but not enforced for POS UX)
- `otp_session_token`: required, valid (not expired, matches verified OTP)

**Response** (200 OK):

```json
{
    "success": true,
    "message": "Contraseña actualizada exitosamente. Por favor inicia sesión nuevamente",
    "data": {
        "redirectTo": "/login"
    }
}
```

**Error Responses**:

- 400 Bad Request (Password validation):
    ```json
    {
        "success": false,
        "message": "Validation failed",
        "errors": {
            "new_password": ["La contraseña debe tener al menos 8 caracteres"],
            "new_password_confirmation": ["Las contraseñas no coinciden"]
        }
    }
    ```
- 400 Bad Request (Invalid session token):
    ```json
    {
        "success": false,
        "message": "Sesión de verificación expirada o inválida. Inicia el proceso nuevamente",
        "code": "INVALID_SESSION_TOKEN"
    }
    ```
- 401 Unauthorized: Invalid JWT token

**Side Effects**:

- User's JWT token is **invalidated** immediately after password reset
- User is **logged out** from all devices/sessions (via `Auth::guard()->logoutOtherDevices($user)`)
- User must re-login with new password

---

### POST /api/password/resend-otp

**Purpose**: Request a new OTP code if the previous one was not received or expired.

**Authentication**: Required (JWT)

**Request**:

```http
POST /api/password/resend-otp HTTP/1.1
Authorization: Bearer {jwt_token}
```

**Response** (200 OK):

```json
{
    "success": true,
    "message": "Enviamos un nuevo código a tu correo electrónico",
    "data": {
        "expires_in_seconds": 600
    }
}
```

**Error Responses**:

- 429 Too Many Requests: Rate limited (max 3 OTP requests per 10 minutes)
- 401 Unauthorized: Invalid JWT token

---

## Frontend Component Contracts

### ProfilePage Component

**Props**: None (data fetched via API on mount)

**Emits**:

- `profile:updated`: Fired when profile successfully updates
- `password:changed`: Fired when password successfully changes
- `theme:toggled`: Fired when theme preference updates

**State**:

```typescript
interface ProfilePageState {
    user: User | null;
    loading: boolean;
    error: string | null;
    editMode: boolean;
    passwordChangeStep: "request" | "verify" | "reset" | null;
    otp_request_id: string | null;
    otp_session_token: string | null;
}
```

**Methods**:

- `loadProfile()`: Fetch user data from `GET /api/profile`
- `updateProfile(data)`: Submit `PUT /api/profile`
- `requestPasswordOtp(currentPassword)`: Submit `POST /api/password/request-otp`
- `verifyOtp(otpCode)`: Submit `POST /api/password/verify-otp`
- `resetPassword(newPassword, confirmation)`: Submit `POST /api/password/reset`
- `resendOtp()`: Submit `POST /api/password/resend-otp`

---

### ThemeToggle Component

**Props**:

- `currentTheme`: 'light' | 'dark' | 'system'

**Emits**:

- `theme:change(newTheme)`: Fired when user toggles theme

**Methods**:

- `toggleTheme()`: Call `PATCH /api/profile/theme` + update localStorage

**Behavior**:

- Button visible in top-right corner of all pages
- Immediately applies CSS class toggle (no network wait)
- Async API call to persist preference
- Falls back to localStorage if API fails

---

### Navigation Back Button

**Props**:

- `targetPage`: 'login' | 'registration'

**Behavior**:

- Saves form state to localStorage before navigation
- On target page load, restores form state from localStorage
- Clears localStorage after successful form submission

---

## Error Response Format (Standardized)

**Success Response**:

```json
{
    "success": true,
    "message": "Action completed successfully",
    "data": {
        /* entity data */
    }
}
```

**Error Response**:

```json
{
    "success": false,
    "message": "Human-readable error message in user's language",
    "code": "MACHINE_ERROR_CODE",
    "errors": {
        /* field-level validation errors */
    }
}
```

---

## Rate Limiting & Security

| Endpoint                       | Rate Limit         | Window                |
| ------------------------------ | ------------------ | --------------------- |
| POST /api/password/request-otp | 3                  | 10 minutes            |
| POST /api/password/resend-otp  | 3                  | 10 minutes            |
| POST /api/password/verify-otp  | 3 attempts per OTP | OTP lifetime (10 min) |
| PUT /api/profile               | 10                 | 1 minute              |
| PATCH /api/profile/theme       | 10                 | 1 minute              |

**Security Headers**:

- All requests require valid JWT in Authorization header
- CSRF protection via Laravel middleware (if applicable to API)
- CORS headers configured for frontend origin
- OTP tokens hashed in database (never store plaintext)

---

## Integration Checklist

- [ ] Define routes in `routes/api.php`
- [ ] Create ProfileController with methods for each endpoint
- [ ] Create PasswordChangeController with OTP workflow
- [ ] Implement OtpService for token generation/verification
- [ ] Implement ProfileService for profile updates
- [ ] Add rate limiting middleware to applicable routes
- [ ] Add request validation FormRequest classes
- [ ] Add API response wrapper/envelope class
- [ ] Document in Laravel API docs / Postman collection
- [ ] Test with Postman/Insomnia before frontend integration
- [ ] Frontend integration tests for each component
