# Reference CRUD System

This system provides a generic CRUD API for all reference tables (Nationality, Occupation, MaritalStatus, etc.) that share the same structure.

## Structure

All reference tables have these common fields:
- `id` - Primary key
- `code` - Unique code
- `name` - Display name
- `description` - Description text
- `status_id` - Status (0=inactive, 1=active)
- `created_at`, `updated_at`, `deleted_at` - Timestamps
- `created_by`, `updated_by`, `deleted_by` - User tracking

## API Endpoints

All endpoints use the pattern: `/api/references/{type}`

Available types:
- `nationalities`
- `occupations`
- `marital-statuses`

### List References
```
GET /api/references/{type}
```

Query parameters:
- `search` - Search in code, name, or description
- `status_id` - Filter by status (0 or 1)
- `per_page` - Items per page (default: 15)

Example:
```
GET /api/references/nationalities?search=american&per_page=20
```

### Show Reference
```
GET /api/references/{type}/{id}
```

Example:
```
GET /api/references/nationalities/1
```

### Create Reference
```
POST /api/references/{type}
```

Body:
```json
{
  "code": "US",
  "name": "American",
  "description": "United States nationality",
  "status_id": 1
}
```

### Update Reference
```
PUT /api/references/{type}/{id}
```

Body (all fields optional):
```json
{
  "code": "USA",
  "name": "American",
  "description": "Updated description",
  "status_id": 1
}
```

### Delete Reference
```
DELETE /api/references/{type}/{id}
```

## Adding New Reference Tables

To add a new reference table:

1. Create migration with `commonFields()` and `metaFields()` macros
2. Create model extending `App\Models\Reference\BaseReference`
3. Add to `$modelMap` in `ReferenceController`:
   ```php
   'your-type' => \App\Models\Reference\YourModel::class,
   ```

That's it! The CRUD endpoints will automatically work.

## Example Usage

```bash
# List all nationalities
curl -X GET "http://localhost/api/references/nationalities" \
  -H "Authorization: Bearer {token}"

# Create occupation
curl -X POST "http://localhost/api/references/occupations" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "ENG",
    "name": "Engineer",
    "description": "Engineering profession"
  }'

# Update marital status
curl -X PUT "http://localhost/api/references/marital-statuses/1" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Single"
  }'

# Delete occupation
curl -X DELETE "http://localhost/api/references/occupations/5" \
  -H "Authorization: Bearer {token}"
```
