# Patient UI Improvements

## Overview
Enhanced the patient management interface with better UX for trash access and improved filtering capabilities.

## Changes Made

### 1. Removed Tabs, Added Trash Button
**Before:** Tabs component with "Active Patients" and "Trash" tabs
**After:** Single view with a trash icon button in the filter bar

**Rationale:**
- Trash access is rare, so it shouldn't take up prominent UI space
- Button approach is more compact and intuitive
- Trash button shows current state (highlighted when in trash view)

**Implementation:**
```vue
<Button 
  :variant="viewMode === 'trash' ? 'default' : 'outline'"
  size="sm"
  @click="viewMode = viewMode === 'active' ? 'trash' : 'active'"
  class="gap-2"
>
  <Trash class="h-4 w-4" />
  <span v-if="viewMode === 'trash'">Back to Active</span>
  <span v-else>Trash</span>
</Button>
```

### 2. Added Search Functionality
**Feature:** Search patients by name or code
**Location:** Left side of filter bar, takes up flexible space

**Search Capabilities:**
- Search by patient code (e.g., "PAT-2026-00123")
- Search by first name
- Search by surname
- Search by full name (both "John Doe" and "Doe John" work)
- Real-time search with 300ms debounce
- Clear button (X) appears when search has text

**Frontend Implementation:**
```typescript
const searchQuery = ref('')

// Debounced search
let searchTimeout: NodeJS.Timeout
watch(searchQuery, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    fetchPatients(1)
  }, 300)
})

// Clear search
const clearSearch = () => {
  searchQuery.value = ''
}
```

**Backend Implementation:**
Both `ListPatientsAction` and `ListTrashedPatientsAction` now support search:

```php
// Apply search filter if provided
if (request()->has('search') && request('search') !== null) {
    $search = request('search');
    $query->where(function ($q) use ($search) {
        $q->where('code', 'like', "%{$search}%")
          ->orWhere('name', 'like', "%{$search}%")
          ->orWhere('surname', 'like', "%{$search}%")
          ->orWhereRaw("CONCAT(surname, ' ', name) LIKE ?", ["%{$search}%"])
          ->orWhereRaw("CONCAT(name, ' ', surname) LIKE ?", ["%{$search}%"]);
    });
}
```

### 3. Improved Filter Layout
**New Layout:**
```
[Search Input (flexible)]  [Status: Dropdown]  [Trash Button]
```

**Features:**
- Search input takes up flexible space (max-width: 28rem)
- Status filter remains compact with label
- Trash button on the right for easy access
- All filters work together (search + status + trash view)
- Responsive design with proper spacing

### 4. Enhanced Card Header
**Dynamic Title:**
- Shows "Patients" when in active view
- Shows "Trash" when in trash view
- Description updates accordingly

## UI Components Used

### New Icons
- `Search` - Search input icon
- `X` - Clear search button
- `Trash` - Trash toggle button

### Removed Components
- `Tabs`, `TabsContent`, `TabsList`, `TabsTrigger` - No longer needed

## API Changes

### Query Parameters
Both `/api/patients` and `/api/patients/trash` now accept:

| Parameter | Type | Description |
|-----------|------|-------------|
| `per_page` | integer | Items per page (default: 15) |
| `page` | integer | Page number |
| `status_id` | integer | Filter by patient status |
| `search` | string | Search by name or code |

### Example Requests

**Search active patients:**
```
GET /api/patients?search=john&status_id=1
```

**Search trash:**
```
GET /api/patients/trash?search=PAT-2026
```

## User Experience Improvements

### 1. Faster Access to Common Actions
- Search is immediately visible and accessible
- Status filter remains in the same position
- Trash is one click away (no tab switching needed)

### 2. Better Visual Hierarchy
- Primary action (Add Patient) remains top-right
- Filters are grouped logically in one row
- Trash access is clear but not prominent

### 3. Intuitive Search
- Placeholder text guides users: "Search by name or code..."
- Clear button appears when needed
- Debounced search prevents excessive API calls
- Works with full names in any order

### 4. Consistent Behavior
- All filters work together seamlessly
- Pagination resets when filters change
- Loading states maintained
- Toast notifications for all actions

## Testing Checklist

- [x] Search by patient code
- [x] Search by first name
- [x] Search by surname
- [x] Search by full name (both orders)
- [x] Clear search button works
- [x] Search works with status filter
- [x] Search works in trash view
- [x] Trash button toggles view mode
- [x] Trash button shows correct state
- [x] All filters reset pagination
- [x] Debounce prevents excessive API calls
- [x] No TypeScript errors
- [x] No PHP errors

## Files Modified

### Frontend
- `nuxt/app/pages/patients.vue`
  - Removed Tabs component
  - Added search input with debounce
  - Added trash toggle button
  - Updated filter layout
  - Added clear search function

### Backend
- `app/Actions/Patient/ListPatientsAction.php`
  - Added search query support
  - Added status filter support
  - Searches code, name, surname, and full name combinations

- `app/Actions/Patient/ListTrashedPatientsAction.php`
  - Added search query support
  - Maintains existing status filter
  - Same search logic as active patients

## Performance Considerations

### Debouncing
- 300ms delay prevents API spam during typing
- Timeout cleared on each keystroke
- Only fires after user stops typing

### Database Queries
- Uses LIKE queries with wildcards
- CONCAT for full name searches
- Indexed columns (code) for faster searches
- Pagination maintained for large result sets

### Frontend Optimization
- Reactive refs for instant UI updates
- Computed properties for dynamic content
- Watchers with proper cleanup
- No unnecessary re-renders

## Future Enhancements (Optional)

1. **Advanced Search**
   - Filter by nationality
   - Filter by age range
   - Filter by date range

2. **Search Highlighting**
   - Highlight matching text in results
   - Show match count

3. **Search History**
   - Remember recent searches
   - Quick access to previous queries

4. **Keyboard Shortcuts**
   - Ctrl/Cmd + K to focus search
   - Escape to clear search

5. **Export Filtered Results**
   - Export current search results to CSV/Excel
   - Include all applied filters

## Conclusion

The patient management interface is now more intuitive and efficient:
- **Trash access** is available but not intrusive
- **Search functionality** makes finding patients quick and easy
- **Filter layout** is clean and organized
- **User experience** is improved with better visual hierarchy

All changes maintain backward compatibility and follow the existing code patterns in the project.
