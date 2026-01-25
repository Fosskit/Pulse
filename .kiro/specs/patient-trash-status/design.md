# Patient Trash and Status Management - Design Document

## Architecture Overview

This feature extends the existing patient CRUD system with soft delete management and status tracking, utilizing existing database columns without requiring schema changes.

## Database Design

### Existing Schema (No Changes Required)
```sql
patients table:
- deleted_at (timestamp, nullable) - Soft delete timestamp
- deleted_by (smallint, nullable) - User ID who deleted
- created_by (smallint, nullable) - User ID who created
- updated_by (smallint, nullable) - User ID who updated
- status_id (tinyint, default 1) - Patient status reference
```

### New Reference Table
```sql
patient_statuses table:
- id (tinyint, primary key)
- code (string) - Status code (e.g., 'active', 'inactive')
- name (string) - Display name (e.g., 'Active', 'Inactive')
- description (text) - Status description
- color (string) - Badge color (e.g., 'green', 'gray', 'red')
- status_id (tinyint) - Meta status (active/inactive)
```

### Status Values
1. Active - Green badge - Default for new patients
2. Inactive - Gray badge - Patient no longer visits
3. Archived - Blue badge - Old records, read-only
4. Pending Verification - Yellow badge - New registrations
5. Blocked - Red badge - Flagged for review

## Backend Implementation

### 1. Model Updates

**Patient.php**
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes, LogsModelActivity;
    
    protected $fillable = [
        // ... existing fields
        'status_id',
    ];
    
    public function status(): BelongsTo
    {
        return $this->belongsTo(PatientStatus::class, 'status_id');
    }
    
    // Scope for filtering by status
    public function scopeWithStatus($query, $statusId)
    {
        return $query->where('status_id', $statusId);
    }
}
```

**PatientStatus.php** (New Model)
```php
namespace App\Models\Reference;

class PatientStatus extends BaseReference
{
    protected $table = 'patient_statuses';
}
```

### 2. New Actions

**ListTrashedPatientsAction.php**
- Query: `Patient::onlyTrashed()->with(['nationality', 'status'])->paginate()`
- Returns: Paginated list of soft-deleted patients
- Includes: deleted_at, deleted_by information

**RestorePatientAction.php**
- Input: Patient ID
- Logic: `$patient->restore()`
- Activity Log: "Patient restored"
- Returns: Restored patient resource

**ForceDeletePatientAction.php**
- Input: Patient ID
- Logic: `$patient->forceDelete()`
- Activity Log: "Patient permanently deleted"
- Returns: Success message

**UpdatePatientStatusAction.php** (Optional separate action)
- Input: Patient ID, status_id
- Logic: Update status and log change
- Returns: Updated patient resource

### 3. Updated Actions

**StorePatientAction.php**
```php
$data['created_by'] = auth()->id();
$data['status_id'] = $data['status_id'] ?? 1; // Default to Active
```

**UpdatePatientAction.php**
```php
$data['updated_by'] = auth()->id();
```

**DeletePatientAction.php**
```php
// Soft delete
$patient->deleted_by = auth()->id();
$patient->save();
$patient->delete();
```

### 4. API Routes

```php
// Trash management
Route::get('/patients/trash', ListTrashedPatientsAction::class)
    ->middleware('permission:patients.view');
Route::post('/patients/{id}/restore', RestorePatientAction::class)
    ->middleware('permission:patients.restore');
Route::delete('/patients/{id}/force', ForceDeletePatientAction::class)
    ->middleware('permission:patients.delete');

// Status reference
Route::get('/references/patient-statuses', [ReferenceController::class, 'index']);
```

### 5. Permissions

Add to `RolePermissionSeeder`:
- `patients.restore` - Restore deleted patients
- `patients.force-delete` - Permanently delete patients

### 6. Database Seeder

**PatientStatusSeeder.php**
```php
PatientStatus::insert([
    ['id' => 1, 'code' => 'active', 'name' => 'Active', 'color' => 'green'],
    ['id' => 2, 'code' => 'inactive', 'name' => 'Inactive', 'color' => 'gray'],
    ['id' => 3, 'code' => 'archived', 'name' => 'Archived', 'color' => 'blue'],
    ['id' => 4, 'code' => 'pending', 'name' => 'Pending Verification', 'color' => 'yellow'],
    ['id' => 5, 'code' => 'blocked', 'name' => 'Blocked', 'color' => 'red'],
]);
```

## Frontend Implementation

### 1. UI Components

**Tabs Component**
- Active Patients (default view)
- Trash (shows soft-deleted patients)

**Status Filter Dropdown**
- "All Statuses" option
- Individual status options
- Applies to both Active and Trash views

**Status Badge**
- Color-coded based on status
- Displayed in patient list table
- Shows status name

### 2. Patient List Updates

**Active View:**
- Shows non-deleted patients
- Delete button performs soft delete
- Status badge visible
- Status filter dropdown

**Trash View:**
- Shows soft-deleted patients
- Restore button (green)
- Force Delete button (red, requires confirmation)
- Shows "Deleted on [date] by [user]"
- Status badge visible (grayed out)

### 3. Form Updates

**Create/Edit Form:**
- Add status dropdown field
- Default to "Active" for new patients
- Allow status change in edit mode

### 4. State Management

```typescript
const viewMode = ref<'active' | 'trash'>('active')
const statusFilter = ref<number | null>(null)
const patientStatuses = ref<ReferenceData[]>([])

const fetchPatients = async (page = 1) => {
  const endpoint = viewMode.value === 'trash' 
    ? '/patients/trash' 
    : '/patients'
  
  const params = {
    per_page: perPage.value,
    page,
    ...(statusFilter.value && { status_id: statusFilter.value })
  }
  
  // Fetch and update patients
}
```

### 5. Action Handlers

```typescript
const restorePatient = async (patient: Patient) => {
  await api.post(`/patients/${patient.id}/restore`)
  toast.success('Patient restored successfully')
  await fetchPatients(currentPage.value)
}

const forceDeletePatient = async (patient: Patient) => {
  // Show confirmation dialog
  await api.delete(`/patients/${patient.id}/force`)
  toast.success('Patient permanently deleted')
  await fetchPatients(currentPage.value)
}
```

## UI/UX Design

### Color Scheme
- Active: Green (#10b981)
- Inactive: Gray (#6b7280)
- Archived: Blue (#3b82f6)
- Pending: Yellow (#f59e0b)
- Blocked: Red (#ef4444)

### Tabs Design
```
[Active Patients] [Trash]
```

### Status Filter
```
Status: [All Statuses ▼]
```

### Trash View Actions
```
[↻ Restore] [🗑️ Delete Forever]
```

## Testing Considerations

### Backend Tests
- Test soft delete sets deleted_by
- Test restore clears deleted_at and deleted_by
- Test force delete removes record
- Test status filtering
- Test audit trail population

### Frontend Tests
- Test tab switching
- Test status filter
- Test restore action
- Test force delete confirmation
- Test status badge display

## Migration Path

1. Create patient_statuses migration and seeder
2. Update Patient model with SoftDeletes trait
3. Create new backend actions
4. Add API routes
5. Update frontend with tabs and filters
6. Test thoroughly
7. Deploy

## Performance Considerations

- Add index on status_id (already exists via metaFields)
- Add index on deleted_at (already exists via SoftDeletes)
- Paginate trash view same as active view
- Cache patient statuses reference data

## Security Considerations

- Verify permissions for restore and force delete
- Log all trash operations
- Prevent unauthorized access to trash view
- Audit trail for compliance

## Future Enhancements (Out of Scope)

- Bulk restore/delete operations
- Auto-archive patients after X months inactive
- Audit history viewer with timeline
- Status change workflow with approvals
- Email notifications on status changes
