# Project Architecture & Development Guidelines

## Architecture Overview

This is a **monorepo** containing:
- **Backend**: Laravel 12 API with Laravel Passport (OAuth2) authentication
- **Frontend**: Nuxt 4 (Vue 3) TypeScript application with shadcn-vue components

The backend serves a JSON API at `/api/*`, and the frontend is a separate SPA that consumes it.

## Backend (Laravel API)

### Action-Based Architecture

**Routes register Action classes directly** instead of controller methods:
```php
Route::post('/auth/login', LoginAction::class);
Route::post('/admin/users', StoreUserAction::class);
```

- **Actions** (`app/Actions/`) are single-responsibility invokable classes
- Each action handles ONE route endpoint
- Actions live in folders matching their domain: `Auth/`, `Admin/Users/`, `Admin/Roles/`
- Actions validate via Form Requests, transform via Resources, and return JsonResponse

**Creating new endpoints:**
1. Create Action class in `app/Actions/{Domain}/`
2. Create Form Request in `app/Http/Requests/{Domain}/` if input validation needed
3. Register route in `routes/api.php`
4. Add permission check if admin route (see Permission Model below)

### Permission-Based Authorization

Uses **Spatie Laravel Permission** with `guard_name = 'api'`:

```php
// Middleware protects routes by permission (NOT roles)
Route::middleware(['auth:api', 'permission:users.view'])->group(function () {
    Route::post('/admin/users', StoreUserAction::class)
        ->middleware('permission:users.create');
});
```

**Permission naming convention**: `{resource}.{action}` (e.g., `users.create`, `roles.delete`)

- **Roles are permission containers** - routes check permissions, NOT roles
- User model uses `HasRoles` trait with `protected $guard_name = 'api'`
- Permissions seeded in `RolePermissionSeeder` - update there when adding new features
- Users get permissions via roles OR direct assignment

### Authentication Flow

- **Laravel Passport** handles OAuth2 tokens (not Sanctum)
- Login returns: `{ access_token, token_type: 'Bearer', user: {...} }`
- Email verification required before login (`MustVerifyEmail` interface)
- Token refresh available at `/api/auth/refresh`

### Activity Logging

Activity logging is handled automatically via the `LogsModelActivity` trait:

```php
use App\Traits\LogsModelActivity;

class User extends Model
{
    use LogsModelActivity;
    
    // Model automatically logs created, updated, deleted events
}
```

The trait hooks into model events and logs:
- `created` - When a model is created
- `updated` - When a model is updated  
- `deleted` - When a model is deleted (including soft deletes)

Logs are stored in `activity_logs` table with user_id, IP, user agent, and request details automatically captured.

**Manual logging** (for custom actions):
```php
app(ActivityLogService::class)->log(
    action: 'custom.action',
    model: 'ModelName',
    modelId: $id,
    description: 'Custom action description'
);
```

**Disabling logging for specific models:**
Add to `config/activitylog.php`:
```php
'model_logging' => [
    'modelname' => false, // lowercase model name
],
```

### API Documentation

**Scramble** generates OpenAPI docs automatically from code. Docs likely available at `/docs/api`.

### Development Commands

```bash
# Backend setup
composer install
php artisan migrate:fresh --seed  # Creates admin user + roles
php artisan passport:install      # Generate OAuth keys

# Testing
php artisan test                  # Run PHPUnit tests
php artisan pint                  # Fix code style

# Development
php artisan serve                 # Start dev server on :8000
```

## Frontend (Nuxt 4)

### Project Structure

- **Composables** (`app/composables/`):
  - `useAuth()` - Authentication state, login/logout/register
  - `useApi()` - Typed HTTP client with auto Bearer token injection
  - `useErrorHandler()` - Centralized error handling

- **Components**: Using **shadcn-vue** components (`@/components/ui/`)
- **Pages**: File-based routing (`app/pages/`)
- **Types**: TypeScript interfaces (`app/types/`)

### API Integration Pattern

```typescript
// Always use composables, never raw $fetch
const api = useApi()
const response = await api.post<AuthResponse>('/auth/login', credentials)

// Auth composable provides:
const { user, isAuthenticated, login, logout } = useAuth()
```

- Token stored in cookie (`auth_token`, 7-day expiry)
- `useApi()` automatically adds `Authorization: Bearer {token}` header
- API base URL configured via `NUXT_PUBLIC_API_URL` env var (default: `http://localhost:8000/api`)

### Frontend Development

```bash
cd nuxt
pnpm install
pnpm dev                          # Starts on :3000
pnpm build                        # Production build
```

## Key Conventions

1. **Never mix controllers and actions** - This project uses Actions exclusively
2. **Permission checks on routes** - Never check roles directly, always permissions
3. **Guard name is 'api'** - All Spatie models must use `guard_name = 'api'`
4. **Type everything in Nuxt** - Use TypeScript interfaces for API responses
5. **Composables for logic** - Keep components presentational, logic in composables
6. **When you need a new component, always add it via:**
  `pnpm dlx shadcn-vue@latest add <component>`
  Example: `pnpm dlx shadcn-vue@latest add pagination`
7. **Form handling with vee-validate + Zod:**
  - **CRITICAL**: Never use a single `useForm()` for multiple forms (create + edit dialogs)
  - **Always create separate form instances** for each dialog:
    ```typescript
    const createForm = useForm({ validationSchema: toTypedSchema(schema) })
    const editForm = useForm({ validationSchema: toTypedSchema(schema) })
    ```
  - Use `createForm.handleSubmit()`, `createForm.resetForm()`, `createForm.setValues()`
  - Use `editForm.handleSubmit()`, `editForm.resetForm()`, `editForm.setValues()`
  - Access loading state via `createForm.isSubmitting.value` and `editForm.isSubmitting.value`
  - **Alternative approach**: Use computed schema switching (see `users.vue` example)
    ```typescript
    const currentSchema = computed(() => isCreateDialogOpen.value ? createSchema : editSchema)
    const { handleSubmit, setValues, resetForm, isSubmitting } = useForm({
      validationSchema: computed(() => toTypedSchema(currentSchema.value)),
    })
    ```

## Database Seeding

Run `php artisan db:seed` to create:
- Default permissions (users.*, roles.*, permissions.*, activity.*)
- Roles: Super Admin, Admin, Manager, User
- Admin user (check `AdminUserSeeder` for credentials)

## Cross-Origin Setup

Backend likely needs CORS configured in `config/cors.php` to accept frontend requests. Frontend API URL set in `nuxt.config.ts` runtimeConfig.

## Adding New Features

**Backend:**
1. Create migration for new tables
2. Add permissions to `RolePermissionSeeder`
3. Create Action classes for endpoints
4. Add routes with permission middleware
5. Create Form Requests + Resources as needed

**Frontend:**
1. Define types in `app/types/`
2. Add API methods to composable (or create new one)
3. Create pages/components using shadcn-vue
4. Use `useAuth()` for permission-based UI rendering

## Reference Data System

This project uses a generic reference CRUD system for tables with common structure (code, name, description, status_id).

**Reference tables include:**
- Nationalities
- Occupations
- Marital Statuses

**Adding new reference tables:**
1. Create migration using `commonFields()` and `metaFields()` macros
2. Create model extending `App\Models\Reference\BaseReference`
3. Add to `$modelMap` in `ReferenceController`
4. Create seeder if needed

**API endpoints:** `/api/references/{type}` (GET, POST, PUT, DELETE)

See `app/docs/REFERENCE_CRUD.md` for detailed documentation.


## Code Generation System

This project includes an automatic code generation system for entities like patients, invoices, etc.

### Backend

**CodeGenerator Model** (`app/Models/CodeGenerator.php`):
- Stores configuration for each entity type
- Fields: `entity`, `prefix`, `format`, `current_sequence`, `reset_yearly`, `reset_monthly`, `padding`
- Auto-increments sequence and generates codes based on format

**CodeGeneratorService** (`app/Services/CodeGeneratorService.php`):
- `generate(string $entity)` - Generates next code for entity
- `getOrCreate(string $entity, array $defaults)` - Get or create generator config

**Format Placeholders**:
- `{prefix}` - Custom prefix (e.g., PAT, INV)
- `{year}` - Full year (2026)
- `{year2}` - Short year (26)
- `{month}` - Month (01-12)
- `{day}` - Day (01-31)
- `{seq:5}` - Sequence with padding (00001)

**Example Formats**:
- `{prefix}-{year}-{seq:5}` → PAT-2026-00001
- `{prefix}{year2}{month}{seq:4}` → PAT260100001
- `{prefix}-{seq:6}` → PAT-000001

**Usage in Actions**:
```php
public function __invoke(StorePatientRequest $request, CodeGeneratorService $codeGenerator)
{
    $data = $request->validated();
    
    // Auto-generate code if not provided
    if (empty($data['code'])) {
        $data['code'] = $codeGenerator->generate('patient');
    }
    
    $patient = Patient::create($data);
    // ...
}
```

### Frontend

**Code Generator Settings** (`/settings/code-generators`):
- Configure format, prefix, padding for each entity
- Live preview of generated code format
- Toggle yearly/monthly sequence reset
- Helpful placeholder reference card

**Adding Code Generation to New Entities**:
1. Create seeder entry in `CodeGeneratorSeeder`
2. Inject `CodeGeneratorService` in Store action
3. Call `$codeGenerator->generate('entity-name')` if code is empty
4. Frontend will auto-generate on create (leave code field empty)
