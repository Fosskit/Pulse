# Copilot Instructions for fosskit-pulse

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

`ActivityLogService` logs user actions automatically:
```php
$activityLog = app(ActivityLogService::class)->log(
    action: 'user.created',
    model: 'User',
    modelId: $user->id,
    description: 'User created by admin'
);
```

Used in admin actions to track changes. Service captures user_id, IP, user agent automatically.

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
