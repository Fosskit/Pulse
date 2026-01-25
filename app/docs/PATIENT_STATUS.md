# Patient Status Documentation

## Overview

The patient status system provides lifecycle management for patient records, allowing healthcare administrators to track and categorize patients based on their current relationship with the facility. Each patient record must have a status assigned, which helps with filtering, reporting, and workflow management.

## Status Values

The system supports five predefined patient statuses, each with a specific purpose and visual indicator:

### 1. Active (ID: 1)
- **Code**: `active`
- **Color**: Green
- **Default**: Yes (automatically assigned to new patients)
- **Description**: Default status for new patients who are actively receiving care

**When to Use:**
- New patient registrations (automatically set)
- Patients with upcoming or recent appointments
- Patients currently enrolled in treatment programs
- Any patient actively engaged with the facility

**Characteristics:**
- This is the primary working status for most patients
- Patients in this status appear in standard searches and reports
- No restrictions on scheduling or record updates

---

### 2. Inactive (ID: 2)
- **Code**: `inactive`
- **Color**: Gray
- **Description**: Patient no longer visits the facility

**When to Use:**
- Patients who have not visited in an extended period (e.g., 12+ months)
- Patients who have moved to another facility
- Patients who have completed their treatment and are discharged
- Patients who have requested to discontinue services

**Characteristics:**
- Records remain accessible for reference
- May be filtered out of active patient lists
- Can be reactivated if patient returns
- Historical data remains intact

---

### 3. Archived (ID: 3)
- **Code**: `archived`
- **Color**: Blue
- **Description**: Old records kept for historical purposes, read-only

**When to Use:**
- Very old patient records (e.g., 5+ years inactive)
- Records maintained for legal/compliance requirements
- Historical data that should be preserved but not actively used
- Records that should not be modified

**Characteristics:**
- Intended for long-term storage
- Should be treated as read-only in workflows
- Useful for compliance and audit purposes
- Can be used to reduce clutter in active patient lists

**Best Practice:**
Consider implementing UI restrictions to prevent editing of archived records, though this is not enforced at the database level.

---

### 4. Pending Verification (ID: 4)
- **Code**: `pending`
- **Color**: Yellow
- **Description**: New patient registrations awaiting verification

**When to Use:**
- New patient registrations that need administrative review
- Records with incomplete or unverified information
- Patients awaiting insurance verification
- Records flagged for data quality review

**Characteristics:**
- Temporary status - should transition to Active or another status
- Indicates action required by staff
- Useful for workflow management
- Can be filtered to create work queues

**Workflow:**
1. Patient registered → Status set to "Pending Verification"
2. Staff reviews and verifies information
3. Status changed to "Active" once verified
4. Or status changed to "Blocked" if issues found

---

### 5. Blocked (ID: 5)
- **Code**: `blocked`
- **Color**: Red
- **Description**: Patient record flagged for review or restricted access

**When to Use:**
- Patients with outstanding payment issues
- Records flagged for compliance or legal review
- Patients who have been banned from the facility
- Records with data integrity concerns
- Patients requiring special authorization for services

**Characteristics:**
- Indicates restricted or problematic status
- Should trigger alerts or warnings in the UI
- May require supervisor approval to modify
- Prevents certain actions (implementation-dependent)

**Security Note:**
Consider implementing additional access controls or audit logging for blocked patient records.

---

## Status Workflow Examples

### New Patient Registration
```
Registration → Pending Verification → Active
```

### Patient Discharge
```
Active → Inactive → (after retention period) → Archived
```

### Problem Resolution
```
Blocked → (issue resolved) → Active
```

### Patient Returns After Long Absence
```
Inactive → Active
```

---

## Technical Implementation

### Database Structure
Patient statuses are stored in the `patient_statuses` reference table with the following fields:
- `id` (tinyint) - Unique identifier
- `code` (string) - Machine-readable status code
- `name` (string) - Human-readable display name
- `description` (text) - Detailed explanation
- `color` (string) - UI badge color indicator
- `status_id` (tinyint) - Meta status (active/inactive for the status itself)

### Patient Model Relationship
```php
// Patient.php
public function status(): BelongsTo
{
    return $this->belongsTo(PatientStatus::class, 'status_id');
}
```

### Default Status
When creating a new patient, if no `status_id` is provided, the system automatically assigns status ID 1 (Active):
```php
$data['status_id'] = $data['status_id'] ?? 1;
```

### Filtering by Status
Use the `withStatus` scope to filter patients:
```php
Patient::withStatus(1)->get(); // Get all active patients
```

Or use query parameters in API requests:
```
GET /api/patients?status_id=1
```

---

## UI/UX Guidelines

### Badge Colors
Status badges should use the following color scheme for consistency:
- **Active**: Green (#10b981) - Positive, go-ahead signal
- **Inactive**: Gray (#6b7280) - Neutral, dormant state
- **Archived**: Blue (#3b82f6) - Informational, historical
- **Pending**: Yellow (#f59e0b) - Warning, attention needed
- **Blocked**: Red (#ef4444) - Error, stop signal

### Status Display
- Always show status badge in patient lists
- Use color coding for quick visual identification
- Include status name as text (don't rely on color alone for accessibility)
- Consider adding status icons for additional clarity

### Status Selection
- Provide dropdown/select component for status changes
- Show status description on hover or in help text
- Require confirmation for status changes to Blocked or Archived
- Log all status changes in activity log

---

## Best Practices

### 1. Status Transitions
- Document your facility's status transition rules
- Consider implementing validation for allowed transitions
- Require notes/reasons for certain status changes (e.g., to Blocked)

### 2. Reporting and Analytics
- Use status for patient cohort analysis
- Track status distribution over time
- Monitor "Pending Verification" queue length
- Alert on high numbers of Blocked patients

### 3. Data Retention
- Define clear policies for when to move patients to Inactive
- Establish retention periods before archiving
- Consider legal requirements for record retention
- Document archival procedures

### 4. Access Control
- Consider restricting who can set certain statuses (e.g., Blocked)
- Implement approval workflows for sensitive status changes
- Audit all status changes for compliance

### 5. Integration with Soft Deletes
- Status is independent of soft delete (trash) functionality
- Deleted patients retain their status
- Restored patients return with their previous status
- Consider status when deciding to permanently delete records

---

## Maintenance and Extension

### Adding New Statuses
If you need to add new patient statuses:

1. Add entry to `PatientStatusSeeder.php`:
```php
[
    'id' => 6,
    'code' => 'new_status',
    'name' => 'New Status',
    'description' => 'Description of when to use this status',
    'color' => 'purple',
    'status_id' => 1,
]
```

2. Run the seeder:
```bash
php artisan db:seed --class=PatientStatusSeeder
```

3. Update this documentation with the new status details

### Modifying Existing Statuses
To change status names, descriptions, or colors:

1. Update the seeder file
2. Run: `php artisan migrate:fresh --seed` (development only)
3. Or manually update via the reference data management UI
4. Update this documentation to reflect changes

---

## Related Documentation

- [Reference CRUD System](./REFERENCE_CRUD.md) - Managing reference data including patient statuses
- [Patient Trash System](../../PATIENT_TRASH_STATUS_IMPLEMENTATION.md) - Soft delete and trash management
- Project Architecture - Complete system overview

---

## Support and Questions

For questions about patient status usage or to request new statuses, contact your system administrator or development team.

**Last Updated**: January 2026  
**Version**: 1.0
