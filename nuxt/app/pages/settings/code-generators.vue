<script setup lang="ts">
import { z } from 'zod'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { useApi } from '~/composables/useApi'
import { useErrorHandler } from '~/composables/useErrorHandler'
import { Button } from '~/components/ui/button'
import { Input } from '~/components/ui/input'
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
  FormDescription,
} from '~/components/ui/form'
import { Badge } from '~/components/ui/badge'
import { Pencil, Info } from 'lucide-vue-next'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '~/components/ui/dialog'

definePageMeta({
  middleware: 'auth'
})

interface CodeGenerator {
  id: number
  entity: string
  prefix: string | null
  format: string
  current_sequence: number
  reset_yearly: boolean
  reset_monthly: boolean
  padding: number
}

const api = useApi()
const errorHandler = useErrorHandler()

const generators = ref<CodeGenerator[]>([])
const isLoading = ref(false)
const errorMessage = ref<string>('')
const successMessage = ref<string>('')
const isEditDialogOpen = ref(false)
const selectedGenerator = ref<CodeGenerator | null>(null)

// Form schema
const generatorSchema = z.object({
  prefix: z.string().optional(),
  format: z.string().min(1, 'Format is required'),
  reset_yearly: z.boolean(),
  reset_monthly: z.boolean(),
  padding: z.number().min(1).max(10),
})

const { handleSubmit, setValues, resetForm, isSubmitting, values } = useForm({
  validationSchema: toTypedSchema(generatorSchema),
})

// Fetch generators
const fetchGenerators = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await api.get<{ data: CodeGenerator[] }>('/settings/code-generators')
    generators.value = response.data
  } catch (error: any) {
    errorMessage.value = errorHandler.formatError(error)
  } finally {
    isLoading.value = false
  }
}

// Edit generator
const onEditSubmit = handleSubmit(async (formValues) => {
  if (!selectedGenerator.value) return

  errorMessage.value = ''
  successMessage.value = ''
  try {
    await api.put(`/settings/code-generators/${selectedGenerator.value.id}`, formValues)
    successMessage.value = 'Code generator updated successfully'
    isEditDialogOpen.value = false
    selectedGenerator.value = null
    resetForm()
    await fetchGenerators()
  } catch (error: any) {
    errorMessage.value = errorHandler.formatError(error)
  }
})

// Open edit dialog
const openEditDialog = (generator: CodeGenerator) => {
  selectedGenerator.value = generator
  setValues({
    prefix: generator.prefix || '',
    format: generator.format,
    reset_yearly: generator.reset_yearly,
    reset_monthly: generator.reset_monthly,
    padding: generator.padding,
  })
  isEditDialogOpen.value = true
}

// Preview code format
const previewCode = computed(() => {
  if (!values.format) return 'N/A'
  
  let preview = values.format
  const replacements: Record<string, string> = {
    '{prefix}': values.prefix || 'XXX',
    '{year}': new Date().getFullYear().toString(),
    '{year2}': new Date().getFullYear().toString().slice(-2),
    '{month}': String(new Date().getMonth() + 1).padStart(2, '0'),
    '{day}': String(new Date().getDate()).padStart(2, '0'),
    '{seq}': '1'.padStart(values.padding || 5, '0'),
  }
  
  // Handle dynamic padding
  preview = preview.replace(/\{seq:(\d+)\}/, (_, p) => '1'.padStart(parseInt(p), '0'))
  
  Object.entries(replacements).forEach(([key, value]) => {
    preview = preview.replace(key, value)
  })
  
  return preview
})

// Initialize
onMounted(() => {
  fetchGenerators()
})
</script>

<template>
  <div class="space-y-4">
    <div>
      <h1 class="text-3xl font-bold">Code Generator Settings</h1>
      <p class="text-muted-foreground">Configure automatic code generation for entities</p>
    </div>

    <Alert v-if="errorMessage" variant="destructive">
      <AlertDescription>{{ errorMessage }}</AlertDescription>
    </Alert>

    <Alert v-if="successMessage" variant="default">
      <AlertDescription>{{ successMessage }}</AlertDescription>
    </Alert>

    <!-- Format Help Card -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Info class="h-5 w-5" />
          Available Placeholders
        </CardTitle>
        <CardDescription>
          Use these placeholders in your format string
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
          <div>
            <code class="bg-muted px-2 py-1 rounded">{prefix}</code>
            <p class="text-muted-foreground mt-1">Custom prefix</p>
          </div>
          <div>
            <code class="bg-muted px-2 py-1 rounded">{year}</code>
            <p class="text-muted-foreground mt-1">Full year (2026)</p>
          </div>
          <div>
            <code class="bg-muted px-2 py-1 rounded">{year2}</code>
            <p class="text-muted-foreground mt-1">Short year (26)</p>
          </div>
          <div>
            <code class="bg-muted px-2 py-1 rounded">{month}</code>
            <p class="text-muted-foreground mt-1">Month (01-12)</p>
          </div>
          <div>
            <code class="bg-muted px-2 py-1 rounded">{day}</code>
            <p class="text-muted-foreground mt-1">Day (01-31)</p>
          </div>
          <div>
            <code class="bg-muted px-2 py-1 rounded">{seq:5}</code>
            <p class="text-muted-foreground mt-1">Sequence (00001)</p>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Generators List -->
    <div v-if="isLoading" class="text-center py-8">
      <p class="text-muted-foreground">Loading generators...</p>
    </div>
    <div v-else class="grid gap-4">
      <Card v-for="generator in generators" :key="generator.id">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle class="capitalize">{{ generator.entity }}</CardTitle>
              <CardDescription>
                Current sequence: {{ generator.current_sequence }}
              </CardDescription>
            </div>
            <Button variant="outline" size="sm" @click="openEditDialog(generator)">
              <Pencil class="h-4 w-4 mr-2" />
              Edit
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          <div class="space-y-2">
            <div class="flex items-center gap-2">
              <span class="text-sm font-medium">Format:</span>
              <code class="bg-muted px-2 py-1 rounded text-sm">{{ generator.format }}</code>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-sm font-medium">Prefix:</span>
              <Badge variant="secondary">{{ generator.prefix || 'None' }}</Badge>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-sm font-medium">Reset:</span>
              <Badge v-if="generator.reset_yearly" variant="default">Yearly</Badge>
              <Badge v-if="generator.reset_monthly" variant="default">Monthly</Badge>
              <Badge v-if="!generator.reset_yearly && !generator.reset_monthly" variant="secondary">Never</Badge>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Edit Dialog -->
    <Dialog v-model:open="isEditDialogOpen">
      <DialogContent class="max-w-2xl">
        <DialogHeader>
          <DialogTitle>Edit Code Generator</DialogTitle>
          <DialogDescription>
            Configure code generation for {{ selectedGenerator?.entity }}
          </DialogDescription>
        </DialogHeader>
        <form @submit="onEditSubmit" class="space-y-4">
          <FormField v-slot="{ componentField }" name="prefix">
            <FormItem>
              <FormLabel>Prefix</FormLabel>
              <FormControl>
                <Input type="text" placeholder="PAT" v-bind="componentField" />
              </FormControl>
              <FormDescription>Optional prefix for generated codes</FormDescription>
              <FormMessage />
            </FormItem>
          </FormField>

          <FormField v-slot="{ componentField }" name="format">
            <FormItem>
              <FormLabel>Format</FormLabel>
              <FormControl>
                <Input type="text" placeholder="{prefix}-{year}-{seq:5}" v-bind="componentField" />
              </FormControl>
              <FormDescription>
                Preview: <code class="bg-muted px-2 py-1 rounded">{{ previewCode }}</code>
              </FormDescription>
              <FormMessage />
            </FormItem>
          </FormField>

          <FormField v-slot="{ componentField }" name="padding">
            <FormItem>
              <FormLabel>Sequence Padding</FormLabel>
              <FormControl>
                <Input type="number" min="1" max="10" v-bind="componentField" />
              </FormControl>
              <FormDescription>Number of digits for sequence (e.g., 5 = 00001)</FormDescription>
              <FormMessage />
            </FormItem>
          </FormField>

          <div class="space-y-3">
            <FormField v-slot="{ value, handleChange }" name="reset_yearly">
              <FormItem class="flex items-center gap-2">
                <FormControl>
                  <input
                    type="checkbox"
                    :checked="value"
                    @change="handleChange"
                    class="h-4 w-4 rounded border-gray-300"
                  />
                </FormControl>
                <FormLabel class="!mt-0">Reset sequence yearly</FormLabel>
              </FormItem>
            </FormField>

            <FormField v-slot="{ value, handleChange }" name="reset_monthly">
              <FormItem class="flex items-center gap-2">
                <FormControl>
                  <input
                    type="checkbox"
                    :checked="value"
                    @change="handleChange"
                    class="h-4 w-4 rounded border-gray-300"
                  />
                </FormControl>
                <FormLabel class="!mt-0">Reset sequence monthly</FormLabel>
              </FormItem>
            </FormField>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" @click="isEditDialogOpen = false; resetForm()">
              Cancel
            </Button>
            <Button type="submit" :disabled="isSubmitting">
              <span v-if="isSubmitting">Updating...</span>
              <span v-else">Update</span>
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </div>
</template>
