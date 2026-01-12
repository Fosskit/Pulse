<script setup lang="ts">
import { z } from 'zod'
import { useForm, Form } from 'vee-validate'
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
import { Plus, Pencil, Trash2 } from 'lucide-vue-next'
import type { User } from '~/types/auth'

definePageMeta({
  middleware: 'auth'
})

const api = useApi()
const errorHandler = useErrorHandler()

const users = ref<User[]>([])
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
const selectedUser = ref<User | null>(null)

// Form refs
const createFormRef = ref<HTMLFormElement | null>(null)
const editFormRef = ref<HTMLFormElement | null>(null)

// Shared form schema - switches based on dialog type
const createSchema = z.object({
  name: z.string().min(1, 'Name is required'),
  email: z.string().email('Please enter a valid email address'),
  password: z.string().min(8, 'Password must be at least 8 characters'),
})

const editSchema = z.object({
  name: z.string().min(1, 'Name is required'),
  email: z.string().email('Please enter a valid email address'),
  password: z.string().min(8, 'Password must be at least 8 characters').optional().or(z.literal('')),
})

// Single shared form context - switches validation based on active dialog
const currentSchema = computed(() => isCreateDialogOpen.value ? createSchema : editSchema)

const { handleSubmit, setValues, resetForm, isSubmitting } = useForm({
  validationSchema: computed(() => toTypedSchema(currentSchema.value)),
})

// Fetch users
const fetchUsers = async (page = 1) => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await api.get<{
      data: User[]
      meta: {
        current_page: number
        last_page: number
        per_page: number
        total: number
      }
    }>(`/admin/users?per_page=${perPage.value}&page=${page}`)

    users.value = response.data
    currentPage.value = response.meta.current_page
    lastPage.value = response.meta.last_page
    total.value = response.meta.total
  } catch (error: any) {
    errorMessage.value = errorHandler.formatError(error)
  } finally {
    isLoading.value = false
  }
}

// Create user
const onCreateSubmit = handleSubmit(async (values) => {
  console.log('onCreateSubmit called with values:', values)
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const response = await api.post('/admin/users', {
      name: values.name,
      email: values.email,
      password: values.password,
    })
    console.log('User created successfully:', response)
    successMessage.value = 'User created successfully'
    isCreateDialogOpen.value = false
    resetForm()
    await fetchUsers(currentPage.value)
  } catch (error) {
    console.error('Error creating user:', error)
    errorMessage.value = errorHandler.formatError(error)
  }
})

// Edit user
const onEditSubmit = handleSubmit(async (values) => {
  if (!selectedUser.value) return

  errorMessage.value = ''
  successMessage.value = ''
  try {
    const updateData: any = {
      name: values.name,
      email: values.email,
    }

    if (values.password) {
      updateData.password = values.password
    }

    await api.put(`/admin/users/${selectedUser.value.id}`, updateData)
    successMessage.value = 'User updated successfully'
    isEditDialogOpen.value = false
    selectedUser.value = null
    resetForm()
    await fetchUsers(currentPage.value)
  } catch (error: any) {
    errorMessage.value = errorHandler.formatError(error)
  }
})

// Delete user
const deleteUser = async () => {
  if (!selectedUser.value) return

  errorMessage.value = ''
  successMessage.value = ''
  try {
    await api.delete(`/admin/users/${selectedUser.value.id}`)
    successMessage.value = 'User deleted successfully'
    isDeleteDialogOpen.value = false
    selectedUser.value = null
    await fetchUsers(currentPage.value)
  } catch (error: any) {
    errorMessage.value = errorHandler.formatError(error)
  }
}

// Open create dialog
const openCreateDialog = () => {
  resetForm()
  errorMessage.value = ''
  successMessage.value = ''
  isCreateDialogOpen.value = true
}

// Open edit dialog
const openEditDialog = (user: User) => {
  selectedUser.value = user
  setValues({
    name: user.name,
    email: user.email,
    password: '',
  })
  isEditDialogOpen.value = true
}

// Open delete dialog
const openDeleteDialog = (user: User) => {
  selectedUser.value = user
  isDeleteDialogOpen.value = true
}

// Initialize
onMounted(() => {
  fetchUsers()
})
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">User Management</h1>
        <p class="text-muted-foreground">Manage users and their permissions</p>
      </div>
      <Button @click="openCreateDialog">
        <Plus class="mr-2 h-4 w-4" />
        Add User
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
        <CardTitle>Users</CardTitle>
        <CardDescription>
          A list of all users in the system
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div v-if="isLoading" class="text-center py-8">
          <p class="text-muted-foreground">Loading users...</p>
        </div>
        <div v-else-if="users.length === 0" class="text-center py-8">
          <p class="text-muted-foreground">No users found</p>
        </div>
        <div v-else class="rounded-md border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Roles</TableHead>
                <TableHead>Permissions</TableHead>
                <TableHead>Verified</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="user in users" :key="user.id">
                <TableCell class="font-medium">{{ user.name }}</TableCell>
                <TableCell>{{ user.email }}</TableCell>
                <TableCell>
                  <div class="flex flex-wrap gap-1">
                    <Badge v-for="role in user.roles" :key="role.id" variant="secondary">
                      {{ role.name }}
                    </Badge>
                    <span v-if="user.roles.length === 0" class="text-muted-foreground text-sm">No roles</span>
                  </div>
                </TableCell>
                <TableCell>
                  <div class="flex flex-wrap gap-1 max-w-xs">
                    <Badge v-for="permission in user.all_permissions.slice(0, 3)" :key="permission" variant="default" class="text-xs">
                      {{ permission }}
                    </Badge>
                    <Badge v-if="user.all_permissions.length > 3" variant="outline" class="text-xs">
                      +{{ user.all_permissions.length - 3 }} more
                    </Badge>
                    <span v-if="user.all_permissions.length === 0" class="text-muted-foreground text-sm">No permissions</span>
                  </div>
                </TableCell>
                <TableCell>
                  <Badge v-if="user.email_verified_at" variant="default">Verified</Badge>
                  <Badge v-else variant="secondary">Unverified</Badge>
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <Button variant="ghost" size="sm" @click="openEditDialog(user)">
                      <Pencil class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" @click="openDeleteDialog(user)">
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
            Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, total) }} of {{ total }} users
          </p>
          <div class="flex gap-2">
            <Button variant="outline" size="sm" :disabled="currentPage === 1" @click="fetchUsers(currentPage - 1)">
              Previous
            </Button>
            <Button variant="outline" size="sm" :disabled="currentPage === lastPage" @click="fetchUsers(currentPage + 1)">
              Next
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Create User Dialog -->
    <Dialog v-model:open="isCreateDialogOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Create User</DialogTitle>
          <DialogDescription>
            Add a new user to the system
          </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="onCreateSubmit" class="space-y-4">
          <FormField v-slot="{ componentField }" name="name">
            <FormItem>
              <FormLabel>Name</FormLabel>
              <FormControl>
                <Input type="text" placeholder="John Doe" v-bind="componentField" />
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <FormField v-slot="{ componentField }" name="email">
            <FormItem>
              <FormLabel>Email</FormLabel>
              <FormControl>
                <Input type="email" placeholder="user@example.com" v-bind="componentField" />
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <FormField v-slot="{ componentField }" name="password">
            <FormItem>
              <FormLabel>Password</FormLabel>
              <FormControl>
                <Input type="password" placeholder="••••••••" v-bind="componentField" />
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <DialogFooter>
            <Button type="button" variant="outline" @click="isCreateDialogOpen = false; resetForm()">
              Cancel
            </Button>
            <Button
              type="submit"
              :disabled="isSubmitting"
            >
              <span v-if="isSubmitting">Creating...</span>
              <span v-else>Create User</span>
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Edit User Dialog -->
    <Dialog v-model:open="isEditDialogOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Edit User</DialogTitle>
          <DialogDescription>
            Update user information
          </DialogDescription>
        </DialogHeader>
        <form @submit="onEditSubmit" class="space-y-4">
          <FormField v-slot="{ componentField }" name="name">
            <FormItem>
              <FormLabel>Name</FormLabel>
              <FormControl>
                <Input type="text" v-bind="componentField" />
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <FormField v-slot="{ componentField }" name="email">
            <FormItem>
              <FormLabel>Email</FormLabel>
              <FormControl>
                <Input type="email" v-bind="componentField" />
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <FormField v-slot="{ componentField }" name="password">
            <FormItem>
              <FormLabel>Password (leave blank to keep current)</FormLabel>
              <FormControl>
                <Input type="password" placeholder="••••••••" v-bind="componentField" />
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <DialogFooter>
            <Button type="button" variant="outline" @click="isEditDialogOpen = false; resetForm()">
              Cancel
            </Button>
            <Button type="submit" :disabled="isSubmitting">
              <span v-if="isSubmitting">Updating...</span>
              <span v-else>Update User</span>
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Delete User Dialog -->
    <AlertDialog v-model:open="isDeleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Are you sure?</AlertDialogTitle>
          <AlertDialogDescription>
            This action cannot be undone. This will permanently delete the user
            <strong>{{ selectedUser?.name }}</strong> and all associated data.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction @click="deleteUser" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
            Delete
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>
</template>
