# Research: Multi-Enterprise Registration Wizard with OTP

## Decision

Implement the registration workflow as a 5-step wizard inside a dedicated Laravel `Registration` module. The wizard will support both Legal Entity and Natural Person registration paths and require OTP-based email confirmation before a user may log in.

## Rationale

- The existing specification emphasizes separate legal entity and natural person flows, so a wizard is the best UX pattern to guide users cleanly through the conditional fields.
- OTP verification during registration provides a stronger email confirmation experience than a passive verification link and keeps the flow tightly coupled to user onboarding.
- A dedicated module preserves the repository's module-based architecture and makes the feature easier to test and maintain.
- Tailwind CSS is already mandated, so the visual design can be implemented with utility classes and a shared brand palette.

## Alternatives Considered

- **Single-page form**: rejected because the spec requires two distinct company types and a wizard helps avoid data overload.
- **Email verification link only**: rejected due to the explicit OTP requirement in the user request.
- **Persisting wizard state only in the frontend**: rejected because server-side session or temporary storage provides more reliable recovery and validation.
- **Embedding OTP in login flow after registration**: rejected because the requirement calls for OTP confirmation during registration.

## Clarifications

- Use a time-limited 6-digit OTP sent by email.
- Verify OTP in a dedicated fifth wizard step.
- Keep `legal_entity_flag` and `natural_entity_flag` fields while introducing `company_type` as canonical metadata.
- Prefer service classes and repository abstractions to preserve layered architecture.
