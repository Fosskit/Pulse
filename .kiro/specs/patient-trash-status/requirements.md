# Patient Trash and Status Management

## Overview
Implement a comprehensive trash/soft delete system and status management for patient records, leveraging existing database columns (`deleted_at`, `deleted_by`, `status_id`) to provide better record lifecycle management.

## User Stories

### 1. Trash Management
**As a** healthcare administrator  
**I want to** view and manage deleted patient records  
**So that** I can restore accidentally deleted patients or permanently remove them

**Acceptance Criteria:**
- 1.1 Users can view a list of soft-deleted (trashed) patients
- 1.2 Users can switch between "Active Patients" and "Trash" views using tabs
- 1.3 Trashed patients show deletion date and who deleted them
- 1.4 Users can restore a trashed patient to active status
- 1.5 Users can permanently delete a trashed patient (force delete)
- 1.6 Permanent deletion requires confirmation dialog
- 1.7 Restore action shows success notification
- 1.8 Trashed patients are excluded from normal patient list by default

### 2. Status Management
**As a** healthcare administrator  
**I want to** assign and track patient record statuses  
**So that** I can manage patient lifecycle and filter by activity level

**Acceptance Criteria:**
- 2.1 System supports multiple patient statuses (Active, Inactive, Archived, Pending Verification, Blocked)
- 2.2 Default status for new patients is "Active" (status_id = 1)
- 2.3 Users can change patient status in the edit form
- 2.4 Patient list displays status badge with color coding
- 2.5 Users can filter patients by status using dropdown
- 2.6 Status changes are logged in activity log
- 2.7 Status reference data is seeded in database

### 3. Audit Trail Enhancement
**As a** system administrator  
**I want to** track who created, updated, and deleted patient records  
**So that** I can maintain accountability and compliance

**Acceptance Criteria:**
- 3.1 System records user ID when creating a patient (created_by)
- 3.2 System records user ID when updating a patient (updated_by)
- 3.3 System records user ID when soft-deleting a patient (deleted_by)
- 3.4 Audit information is stored but not displayed in main UI (Phase 3)

## Technical Requirements

### Backend
- Patient model must use `SoftDeletes` trait
- Create new actions: `ListTrashedPatientsAction`, `RestorePatientAction`, `ForceDeletePatientAction`
- Create `patient_statuses` reference table with seeder
- Update existing actions to populate audit fields (created_by, updated_by, deleted_by)
- Add API routes for trash operations
- Add query parameter support for filtering by status

### Frontend
- Add tabs component to switch between Active and Trash views
- Add status filter dropdown in patient list
- Add status badge display in patient table
- Add status selection in create/edit forms
- Update delete button behavior (soft delete for active, force delete for trashed)
- Add restore button for trashed patients
- Use Sonner toasts for all notifications

## Out of Scope
- Audit history viewer (detailed timeline of changes) - Phase 3
- Bulk operations (bulk restore, bulk delete)
- Auto-archive old records
- Status workflow automation

## Dependencies
- Existing soft delete columns in database (deleted_at, deleted_by)
- Existing status_id column in database
- Authentication system for user tracking
- Sonner toast notifications (already implemented)

## Success Metrics
- Users can successfully restore deleted patients
- Zero data loss from accidental deletions
- Status filtering reduces time to find specific patient groups
- Audit trail provides accountability for all record changes
