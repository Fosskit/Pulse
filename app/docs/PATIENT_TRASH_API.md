# Patient Trash Management API

This document describes the API endpoints for managing soft-deleted (trashed) patient records, including listing, restoring, and permanently deleting patients.

## Overview

The trash management system allows administrators to:
- View all soft-deleted patient records
- Restore accidentally deleted patients
- Permanently delete patients from the database
- Filter trashed patients by status

All trash operations require authentication via Bearer token.

## Endpoints

### List Trashed Patients

Retrieves a paginated list of soft-deleted patient records.

```
GET /api/patients/trash
```

**Authentication:** Required (Bearer token)

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `per_page` | integer | No | 15 | Number of items per page (1-100) |
| `page` | integer | No | 1 | Page number for pagination |
| `status_id` | integer | No | null | Filter by patient status (1-5) |

**Status IDs:**
- `1` - Active
- `2` - Inactive
- `3` - Archived
- `4` - Pending Verification
- `5` - Blocked

**Request Example:**

```bash
curl -X GET "http://localhost:8000/api/patients/trash?per_page=20&status_id=1" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json"
```

**Success Response (200 OK):**

```json
{
  "data": [
    {
      "id": 123,
      "code": "PAT-2026-00123",
      "surname": "Smith",
      "name": "John",
      "telephone": "+1234567890",
      "sex": "M",
      "birthdate": "1990-05-15",
      "multiple_birth": false,
      "nationality_id": 1,
      "nationality": {
        "id": 1,
        "code": "US",
        "name": "American",
        "description": "United States nationality"
      },
      "marital_status_id": 1,
      "marital_status": {
        "id": 1,
        "code": "single",
        "name": "Single",
        "description": "Not married"
      },
      "occupation_id": 5,
      "occupation": {
        "id": 5,
        "code": "ENG",
        "name": "Engineer",
        "description": "Engineering profession"
      },
      "deceased": false,
      "deceased_at": null,
      "status_id": 1,
      "status": {
        "id": 1,
        "code": "active",
        "name": "Active",
        "description": "Active patient",
        "color": "green"
      },
      "created_at": "2026-01-20T10:30:00.000000Z",
      "updated_at": "2026-01-25T14:20:00.000000Z",
      "deleted_at": "2026-01-26T09:15:00.000000Z",
      "deleted_by": 1
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 95
  }
}
```

**Response Fields:**

| Field | Type | Description |
|-------|------|-------------|
| `data` | array | Array of trashed patient objects |
| `data[].id` | integer | Patient unique identifier |
| `data[].code` | string | Auto-generated patient code |
| `data[].surname` | string | Patient's surname |
| `data[].name` | string | Patient's given name |
| `data[].telephone` | string | Contact phone number |
| `data[].sex` | string | Gender (M/F/O) |
| `data[].birthdate` | string | Date of birth (YYYY-MM-DD) |
| `data[].multiple_birth` | boolean | Multiple birth indicator |
| `data[].nationality` | object | Nationality reference data |
| `data[].marital_status` | object | Marital status reference data |
| `data[].occupation` | object | Occupation reference data |
| `data[].deceased` | boolean | Deceased indicator |
| `data[].deceased_at` | string\|null | Date of death (YYYY-MM-DD) |
| `data[].status` | object | Patient status with color |
| `data[].deleted_at` | string | Soft delete timestamp (ISO 8601) |
| `data[].deleted_by` | integer | User ID who deleted the patient |
| `meta.current_page` | integer | Current page number |
| `meta.last_page` | integer | Total number of pages |
| `meta.per_page` | integer | Items per page |
| `meta.total` | integer | Total number of trashed patients |

**Error Responses:**

**401 Unauthorized:**
```json
{
  "message": "Unauthenticated."
}
```

**422 Validation Error:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "per_page": ["The per page must be between 1 and 100."]
  }
}
```

---

### Restore Patient

Restores a soft-deleted patient back to active status.

```
POST /api/patients/{id}/restore
```

**Authentication:** Required (Bearer token)

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Patient ID to restore |

**Request Example:**

```bash
curl -X POST "http://localhost:8000/api/patients/123/restore" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json"
```

**Success Response (200 OK):**

```json
{
  "data": {
    "id": 123,
    "code": "PAT-2026-00123",
    "surname": "Smith",
    "name": "John",
    "telephone": "+1234567890",
    "sex": "M",
    "birthdate": "1990-05-15",
    "multiple_birth": false,
    "nationality_id": 1,
    "nationality": {
      "id": 1,
      "code": "US",
      "name": "American",
      "description": "United States nationality"
    },
    "marital_status_id": 1,
    "marital_status": {
      "id": 1,
      "code": "single",
      "name": "Single",
      "description": "Not married"
    },
    "occupation_id": 5,
    "occupation": {
      "id": 5,
      "code": "ENG",
      "name": "Engineer",
      "description": "Engineering profession"
    },
    "deceased": false,
    "deceased_at": null,
    "status_id": 1,
    "status": {
      "id": 1,
      "code": "active",
      "name": "Active",
      "description": "Active patient",
      "color": "green"
    },
    "created_at": "2026-01-20T10:30:00.000000Z",
    "updated_at": "2026-01-26T10:00:00.000000Z",
    "deleted_at": null,
    "deleted_by": null
  },
  "message": "Patient restored successfully"
}
```

**Response Fields:**

| Field | Type | Description |
|-------|------|-------------|
| `data` | object | Restored patient object with all relationships |
| `data.deleted_at` | null | Cleared after restoration |
| `data.deleted_by` | null | Cleared after restoration |
| `message` | string | Success message |

**Behavior:**
- Clears `deleted_at` timestamp (restores the record)
- Clears `deleted_by` field (removes deletion audit)
- Triggers activity log entry for restoration
- Loads all relationships (nationality, occupation, marital status, status)
- Patient becomes visible in normal patient list

**Error Responses:**

**401 Unauthorized:**
```json
{
  "message": "Unauthenticated."
}
```

**404 Not Found:**
```json
{
  "message": "No query results for model [App\\Models\\Patient] {id}"
}
```

*Note: This error occurs if the patient ID doesn't exist in the trash (either never existed or is not soft-deleted).*

---

### Force Delete Patient

Permanently deletes a patient from the database. This action cannot be undone.

```
DELETE /api/patients/{id}/force
```

**Authentication:** Required (Bearer token)

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Patient ID to permanently delete |

**Security Note:** This endpoint only works on patients that are already soft-deleted (in trash). You cannot force delete an active patient directly.

**Request Example:**

```bash
curl -X DELETE "http://localhost:8000/api/patients/123/force" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json"
```

**Success Response (200 OK):**

```json
{
  "message": "Patient permanently deleted"
}
```

**Response Fields:**

| Field | Type | Description |
|-------|------|-------------|
| `message` | string | Success confirmation message |

**Behavior:**
- Only works on soft-deleted patients (must be in trash first)
- Permanently removes the patient record from the database
- Logs the permanent deletion in activity log with patient details
- Cannot be undone - patient data is lost forever
- Activity log entry includes patient code and name for audit trail

**Activity Log Entry:**
```
Action: force_deleted
Model: Patient
Model ID: 123
Description: Patient permanently deleted: PAT-2026-00123 - John Smith
User ID: {authenticated_user_id}
IP Address: {request_ip}
User Agent: {request_user_agent}
```

**Error Responses:**

**401 Unauthorized:**
```json
{
  "message": "Unauthenticated."
}
```

**404 Not Found:**
```json
{
  "message": "No query results for model [App\\Models\\Patient] {id}"
}
```

*Note: This error occurs if:*
- *The patient ID doesn't exist*
- *The patient exists but is NOT soft-deleted (not in trash)*

**Best Practice:** Always show a confirmation dialog in the UI before calling this endpoint, as the action is irreversible.

---

## Workflow Examples

### Complete Trash Management Flow

**1. List trashed patients:**
```bash
GET /api/patients/trash?per_page=15&page=1
```

**2. Restore a patient:**
```bash
POST /api/patients/123/restore
```

**3. Permanently delete a patient:**
```bash
DELETE /api/patients/456/force
```

### Filtering Trashed Patients by Status

**Get all trashed "Active" patients:**
```bash
GET /api/patients/trash?status_id=1
```

**Get all trashed "Blocked" patients:**
```bash
GET /api/patients/trash?status_id=5
```

### Soft Delete to Force Delete Flow

**Step 1: Soft delete a patient (moves to trash):**
```bash
DELETE /api/patients/123
```

**Step 2: List trash to verify:**
```bash
GET /api/patients/trash
```

**Step 3: Permanently delete:**
```bash
DELETE /api/patients/123/force
```

---

## Related Endpoints

### Soft Delete Patient

To move a patient to trash (soft delete):

```
DELETE /api/patients/{patient}
```

This sets `deleted_at` timestamp and `deleted_by` user ID, making the patient appear in the trash list.

### List Active Patients

To list non-deleted patients:

```
GET /api/patients
```

Supports the same query parameters as trash list, but excludes soft-deleted records.

---

## Permissions

Currently, trash management endpoints require authentication but do not enforce specific permissions. Consider implementing:

- `patients.view` - View trashed patients
- `patients.restore` - Restore deleted patients
- `patients.force-delete` - Permanently delete patients

---

## Status Reference Data

Patient statuses are managed via the reference CRUD system:

```
GET /api/references/patient-statuses
```

Returns all available patient statuses with their colors for UI rendering.

---

## Error Handling

All endpoints follow standard Laravel error response format:

**Validation Errors (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

**Authentication Errors (401):**
```json
{
  "message": "Unauthenticated."
}
```

**Not Found Errors (404):**
```json
{
  "message": "No query results for model [App\\Models\\Patient] {id}"
}
```

**Server Errors (500):**
```json
{
  "message": "Server Error"
}
```

---

## Testing Examples

### Using cURL

**List trashed patients:**
```bash
curl -X GET "http://localhost:8000/api/patients/trash" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Accept: application/json"
```

**Restore patient:**
```bash
curl -X POST "http://localhost:8000/api/patients/123/restore" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Accept: application/json"
```

**Force delete patient:**
```bash
curl -X DELETE "http://localhost:8000/api/patients/123/force" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Accept: application/json"
```

### Using JavaScript (Fetch API)

```javascript
// List trashed patients
const response = await fetch('http://localhost:8000/api/patients/trash?per_page=20', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${accessToken}`,
    'Accept': 'application/json'
  }
});
const data = await response.json();

// Restore patient
const restoreResponse = await fetch(`http://localhost:8000/api/patients/${patientId}/restore`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${accessToken}`,
    'Accept': 'application/json'
  }
});
const restoreData = await restoreResponse.json();

// Force delete patient
const deleteResponse = await fetch(`http://localhost:8000/api/patients/${patientId}/force`, {
  method: 'DELETE',
  headers: {
    'Authorization': `Bearer ${accessToken}`,
    'Accept': 'application/json'
  }
});
const deleteData = await deleteResponse.json();
```

---

## Notes

- All timestamps are in ISO 8601 format (UTC)
- Trashed patients are ordered by `deleted_at` descending (most recent first)
- Relationships (nationality, occupation, marital status, status) are eager-loaded
- Activity logging is automatic for restore operations
- Force delete requires manual activity logging (handled by the action)
- Pagination follows Laravel's standard pagination format
- All endpoints return JSON responses

---

## See Also

- [Reference CRUD System](./REFERENCE_CRUD.md) - For managing patient statuses
- [Patient Status Documentation](./PATIENT_STATUS.md) - For status definitions and usage
- Laravel Soft Deletes Documentation
- Laravel Passport Authentication
