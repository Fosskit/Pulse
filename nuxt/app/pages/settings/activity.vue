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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '~/components/ui/select'
import { Badge } from '~/components/ui/badge'
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '~/components/ui/pagination'
import { Activity, Search, Filter } from 'lucide-vue-next'
import type { ActivityLog } from '~/types/auth'

definePageMeta({
  middleware: 'auth'
})

const api = useApi()
const errorHandler = useErrorHandler()

const activityLogs = ref<ActivityLog[]>([])
const isLoading = ref(false)
const errorMessage = ref<string>('')

// Pagination
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
const perPage = ref(15)

// Filters
const searchQuery = ref('')
const selectedAction = ref<string>('all')
const selectedModel = ref<string>('all')

// Available filter options
const availableActions = ref<string[]>([])
const availableModels = ref<string[]>([])

// Fetch activity logs
const fetchActivityLogs = async (page = 1) => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const params = new URLSearchParams()
    params.append('per_page', perPage.value.toString())
    params.append('page', page.toString())

    if (selectedAction.value && selectedAction.value !== 'all') {
      params.append('action', selectedAction.value)
    }

    if (selectedModel.value && selectedModel.value !== 'all') {
      params.append('model', selectedModel.value)
    }

    const response = await api.get<{
      data: ActivityLog[]
      meta: {
        current_page: number
        last_page: number
        per_page: number
        total: number
      }
    }>(`/admin/activity?${params.toString()}`)

    activityLogs.value = response.data
    currentPage.value = response.meta.current_page
    lastPage.value = response.meta.last_page
    total.value = response.meta.total

    // Extract unique actions and models for filters
    updateFilterOptions()
  } catch (error: any) {
    errorMessage.value = errorHandler.formatError(error)
  } finally {
    isLoading.value = false
  }
}

// Update filter options from current data
const updateFilterOptions = () => {
  const actions = new Set<string>()
  const models = new Set<string>()

  activityLogs.value.forEach(log => {
    if (log.action) actions.add(log.action)
    if (log.model) models.add(log.model)
  })

  availableActions.value = Array.from(actions).sort()
  availableModels.value = Array.from(models).sort()
}

// Handle page change
const handlePageChange = (newPage: number) => {
  currentPage.value = newPage
  fetchActivityLogs(newPage)
}

// Watch for filter changes
watch([selectedAction, selectedModel], () => {
  fetchActivityLogs(1)
})

// Get action badge variant
const getActionBadgeVariant = (action: string) => {
  const variants: Record<string, string> = {
    'user.created': 'default',
    'user.updated': 'secondary',
    'user.deleted': 'destructive',
    'role.created': 'default',
    'role.updated': 'secondary',
    'role.deleted': 'destructive',
    'permission.created': 'default',
    'permission.updated': 'secondary',
    'permission.deleted': 'destructive',
  }
  return variants[action] || 'outline'
}

// Format timestamp
const formatTimestamp = (timestamp: string) => {
  return new Date(timestamp).toLocaleString()
}

// Initialize
onMounted(() => {
  fetchActivityLogs()
})
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Activity Logs</h1>
        <p class="text-muted-foreground">Monitor system activities and user actions</p>
      </div>
    </div>

    <Alert v-if="errorMessage" variant="destructive">
      <AlertDescription>{{ errorMessage }}</AlertDescription>
    </Alert>

    <Card>
      <CardHeader>
        <CardTitle>Activity Logs</CardTitle>
        <CardDescription>
          A chronological list of all system activities
        </CardDescription>
      </CardHeader>
      <CardContent>
        <!-- Filters -->
        <div class="flex gap-4 mb-4">
          <div class="flex-1">
            <Input
              v-model="searchQuery"
              type="text"
              placeholder="Search descriptions..."
              class="w-full"
              disabled
            />
          </div>
          <Select v-model="selectedAction">
            <SelectTrigger class="w-[180px]">
              <SelectValue placeholder="Filter by action" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Actions</SelectItem>
              <SelectItem
                v-for="action in availableActions"
                :key="action"
                :value="action"
              >
                {{ action }}
              </SelectItem>
            </SelectContent>
          </Select>
          <Select v-model="selectedModel">
            <SelectTrigger class="w-[180px]">
              <SelectValue placeholder="Filter by model" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Models</SelectItem>
              <SelectItem
                v-for="model in availableModels"
                :key="model"
                :value="model"
              >
                {{ model }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div v-if="isLoading" class="text-center py-8">
          <p class="text-muted-foreground">Loading activity logs...</p>
        </div>
        <div v-else-if="activityLogs.length === 0" class="text-center py-8">
          <p class="text-muted-foreground">No activity logs found</p>
        </div>
        <div v-else>
          <div class="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>User</TableHead>
                  <TableHead>Action</TableHead>
                  <TableHead>Model</TableHead>
                  <TableHead>Description</TableHead>
                  <TableHead>IP Address</TableHead>
                  <TableHead>Timestamp</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="log in activityLogs" :key="log.id">
                  <TableCell class="font-medium">
                    {{ log.user_name || 'System' }}
                  </TableCell>
                  <TableCell>
                    <Badge :variant="getActionBadgeVariant(log.action)">
                      {{ log.action }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline" v-if="log.model">
                      {{ log.model }}
                    </Badge>
                    <span v-else class="text-muted-foreground">-</span>
                  </TableCell>
                  <TableCell class="max-w-xs">
                    <p class="text-sm truncate" :title="log.description">
                      {{ log.description }}
                    </p>
                  </TableCell>
                  <TableCell class="font-mono text-sm">
                    {{ log.ip_address || '-' }}
                  </TableCell>
                  <TableCell class="text-muted-foreground text-sm">
                    {{ formatTimestamp(log.created_at) }}
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>

          <!-- Pagination -->
          <div class="flex items-center justify-between mt-4">
            <p class="text-sm text-muted-foreground">
              Showing page {{ currentPage }} of {{ lastPage }} ({{ total }} total logs)
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
        </div>
      </CardContent>
    </Card>
  </div>
</template>
