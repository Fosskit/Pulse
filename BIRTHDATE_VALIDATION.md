# Patient Birthdate Validation

## Overview
Added comprehensive validation for patient birthdate to ensure data integrity and prevent unrealistic dates.

## Validation Rules

### Age Range
- **Minimum Age**: 1 day old (birthdate must be before today)
- **Maximum Age**: 150 years old
- **Rationale**: Prevents future dates and unrealistic historical dates

### Validation Logic
```
Today - 150 years <= Birthdate < Today
```

## Implementation

### Backend Validation (Laravel)

#### Files Modified
- `app/Http/Requests/Patient/StorePatientRequest.php`
- `app/Http/Requests/Patient/UpdatePatientRequest.php`

#### Validation Rules
```php
'birthdate' => [
    'nullable',
    'date',
    'before:today',
    'after_or_equal:' . now()->subYears(150)->format('Y-m-d'),
],
```

#### Custom Error Messages
```php
public function messages(): array
{
    return [
        'birthdate.before' => 'Birthdate must be in the past.',
        'birthdate.after_or_equal' => 'Birthdate cannot be more than 150 years ago.',
    ];
}
```

#### Validation Behavior
- **Future dates**: Rejected with message "Birthdate must be in the past."
- **Dates > 150 years ago**: Rejected with message "Birthdate cannot be more than 150 years ago."
- **Valid dates**: Accepted (between 1 day and 150 years ago)
- **Empty/null**: Accepted (birthdate is optional)

### Frontend Validation (Nuxt/Zod)

#### File Modified
- `nuxt/app/pages/patients.vue`

#### Zod Schema Validation
```typescript
birthdate: z.string().optional().or(z.literal('')).refine((val) => {
    if (!val || val === '') return true // Optional field
    
    // Validate date format
    if (!/^\d{4}-\d{2}-\d{2}$/.test(val)) return false
    
    const date = new Date(val + 'T00:00:00')
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    const minDate = new Date()
    minDate.setFullYear(today.getFullYear() - 150)
    minDate.setHours(0, 0, 0, 0)
    
    return date < today && date >= minDate
  }, {
    message: 'Birthdate must be between 1 day and 150 years ago'
  })
```

#### Validation Steps
1. **Type Check**: Accepts string values (DatePicker emits YYYY-MM-DD strings)
2. **Optional Check**: Empty values are allowed
3. **Format Check**: Must be YYYY-MM-DD format (regex validation)
4. **Date Range Check**: 
   - Must be before today (at midnight)
   - Must be within last 150 years
5. **Error Message**: Clear, user-friendly message

#### Key Implementation Details
- **No Date object handling**: DatePicker always emits strings, no need for union types
- **No transforms**: DatePicker provides correct format, no transformation needed
- **Explicit time component**: Uses `'T00:00:00'` for accurate date-only comparison
- **Timezone safe**: Sets hours to 0 for all date comparisons
- **vee-validate compatible**: Simple schema works seamlessly with form field binding

## Validation Examples

### Valid Birthdates ✅
- Today - 1 day: `2026-01-24` (if today is 2026-01-25)
- 30 years ago: `1996-01-25`
- 100 years ago: `1926-01-25`
- 149 years ago: `1877-01-25`
- Empty/null: `` (optional field)

### Invalid Birthdates ❌
- **Future date**: `2027-01-01`
  - Error: "Birthdate must be in the past."
  
- **Today**: `2026-01-25` (if today is 2026-01-25)
  - Error: "Birthdate must be in the past."
  
- **151 years ago**: `1875-01-01`
  - Error: "Birthdate cannot be more than 150 years ago."
  
- **200 years ago**: `1826-01-01`
  - Error: "Birthdate cannot be more than 150 years ago."

## User Experience

### Form Validation
- **Real-time validation**: Errors appear as user types/selects date
- **Clear messages**: User-friendly error messages
- **Visual feedback**: Red border and error text below field
- **Prevents submission**: Form cannot be submitted with invalid date

### Date Picker Component
- Date picker allows selecting any date
- Validation triggers after selection
- User sees immediate feedback if date is invalid
- Can clear invalid date and try again

## Edge Cases Handled

### 1. Leap Years
- Validation correctly handles leap years (e.g., Feb 29)
- Uses JavaScript Date object for accurate calculations

### 2. Time Zones
- Backend uses server timezone
- Frontend uses browser timezone
- Date comparison is date-only (no time component)

### 3. Empty Values
- Empty birthdate is allowed (optional field)
- Null values pass validation
- Empty string passes validation

### 4. Invalid Formats
- Non-date strings rejected by format validation
- Invalid dates (e.g., "2026-13-45") rejected by date validation

## Testing Scenarios

### Backend Tests (Recommended)
```php
// Test future date rejection
$response = $this->postJson('/api/patients', [
    'birthdate' => now()->addDay()->format('Y-m-d'),
    // ... other fields
]);
$response->assertStatus(422);
$response->assertJsonValidationErrors(['birthdate']);

// Test 151 years ago rejection
$response = $this->postJson('/api/patients', [
    'birthdate' => now()->subYears(151)->format('Y-m-d'),
    // ... other fields
]);
$response->assertStatus(422);
$response->assertJsonValidationErrors(['birthdate']);

// Test valid date acceptance
$response = $this->postJson('/api/patients', [
    'birthdate' => now()->subYears(30)->format('Y-m-d'),
    // ... other fields
]);
$response->assertStatus(201);
```

### Frontend Tests (Manual)
1. Try to enter future date → Should show error
2. Try to enter date > 150 years ago → Should show error
3. Enter valid date (e.g., 30 years ago) → Should accept
4. Leave birthdate empty → Should accept
5. Submit form with invalid date → Should prevent submission

## Benefits

### Data Integrity
- Prevents accidental typos (e.g., 2026 instead of 1926)
- Ensures realistic patient ages
- Maintains database quality

### User Experience
- Clear, immediate feedback
- Prevents frustration from server errors
- Guides users to correct input

### Business Logic
- Age calculations are accurate
- Reports and statistics are reliable
- Compliance with data quality standards

### Security
- Prevents malicious data entry
- Validates on both client and server
- Defense in depth approach

## Configuration

### Adjusting Maximum Age
To change the 150-year limit, update both files:

**Backend:**
```php
'after_or_equal:' . now()->subYears(YOUR_LIMIT)->format('Y-m-d'),
```

**Frontend:**
```typescript
minDate.setFullYear(today.getFullYear() - YOUR_LIMIT)
```

### Making Birthdate Required
To make birthdate required instead of optional:

**Backend:**
```php
'birthdate' => [
    'required',  // Change from 'nullable'
    'date',
    'before:today',
    'after_or_equal:' . now()->subYears(150)->format('Y-m-d'),
],
```

**Frontend:**
```typescript
birthdate: z.string()
  .min(1, 'Birthdate is required')  // Add this
  .regex(/^\d{4}-\d{2}-\d{2}$/, 'Date must be in YYYY-MM-DD format')
  .refine(...)
```

## Troubleshooting

### Issue: Date Picker Not Working / Cannot Select Dates

**Symptoms:**
- Date picker doesn't respond to clicks
- Selected dates don't appear in the form
- Form field remains empty after selecting a date

**Root Cause:**
Complex Zod schema with union types (`z.union([z.string(), z.date()])`) and transforms interfering with vee-validate's field binding. The DatePicker component emits strings in YYYY-MM-DD format, but the schema was trying to handle both strings and Date objects, causing conflicts.

**Solution:**
Simplified the Zod schema to accept only strings (which is what DatePicker emits):

```typescript
// ❌ WRONG - Complex union with transform
birthdate: z.union([z.string(), z.date()]).optional().or(z.literal('')).transform(val => {
    if (!val) return ''
    if (val instanceof Date) {
      return val.toISOString().split('T')[0]
    }
    return val
  }).refine(...)

// ✅ CORRECT - Simple string validation
birthdate: z.string().optional().or(z.literal('')).refine((val) => {
    if (!val || val === '') return true
    
    // Validate date format
    if (!/^\d{4}-\d{2}-\d{2}$/.test(val)) return false
    
    const date = new Date(val + 'T00:00:00')
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    const minDate = new Date()
    minDate.setFullYear(today.getFullYear() - 150)
    minDate.setHours(0, 0, 0, 0)
    
    return date < today && date >= minDate
  }, {
    message: 'Birthdate must be between 1 day and 150 years ago'
  })
```

**Key Changes:**
1. Removed `z.union([z.string(), z.date()])` - only accept strings
2. Removed `.transform()` - DatePicker already provides correct format
3. Added explicit date format validation with regex
4. Added `'T00:00:00'` to Date constructor for accurate date-only comparison
5. Set hours to 0 for both today and minDate to avoid timezone issues

**Why This Works:**
- DatePicker component always emits strings in YYYY-MM-DD format
- No need to handle Date objects or transform values
- Simpler schema = better compatibility with vee-validate
- Direct string validation is more predictable

### Issue: Timezone Discrepancies

**Symptoms:**
- Date validation fails for dates that should be valid
- "Off by one day" errors

**Solution:**
Use explicit time component and set hours to midnight:

```typescript
const date = new Date(val + 'T00:00:00')  // Add time component
today.setHours(0, 0, 0, 0)                // Reset to midnight
minDate.setHours(0, 0, 0, 0)              // Reset to midnight
```

This ensures date-only comparison without time/timezone interference.

## Related Validations

### Deceased Date
Consider adding similar validation for `deceased_at`:
- Must be after birthdate
- Must be before or equal to today
- Cannot be more than patient's age

### Future Enhancements
1. **Age-based validation**: Validate other fields based on age (e.g., occupation for children)
2. **Warning for very old patients**: Show warning (not error) for patients > 100 years
3. **Date picker restrictions**: Disable invalid dates in date picker UI
4. **Smart defaults**: Default to common age ranges (e.g., 30 years ago)

## Conclusion

The birthdate validation ensures:
- ✅ Data quality and integrity
- ✅ Realistic patient ages (1 day to 150 years)
- ✅ Clear user feedback
- ✅ Both client and server validation
- ✅ Consistent validation rules across create and update
- ✅ Working date picker with proper field binding

This prevents data entry errors and maintains a high-quality patient database.
