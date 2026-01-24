# CRUD Implementation Summary

## Backend (Laravel)

### Patient CRUD
Created complete CRUD actions for Patient management:

**Actions** (`app/Actions/Patient/`):
- `ListPatientsAction.php` - List patients with pagination
- `ShowPatientAction.php` - Show single patient
- `StorePatientAction.php` - Create new patient
- `UpdatePatientAction.php` - Update existing patient
- `DeletePatientAction.php` - Delete patient

**Form Requests** (`app/Http/Requests/Patient/`):
- `StorePatientRequest.php` - Validation for creating patients
- `UpdatePatientRequest.php` - Validation for updating patients

**Resource**:
- `app/Http/Resources/PatientResource.php` - API response transformation

**Model Updates**:
- Updated `app/Models/Patient.php` with:
  - Fillable fields
  - Relationships (nationality, occupation, maritalStatus)
  - Activity logging trait
  - Date casting

**Routes**:
Added to `routes/api.php`:
```php
Route::middleware(['auth:api'])->prefix('patients')->group(function () {
    Route::get('/', \App\Actions\Patient\ListPatientsAction::class);
    Route::get('/{patient}', \App\Actions\Patient\ShowPatientAction::class);
    Route::post('/', \App\Actions\Patient\StorePatientAction::class);
    Route::put('/{patient}', \App\Actions\Patient\UpdatePatientAction::class);
    Route::delete('/{patient}', \App\Actions\Patient\DeletePatientAction::class);
});
```

### Reference Data
Reference CRUD already exists via `ReferenceController` for:
- Nationalities (`/api/references/nationalities`)
- Occupations (`/api/references/occupations`)
- Marital Statuses (`/api/references/marital-statuses`)

## Frontend (Nuxt)

### Patient Management Page
**File**: `nuxt/app/pages/patients.vue`

Features:
- List all patients with pagination
- Create new patient with form validation
- Edit existing patient
- Delete patient with confirmation
- Displays patient info: code, name, sex, birthdate, nationality, telephone
- Loads reference data (nationalities, occupations, marital statuses) for dropdowns
- Full CRUD operations with error handling

### Reference Data Management Page
**File**: `nuxt/app/pages/settings/references.vue`

Features:
- **Scalable dropdown selector** instead of tabs (handles many reference types)
- Single page manages all reference types:
  - Nationalities
  - Occupations
  - Marital Statuses
- List with pagination
- Create, edit, delete operations
- Shows code, name, description, status
- Easy to add new reference types by updating the `referenceTypes` array

### Navigation Updates
Updated `nuxt/app/components/AppSidebar.vue`:
- Added "Patients" section with "Patient List" link
- Added "Reference Data" link under Settings section

## Usage

### Backend
```bash
# Already migrated and seeded
php artisan serve
```

### Frontend
```bash
cd nuxt
pnpm dev
```

### Access Pages
- Patients: http://localhost:3000/patients
- Reference Data: http://localhost:3000/settings/references

## Adding More Reference Types

To add a new reference type (e.g., "Blood Types"):

1. **Backend**: Add to `ReferenceController::$modelMap`:
```php
'blood-types' => \App\Models\Reference\BloodType::class,
```

2. **Frontend**: Add to `referenceTypes` array in `references.vue`:
```javascript
{ key: 'blood-types', label: 'Blood Types', singular: 'Blood Type' }
```

That's it! The dropdown will automatically include the new type.
