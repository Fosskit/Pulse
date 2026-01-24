<script setup lang="ts">
import { z } from 'zod'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { toast } from 'vue-sonner'
import { useApi } from '~/composables/useApi'
import { useErrorHandler } from '~/composables/useErrorHandler'
import { Button } from '~/components/ui/button'
import { Input } from '~/components/ui/input'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '~/components/ui/table'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '~/components/ui/dialog'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '~/components/ui/alert-dialog'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '~/components/ui/card'
import {
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '~/components/ui/form'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '~/components/ui/select'
import { Badge } from '~/components/ui/badge'
import { Plus, Pencil, Trash2 } from 'lucide-vue-next'
import { DatePicker } from '~/components/ui/date-picker'
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '~/components/ui/pagination'

definePageMeta({
  middleware: 'auth'
})

interface Patient {
  id: number
  code: string | null
  surname: string | null
  name: string | null
  telephone: string | null
  sex: 'M' | 'F' | 'O' | 'U'
  birthdate: string | null
  multiple_birth: boolean
  nationality_id: number
  nationality?: { id: number; name: string }
  marital_status_id: number | null
  marital_status?: { id: number; name: string }
  occupation_id: number | null
  occupation?: { id: number; name: string }
  deceased: boolean | null
  deceased_at: string | null
}

interface ReferenceData {
  id: number
  code: string
  name: string
}

const api = useApi()
const errorHandler = useErrorHandler()

const patients = ref<Patient[]>([])
const isLoading = ref(false)

// Reference data
const nationalities = ref<ReferenceData[]>([])
const occupations = ref<ReferenceData[]>([])
const maritalStatuses = ref<ReferenceData[]>([])

// Pagination
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
const perPage = ref(15)

// Dialog states
const isCreateDialogOpen = ref(false)
const isEditDialogOpen = ref(false)
const isDeleteDialogOpen = ref(false)
const selectedPatient = ref<Patient | null>(null)

// Shared form schema - switches based on dialog type
const createSchema = z.object({
  surname: z.string().min(1, 'Surname is required'),
  name: z.string().min(1, 'Name is required'),
  telephone: z.string().optional().or(z.literal('')),
  sex: z.enum(['M', 'F']),
  birthdate: z.string().regex(/^\d{4}-\d{2}-\d{2}$/, 'Date must be in YYYY-MM-DD format').optional().or(z.literal('')),
  multiple_birth: z.boolean().optional().default(false),
  nationality_id: z.union([z.string(), z.number()]).transform(val => {
    if (typeof val === 'string') return val ? parseInt(val) : 1
    return val || 1
  }),
  marital_status_id: z.union([z.string(), z.number()]).transform(val => {
    if (typeof val === 'string') return val && val !== '' ? parseInt(val) : null
    return val || null
  }).optional().nullable(),
  occupation_id: z.union([z.string(), z.number()]).transform(val => {
    if (typeof val === 'string') return val && val !== '' ? parseInt(val) : null
    return val || null
  }).optional().nullable(),
  deceased: z.boolean().optional().default(false),
  deceased_at: z.string().regex(/^\d{4}-\d{2}-\d{2}$/, 'Date must be in YYYY-MM-DD format').optional().or(z.literal('')),
})

const editSchema = z.object({
  code: z.string().optional().or(z.literal('')),
  surname: z.string().min(1, 'Surname is required'),
  name: z.string().min(1, 'Name is required'),
  telephone: z.string().optional().or(z.literal('')),
  sex: z.enum(['M', 'F', 'O', 'U']), // Keep all options for existing patients
  birthdate: z.string().regex(/^\d{4}-\d{2}-\d{2}$/, 'Date must be in YYYY-MM-DD format').optional().or(z.literal('')),
  multiple_birth: z.boolean().optional().default(false),
  nationality_id: z.union([z.string(), z.number()]).transform(val => {
    if (typeof val === 'string') return val ? parseInt(val) : 1
    return val || 1
  }),
  marital_status_id: z.union([z.string(), z.number()]).transform(val => {
    if (typeof val === 'string') return val && val !== '' ? parseInt(val) : null
    return val || null
  }).optional().nullable(),
  occupation_id: z.union([z.string(), z.number()]).transform(val => {
    if (typeof val === 'string') return val && val !== '' ? parseInt(val) : null
    return val || null
  }).optional().nullable(),
  deceased: z.boolean().optional().default(false),
  deceased_at: z.string().regex(/^\d{4}-\d{2}-\d{2}$/, 'Date must be in YYYY-MM-DD format').optional().or(z.literal('')),
})

// Single shared form context - switches validation based on active dialog
const currentSchema = computed(() => isCreateDialogOpen.value ? createSchema : editSchema)

const { handleSubmit, setValues, resetForm, isSubmitting } = useForm({
  validationSchema: computed(() => toTypedSchema(currentSchema.value)),
})

// Fetch reference data
const fetchReferenceData = async () => {
  try {
    const [natResponse, occResponse, marResponse] = await Promise.all([
      api.get<{ data: ReferenceData[] }>('/references/nationalities'),
      api.get<{ data: ReferenceData[] }>('/references/occupations'),
      api.get<{ data: ReferenceData[] }>('/references/marital-statuses'),
    ])
    nationalities.value = natResponse.data
    occupations.value = occResponse.data
    maritalStatuses.value = marResponse.data
  } catch (error: any) {
    console.error('Error fetching reference data:', error)
  }
}

// Fetch patients
const fetchPatients = async (page = 1) => {
  isLoading.value = true
  try {
    const response = await api.get<{
      data: Patient[]
      meta: {
        current_page: number
        last_page: number
        per_page: number
        total: number
      }
    }>(`/patients?per_page=${perPage.value}&page=${page}`)

    patients.value = response.data
    currentPage.value = response.meta.current_page
    lastPage.value = response.meta.last_page
    total.value = response.meta.total
  } catch (error: any) {
    toast.error('Failed to load patients', {
      description: errorHandler.formatError(error)
    })
  } finally {
    isLoading.value = false
  }
}

const handlePageChange = (newPage: number) => {
  currentPage.value = newPage
  fetchPatients(newPage)
}

// Create patient
const onCreateSubmit = handleSubmit(async (values) => {
  try {
    console.log('Form values before submission:', values)
    
    // Transform empty strings to null for optional fields
    const payload = {
      ...values,
      birthdate: values.birthdate || null,
      telephone: values.telephone || null,
      marital_status_id: values.marital_status_id || null,
      occupation_id: values.occupation_id || null,
      deceased_at: values.deceased_at || null,
    }
    
    console.log('Submitting patient data:', payload)
    const response = await api.post('/patients', payload)
    console.log('Patient created:', response)
    
    toast.success('Patient created successfully', {
      description: `${values.surname} ${values.name} has been added to the system.`
    })
    
    isCreateDialogOpen.value = false
    resetForm()
    await fetchPatients(currentPage.value)
  } catch (error) {
    console.error('Error creating patient:', error)
    toast.error('Failed to create patient', {
      description: errorHandler.formatError(error)
    })
  }
})

// Edit patient
const onEditSubmit = handleSubmit(async (values) => {
  if (!selectedPatient.value) return

  try {
    await api.put(`/patients/${selectedPatient.value.id}`, values)
    
    toast.success('Patient updated successfully', {
      description: `${values.surname} ${values.name} has been updated.`
    })
    
    isEditDialogOpen.value = false
    selectedPatient.value = null
    resetForm()
    await fetchPatients(currentPage.value)
  } catch (error: any) {
    toast.error('Failed to update patient', {
      description: errorHandler.formatError(error)
    })
  }
})

// Delete patient
const deletePatient = async () => {
  if (!selectedPatient.value) return

  try {
    await api.delete(`/patients/${selectedPatient.value.id}`)
    
    const patientName = [selectedPatient.value.surname, selectedPatient.value.name].filter(Boolean).join(' ')
    toast.success('Patient deleted successfully', {
      description: `${patientName} has been removed from the system.`
    })
    
    isDeleteDialogOpen.value = false
    selectedPatient.value = null
    await fetchPatients(currentPage.value)
  } catch (error: any) {
    toast.error('Failed to delete patient', {
      description: errorHandler.formatError(error)
    })
  }
}

// Open create dialog
const openCreateDialog = () => {
  resetForm()
  setValues({
    surname: '',
    name: '',
    telephone: '',
    sex: 'M',
    birthdate: '',
    multiple_birth: false,
    nationality_id: '1',
    marital_status_id: '',
    occupation_id: '',
    deceased: false,
    deceased_at: '',
  })
  isCreateDialogOpen.value = true
}

// Open edit dialog
const openEditDialog = (patient: Patient) => {
  selectedPatient.value = patient
  setValues({
    code: patient.code || '',
    surname: patient.surname || '',
    name: patient.name || '',
    telephone: patient.telephone || '',
    sex: patient.sex,
    birthdate: patient.birthdate || '',
    multiple_birth: patient.multiple_birth,
    nationality_id: patient.nationality_id.toString(),
    marital_status_id: patient.marital_status_id?.toString() || '',
    occupation_id: patient.occupation_id?.toString() || '',
    deceased: patient.deceased || false,
    deceased_at: patient.deceased_at || '',
  })
  isEditDialogOpen.value = true
}

// Open delete dialog
const openDeleteDialog = (patient: Patient) => {
  selectedPatient.value = patient
  isDeleteDialogOpen.value = true
}

// Initialize
onMounted(() => {
  fetchPatients()
  fetchReferenceData()
})

// Format date for display (DD/MM/YYYY)
const formatDate = (date: string | null) => {
  if (!date) return '-'
  const [year, month, day] = date.split('-')
  return `${day}/${month}/${year}`
}

// Calculate age from birthdate
const calculateAge = (birthdate: string | null) => {
  if (!birthdate) return '-'
  const birth = new Date(birthdate)
  const today = new Date()
  let age = today.getFullYear() - birth.getFullYear()
  const monthDiff = today.getMonth() - birth.getMonth()
  
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
    age--
  }
  
  // Calculate months for more precision
  let months = monthDiff
  if (months < 0) {
    months += 12
  }
  if (today.getDate() < birth.getDate()) {
    months--
    if (months < 0) months += 12
  }
  
  if (age === 0 && months > 0) {
    return `${months}m`
  }
  if (age > 0 && months > 0) {
    return `${age}y ${months}m`
  }
  return `${age}y`
}

// Get sex badge variant
const getSexBadgeVariant = (sex: string) => {
  switch (sex) {
    case 'M': return 'default'
    case 'F': return 'secondary'
    case 'O': return 'outline'
    default: return 'outline'
  }
}

// Get sex badge class with custom colors
const getSexBadgeClass = (sex: string) => {
  switch (sex) {
    case 'M': return 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800'
    case 'F': return 'bg-pink-100 text-pink-700 border-pink-200 dark:bg-pink-950 dark:text-pink-300 dark:border-pink-800'
    default: return 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700'
  }
}

// Get age badge class based on age range
const getAgeBadgeClass = (birthdate: string | null) => {
  if (!birthdate) return 'bg-gray-100 text-gray-700 border-gray-200'
  
  const birth = new Date(birthdate)
  const today = new Date()
  const age = today.getFullYear() - birth.getFullYear()
  
  // Infant (0-2 years) - Soft green
  if (age < 2) return 'bg-green-100 text-green-700 border-green-200 dark:bg-green-950 dark:text-green-300 dark:border-green-800'
  // Child (2-12 years) - Soft blue
  if (age < 12) return 'bg-cyan-100 text-cyan-700 border-cyan-200 dark:bg-cyan-950 dark:text-cyan-300 dark:border-cyan-800'
  // Teen (12-18 years) - Soft purple
  if (age < 18) return 'bg-violet-100 text-violet-700 border-violet-200 dark:bg-violet-950 dark:text-violet-300 dark:border-violet-800'
  // Adult (18-60 years) - Soft indigo
  if (age < 60) return 'bg-indigo-100 text-indigo-700 border-indigo-200 dark:bg-indigo-950 dark:text-indigo-300 dark:border-indigo-800'
  // Senior (60+ years) - Soft amber
  return 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950 dark:text-amber-300 dark:border-amber-800'
}

// Get sex label
const getSexLabel = (sex: string) => {
  switch (sex) {
    case 'M': return 'Male'
    case 'F': return 'Female'
    default: return 'Unknown'
  }
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Patient Management</h1>
        <p class="text-muted-foreground">Manage patient records</p>
      </div>
      <Button @click="openCreateDialog">
        <Plus class="mr-2 h-4 w-4" />
        Add Patient
      </Button>
    </div>

    <Card>
      <CardHeader>
        <CardTitle>Patients</CardTitle>
        <CardDescription>
          A list of all patients in the system
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div v-if="isLoading" class="text-center py-8">
          <p class="text-muted-foreground">Loading patients...</p>
        </div>
        <div v-else-if="patients.length === 0" class="text-center py-8">
          <p class="text-muted-foreground">No patients found</p>
        </div>
        <div v-else class="rounded-lg border bg-card">
          <Table>
            <TableHeader>
              <TableRow class="bg-muted/50">
                <TableHead class="w-[110px] font-semibold">Code</TableHead>
                <TableHead class="font-semibold">Patient Information</TableHead>
                <TableHead class="w-[100px] font-semibold">Sex</TableHead>
                <TableHead class="w-[130px] font-semibold">Birth Date</TableHead>
                <TableHead class="w-[90px] font-semibold">Age</TableHead>
                <TableHead class="w-[140px] font-semibold">Contact</TableHead>
                <TableHead class="text-right w-[120px] font-semibold">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="patient in patients" :key="patient.id" class="group hover:bg-accent/50 transition-colors">
                <TableCell>
                  <div class="flex items-center gap-2">
                    <div class="h-8 w-1 rounded-full bg-primary/20 group-hover:bg-primary/40 transition-colors" />
                    <Badge variant="outline" class="font-mono text-xs bg-primary/5 border-primary/20 text-primary">
                      {{ patient.code || 'N/A' }}
                    </Badge>
                  </div>
                </TableCell>
                <TableCell>
                  <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-2">
                      <span class="font-semibold text-base">
                        {{ [patient.surname, patient.name].filter(Boolean).join(' ') || 'Unnamed Patient' }}
                      </span>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                      <span class="inline-flex items-center gap-1 text-muted-foreground">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                          <circle cx="12" cy="10" r="3"/>
                        </svg>
                        {{ patient.nationality?.name || 'Unknown' }}
                      </span>
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  <Badge 
                    :class="getSexBadgeClass(patient.sex)"
                    class="font-medium"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle v-if="patient.sex === 'M'" cx="12" cy="8" r="5"/>
                      <path v-if="patient.sex === 'M'" d="M12 13v8"/>
                      <path v-if="patient.sex === 'M'" d="M8 21h8"/>
                      <circle v-if="patient.sex === 'F'" cx="12" cy="8" r="5"/>
                      <path v-if="patient.sex === 'F'" d="M12 13v8"/>
                      <path v-if="patient.sex === 'F'" d="M12 21h-4"/>
                      <path v-if="patient.sex === 'F'" d="M12 21h4"/>
                      <circle v-if="patient.sex !== 'M' && patient.sex !== 'F'" cx="12" cy="12" r="5"/>
                    </svg>
                    {{ getSexLabel(patient.sex) }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <div class="flex flex-col gap-0.5">
                    <span class="text-sm font-medium">{{ formatDate(patient.birthdate) }}</span>
                  </div>
                </TableCell>
                <TableCell>
                  <Badge 
                    :class="getAgeBadgeClass(patient.birthdate)"
                    class="font-mono font-semibold"
                  >
                    {{ calculateAge(patient.birthdate) }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <div v-if="patient.telephone" class="flex items-center gap-1.5 text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-muted-foreground" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    <span class="text-muted-foreground">{{ patient.telephone }}</span>
                  </div>
                  <span v-else class="text-xs text-muted-foreground">No contact</span>
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-1.5">
                    <Button 
                      variant="ghost" 
                      size="sm" 
                      @click="openEditDialog(patient)"
                      class="h-8 w-8 p-0 hover:bg-primary/10 hover:text-primary transition-colors"
                    >
                      <Pencil class="h-4 w-4" />
                    </Button>
                    <Button 
                      variant="ghost" 
                      size="sm" 
                      @click="openDeleteDialog(patient)"
                      class="h-8 w-8 p-0 hover:bg-destructive/10 hover:text-destructive transition-colors"
                    >
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between mt-4">
          <p class="text-sm text-muted-foreground">
            Showing page {{ currentPage }} of {{ lastPage }} ({{ total }} total patients)
          </p>
          <Pagination v-if="lastPage > 1" :page="currentPage" @update:page="handlePageChange" :items-per-page="perPage" :total="total">
            <PaginationContent v-slot="{ items }">
              <PaginationPrevious />

              <template v-for="(item, index) in items" :key="index">
                <PaginationItem
                  v-if="item.type === 'page'"
                  :value="item.value"
                  :is-active="item.value === currentPage"
                >
                  {{ item.value }}
                </PaginationItem>
                <PaginationEllipsis v-else-if="item.type === 'ellipsis'" :index="item.index" />
              </template>

              <PaginationNext />
            </PaginationContent>
          </Pagination>
        </div>
      </CardContent>
    </Card>

    <!-- Create Patient Dialog -->
    <Dialog v-model:open="isCreateDialogOpen">
      <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Create Patient</DialogTitle>
          <DialogDescription>
            Add a new patient to the system. Code will be auto-generated if left empty.
          </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="onCreateSubmit" class="space-y-4">
          <!-- Sex Selection with Radio Buttons -->
          <FormField v-slot="{ componentField }" name="sex">
            <FormItem>
              <FormLabel>Sex *</FormLabel>
              <FormControl>
                <div class="flex gap-6">
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input
                      type="radio"
                      value="M"
                      :checked="componentField.modelValue === 'M'"
                      @change="componentField['onUpdate:modelValue']('M')"
                      class="h-4 w-4"
                    />
                    <span>Male</span>
                  </label>
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input
                      type="radio"
                      value="F"
                      :checked="componentField.modelValue === 'F'"
                      @change="componentField['onUpdate:modelValue']('F')"
                      class="h-4 w-4"
                    />
                    <span>Female</span>
                  </label>
                </div>
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <div class="grid grid-cols-2 gap-4">
            <FormField v-slot="{ componentField }" name="surname">
              <FormItem>
                <FormLabel>Surname</FormLabel>
                <FormControl>
                  <Input type="text" placeholder="Doe" v-bind="componentField" />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="name">
              <FormItem>
                <FormLabel>Name</FormLabel>
                <FormControl>
                  <Input type="text" placeholder="John" v-bind="componentField" />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <FormField v-slot="{ componentField }" name="birthdate">
              <FormItem>
                <FormLabel>Birthdate</FormLabel>
                <FormControl>
                  <DatePicker 
                    :model-value="componentField.modelValue" 
                    @update:model-value="componentField['onUpdate:modelValue']"
                    placeholder="Select birthdate" 
                  />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="telephone">
              <FormItem>
                <FormLabel>Telephone</FormLabel>
                <FormControl>
                  <Input type="text" placeholder="+1234567890" v-bind="componentField" />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <FormField v-slot="{ componentField }" name="nationality_id">
              <FormItem>
                <FormLabel>Nationality</FormLabel>
                <Select v-bind="componentField">
                  <FormControl>
                    <SelectTrigger>
                      <SelectValue placeholder="Select nationality" />
                    </SelectTrigger>
                  </FormControl>
                  <SelectContent>
                    <SelectItem v-for="nat in nationalities" :key="nat.id" :value="nat.id.toString()">
                      {{ nat.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <FormMessage />
              </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="occupation_id">
              <FormItem>
                <FormLabel>Occupation</FormLabel>
                <Select v-bind="componentField">
                  <FormControl>
                    <SelectTrigger>
                      <SelectValue placeholder="Select occupation" />
                    </SelectTrigger>
                  </FormControl>
                  <SelectContent>
                    <SelectItem v-for="occ in occupations" :key="occ.id" :value="occ.id.toString()">
                      {{ occ.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <FormMessage />
              </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="marital_status_id">
              <FormItem>
                <FormLabel>Marital Status</FormLabel>
                <Select v-bind="componentField">
                  <FormControl>
                    <SelectTrigger>
                      <SelectValue placeholder="Select status" />
                    </SelectTrigger>
                  </FormControl>
                  <SelectContent>
                    <SelectItem v-for="status in maritalStatuses" :key="status.id" :value="status.id.toString()">
                      {{ status.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <FormMessage />
              </FormItem>
            </FormField>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" @click="isCreateDialogOpen = false; resetForm()">
              Cancel
            </Button>
            <Button type="submit" :disabled="isSubmitting">
              <span v-if="isSubmitting">Creating...</span>
              <span v-else>Create Patient</span>
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Edit Patient Dialog -->
    <Dialog v-model:open="isEditDialogOpen">
      <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Edit Patient</DialogTitle>
          <DialogDescription>
            Update patient information
          </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="onEditSubmit" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <FormField v-slot="{ componentField }" name="code">
              <FormItem>
                <FormLabel>Code</FormLabel>
                <FormControl>
                  <Input type="text" v-bind="componentField" />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="sex">
              <FormItem>
                <FormLabel>Sex</FormLabel>
                <Select v-bind="componentField">
                  <FormControl>
                    <SelectTrigger>
                      <SelectValue placeholder="Select sex" />
                    </SelectTrigger>
                  </FormControl>
                  <SelectContent>
                    <SelectItem value="M">Male</SelectItem>
                    <SelectItem value="F">Female</SelectItem>
                    <SelectItem value="O">Other</SelectItem>
                    <SelectItem value="U">Unknown</SelectItem>
                  </SelectContent>
                </Select>
                <FormMessage />
              </FormItem>
            </FormField>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <FormField v-slot="{ componentField }" name="surname">
              <FormItem>
                <FormLabel>Surname</FormLabel>
                <FormControl>
                  <Input type="text" v-bind="componentField" />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="name">
              <FormItem>
                <FormLabel>Name</FormLabel>
                <FormControl>
                  <Input type="text" v-bind="componentField" />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <FormField v-slot="{ componentField }" name="birthdate">
              <FormItem>
                <FormLabel>Birthdate</FormLabel>
                <FormControl>
                  <DatePicker 
                    :model-value="componentField.modelValue" 
                    @update:model-value="componentField['onUpdate:modelValue']"
                    placeholder="Select birthdate" 
                  />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="telephone">
              <FormItem>
                <FormLabel>Telephone</FormLabel>
                <FormControl>
                  <Input type="text" v-bind="componentField" />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <FormField v-slot="{ componentField }" name="nationality_id">
              <FormItem>
                <FormLabel>Nationality</FormLabel>
                <Select v-bind="componentField">
                  <FormControl>
                    <SelectTrigger>
                      <SelectValue placeholder="Select nationality" />
                    </SelectTrigger>
                  </FormControl>
                  <SelectContent>
                    <SelectItem v-for="nat in nationalities" :key="nat.id" :value="nat.id.toString()">
                      {{ nat.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <FormMessage />
              </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="occupation_id">
              <FormItem>
                <FormLabel>Occupation</FormLabel>
                <Select v-bind="componentField">
                  <FormControl>
                    <SelectTrigger>
                      <SelectValue placeholder="Select occupation" />
                    </SelectTrigger>
                  </FormControl>
                  <SelectContent>
                    <SelectItem v-for="occ in occupations" :key="occ.id" :value="occ.id.toString()">
                      {{ occ.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <FormMessage />
              </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="marital_status_id">
              <FormItem>
                <FormLabel>Marital Status</FormLabel>
                <Select v-bind="componentField">
                  <FormControl>
                    <SelectTrigger>
                      <SelectValue placeholder="Select status" />
                    </SelectTrigger>
                  </FormControl>
                  <SelectContent>
                    <SelectItem v-for="status in maritalStatuses" :key="status.id" :value="status.id.toString()">
                      {{ status.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <FormMessage />
              </FormItem>
            </FormField>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" @click="isEditDialogOpen = false; resetForm()">
              Cancel
            </Button>
            <Button type="submit" :disabled="isSubmitting">
              <span v-if="isSubmitting">Updating...</span>
              <span v-else>Update Patient</span>
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Delete Patient Dialog -->
    <AlertDialog v-model:open="isDeleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Are you sure?</AlertDialogTitle>
          <AlertDialogDescription>
            This action cannot be undone. This will permanently delete the patient
            <strong>{{ [selectedPatient?.surname, selectedPatient?.name].filter(Boolean).join(' ') }}</strong>.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction @click="deletePatient" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
            Delete
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>
</template>
