# Quickstart: Auth UI & Profile Management Implementation

**Phase**: 1 (Implementation Guide) | **Date**: 2026-06-25

This guide outlines the step-by-step implementation path for the Auth UI & Profile Management feature, following TDD principles as per the FlexDash Constitution.

---

## Overview

**Feature Scope**:

1. Navigation between login/registration with state persistence
2. Theme toggle (light/dark/system) accessible on all pages
3. User profile view & edit (name, phone, preferences)
4. Password change with OTP verification
5. Additional preferences management (language, timezone, notifications)

**Implementation Order** (by priority):

- **Phase 1** (P1 - Week 1): Profile view/edit + Theme toggle
- **Phase 2** (P1 - Week 2): Password change with OTP
- **Phase 3** (P2 - Week 3+): Additional preferences + Navigation

---

## Setup & Prerequisites

### 1. Database Migrations

**Time**: 30 min

```bash
# Generate migrations
php artisan make:migration add_user_preferences --table=users
php artisan make:migration create_otp_tokens_table

# Copy migration files from specs/018-auth-ui-and-profile/data-model.md
# Edit and run
php artisan migrate
```

**Verify**:

```bash
php artisan tinker
>>> DB::table('users')->first(); // Check for new columns
>>> DB::table('otp_tokens')->first(); // Should exist (empty)
```

### 2. Models & Database Setup

**Time**: 45 min

Create/update `app/Models/User.php`:

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'name', 'email', 'phone', 'theme_preference',
        'language', 'timezone', 'notifications_enabled'
    ];

    protected $hidden = ['password_hash'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'notifications_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationship
    public function otpTokens(): HasMany
    {
        return $this->hasMany(OtpToken::class);
    }
}
```

Create `app/Models/OtpToken.php`:

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpToken extends Model
{
    public $timestamps = false; // Only created_at

    protected $fillable = [
        'id', 'user_id', 'token', 'purpose', 'attempts',
        'expires_at', 'used_at', 'invalidated_at', 'created_at'
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'invalidated_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scope: active OTP tokens (not used, not expired, not invalidated)
    public function scopeActive($query)
    {
        return $query
            ->whereNull('used_at')
            ->whereNull('invalidated_at')
            ->where('expires_at', '>', now());
    }
}
```

### 3. Service Layer (TDD First!)

**Time**: 2-3 hours

#### Step 1: Write Tests First

Create `tests/Feature/ProfileServiceTest.php`:

```php
<?php
namespace Tests\Feature;

use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\TestCase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_profile_returns_user_data()
    {
        $user = User::factory()->create(['name' => 'Juan']);
        $service = new ProfileService();

        $profile = $service->getProfile($user);

        $this->assertEquals('Juan', $profile['name']);
        $this->assertEquals($user->email, $profile['email']);
    }

    public function test_update_profile_persists_changes()
    {
        $user = User::factory()->create();
        $service = new ProfileService();

        $updated = $service->updateProfile($user, [
            'name' => 'Carlos',
            'phone' => '+593987654321',
        ]);

        $this->assertEquals('Carlos', $updated->name);
        $this->assertEquals('+593987654321', $updated->phone);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Carlos']);
    }

    public function test_update_profile_validates_email()
    {
        $user = User::factory()->create();
        $service = new ProfileService();

        $this->expectException(\InvalidArgumentException::class);
        $service->updateProfile($user, ['email' => 'invalid-email']);
    }
}
```

Run test (watch it fail):

```bash
php artisan test tests/Feature/ProfileServiceTest.php
# Tests FAIL (expected - TDD Red phase)
```

#### Step 2: Implement Service (Green Phase)

Create `app/Modules/Auth/Services/ProfileService.php`:

```php
<?php
namespace App\Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class ProfileService
{
    public function getProfile(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'theme_preference' => $user->theme_preference,
            'language' => $user->language,
            'timezone' => $user->timezone,
            'notifications_enabled' => $user->notifications_enabled,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    public function updateProfile(User $user, array $data): User
    {
        $validator = Validator::make($data, [
            'name' => 'string|max:255',
            'phone' => 'nullable|regex:/^\+?593\d{9}$/',
            'language' => 'in:es,en',
            'timezone' => 'in:America/Guayaquil,America/New_York',
            'notifications_enabled' => 'boolean',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        return tap($user)->update($validator->validated());
    }
}
```

Run test (watch it pass):

```bash
php artisan test tests/Feature/ProfileServiceTest.php
# Tests PASS (Green phase)
```

#### Step 3: Refactor (Refactor Phase)

Improve service code, extract validation, etc.

### 4. Controllers & Routes

**Time**: 1 hour

Create `app/Modules/Auth/Controllers/ProfileController.php`:

```php
<?php
namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController
{
    public function __construct(
        private ProfileService $profileService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $profile = $this->profileService->getProfile($request->user());

        return response()->json([
            'success' => true,
            'data' => $profile,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $updated = $this->profileService->updateProfile(
            $request->user(),
            $request->only(['name', 'phone', 'language', 'timezone', 'notifications_enabled'])
        );

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado exitosamente',
            'data' => $this->profileService->getProfile($updated),
        ]);
    }

    public function updateTheme(Request $request): JsonResponse
    {
        $request->validate(['theme_preference' => 'in:light,dark,system']);

        $user = $request->user();
        $user->update(['theme_preference' => $request->theme_preference]);

        return response()->json([
            'success' => true,
            'message' => 'Tema actualizado exitosamente',
            'data' => ['theme_preference' => $user->theme_preference],
        ]);
    }
}
```

Add routes in `routes/api.php`:

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::patch('/profile/theme', [ProfileController::class, 'updateTheme']);
});
```

### 5. OTP Service (Password Change)

**Time**: 2 hours (same TDD workflow)

Create `app/Modules/Auth/Services/OtpService.php`:

```php
<?php
namespace App\Modules\Auth\Services;

use App\Models\OtpToken;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OtpService
{
    private const OTP_LENGTH = 6;
    private const EXPIRY_MINUTES = 10;
    private const MAX_ATTEMPTS = 3;

    public function generateOtp(User $user, string $purpose = 'password_change'): OtpToken
    {
        // Invalidate previous OTPs
        $user->otpTokens()
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->update(['invalidated_at' => now()]);

        // Generate new OTP
        $plainToken = str_pad(random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
        $hashedToken = hash('sha256', $plainToken);

        $otp = OtpToken::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'token' => $hashedToken,
            'purpose' => $purpose,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
            'created_at' => now(),
        ]);

        // Send OTP via email (async)
        Mail::queue(new \App\Mail\OtpMail($user, $plainToken));

        return $otp;
    }

    public function verifyOtp(User $user, string $plainToken): bool
    {
        $hashedToken = hash('sha256', $plainToken);

        $otp = $user->otpTokens()
            ->active()
            ->where('token', $hashedToken)
            ->first();

        if (!$otp) {
            return false;
        }

        $otp->increment('attempts');

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            $otp->update(['invalidated_at' => now()]);
            return false;
        }

        $otp->update(['used_at' => now()]);
        return true;
    }
}
```

---

## Frontend Implementation (Vue 3)

### Theme Toggle Component

Create `resources/js/components/ThemeToggle.vue`:

```vue
<template>
    <button
        @click="toggleTheme"
        class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700"
        :title="themeLabel"
    >
        <svg v-if="currentTheme === 'dark'" class="w-5 h-5 text-yellow-400">
            <!-- Sun icon -->
        </svg>
        <svg v-else class="w-5 h-5 text-gray-600">
            <!-- Moon icon -->
        </svg>
    </button>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

const currentTheme = ref("light");

onMounted(() => {
    // Load from localStorage
    const saved = localStorage.getItem("flexdash_theme");
    if (saved) currentTheme.value = saved;
    applyTheme(currentTheme.value);
});

const toggleTheme = async () => {
    const newTheme = currentTheme.value === "dark" ? "light" : "dark";
    currentTheme.value = newTheme;
    localStorage.setItem("flexdash_theme", newTheme);

    applyTheme(newTheme);

    // Persist to server
    try {
        await axios.patch("/api/profile/theme", { theme_preference: newTheme });
    } catch (error) {
        console.error("Failed to save theme preference", error);
        // Gracefully degrade: theme toggle works offline
    }
};

const applyTheme = (theme) => {
    if (theme === "dark") {
        document.documentElement.classList.add("dark");
    } else {
        document.documentElement.classList.remove("dark");
    }
};

const themeLabel = computed(() =>
    currentTheme.value === "dark"
        ? "Cambiar a tema claro"
        : "Cambiar a tema oscuro",
);
</script>
```

### Profile Page Component

Create `resources/js/pages/Profile.vue`:

```vue
<template>
    <div class="max-w-2xl mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6">Mi Perfil</h1>

        <!-- Edit Mode -->
        <form v-if="editMode" @submit.prevent="saveProfile">
            <div class="space-y-4 mb-6">
                <input
                    v-model="formData.name"
                    placeholder="Nombre"
                    class="w-full border p-2"
                />
                <input
                    v-model="formData.phone"
                    placeholder="Teléfono"
                    class="w-full border p-2"
                />
                <select v-model="formData.language" class="w-full border p-2">
                    <option value="es">Español</option>
                    <option value="en">English</option>
                </select>
            </div>
            <button
                type="submit"
                class="bg-blue-500 text-white px-4 py-2 rounded"
            >
                Guardar
            </button>
            <button
                type="button"
                @click="editMode = false"
                class="ml-2 px-4 py-2"
            >
                Cancelar
            </button>
        </form>

        <!-- View Mode -->
        <div v-else class="space-y-4">
            <p><strong>Nombre:</strong> {{ user?.name }}</p>
            <p><strong>Email:</strong> {{ user?.email }}</p>
            <p><strong>Teléfono:</strong> {{ user?.phone || "-" }}</p>
            <p>
                <strong>Idioma:</strong>
                {{ user?.language === "es" ? "Español" : "English" }}
            </p>
            <button
                @click="editMode = true"
                class="bg-blue-500 text-white px-4 py-2 rounded"
            >
                Editar
            </button>
        </div>

        <!-- Password Change -->
        <hr class="my-6" />
        <button
            @click="showPasswordForm = true"
            class="bg-red-500 text-white px-4 py-2 rounded"
        >
            Cambiar Contraseña
        </button>

        <!-- Password Change Modal -->
        <PasswordChangeModal
            v-if="showPasswordForm"
            @close="showPasswordForm = false"
        />
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

const user = ref(null);
const editMode = ref(false);
const showPasswordForm = ref(false);
const formData = ref({});

onMounted(async () => {
    const response = await axios.get("/api/profile");
    user.value = response.data.data;
    formData.value = { ...user.value };
});

const saveProfile = async () => {
    try {
        const response = await axios.put("/api/profile", formData.value);
        user.value = response.data.data;
        editMode.value = false;
    } catch (error) {
        console.error("Error saving profile", error);
    }
};
</script>
```

---

## Testing Checklist

- [ ] Unit: ProfileService.getProfile()
- [ ] Unit: ProfileService.updateProfile()
- [ ] Unit: OtpService.generateOtp()
- [ ] Unit: OtpService.verifyOtp()
- [ ] Feature: GET /api/profile returns user data
- [ ] Feature: PUT /api/profile updates user
- [ ] Feature: PATCH /api/profile/theme updates theme
- [ ] Feature: POST /api/password/request-otp sends email
- [ ] Feature: POST /api/password/verify-otp validates token
- [ ] Feature: POST /api/password/reset changes password and logs out user
- [ ] Integration: Theme toggle persists across page reload
- [ ] Integration: Form state persists when navigating between pages
- [ ] Acceptance: User can view and edit profile
- [ ] Acceptance: User can change password with OTP

---

## Deployment Checklist

- [ ] Run migrations: `php artisan migrate --force`
- [ ] Run tests: `php artisan test`
- [ ] Check code standards: `php artisan pint` + `phpstan analyse`
- [ ] Verify Resend API key in `.env`
- [ ] Test OTP email delivery in staging
- [ ] Verify theme toggle works in all browsers
- [ ] Verify profile persistence
- [ ] Load test: 100 concurrent profile updates
- [ ] Security: Verify OTP tokens are hashed
- [ ] Security: Verify password reset logs out all sessions

---

## Estimated Timeline

| Phase | Task                        | Time | Dependencies |
| ----- | --------------------------- | ---- | ------------ |
| 1     | Database + Models           | 1h   | -            |
| 1     | Profile Service (TDD)       | 2h   | Phase 1      |
| 1     | Profile Controller + Routes | 1h   | Phase 1      |
| 1     | Theme Toggle (Frontend)     | 1.5h | Phase 1      |
| 2     | OTP Service (TDD)           | 2h   | Phase 1      |
| 2     | Password Change Controller  | 1h   | Phase 2      |
| 2     | Password Change UI          | 1.5h | Phase 2      |
| 3     | Additional Preferences UI   | 1h   | Phase 1      |
| 3     | Navigation Back Button      | 1h   | -            |
| 3     | Testing & QA                | 2h   | All          |

**Total Estimated Effort**: 14.5 hours (1.8 engineering days)
