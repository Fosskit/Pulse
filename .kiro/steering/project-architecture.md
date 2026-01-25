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

  ### Pattern 1: Single Form with Schema Switching (RECOMMENDED for Create/Edit Dialogs)
  
  Use when you have create and edit dialogs for the **same entity** (see `users.vue` and `patients.vue` examples):
  
  ```typescript
  // Define separate schemas for create and edit
  const createSchema = z.object({
    name: z.string().min(1, 'Name is required'),
    email: z.string().email('Please enter a valid email address'),
    password: z.string().min(8, 'Password must be at least 8 characters'),
  })

  const editSchema = z.object({
    name: z.string().min(1, 'Name is required'),
    email: z.string().email('Please enter a valid email address'),
    password: z.string().min(8, 'Password must be at least 8 characters').optional().or(z.literal('')),
  })

  // Single form context that switches validation based on active dialog
  const currentSchema = computed(() => isCreateDialogOpen.value ? createSchema : editSchema)

  const { handleSubmit, setValues, resetForm, isSubmitting } = useForm({
    validationSchema: computed(() => toTypedSchema(currentSchema.value)),
  })

  // Use the same handleSubmit, setValues, resetForm for both dialogs
  const onCreateSubmit = handleSubmit(async (values) => {
    // Create logic
  })

  const onEditSubmit = handleSubmit(async (values) => {
    // Edit logic
  })
  ```

  **Key Points:**
  - Use `computed()` to switch between schemas based on dialog state
  - Wrap `toTypedSchema()` in another `computed()` for reactivity
  - Single form instance works for both create and edit dialogs
  - Different schemas allow different validation rules (e.g., optional password in edit)
  - Use `@submit.prevent` on both forms to prevent default submission

  ### Pattern 2: Multiple Independent Forms (for Different Entities)
  
  Use when you have **truly different forms** on the same page (e.g., user profile form + settings form):
  
  ```typescript
  // Profile form
  const profileSchema = z.object({
    name: z.string().min(1),
    bio: z.string().optional(),
  })

  const profileForm = useForm({
    validationSchema: toTypedSchema(profileSchema),
  })

  const onProfileSubmit = profileForm.handleSubmit(async (values) => {
    // Profile update logic
  })

  // Settings form (completely different entity)
  const settingsSchema = z.object({
    theme: z.enum(['light', 'dark']),
    notifications: z.boolean(),
  })

  const settingsForm = useForm({
    validationSchema: toTypedSchema(settingsSchema),
  })

  const onSettingsSubmit = settingsForm.handleSubmit(async (values) => {
    // Settings update logic
  })
  ```

  **When to use multiple form instances:**
  - Forms manage **different entities** (User vs Settings)
  - Forms are **always visible simultaneously** (not in dialogs)
  - Forms have **completely different fields and validation**
  - Each form needs independent state management

  **DO NOT use multiple forms for:**
  - Create/Edit dialogs of the same entity (use Pattern 1 instead)
  - Forms that share similar fields or structure

  ### Custom Component Bindings:
  
  - For Input components: Use `v-bind="componentField"` directly
  - For custom components (DatePicker, etc): Explicitly bind props:
    ```vue
    <DatePicker 
      :model-value="componentField.modelValue" 
      @update:model-value="componentField['onUpdate:modelValue']"
    />
    ```
  - For radio buttons:
    ```vue
    <input
      type="radio"
      value="M"
      :checked="componentField.modelValue === 'M'"
      @change="componentField['onUpdate:modelValue']('M')"
    />
    ```
  - For Select components with transformations: Use `z.union([z.string(), z.number()]).transform()` in schema

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

## Soft Delete & Trash Management System

This project implements a comprehensive soft delete system with trash management, audit trails, and status tracking for entities like patients.

### Backend Implementation

#### Soft Deletes with Audit Trail

Models use Laravel's `SoftDeletes` trait combined with audit fields to track record lifecycle:

```php
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsModelActivity;

class Patient extends Model
{
    use SoftDeletes, LogsModelActivity;
    
    protected $fillable = [
        // ... other fields
        'status_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
```

**Audit Fields:**
- `created_by` - User ID who created the record
- `updated_by` - User ID who last updated the record
- `deleted_by` - User ID who soft-deleted the record
- `deleted_at` - Timestamp of soft deletion (from SoftDeletes trait)

#### Trash Management Actions

**ListTrashedPatientsAction** (`app/Actions/Patient/ListTrashedPatientsAction.php`):
- Retrieves soft-deleted records using `Patient::onlyTrashed()`
- Supports pagination and status filtering
- Orders by `deleted_at` descending (most recently deleted first)
- Returns paginated results with relationships loaded

```php
$query = Patient::onlyTrashed()
    ->with(['nationality', 'occupation', 'maritalStatus', 'status']);

if (request()->has('status_id')) {
    $query->where('status_id', request('status_id'));
}

$patients = $query->orderBy('deleted_at', 'desc')->paginate($perPage);
```

**RestorePatientAction** (`app/Actions/Patient/RestorePatientAction.php`):
- Restores soft-deleted records back to active state
- Clears `deleted_by` field after restoration
- Triggers activity logging via `LogsModelActivity` trait
- Returns restored record with relationships

```php
$patient = Patient::onlyTrashed()->findOrFail($id);
$patient->restore();
$patient->deleted_by = null;
$patient->save();
```

**ForceDeletePatientAction** (`app/Actions/Patient/ForceDeletePatientAction.php`):
- Permanently deletes soft-deleted records (cannot be undone)
- Only works on already trashed records (security measure)
- Manually logs the permanent deletion before removing record
- Stores patient info before deletion for audit log

```php
$patient = Patient::onlyTrashed()->findOrFail($id);

$activityLogService->log(
    action: 'force_deleted',
    model: 'Patient',
    modelId: $id,
    description: "Patient permanently deleted: {$patientCode} - {$patientName}"
);

$patient->forceDelete();
```

**DeletePatientAction** (`app/Actions/Patient/DeletePatientAction.php`):
- Performs soft delete (sets `deleted_at` timestamp)
- Records `deleted_by` user ID before deletion
- Preserves record in database for potential restoration
- Activity logging handled automatically by trait

```php
$patient->deleted_by = auth()->id();
$patient->save();
$patient->delete();
```

#### API Routes

```php
Route::middleware(['auth:api'])->prefix('patients')->group(function () {
    Route::get('/trash', ListTrashedPatientsAction::class);
    Route::post('/{id}/restore', RestorePatientAction::class);
    Route::delete('/{id}/force', ForceDeletePatientAction::class);
    Route::delete('/{patient}', DeletePatientAction::class); // Soft delete
});
```

**Note:** No specific permissions are enforced on trash routes in current implementation. Consider adding:
- `patients.restore` - For restore operations
- `patients.force-delete` - For permanent deletion

#### Status Management System

Patient records support lifecycle status tracking via the `patient_statuses` reference table:

**PatientStatus Model** (`app/Models/Reference/PatientStatus.php`):
- Extends `BaseReference` for consistent reference data structure
- Includes `color` field for UI badge styling

**Available Statuses:**
1. **Active** (ID: 1, Color: green) - Default for new patients actively receiving care
2. **Inactive** (ID: 2, Color: gray) - Patient no longer visits facility
3. **Archived** (ID: 3, Color: blue) - Historical records, read-only
4. **Pending Verification** (ID: 4, Color: yellow) - New registrations awaiting verification
5. **Blocked** (ID: 5, Color: red) - Flagged for review or restricted access

**Status Filtering:**
```php
// In Patient model
public function scopeWithStatus($query, $statusId)
{
    return $query->where('status_id', $statusId);
}

// Usage in actions
Patient::withStatus(1)->get(); // Get active patients
```

**Seeding Statuses:**
Run `PatientStatusSeeder` to populate default statuses. Status data is managed via the generic reference CRUD system at `/api/references/patient-statuses`.

### Frontend Implementation

#### Tab-Based View Switching

Patient list page uses tabs to switch between active and trashed records:

```typescript
const viewMode = ref<'active' | 'trash'>('active')

const fetchPatients = async (page = 1) => {
  const endpoint = viewMode.value === 'trash' 
    ? '/patients/trash' 
    : '/patients'
  
  const params = {
    per_page: perPage.value,
    page,
    ...(statusFilter.value && { status_id: statusFilter.value })
  }
  
  const response = await api.get(endpoint, { params })
  // Update state...
}
```

**UI Structure:**
- **Active Patients Tab**: Shows non-deleted records with delete button (soft delete)
- **Trash Tab**: Shows soft-deleted records with restore and force delete buttons

#### Status Management UI

**Status Badge Display:**
- Color-coded badges based on `color` field from patient_statuses
- Displayed in patient list table for both active and trash views
- Uses shadcn-vue Badge component with dynamic variant

**Status Filtering:**
- Dropdown above patient table to filter by status
- "All Statuses" option to clear filter
- Applies to both active and trash views
- Fetches status options from `/api/references/patient-statuses`

**Status Selection in Forms:**
- Status dropdown in create/edit dialogs
- Defaults to "Active" (status_id: 1) for new patients
- Allows status changes during patient updates

#### Trash Management Actions

**Restore Patient:**
```typescript
const restorePatient = async (patient: Patient) => {
  await api.post(`/patients/${patient.id}/restore`)
  toast.success('Patient restored successfully')
  await fetchPatients(currentPage.value)
}
```

**Force Delete Patient:**
```typescript
const forceDeletePatient = async (patient: Patient) => {
  // Show confirmation AlertDialog first
  await api.delete(`/patients/${patient.id}/force`)
  toast.success('Patient permanently deleted')
  await fetchPatients(currentPage.value)
}
```

**Soft Delete Patient:**
```typescript
const deletePatient = async (patient: Patient) => {
  await api.delete(`/patients/${patient.id}`)
  toast.success('Patient moved to trash')
  await fetchPatients(currentPage.value)
}
```

#### UI Components Used

- **Tabs** (shadcn-vue): Switch between Active/Trash views
- **Badge** (shadcn-vue): Display color-coded status
- **Select** (shadcn-vue): Status filter dropdown
- **AlertDialog** (shadcn-vue): Confirmation for force delete
- **Sonner Toast**: Success/error notifications

### Adding Trash System to New Entities

**Backend Steps:**
1. Add `SoftDeletes` trait to model
2. Add audit fields to fillable: `created_by`, `updated_by`, `deleted_by`
3. Create trash management actions (List, Restore, ForceDelete)
4. Update Store action to set `created_by = auth()->id()`
5. Update Update action to set `updated_by = auth()->id()`
6. Update Delete action to set `deleted_by` before calling `delete()`
7. Add trash routes: `/entity/trash`, `/entity/{id}/restore`, `/entity/{id}/force`

**Frontend Steps:**
1. Add `viewMode` state for tab switching
2. Add tabs component with Active/Trash views
3. Update fetch function to use correct endpoint based on viewMode
4. Add restore button in trash view
5. Add force delete button with confirmation dialog in trash view
6. Update delete button to perform soft delete in active view
7. Add appropriate toast notifications

### Best Practices

1. **Always set audit fields** - Use `auth()->id()` to populate created_by, updated_by, deleted_by
2. **Force delete only trashed records** - Use `onlyTrashed()` in ForceDeleteAction for safety
3. **Clear deleted_by on restore** - Set to null when restoring records
4. **Manual logging for force delete** - Activity trait doesn't catch forceDelete, log manually
5. **Confirmation for permanent deletion** - Always show AlertDialog before force delete
6. **Order trash by deleted_at desc** - Show most recently deleted first
7. **Load relationships in trash view** - Include necessary relationships for display
8. **Support status filtering in trash** - Allow filtering trashed records by status
