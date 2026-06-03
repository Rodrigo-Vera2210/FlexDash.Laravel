# Data Model: Multi-Enterprise Registration

## Entities

### Company

- `id`
- `company_type` enum: `legal_entity` | `natural_person`
- `name`
- `tax_id` nullable
- `legal_address` nullable
- `address` nullable
- `city`
- `state_province`
- `postal_code`
- `country`
- `legal_entity_flag` boolean
- `natural_entity_flag` boolean
- `created_at`
- `updated_at`

### User

- `id`
- `company_id` foreign key to `companies`
- `email`
- `password`
- `name`
- `role` enum: `company_representative` | `owner` | `staff`
- `email_verified_at` nullable
- `status` enum: `pending_verification` | `active`
- `created_at`
- `updated_at`

### EmailVerification

- `id`
- `user_id` foreign key to `users`
- `verification_code` hashed
- `expires_at`
- `attempts` integer
- `created_at`
- `updated_at`

## Relationships

- Company has many Users
- User belongs to Company
- EmailVerification belongs to User

## Notes

- For natural person registration, user and company records share the same personal/address fields.
- Company flags (`legal_entity_flag`, `natural_entity_flag`) allow compatibility with existing expectations while `company_type` is the canonical discriminator.
- OTP state is kept separate from `User` to minimize optional verification state on the user entity.
- The registration module should create pending records in a transaction and only mark the user as active after OTP verification.
