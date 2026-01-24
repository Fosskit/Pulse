<script setup lang="ts">
import { z } from 'zod'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
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
  Alert,
  AlertDescription,
} from '~/components/ui/alert'
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
const errorMessage = ref<string>('')
const successMessage = ref<string>('')

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

// Form schema
const patientSchema = z.object({
  code: z.string().optional(),
  surname: z.string().optional(),
  name: z.string().optional(),
  telephone: z.string().optional(),
  sex: z.enum(['M', 'F', 'O', 'U']),
  birthdate: z.string().optional(),
  multiple_birth: z.boolean().optional(),
  nationality_id: z.number(),
  marital_status_id: z.number().optional(),
  occupation_id: z.number().optional(),
  deceased: z.boolean().optional(),
  deceased_at: z.string().optional(),
})

const { handleSubmit, setValues, resetForm, isSubmitting } = useForm({
  validationSchema: toTypedSchema(patientSchema),
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
  errorMessage.value = ''
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
    errorMessage.value = errorHandler.formatError(error)
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
  errorMessage.value = ''
  successMessage.value = ''
  try {
    await api.post('/patients', values)
    successMessage.value = 'Patient created successfully'
    isCreateDialogOpen.value = false
    resetForm()
    await fetchPatients(currentPage.value)
  } catch (error) {
    errorMessage.value = errorHandler.formatError(error)
  }
})

// Edit patient
const onEditSubmit = handleSubmit(async (values) => {
  if (!selectedPatient.value) return

  errorMessage.value = ''
  successMessage.value = ''
  try {
    await api.put(`/patients/${selectedPatient.value.id}`, values)
    successMessage.value = 'Patient updated successfully'
    isEditDialogOpen.value = false
    selectedPatient.value = null
    resetForm()
    await fetchPatients(currentPage.value)
  } catch (error: any) {
    errorMessage.value = errorHandler.formatError(error)
  }
})

// Delete patient
const deletePatient = async () => {
  if (!selectedPatient.value) return

  errorMessage.value = ''
  successMessage.value = ''
  try {
    await api.delete(`/patients/${selectedPatient.value.id}`)
    successMessage.value = 'Patient deleted successfully'
    isDeleteDialogOpen.value = false
    selectedPatient.value = null
    await fetchPatients(currentPage.value)
  } catch (error: any) {
    errorMessage.value = errorHandler.formatError(error)
  }
}

// Open create dialog
const openCreateDialog = () => {
  resetForm()
  setValues({
    sex: 'U',
    nationality_id: 1,
    multiple_birth: false,
    deceased: false,
  })
  errorMessage.value = ''
  successMessage.value = ''
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
    nationality_id: patient.nationality_id,
    marital_status_id: patient.marital_status_id || undefined,
    occupation_id: patient.occupation_id || undefined,
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

    <Alert v-if="errorMessage" variant="destructive">
      <AlertDescription>{{ errorMessage }}</AlertDescription>
    </Alert>

    <Alert v-if="successMessage" variant="default">
      <AlertDescription>{{ successMessage }}</AlertDescription>
    </Alert>

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
        <div v-else class="rounded-md border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Code</TableHead>
                <TableHead>Name</TableHead>
                <TableHead>Sex</TableHead>
                <TableHead>Birthdate</TableHead>
                <TableHead>Nationality</TableHead>
                <TableHead>Telephone</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="patient in patients" :key="patient.id">
                <TableCell class="font-medium">{{ patient.code || '-' }}</TableCell>
                <TableCell>{{ [patient.surname, patient.name].filter(Boolean).join(' ') || '-' }}</TableCell>
                <TableCell>
                  <Badge variant="secondary">{{ patient.sex }}</Badge>
                </TableCell>
                <TableCell>{{ patient.birthdate || '-' }}</TableCell>
                <TableCell>{{ patient.nationality?.name || '-' }}</TableCell>
                <TableCell>{{ patient.telephone || '-' }}</TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <Button variant="ghost" size="sm" @click="openEditDialog(patient)">
                      <Pencil class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" @click="openDeleteDialog(patient)">
                      <Trash2 class="h-4 w-4 text-destructive" />
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
      <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Create Patient</DialogTitle>
          <DialogDescription>
            Add a new patient to the system
          </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="onCreateSubmit" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <FormField v-slot="{ componentField }" name="code">
              <FormItem>
                <FormLabel>Code</FormLabel>
                <FormControl>
                  <Input type="text" placeholder="P001" v-bind="componentField" />
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
                  <Input type="date" v-bind="componentField" />
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
      <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Edit Patient</DialogTitle>
          <DialogDescription>
            Update patient information
          </DialogDescription>
        </DialogHeader>
        <form @submit="onEditSubmit" class="space-y-4">
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
                  <Input type="date" v-bind="componentField" />
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
