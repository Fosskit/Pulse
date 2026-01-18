<script setup lang="ts">
import { useApi } from '~/composables/useApi'
import { useErrorHandler } from '~/composables/useErrorHandler'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '~/components/ui/card'
import { Badge } from '~/components/ui/badge'
import { Users, Shield, Key, Activity } from 'lucide-vue-next'

definePageMeta({
  middleware: 'auth'
})

const api = useApi()
const errorHandler = useErrorHandler()

const stats = ref({
  users: { total: 0, verified: 0, unverified: 0 },
  roles: { total: 0 },
  permissions: { total: 0 },
  activity: { total_logs: 0, recent_logs: [] }
})

const isLoading = ref(true)
const errorMessage = ref('')

const fetchStats = async () => {
  try {
    const response = await api.get('/admin/dashboard/stats')
    stats.value = response.data
  } catch (error: any) {
    errorMessage.value = errorHandler.formatError(error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchStats()
})
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-3xl font-bold">Dashboard</h1>
      <p class="text-muted-foreground">Welcome to your admin dashboard</p>
    </div>

    <div v-if="errorMessage" class="text-red-600">
      {{ errorMessage }}
    </div>

    <div v-else-if="isLoading" class="text-center py-8">
      <p class="text-muted-foreground">Loading dashboard...</p>
    </div>

    <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      <!-- Users Stats -->
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Total Users</CardTitle>
          <Users class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ stats.users.total }}</div>
          <p class="text-xs text-muted-foreground">
            {{ stats.users.verified }} verified, {{ stats.users.unverified }} pending
          </p>
        </CardContent>
      </Card>

      <!-- Roles Stats -->
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Total Roles</CardTitle>
          <Shield class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ stats.roles.total }}</div>
          <p class="text-xs text-muted-foreground">
            Role-based access control
          </p>
        </CardContent>
      </Card>

      <!-- Permissions Stats -->
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Total Permissions</CardTitle>
          <Key class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ stats.permissions.total }}</div>
          <p class="text-xs text-muted-foreground">
            Granular permissions
          </p>
        </CardContent>
      </Card>

      <!-- Activity Stats -->
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Activity Logs</CardTitle>
          <Activity class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ stats.activity.total_logs }}</div>
          <p class="text-xs text-muted-foreground">
            Total logged actions
          </p>
        </CardContent>
      </Card>
    </div>

    <!-- Recent Activity -->
    <Card v-if="stats.activity.recent_logs.length > 0">
      <CardHeader>
        <CardTitle>Recent Activity</CardTitle>
        <CardDescription>Latest system activities</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="space-y-4">
          <div
            v-for="log in stats.activity.recent_logs"
            :key="log.created_at"
            class="flex items-center space-x-4"
          >
            <div class="flex-1">
              <p class="text-sm font-medium">{{ log.action }}</p>
              <p class="text-sm text-muted-foreground">{{ log.description }}</p>
            </div>
            <div class="text-xs text-muted-foreground">
              {{ new Date(log.created_at).toLocaleString() }}
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
