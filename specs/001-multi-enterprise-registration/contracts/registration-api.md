# Registration API Contract

## Route Summary

- `GET /register/type`
  - Description: Show registration path selection.
  - Response: Wizard step view.

- `POST /register/account`
  - Description: Submit email, password, and representative name.
  - Request:
    - `company_type`: `legal_entity` or `natural_person`
    - `email`
    - `password`
    - `password_confirmation`
    - `name`
  - Response: Next wizard step or validation errors.

- `POST /register/entity`
  - Description: Submit legal entity or natural person details.
  - Request fields vary by `company_type`.
  - Response: Next wizard step or validation errors.

- `POST /register/review`
  - Description: Confirm registration details and create pending registration.
  - Request:
    - `consent_terms`: boolean
    - `consent_privacy`: boolean
  - Response: OTP verification step and email dispatch status.

- `POST /register/verify-otp`
  - Description: Validate the OTP code and complete registration.
  - Request:
    - `verification_code`
  - Response: Success or error message.

- `POST /register/resend-otp`
  - Description: Resend OTP to the registered email.
  - Response: Resend confirmation or rate-limit error.

## Error Contract

Common validation errors:

- `company_type` missing or invalid
- `email` invalid or already taken
- `password` weak or mismatched
- `name` required
- `tax_id` required for legal entity
- `id_number` required for natural person
- `verification_code` invalid or expired

## Success Contract

- `200 OK` on valid step transition
- `302 Redirect` to next wizard route for server-side navigation
- `422 Unprocessable Entity` for validation failures
- `200 OK` with success JSON for OTP completion in API mode

## Notes

- The wizard may be implemented using Blade views and server-side step transitions.
- If using AJAX, the endpoints should return structured JSON with `valid`, `errors`, and `next_step`.
- All registration actions should remain idempotent for the current wizard session to avoid duplicate company/user creation.
