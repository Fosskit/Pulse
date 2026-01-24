<script setup lang="ts">
import { Calendar as CalendarIcon } from 'lucide-vue-next'
import { Calendar } from '@/components/ui/calendar'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { cn } from '@/lib/utils'

const props = defineProps<{
  modelValue?: string
  placeholder?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const dateInput = ref('')
const ageYears = ref('')
const ageMonths = ref('')

// Auto-format date input as user types (DD/MM/YYYY)
const handleDateInput = (event: Event) => {
  const input = event.target as HTMLInputElement
  let value = input.value.replace(/\D/g, '') // Remove non-digits
  
  if (value.length >= 2) {
    value = value.slice(0, 2) + '/' + value.slice(2)
  }
  if (value.length >= 5) {
    value = value.slice(0, 5) + '/' + value.slice(5, 9)
  }
  
  dateInput.value = value
  
  // If complete date, validate and emit
  if (value.length === 10) {
    const isoDate = parseDisplayDate(value)
    if (isoDate) {
      emit('update:modelValue', isoDate)
    }
  }
}

// Convert YYYY-MM-DD to DD/MM/YYYY for display
const formatDateForDisplay = (isoDate: string) => {
  if (!isoDate) return ''
  const [year, month, day] = isoDate.split('-')
  return `${day}/${month}/${year}`
}

// Convert DD/MM/YYYY to YYYY-MM-DD for storage
const parseDisplayDate = (displayDate: string) => {
  const match = displayDate.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/)
  if (!match) return null
  const [, day, month, year] = match
  const paddedDay = day.padStart(2, '0')
  const paddedMonth = month.padStart(2, '0')
  return `${year}-${paddedMonth}-${paddedDay}`
}

// Calculate birthdate from age
const calculateBirthdateFromAge = (years: number, months: number = 0) => {
  const today = new Date()
  const birthYear = today.getFullYear() - years
  const birthMonth = today.getMonth() - months
  const birthDate = new Date(birthYear, birthMonth, today.getDate())
  
  const year = birthDate.getFullYear()
  const month = String(birthDate.getMonth() + 1).padStart(2, '0')
  const day = String(birthDate.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

// Calendar value
const calendarValue = computed({
  get: () => props.modelValue ? new Date(props.modelValue) : undefined,
  set: (date) => {
    if (date) {
      const year = date.getFullYear()
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const day = String(date.getDate()).padStart(2, '0')
      const isoDate = `${year}-${month}-${day}`
      emit('update:modelValue', isoDate)
      dateInput.value = formatDateForDisplay(isoDate)
    }
  }
})

// Display value for button
const displayValue = computed(() => {
  if (!props.modelValue) return props.placeholder || 'Pick a date'
  return formatDateForDisplay(props.modelValue)
})

// Watch for manual date input changes
watch(dateInput, (newValue) => {
  // Only validate if user stopped typing (complete format)
  if (newValue.length === 10) {
    const isoDate = parseDisplayDate(newValue)
    if (isoDate) {
      emit('update:modelValue', isoDate)
    }
  }
})

// Watch for age input changes
watch([ageYears, ageMonths], ([years, months]) => {
  const y = parseInt(years) || 0
  const m = parseInt(months) || 0
  if (y > 0 || m > 0) {
    const birthdate = calculateBirthdateFromAge(y, m)
    emit('update:modelValue', birthdate)
    dateInput.value = formatDateForDisplay(birthdate)
  }
})

// Initialize dateInput when modelValue changes
watch(() => props.modelValue, (newValue) => {
  if (newValue && !dateInput.value) {
    dateInput.value = formatDateForDisplay(newValue)
  }
}, { immediate: true })
</script>

<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        :class="cn(
          'w-full justify-start text-left font-normal',
          !modelValue && 'text-muted-foreground',
        )"
      >
        <CalendarIcon class="mr-2 h-4 w-4" />
        {{ displayValue }}
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-auto p-0">
      <Tabs default-value="calendar" class="w-full">
        <TabsList class="grid w-full grid-cols-3">
          <TabsTrigger value="calendar">Calendar</TabsTrigger>
          <TabsTrigger value="type">Type Date</TabsTrigger>
          <TabsTrigger value="age">By Age</TabsTrigger>
        </TabsList>
        
        <TabsContent value="calendar" class="p-0">
          <Calendar v-model="calendarValue" initial-focus />
        </TabsContent>
        
        <TabsContent value="type" class="p-4">
          <div class="space-y-2">
            <label class="text-sm font-medium">Enter date</label>
            <Input 
              v-model="dateInput"
              @input="handleDateInput"
              placeholder="25012026"
              maxlength="10"
              @keydown.enter="$emit('close')"
            />
            <p class="text-xs text-muted-foreground">
              Type: 25012026 → Auto-formats to: 25/01/2026
            </p>
          </div>
        </TabsContent>
        
        <TabsContent value="age" class="p-4">
          <div class="space-y-3">
            <div>
              <label class="text-sm font-medium">Years</label>
              <Input 
                v-model="ageYears" 
                type="number" 
                min="0" 
                max="150"
                placeholder="25"
              />
            </div>
            <div>
              <label class="text-sm font-medium">Months (optional)</label>
              <Input 
                v-model="ageMonths" 
                type="number" 
                min="0" 
                max="11"
                placeholder="6"
              />
            </div>
            <p class="text-xs text-muted-foreground">
              Calculates birthdate from current date
            </p>
          </div>
        </TabsContent>
      </Tabs>
    </PopoverContent>
  </Popover>
</template>
