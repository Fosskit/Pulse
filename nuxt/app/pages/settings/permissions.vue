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
import { Badge } from '~/components/ui/badge'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '~/components/ui/select'
import { Plus, Pencil, Trash2, Shield, Search } from 'lucide-vue-next'
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '~/components/ui/pagination'
import type { Permission } from '~/types/auth'

definePageMeta({
  middleware: 'auth'
})

const api = useApi()
const errorHandler = useErrorHandler()

const permissions = ref<Permission[]>([])
const groups = ref<string[]>([])
const isLoading = ref(false)
const errorMessage = ref<string>('')
const successMessage = ref<string>('')

// Search and filter
const searchQuery = ref('')
const selectedGroup = ref<string>('all')

// Pagination
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
const perPage = ref(15)

// Dialog states
const isCreateDialogOpen = ref(false)
const isEditDialogOpen = ref(false)
const isDeleteDialogOpen = ref(false)
const selectedPermission = ref<Permission | null>(null)

// Form schema (shared for create and edit)
const permissionSchema = z.object({
  name: z.string().min(1, 'Name is required').max(255, 'Name must be less than 255 characters'),
})

const form = useForm({
  validationSchema: toTypedSchema(permissionSchema),
})

const { handleSubmit, setValues, resetForm, isSubmitting } = form

// Fetch permissions from backend with filters
const fetchPermissions = async (page = 1) => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const params = new URLSearchParams()
    params.append('per_page', perPage.value.toString())
    params.append('page', page.toString())
    if (selectedGroup.value && selectedGroup.value !== 'all') {
      params.append('group', selectedGroup.value)
    }
    if (searchQuery.value.trim()) {
      params.append('search', searchQuery.value.trim())
    }
    const response = await api.get<{
      data: Permission[]
      meta: {
        current_page: number
        last_page: number
        per_page: number
        total: number
      }
    }>(`/admin/permissions?${params.toString()}`)
    permissions.value = response.data
    currentPage.value = response.meta.current_page
    lastPage.value = response.meta.last_page
    total.value = response.meta.total
  } catch (error: any) {
    errorMessage.value = errorHandler.formatError(error)
  } finally {
    isLoading.value = false
  }
}

// Fetch permission groups from backend
const fetchGroups = async () => {
  try {
    const response = await api.get<{ data: string[] }>('/admin/permission-groups')
    groups.value = response.data
  } catch (error: any) {
    // fallback: just show 'all' if error
    groups.value = []
  }
}

// Single submit handler that handles both create and edit
const onSubmit = handleSubmit(async (values) => {
  errorMessage.value = ''
  successMessage.value = ''

  // Determine if we're creating or editing based on selectedPermission
  const isEditing = !!selectedPermission.value

  try {
    if (isEditing) {
      // Update existing permission
      await api.put(`/admin/permissions/${selectedPermission.value!.id}`, {
        name: values.name,
      })
      successMessage.value = 'Permission updated successfully'
      isEditDialogOpen.value = false
      selectedPermission.value = null
    } else {
      // Create new permission
      await api.post('/admin/permissions', {
        name: values.name,
      })
      successMessage.value = 'Permission created successfully'
      isCreateDialogOpen.value = false
    }

    resetForm()
    await fetchPermissions(currentPage.value)
  } catch (error: any) {
    errorMessage.value = errorHandler.formatError(error)
  }
})

// Delete permission
const deletePermission = async () => {
  if (!selectedPermission.value) return

  errorMessage.value = ''
  successMessage.value = ''
  try {
    await api.delete(`/admin/permissions/${selectedPermission.value.id}`)
    successMessage.value = 'Permission deleted successfully'
    isDeleteDialogOpen.value = false
    selectedPermission.value = null
    await fetchPermissions(currentPage.value)
  } catch (error: any) {
    errorMessage.value = errorHandler.formatError(error)
  }
}

// No more frontend filtering; permissions is always the current page from backend

// Remove groupedPermissions (not used in UI)

// Open create dialog
const openCreateDialog = () => {
  selectedPermission.value = null // Clear selected permission
  errorMessage.value = ''
  successMessage.value = ''
  // Reset form with empty values
  resetForm({
    values: {
      name: ''
    }
  })
  isCreateDialogOpen.value = true
}

// Open edit dialog
const openEditDialog = (permission: Permission) => {
  selectedPermission.value = permission
  setValues({
    name: permission.name,
  })
  isEditDialogOpen.value = true
}

// Open delete dialog
const openDeleteDialog = (permission: Permission) => {
  selectedPermission.value = permission
  isDeleteDialogOpen.value = true
}

// Watch for create dialog opening to ensure form is cleared
watch(isCreateDialogOpen, (isOpen) => {
  if (isOpen) {
    selectedPermission.value = null
    resetForm({
      values: {
        name: ''
      }
    })
  }
})


// Watchers for filter/search
watch([selectedGroup, searchQuery], () => {
  fetchPermissions(1)
})

const handlePageChange = (newPage: number) => {
  currentPage.value = newPage
  fetchPermissions(newPage)
}

onMounted(() => {
  fetchGroups()
  fetchPermissions()
})
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Permission Management</h1>
        <p class="text-muted-foreground">Manage permissions in the system</p>
      </div>
      <Button @click="openCreateDialog">
        <Plus class="mr-2 h-4 w-4" />
        Add Permission
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
        <CardTitle>Permissions</CardTitle>
        <CardDescription>
          A list of all permissions in the system
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div v-if="isLoading" class="text-center py-8">
          <p class="text-muted-foreground">Loading permissions...</p>
        </div>
        <div v-else-if="permissions.length === 0" class="text-center py-8">
          <p class="text-muted-foreground">No permissions found</p>
        </div>
        <div v-else>
          <!-- Search and Filter Controls -->
          <div class="flex gap-4 mb-4">
            <div class="flex-1">
              <Input
                v-model="searchQuery"
                type="text"
                placeholder="Search permissions..."
                class="w-full"
              />
            </div>
            <Select v-model="selectedGroup">
              <SelectTrigger class="w-[180px]">
                <SelectValue placeholder="Filter by group" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Groups</SelectItem>
                <SelectItem
                  v-for="group in groups"
                  :key="group"
                  :value="group"
                >
                  {{ group }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div v-if="permissions.length === 0" class="text-center py-8 border rounded-md">
            <p class="text-muted-foreground">No permissions match your filters</p>
          </div>
          <div v-else class="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Group</TableHead>
                  <TableHead>Created</TableHead>
                  <TableHead class="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="permission in permissions" :key="permission.id">
                <TableCell class="font-medium">{{ permission.name }}</TableCell>
                <TableCell>
                  <Badge variant="outline">
                    {{ permission.name.split('.')[0] || 'other' }}
                  </Badge>
                </TableCell>
                <TableCell class="text-muted-foreground text-sm">
                  {{ new Date(permission.created_at).toLocaleDateString() }}
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <Button variant="ghost" size="sm" @click="openEditDialog(permission)">
                      <Pencil class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" @click="openDeleteDialog(permission)">
                      <Trash2 class="h-4 w-4 text-destructive" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
        </div>

        <!-- Results count -->
        <div class="flex items-center justify-between mt-4">
          <p class="text-sm text-muted-foreground">
            Showing page {{ currentPage }} of {{ lastPage }} ({{ total }} total permissions)
            <span v-if="searchQuery || selectedGroup !== 'all'" class="text-xs">
              (filtered)
            </span>
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

    <!-- Create Permission Dialog -->
    <Dialog v-model:open="isCreateDialogOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Create Permission</DialogTitle>
          <DialogDescription>
            Add a new permission to the system
          </DialogDescription>
        </DialogHeader>
        <form @submit="onSubmit" class="space-y-4" :key="`create-form-${isCreateDialogOpen}`">
          <Alert v-if="errorMessage" variant="destructive" class="mb-4">
            <AlertDescription>{{ errorMessage }}</AlertDescription>
          </Alert>
          <FormField v-slot="{ componentField }" name="name">
            <FormItem>
              <FormLabel>Name</FormLabel>
              <FormControl>
                <Input type="text" placeholder="users.create" v-bind="componentField" />
              </FormControl>
              <FormMessage />
              <p class="text-xs text-muted-foreground">
                Use dot notation (e.g., "users.create", "roles.update")
              </p>
            </FormItem>
          </FormField>

          <DialogFooter>
            <Button type="button" variant="outline" @click="isCreateDialogOpen = false; resetForm()">
              Cancel
            </Button>
            <Button type="submit" :disabled="isSubmitting">
              <span v-if="isSubmitting">Creating...</span>
              <span v-else>Create Permission</span>
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Edit Permission Dialog -->
    <Dialog v-model:open="isEditDialogOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Edit Permission</DialogTitle>
          <DialogDescription>
            Update permission information
          </DialogDescription>
        </DialogHeader>
        <form @submit="onSubmit" class="space-y-4">
          <Alert v-if="errorMessage" variant="destructive" class="mb-4">
            <AlertDescription>{{ errorMessage }}</AlertDescription>
          </Alert>
          <FormField v-slot="{ componentField }" name="name">
            <FormItem>
              <FormLabel>Name</FormLabel>
              <FormControl>
                <Input type="text" v-bind="componentField" />
              </FormControl>
              <FormMessage />
              <p class="text-xs text-muted-foreground">
                Use dot notation (e.g., "users.create", "roles.update")
              </p>
            </FormItem>
          </FormField>

          <DialogFooter>
            <Button type="button" variant="outline" @click="isEditDialogOpen = false; resetForm()">
              Cancel
            </Button>
            <Button type="submit" :disabled="isSubmitting">
              <span v-if="isSubmitting">Updating...</span>
              <span v-else>Update Permission</span>
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Delete Permission Dialog -->
    <AlertDialog v-model:open="isDeleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Are you sure?</AlertDialogTitle>
          <AlertDialogDescription>
            This action cannot be undone. This will permanently delete the permission
            <strong>{{ selectedPermission?.name }}</strong>.
            <br><br>
            <strong>Warning:</strong> This may affect roles and users that have this permission assigned.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction @click="deletePermission" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
            Delete
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>
</template>
