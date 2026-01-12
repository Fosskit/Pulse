<script setup lang="ts">
import { z } from 'zod'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { useAuth } from '~/composables/useAuth'
import { useErrorHandler } from '~/composables/useErrorHandler'
import { Button } from '~/components/ui/button'
import { Input } from '~/components/ui/input'
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
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

definePageMeta({
  middleware: 'guest',
  layout: 'auth'
})

const auth = useAuth()
const errorHandler = useErrorHandler()
const router = useRouter()

const errorMessage = ref<string>('')
const successMessage = ref<string>('')

const registerSchema = z.object({
  name: z.string().min(1, 'Name is required'),
  email: z.string().email('Please enter a valid email address'),
  password: z.string().min(8, 'Password must be at least 8 characters'),
  password_confirmation: z.string().min(1, 'Please confirm your password'),
}).refine((data) => data.password === data.password_confirmation, {
  message: "Passwords don't match",
  path: ['password_confirmation'],
})

const { handleSubmit, isSubmitting } = useForm({
  validationSchema: toTypedSchema(registerSchema),
})

const onSubmit = handleSubmit(async (values) => {
  errorMessage.value = ''
  successMessage.value = ''
  try {
    await auth.register({
      name: values.name,
      email: values.email,
      password: values.password,
      password_confirmation: values.password_confirmation,
    })
    successMessage.value = 'Registration successful! Please check your email to verify your account.'
    // Optionally redirect to login after a delay
    setTimeout(() => {
      router.push('/login')
    }, 3000)
  } catch (error: any) {
    errorMessage.value = errorHandler.formatError(error)
  }
})
</script>

<template>
  <div class="flex min-h-screen items-center justify-center p-4">
    <Card class="w-full max-w-md">
      <CardHeader>
        <CardTitle>Register</CardTitle>
        <CardDescription>
          Create a new account to get started
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form @submit="onSubmit" class="space-y-4">
          <Alert v-if="errorMessage" variant="destructive">
            <AlertDescription>{{ errorMessage }}</AlertDescription>
          </Alert>

          <Alert v-if="successMessage" variant="default">
            <AlertDescription>{{ successMessage }}</AlertDescription>
          </Alert>

          <FormField v-slot="{ componentField }" name="name">
            <FormItem>
              <FormLabel>Name</FormLabel>
              <FormControl>
                <Input
                  type="text"
                  placeholder="John Doe"
                  v-bind="componentField"
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <FormField v-slot="{ componentField }" name="email">
            <FormItem>
              <FormLabel>Email</FormLabel>
              <FormControl>
                <Input
                  type="email"
                  placeholder="you@example.com"
                  v-bind="componentField"
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <FormField v-slot="{ componentField }" name="password">
            <FormItem>
              <FormLabel>Password</FormLabel>
              <FormControl>
                <Input
                  type="password"
                  placeholder="••••••••"
                  v-bind="componentField"
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <FormField v-slot="{ componentField }" name="password_confirmation">
            <FormItem>
              <FormLabel>Confirm Password</FormLabel>
              <FormControl>
                <Input
                  type="password"
                  placeholder="••••••••"
                  v-bind="componentField"
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <Button type="submit" class="w-full" :disabled="isSubmitting">
            <span v-if="isSubmitting">Registering...</span>
            <span v-else>Register</span>
          </Button>
        </form>
      </CardContent>
      <CardFooter class="flex justify-center">
        <p class="text-sm text-muted-foreground">
          Already have an account?
          <NuxtLink to="/login" class="text-primary hover:underline">
            Login
          </NuxtLink>
        </p>
      </CardFooter>
    </Card>
  </div>
</template>
