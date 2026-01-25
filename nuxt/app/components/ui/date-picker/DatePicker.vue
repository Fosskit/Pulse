<script setup lang="ts">
import { Input } from '@/components/ui/input'

const props = defineProps<{
  modelValue?: string
  placeholder?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const inputMode = ref<'date' | 'age'>('date')
const day = ref('')
const month = ref('')
const year = ref('')
const ageYears = ref('')
const ageMonths = ref('')
const ageDays = ref('')

// Calculate birthdate from age
const calculateBirthdateFromAge = (years: number, months: number = 0, days: number = 0) => {
  const today = new Date()
  const birthDate = new Date(today)
  birthDate.setFullYear(today.getFullYear() - years)
  birthDate.setMonth(today.getMonth() - months)
  birthDate.setDate(today.getDate() - days)
  
  const y = birthDate.getFullYear()
  const m = String(birthDate.getMonth() + 1).padStart(2, '0')
  const d = String(birthDate.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

// Calculate age from birthdate
const calculateAgeFromBirthdate = (isoDate: string) => {
  if (!isoDate) return { years: 0, months: 0, days: 0 }
  const birth = new Date(isoDate)
  const today = new Date()
  
  let years = today.getFullYear() - birth.getFullYear()
  let months = today.getMonth() - birth.getMonth()
  let days = today.getDate() - birth.getDate()
  
  if (days < 0) {
    months--
    const prevMonth = new Date(today.getFullYear(), today.getMonth(), 0)
    days += prevMonth.getDate()
  }
  
  if (months < 0) {
    years--
    months += 12
  }
  
  return { years, months, days }
}

// Get maximum days in a month (handles leap years)
const getMaxDaysInMonth = (month: number, year: number) => {
  if (!month || !year) return 31
  // Create date for next month's day 0 (last day of current month)
  return new Date(year, month, 0).getDate()
}

// Validate and constrain day input based on month and year
const handleDayInput = (event: Event) => {
  const input = event.target as HTMLInputElement
  let value = input.value.replace(/\D/g, '')
  
  if (value.length > 0) {
    let num = parseInt(value)
    
    // Get max days for current month/year
    const monthNum = parseInt(month.value) || 12
    const yearNum = parseInt(year.value) || new Date().getFullYear()
    const maxDays = getMaxDaysInMonth(monthNum, yearNum)
    
    if (num > maxDays) num = maxDays
    if (num < 1) num = 1
    value = num.toString()
  }
  
  day.value = value
  updateDateFromFields()
}

// Validate and constrain month input, adjust day if needed
const handleMonthInput = (event: Event) => {
  const input = event.target as HTMLInputElement
  let value = input.value.replace(/\D/g, '')
  
  if (value.length > 0) {
    let num = parseInt(value)
    if (num > 12) num = 12
    if (num < 1) num = 1
    value = num.toString()
  }
  
  month.value = value
  
  // Adjust day if it exceeds max days in new month
  if (day.value && year.value) {
    const monthNum = parseInt(value)
    const yearNum = parseInt(year.value)
    const maxDays = getMaxDaysInMonth(monthNum, yearNum)
    const currentDay = parseInt(day.value)
    
    if (currentDay > maxDays) {
      day.value = maxDays.toString()
    }
  }
  
  updateDateFromFields()
}

// Handle year input, adjust day for leap year if needed
const handleYearInput = (event: Event) => {
  const input = event.target as HTMLInputElement
  let value = input.value.replace(/\D/g, '')
  
  if (value.length > 4) {
    value = value.slice(0, 4)
  }
  
  year.value = value
  
  // Adjust day if February 29 becomes invalid (non-leap year)
  if (day.value && month.value && value.length === 4) {
    const monthNum = parseInt(month.value)
    const yearNum = parseInt(value)
    const maxDays = getMaxDaysInMonth(monthNum, yearNum)
    const currentDay = parseInt(day.value)
    
    if (currentDay > maxDays) {
      day.value = maxDays.toString()
    }
  }
  
  updateDateFromFields()
}

// Update date from DD/MM/YYYY fields
const updateDateFromFields = () => {
  if (day.value && month.value && year.value.length === 4) {
    const d = day.value.padStart(2, '0')
    const m = month.value.padStart(2, '0')
    const y = year.value
    const isoDate = `${y}-${m}-${d}`
    
    // Validate date
    const date = new Date(isoDate)
    if (!isNaN(date.getTime())) {
      emit('update:modelValue', isoDate)
    }
  }
}

// Handle age input changes
const handleAgeInput = () => {
  const y = parseInt(ageYears.value) || 0
  const m = parseInt(ageMonths.value) || 0
  const d = parseInt(ageDays.value) || 0
  
  if (y > 0 || m > 0 || d > 0) {
    const birthdate = calculateBirthdateFromAge(y, m, d)
    emit('update:modelValue', birthdate)
  }
}

// Sync fields when modelValue changes
watch(() => props.modelValue, (newValue) => {
  if (!newValue) {
    day.value = ''
    month.value = ''
    year.value = ''
    ageYears.value = ''
    ageMonths.value = ''
    ageDays.value = ''
    return
  }
  
  // Update date fields
  const [y, m, d] = newValue.split('-')
  year.value = y
  month.value = parseInt(m).toString()
  day.value = parseInt(d).toString()
  
  // Update age fields
  const age = calculateAgeFromBirthdate(newValue)
  ageYears.value = age.years.toString()
  ageMonths.value = age.months.toString()
  ageDays.value = age.days.toString()
}, { immediate: true })

// Watch age inputs
watch([ageYears, ageMonths, ageDays], () => {
  if (inputMode.value === 'age') {
    handleAgeInput()
  }
})

// Display value
const displayValue = computed(() => {
  if (!props.modelValue) return ''
  const [y, m, d] = props.modelValue.split('-')
  return `${d}/${m}/${y}`
})
</script>

<template>
  <div class="space-y-2">
    <!-- Mode Toggle -->
    <div class="flex gap-2 text-xs">
      <button
        type="button"
        @click="inputMode = 'date'"
        :class="[
          'px-2 py-1 rounded transition-colors',
          inputMode === 'date' 
            ? 'bg-primary text-primary-foreground' 
            : 'bg-muted text-muted-foreground hover:bg-muted/80'
        ]"
      >
        Date
      </button>
      <button
        type="button"
        @click="inputMode = 'age'"
        :class="[
          'px-2 py-1 rounded transition-colors',
          inputMode === 'age' 
            ? 'bg-primary text-primary-foreground' 
            : 'bg-muted text-muted-foreground hover:bg-muted/80'
        ]"
      >
        Age
      </button>
      <div v-if="displayValue" class="ml-auto text-muted-foreground">
        {{ displayValue }}
      </div>
    </div>

    <!-- Date Input Mode -->
    <div v-if="inputMode === 'date'" class="flex gap-2">
      <div class="flex-1">
        <Input
          v-model="day"
          @input="handleDayInput"
          type="text"
          inputmode="numeric"
          placeholder="DD"
          maxlength="2"
          class="text-center"
        />
      </div>
      <div class="flex items-center text-muted-foreground">/</div>
      <div class="flex-1">
        <Input
          v-model="month"
          @input="handleMonthInput"
          type="text"
          inputmode="numeric"
          placeholder="MM"
          maxlength="2"
          class="text-center"
        />
      </div>
      <div class="flex items-center text-muted-foreground">/</div>
      <div class="flex-[1.5]">
        <Input
          v-model="year"
          @input="handleYearInput"
          type="text"
          inputmode="numeric"
          placeholder="YYYY"
          maxlength="4"
          class="text-center"
        />
      </div>
    </div>

    <!-- Age Input Mode -->
    <div v-else class="flex gap-2">
      <div class="flex-1">
        <Input
          v-model="ageYears"
          @input="handleAgeInput"
          type="number"
          min="0"
          max="150"
          placeholder="Years"
        />
      </div>
      <div class="flex-1">
        <Input
          v-model="ageMonths"
          @input="handleAgeInput"
          type="number"
          min="0"
          max="11"
          placeholder="Months"
        />
      </div>
      <div class="flex-1">
        <Input
          v-model="ageDays"
          @input="handleAgeInput"
          type="number"
          min="0"
          max="30"
          placeholder="Days"
        />
      </div>
    </div>
  </div>
</template>
