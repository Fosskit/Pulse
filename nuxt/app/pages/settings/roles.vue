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
import { Checkbox } from '~/components/ui/checkbox'
import { Plus, Pencil, Trash2, Shield } from 'lucide-vue-next'
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '~/components/ui/pagination'
import type { Role, Permission } from '~/types/auth'

definePageMeta({
  middleware: 'auth'
})

const api = useApi()
const errorHandler = useErrorHandler()

const roles = ref<Role[]>([])
const permissions = ref<Permission[]>([])
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
const isPermissionsDialogOpen = ref(false)
const selectedRole = ref<Role | null>(null)
const selectedPermissions = ref<string[]>([])

// Create form schema
const createSchema = z.object({
  name: z.string().min(1, 'Name is required').max(255, 'Name must be less than 255 characters'),
})

const createForm = useForm({
  validationSchema: toTypedSchema(createSchema),
})

const { handleSubmit: handleCreateSubmit, resetForm: resetCreateForm, isSubmitting: isCreating } = createForm

// Edit form schema
const editSchema = z.object({
  name: z.string().min(1, 'Name is required').max(255, 'Name must be less than 255 characters'),
})

const editForm = useForm({
  validationSchema: toTypedSchema(editSchema),
})

const { handleSubmit: handleEditSubmit, setValues: setEditValues, resetForm: resetEditForm, isSubmitting: isUpdating } = editForm

// Fetch roles
const fetchRoles = async (page = 1) => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await api.get<{
      data: Role[]
      meta: {
        current_page: number
        last_page: number
        per_page: number
        total: number
      }
    }>(`/admin/roles?per_page=${perPage.value}&page=${page}`)

    roles.value = response.data
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
  fetchRoles(newPage)
}

// Fetch permissions
const fetchPermissions = async () => {
  try {
    const response = await api.get<{
      data: Permission[]
    }>(`/admin/permissions?per_page=1000`)
    permissions.value = response.data
  } catch (error: any) {
    console.error('Failed to fetch permissions:', error)
  }
}

// Create role
const onCreateSubmit = handleCreateSubmit(async (values) => {
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const response = await api.post<{ data: Role }>('/admin/roles', {
      name: values.name,
    })

    // Assign permissions if any were selected
    if (selectedPermissions.value.length > 0) {
      await api.post(`/admin/roles/${response.data.id}/permissions`, {
        permissions: selectedPermissions.value,
      })
    }

    successMessage.value = 'Role created successfully'
    isCreateDialogOpen.value = false
    resetCreateForm()
    selectedPermissions.value = []
    await fetchRoles(currentPage.value)
  } catch (error: any) {
    errorMessage.value = errorHandler.formatError(error)
  }
})

// Edit role
const onEditSubmit = handleEditSubmit(async (values) => {
  if (!selectedRole.value) return

  errorMessage.value = ''
  successMessage.value = ''
  try {
    await api.put(`/admin/roles/${selectedRole.value.id}`, {
      name: values.name,
    })
    successMessage.value = 'Role updated successfully'
    isEditDialogOpen.value = false
    selectedRole.value = null
    resetEditForm()
    await fetchRoles(currentPage.value)
  } catch (error: any) {
    errorMessage.value = errorHandler.formatError(error)
  }
})

// Delete role
const deleteRole = async () => {
  if (!selectedRole.value) return

  errorMessage.value = ''
  successMessage.value = ''
  try {
    await api.delete(`/admin/roles/${selectedRole.value.id}`)
    successMessage.value = 'Role deleted successfully'
    isDeleteDialogOpen.value = false
    selectedRole.value = null
    await fetchRoles(currentPage.value)
  } catch (error: any) {
    errorMessage.value = errorHandler.formatError(error)
  }
}

// Assign permissions
const assignPermissions = async () => {
  if (!selectedRole.value) return

  errorMessage.value = ''
  successMessage.value = ''
  try {
    await api.post(`/admin/roles/${selectedRole.value.id}/permissions`, {
      permissions: selectedPermissions.value,
    })
    successMessage.value = 'Permissions assigned successfully'
    isPermissionsDialogOpen.value = false
    await fetchRoles(currentPage.value)
  } catch (error: any) {
    errorMessage.value = errorHandler.formatError(error)
  }
}

// Open create dialog
const openCreateDialog = () => {
  resetCreateForm()
  selectedPermissions.value = []
  errorMessage.value = ''
  successMessage.value = ''
  isCreateDialogOpen.value = true
}

// Open edit dialog
const openEditDialog = (role: Role) => {
  selectedRole.value = role
  setEditValues({
    name: role.name,
  })
  isEditDialogOpen.value = true
}

// Open delete dialog
const openDeleteDialog = (role: Role) => {
  selectedRole.value = role
  isDeleteDialogOpen.value = true
}

// Open permissions dialog
const openPermissionsDialog = (role: Role) => {
  selectedRole.value = role
  selectedPermissions.value = [...role.permissions]
  isPermissionsDialogOpen.value = true
}

// Group permissions by prefix (e.g., "users", "roles", "permissions")
const groupedPermissions = computed(() => {
  const groups: Record<string, Permission[]> = {}

  permissions.value.forEach(permission => {
    const parts = permission.name.split('.')
    const group = parts.length > 1 ? parts[0] : 'other'

    if (!groups[group]) {
      groups[group] = []
    }
    groups[group].push(permission)
  })

  return groups
})

// Toggle permission
const togglePermission = (permissionName: string) => {
  const index = selectedPermissions.value.indexOf(permissionName)
  if (index > -1) {
    selectedPermissions.value.splice(index, 1)
  } else {
    selectedPermissions.value.push(permissionName)
  }
}

// Check if permission is selected
const isPermissionSelected = (permissionName: string) => {
  return selectedPermissions.value.includes(permissionName)
}

// Initialize
onMounted(() => {
  fetchRoles()
  fetchPermissions()
})
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Role Management</h1>
        <p class="text-muted-foreground">Manage roles and their permissions</p>
      </div>
      <Button @click="openCreateDialog">
        <Plus class="mr-2 h-4 w-4" />
        Add Role
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
        <CardTitle>Roles</CardTitle>
        <CardDescription>
          A list of all roles in the system
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div v-if="isLoading" class="text-center py-8">
          <p class="text-muted-foreground">Loading roles...</p>
        </div>
        <div v-else-if="roles.length === 0" class="text-center py-8">
          <p class="text-muted-foreground">No roles found</p>
        </div>
        <div v-else class="rounded-md border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Permissions</TableHead>
                <TableHead>Created</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="role in roles" :key="role.id">
                <TableCell class="font-medium">{{ role.name }}</TableCell>
                <TableCell>
                  <div class="flex flex-wrap gap-1 max-w-md">
                    <Badge v-for="permission in role.permissions.slice(0, 5)" :key="permission" variant="default" class="text-xs">
                      {{ permission }}
                    </Badge>
                    <Badge v-if="role.permissions.length > 5" variant="outline" class="text-xs">
                      +{{ role.permissions.length - 5 }} more
                    </Badge>
                    <span v-if="role.permissions.length === 0" class="text-muted-foreground text-sm">No permissions</span>
                  </div>
                </TableCell>
                <TableCell class="text-muted-foreground text-sm">
                  {{ new Date(role.created_at).toLocaleDateString() }}
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <Button variant="ghost" size="sm" @click="openPermissionsDialog(role)" title="Manage Permissions">
                      <Shield class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" @click="openEditDialog(role)">
                      <Pencil class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" @click="openDeleteDialog(role)">
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
            Showing page {{ currentPage }} of {{ lastPage }} ({{ total }} total roles)
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

    <!-- Create Role Dialog -->
    <Dialog v-model:open="isCreateDialogOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Create Role</DialogTitle>
          <DialogDescription>
            Add a new role to the system
          </DialogDescription>
        </DialogHeader>
        <form @submit="onCreateSubmit" class="space-y-4" :key="`create-form-${isCreateDialogOpen}`">
          <Alert v-if="errorMessage" variant="destructive" class="mb-4">
            <AlertDescription>{{ errorMessage }}</AlertDescription>
          </Alert>
          <FormField v-slot="{ componentField }" name="name">
            <FormItem>
              <FormLabel>Name</FormLabel>
              <FormControl>
                <Input type="text" placeholder="admin" v-bind="componentField" />
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <div class="space-y-3">
            <label class="text-sm font-medium leading-none">Permissions</label>
            <div class="border rounded-md p-4 max-h-60 overflow-y-auto space-y-4">
              <div v-for="(permissions, group) in groupedPermissions" :key="group" class="space-y-2">
                <h4 class="text-sm font-semibold capitalize text-muted-foreground">{{ group }}</h4>
                <div class="grid grid-cols-2 gap-2">
                  <label
                    v-for="permission in permissions"
                    :key="permission.id"
                    class="flex items-center space-x-2 cursor-pointer hover:bg-accent p-2 rounded"
                  >
                    <input
                      type="checkbox"
                      :checked="isPermissionSelected(permission.name)"
                      @change="togglePermission(permission.name)"
                      class="h-4 w-4 rounded border-gray-300"
                    />
                    <span class="text-sm">{{ permission.name }}</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" @click="isCreateDialogOpen = false; resetCreateForm(); selectedPermissions = []">
              Cancel
            </Button>
            <Button type="submit" :disabled="isCreating">
              <span v-if="isCreating">Creating...</span>
              <span v-else>Create Role</span>
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Edit Role Dialog -->
    <Dialog v-model:open="isEditDialogOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Edit Role</DialogTitle>
          <DialogDescription>
            Update role information
          </DialogDescription>
        </DialogHeader>
        <form @submit="onEditSubmit" class="space-y-4">
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
            </FormItem>
          </FormField>

          <DialogFooter>
            <Button type="button" variant="outline" @click="isEditDialogOpen = false; resetEditForm()">
              Cancel
            </Button>
            <Button type="submit" :disabled="isUpdating">
              <span v-if="isUpdating">Updating...</span>
              <span v-else>Update Role</span>
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Delete Role Dialog -->
    <AlertDialog v-model:open="isDeleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Are you sure?</AlertDialogTitle>
          <AlertDialogDescription>
            This action cannot be undone. This will permanently delete the role
            <strong>{{ selectedRole?.name }}</strong>.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction @click="deleteRole" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
            Delete
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>

    <!-- Permissions Dialog -->
    <Dialog v-model:open="isPermissionsDialogOpen">
      <DialogContent class="max-w-2xl max-h-[80vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Manage Permissions</DialogTitle>
          <DialogDescription>
            Assign permissions to role: <strong>{{ selectedRole?.name }}</strong>
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4">
          <Alert v-if="errorMessage" variant="destructive">
            <AlertDescription>{{ errorMessage }}</AlertDescription>
          </Alert>
          <div class="border rounded-md p-4 max-h-[400px] overflow-y-auto space-y-4">
            <div v-for="(permissions, group) in groupedPermissions" :key="group" class="space-y-2">
              <h4 class="text-sm font-semibold capitalize text-muted-foreground">{{ group }}</h4>
              <div class="grid grid-cols-2 gap-2">
                <label
                  v-for="permission in permissions"
                  :key="permission.id"
                  class="flex items-center space-x-2 cursor-pointer hover:bg-accent p-2 rounded"
                >
                  <input
                    type="checkbox"
                    :checked="isPermissionSelected(permission.name)"
                    @change="togglePermission(permission.name)"
                    class="h-4 w-4 rounded border-gray-300"
                  />
                  <span class="text-sm">{{ permission.name }}</span>
                </label>
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" @click="isPermissionsDialogOpen = false">
              Cancel
            </Button>
            <Button @click="assignPermissions">
              Save Permissions
            </Button>
          </DialogFooter>
        </div>
      </DialogContent>
    </Dialog>
  </div>
</template>
