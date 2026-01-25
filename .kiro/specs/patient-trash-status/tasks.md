# Patient Trash and Status Management - Implementation Tasks

## Phase 1: Database and Backend Setup

- [ ] 1. Create patient_statuses reference table
  - [x] 1.1 Create migration for patient_statuses table
  - [x] 1.2 Create PatientStatus model extending BaseReference
  - [x] 1.3 Create PatientStatusSeeder with 5 status types
  - [x] 1.4 Add patient_statuses to ReferenceController $modelMap

- [ ] 2. Update Patient model for soft deletes
  - [x] 2.1 Add SoftDeletes trait to Patient model
  - [x] 2.2 Add status relationship to Patient model
  - [x] 2.3 Add status_id to fillable array
  - [x] 2.4 Add withStatus scope for filtering

- [ ] 3. Create trash management actions
  - [x] 3.1 Create ListTrashedPatientsAction
  - [x] 3.2 Create RestorePatientAction
  - [x] 3.3 Create ForceDeletePatientAction

- [ ] 4. Update existing patient actions
  - [x] 4.1 Update StorePatientAction to set created_by and default status_id
  - [x] 4.2 Update UpdatePatientAction to set updated_by
  - [x] 4.3 Update DeletePatientAction to set deleted_by before soft delete

- [ ] 5. Add API routes
  - [x] 5.1 Add GET /patients/trash route
  - [x] 5.2 Add POST /patients/{id}/restore route
  - [x] 5.3 Add DELETE /patients/{id}/force route
  - [x] 5.4 Add permissions middleware to routes

- [x] 6. Update permissions
  - [x] 6.1 Add patients.restore permission to RolePermissionSeeder
  - [x] 6.2 Add patients.force-delete permission to RolePermissionSeeder
  - [x] 6.3 Run seeder to update permissions

## Phase 2: Frontend Implementation

- [ ] 7. Add status management to patient list
  - [x] 7.1 Fetch patient statuses reference data
  - [x] 7.2 Add status badge component to patient table
  - [x] 7.3 Add status filter dropdown above table
  - [x] 7.4 Implement status filtering logic

- [ ] 8. Add tabs for Active/Trash views
  - [x] 8.1 Install shadcn-vue tabs component if not present
  - [x] 8.2 Add tabs UI (Active Patients | Trash)
  - [x] 8.3 Add viewMode state management
  - [x] 8.4 Update fetchPatients to use correct endpoint based on viewMode

- [ ] 9. Update patient table for trash view
  - [x] 9.1 Add conditional rendering for trash vs active view
  - [x] 9.2 Show deleted_at and deleted_by in trash view
  - [x] 9.3 Replace delete button with restore button in trash view
  - [x] 9.4 Add force delete button in trash view

- [ ] 10. Implement restore functionality
  - [x] 10.1 Create restorePatient function
  - [x] 10.2 Add restore button with icon
  - [x] 10.3 Show success toast on restore
  - [x] 10.4 Refresh patient list after restore

- [ ] 11. Implement force delete functionality
  - [x] 11.1 Create forceDeletePatient function
  - [x] 11.2 Add force delete button with warning color
  - [x] 11.3 Add confirmation AlertDialog for force delete
  - [x] 11.4 Show success toast on force delete
  - [x] 11.5 Refresh patient list after force delete

- [ ] 12. Add status to create/edit forms
  - [x] 12.1 Add status dropdown to create form
  - [x] 12.2 Add status dropdown to edit form
  - [x] 12.3 Set default status to "Active" (id: 1) for new patients
  - [x] 12.4 Update form schemas to include status_id

## Phase 3: Testing and Polish

- [ ] 13. Backend testing
  - [x] 13.1 Test soft delete sets deleted_by correctly
  - [x] 13.2 Test restore clears deleted_at and deleted_by
  - [x] 13.3 Test force delete removes record permanently
  - [x] 13.4 Test status filtering works correctly
  - [x] 13.5 Test audit fields (created_by, updated_by) are set

- [ ] 14. Frontend testing
  - [x] 14.1 Test tab switching between Active and Trash
  - [x] 14.2 Test status filter dropdown
  - [x] 14.3 Test restore action with toast notification
  - [x] 14.4 Test force delete with confirmation dialog
  - [x] 14.5 Test status badge colors display correctly

- [ ] 15. UI/UX polish
  - [x] 15.1 Ensure status badge colors match design
  - [x] 15.2 Add loading states for restore/force delete
  - [x] 15.3 Add empty state for trash view
  - [x] 15.4 Ensure responsive design works on mobile
  - [x] 15.5 Add tooltips for action buttons

- [ ] 16. Documentation
  - [x] 16.1 Update project architecture document with trash system
  - [x] 16.2 Document status values and their meanings
  - [x] 16.3 Add API documentation for new endpoints

## Notes

- All tasks should be completed in order within each phase
- Phase 1 must be completed before Phase 2
- Test after each major feature (trash, status, restore, force delete)
- Use Sonner toasts for all user notifications
- Follow existing code patterns from users.vue and patients.vue
