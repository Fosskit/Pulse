<script setup lang="ts">
definePageMeta({
  middleware: 'auth'
})

const auth = useAuth()
</script>

<template>
  <div>
    <h1 class="text-3xl font-bold mb-4">Welcome, {{ auth.user?.name || 'User' }}!</h1>
    <div class="space-y-4">
      <div class="p-4 bg-card rounded-lg border">
        <h2 class="text-xl font-semibold mb-2">Your Account</h2>
        <p class="text-muted-foreground">Email: {{ auth.user?.email }}</p>
        <p class="text-muted-foreground" v-if="auth.user?.email_verified_at">
          Email verified
        </p>
        <p class="text-muted-foreground" v-else>
          Please verify your email address
        </p>
      </div>
      <div class="p-4 bg-card rounded-lg border" v-if="auth.user?.roles && auth.user.roles.length > 0">
        <h2 class="text-xl font-semibold mb-2">Roles</h2>
        <ul class="list-disc list-inside">
          <li v-for="role in auth.user.roles" :key="role.id">
            {{ role.name }}
          </li>
        </ul>
      </div>
      <div class="p-4 bg-card rounded-lg border" v-if="auth.user?.all_permissions && auth.user.all_permissions.length > 0">
        <h2 class="text-xl font-semibold mb-2">Permissions</h2>
        <ul class="list-disc list-inside">
          <li v-for="permission in auth.user.all_permissions" :key="permission">
            {{ permission }}
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>
