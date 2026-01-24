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
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '~/components/ui/tabs'
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

interface ReferenceData {
  id: number
  code: string
  name: string
  description: string | null
  status_id: number
}

const api = useApi()
const errorHandler = useErrorHandler()

// Reference types configuration
const referenceTypes = [
  { key: 'nationalities', label: 'Nationalities', singular: 'Nationality' },
  { key: 'occupations', label: 'Occupations', singular: 'Occupation' },
  { key: 'marital-statuses', label: 'Marital Statuses', singular: 'Marital Status' },
]

const activeType = ref(referenceTypes[0].key)
const references = ref<ReferenceData[]>([])
const isLoading = ref(false)
const errorMessage = ref<string>('')
const successMessage = ref<string>('')

// Pagination
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
const perPage = ref(15)

// Dialog states
const isCreateDialogOpen = ref(false)
const isEditDialogOpen = ref(false)
const isDeleteDialogOpen = ref(false)
const selectedReference = ref<ReferenceData | null>(null)

// Form schema
const referenceSchema = z.object({
  code: z.string().min(1, 'Code is required'),
  name: z.string().min(1, 'Name is required'),
  description: z.string().optional(),
  status_id: z.number().default(1),
})

const createForm = useForm({
  validationSchema: toTypedSchema(referenceSchema),
})

const editForm = useForm({
  validationSchema: toTypedSchema(referenceSchema),
})

// Get current type info
const currentType = computed(() => 
  referenceTypes.find(t => t.key === activeType.value) || referenceTypes[0]
)

// Fetch references
const fetchReferences = async (page = 1) => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await api.get<{
      data: ReferenceData[]
      meta?: {
        current_page: number
        last_page: number
        per_page: number
        total: number
      }
    }>(`/references/${activeType.value}?per_page=${perPage.value}&page=${page}`)

    references.value = response.data
    if (response.meta) {
      currentPage.value = response.meta.current_page
      lastPage.value = response.meta.last_page
      total.value = response.meta.total
    }
  } catch (error: any) {
    errorMessage.value = errorHandler.formatError(error)
  } finally {
    isLoading.value = false
  }
}

const handlePageChange = (newPage: number) => {
  currentPage.value = newPage
  fetchReferences(newPage)
}

// Create reference
const onCreateSubmit = createForm.handleSubmit(async (values) => {
  errorMessage.value = ''
  successMessage.value = ''
  try {
    await api.post(`/references/${activeType.value}`, values)
    successMessage.value = `${currentType.value.singular} created successfully`
    isCreateDialogOpen.value = false
    createForm.resetForm()
    await fetchReferences(currentPage.value)
  } catch (error) {
    errorMessage.value = errorHandler.formatError(error)
  }
})

// Edit reference
const onEditSubmit = editForm.handleSubmit(async (values) => {
  if (!selectedReference.value) return

  errorMessage.value = ''
  successMessage.value = ''
  try {
    await api.put(`/references/${activeType.value}/${selectedReference.value.id}`, values)
    successMessage.value = `${currentType.value.singular} updated successfully`
    isEditDialogOpen.value = false
    selectedReference.value = null
    editForm.resetForm()
    await fetchReferences(currentPage.value)
  } catch (error: any) {
    errorMessage.value = errorHandler.formatError(error)
  }
})

// Delete reference
const deleteReference = async () => {
  if (!selectedReference.value) return

  errorMessage.value = ''
  successMessage.value = ''
  try {
    await api.delete(`/references/${activeType.value}/${selectedReference.value.id}`)
    successMessage.value = `${currentType.value.singular} deleted successfully`
    isDeleteDialogOpen.value = false
    selectedReference.value = null
    await fetchReferences(currentPage.value)
  } catch (error: any) {
    errorMessage.value = errorHandler.formatError(error)
  }
}

// Open create dialog
const openCreateDialog = () => {
  createForm.resetForm()
  createForm.setValues({
    code: '',
    name: '',
    description: '',
    status_id: 1,
  })
  errorMessage.value = ''
  successMessage.value = ''
  isCreateDialogOpen.value = true
}

// Open edit dialog
const openEditDialog = (reference: ReferenceData) => {
  selectedReference.value = reference
  editForm.setValues({
    code: reference.code,
    name: reference.name,
    description: reference.description || '',
    status_id: reference.status_id,
  })
  isEditDialogOpen.value = true
}

// Open delete dialog
const openDeleteDialog = (reference: ReferenceData) => {
  selectedReference.value = reference
  isDeleteDialogOpen.value = true
}

// Watch for tab changes
watch(activeType, () => {
  currentPage.value = 1
  fetchReferences(1)
})

// Initialize
onMounted(() => {
  fetchReferences()
})
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Reference Data Management</h1>
        <p class="text-muted-foreground">Manage reference data for the system</p>
      </div>
      <Button @click="openCreateDialog">
        <Plus class="mr-2 h-4 w-4" />
        Add {{ currentType.singular }}
      </Button>
    </div>

    <Alert v-if="errorMessage" variant="destructive">
      <AlertDescription>{{ errorMessage }}</AlertDescription>
    </Alert>

    <Alert v-if="successMessage" variant="default">
      <AlertDescription>{{ successMessage }}</AlertDescription>
    </Alert>

    <!-- Vertical Tabs Layout -->
    <Tabs v-model="activeType" class="w-full">
      <div class="flex gap-6">
        <!-- Vertical Navigation -->
        <TabsList class="flex flex-col h-fit w-48 bg-muted p-1 shrink-0">
          <TabsTrigger
            v-for="type in referenceTypes"
            :key="type.key"
            :value="type.key"
            class="w-full justify-start"
          >
            {{ type.label }}
          </TabsTrigger>
        </TabsList>

        <!-- Content Area -->
        <div class="flex-1 min-w-0">
          <TabsContent
            v-for="type in referenceTypes"
            :key="type.key"
            :value="type.key"
            class="mt-0"
          >
            <Card>
            <CardHeader>
              <CardTitle>{{ type.label }}</CardTitle>
              <CardDescription>
                Manage {{ type.label.toLowerCase() }} reference data
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="isLoading" class="text-center py-8">
                <p class="text-muted-foreground">Loading {{ type.label.toLowerCase() }}...</p>
              </div>
              <div v-else-if="references.length === 0" class="text-center py-8">
                <p class="text-muted-foreground">No {{ type.label.toLowerCase() }} found</p>
              </div>
              <div v-else class="rounded-md border">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Code</TableHead>
                      <TableHead>Name</TableHead>
                      <TableHead>Description</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="reference in references" :key="reference.id">
                      <TableCell class="font-medium">{{ reference.code }}</TableCell>
                      <TableCell>{{ reference.name }}</TableCell>
                      <TableCell>{{ reference.description || '-' }}</TableCell>
                      <TableCell>
                        <Badge :variant="reference.status_id === 1 ? 'default' : 'secondary'">
                          {{ reference.status_id === 1 ? 'Active' : 'Inactive' }}
                        </Badge>
                      </TableCell>
                      <TableCell class="text-right">
                        <div class="flex justify-end gap-2">
                          <Button variant="ghost" size="sm" @click="openEditDialog(reference)">
                            <Pencil class="h-4 w-4" />
                          </Button>
                          <Button variant="ghost" size="sm" @click="openDeleteDialog(reference)">
                            <Trash2 class="h-4 w-4 text-destructive" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>

              <!-- Pagination -->
              <div v-if="lastPage > 1" class="flex items-center justify-between mt-4">
                <p class="text-sm text-muted-foreground">
                  Showing page {{ currentPage }} of {{ lastPage }} ({{ total }} total)
                </p>
                <Pagination :page="currentPage" @update:page="handlePageChange" :items-per-page="perPage" :total="total">
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
        </TabsContent>
        </div>
      </div>
    </Tabs>

    <!-- Create Dialog -->
    <Dialog v-model:open="isCreateDialogOpen">
      <DialogContent class="max-w-lg">
        <DialogHeader>
          <DialogTitle>Create {{ currentType.singular }}</DialogTitle>
          <DialogDescription>
            Add a new {{ currentType.singular.toLowerCase() }} to the system
          </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="onCreateSubmit" class="space-y-4">
          <FormField v-slot="{ componentField }" name="code">
            <FormItem>
              <FormLabel>Code</FormLabel>
              <FormControl>
                <Input type="text" placeholder="CODE" v-bind="componentField" />
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <FormField v-slot="{ componentField }" name="name">
            <FormItem>
              <FormLabel>Name</FormLabel>
              <FormControl>
                <Input type="text" placeholder="Name" v-bind="componentField" />
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <FormField v-slot="{ componentField }" name="description">
            <FormItem>
              <FormLabel>Description (Optional)</FormLabel>
              <FormControl>
                <Input type="text" placeholder="Description" v-bind="componentField" />
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <DialogFooter>
            <Button type="button" variant="outline" @click="isCreateDialogOpen = false; createForm.resetForm()">
              Cancel
            </Button>
            <Button type="submit" :disabled="createForm.isSubmitting.value">
              <span v-if="createForm.isSubmitting.value">Creating...</span>
              <span v-else>Create</span>
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Edit Dialog -->
    <Dialog v-model:open="isEditDialogOpen">
      <DialogContent class="max-w-lg">
        <DialogHeader>
          <DialogTitle>Edit {{ currentType.singular }}</DialogTitle>
          <DialogDescription>
            Update {{ currentType.singular.toLowerCase() }} information
          </DialogDescription>
        </DialogHeader>
        <form @submit="onEditSubmit" class="space-y-4">
          <FormField v-slot="{ componentField }" name="code">
            <FormItem>
              <FormLabel>Code</FormLabel>
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

          <FormField v-slot="{ componentField }" name="description">
            <FormItem>
              <FormLabel>Description (Optional)</FormLabel>
              <FormControl>
                <Input type="text" v-bind="componentField" />
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <DialogFooter>
            <Button type="button" variant="outline" @click="isEditDialogOpen = false; editForm.resetForm()">
              Cancel
            </Button>
            <Button type="submit" :disabled="editForm.isSubmitting.value">
              <span v-if="editForm.isSubmitting.value">Updating...</span>
              <span v-else>Update</span>
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Delete Dialog -->
    <AlertDialog v-model:open="isDeleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Are you sure?</AlertDialogTitle>
          <AlertDialogDescription>
            This action cannot be undone. This will permanently delete
            <strong>{{ selectedReference?.name }}</strong>.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction @click="deleteReference" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
            Delete
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>
</template>
