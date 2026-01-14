<script setup lang="ts">
import { computed, ref } from 'vue'
import { Check, X, ChevronsUpDown } from 'lucide-vue-next'
import { cn } from '~/lib/utils'
import { Button } from '~/components/ui/button'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '~/components/ui/popover'
import { Badge } from '~/components/ui/badge'

interface Option {
  label: string
  value: string
}

interface Props {
  options: Option[]
  modelValue: string[]
  placeholder?: string
  searchPlaceholder?: string
  emptyText?: string
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select items...',
  searchPlaceholder: 'Search...',
  emptyText: 'No items found.',
})

const emit = defineEmits<{
  'update:modelValue': [value: string[]]
}>()

const open = ref(false)
const searchQuery = ref('')

const selectedValues = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const filteredOptions = computed(() => {
  if (!searchQuery.value) return props.options
  return props.options.filter(option =>
    option.label.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const selectedLabels = computed(() => {
  return props.options
    .filter(option => selectedValues.value.includes(option.value))
    .map(option => option.label)
})

const toggleOption = (value: string) => {
  const index = selectedValues.value.indexOf(value)
  if (index === -1) {
    selectedValues.value = [...selectedValues.value, value]
  } else {
    selectedValues.value = selectedValues.value.filter(v => v !== value)
  }
}

const removeOption = (value: string) => {
  selectedValues.value = selectedValues.value.filter(v => v !== value)
}

const isSelected = (value: string) => {
  return selectedValues.value.includes(value)
}
</script>

<template>
  <div class="space-y-2">
    <Popover v-model:open="open">
      <PopoverTrigger as-child>
        <Button
          variant="outline"
          role="combobox"
          :aria-expanded="open"
          class="w-full justify-between"
        >
          <span v-if="selectedValues.length === 0" class="text-muted-foreground">
            {{ placeholder }}
          </span>
          <span v-else class="text-sm">
            {{ selectedValues.length }} selected
          </span>
          <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent class="w-full p-0" align="start">
        <div class="p-2 border-b">
          <input
            type="text"
            :placeholder="searchPlaceholder"
            class="w-full px-3 py-2 text-sm border rounded-md focus:outline-none focus:ring-2 focus:ring-ring"
            v-model="searchQuery"
          />
        </div>
        <div class="max-h-60 overflow-y-auto p-1">
          <div v-if="filteredOptions.length === 0" class="py-6 text-center text-sm text-muted-foreground">
            {{ emptyText }}
          </div>
          <button
            v-for="option in filteredOptions"
            :key="option.value"
            type="button"
            class="w-full flex items-center px-2 py-1.5 text-sm rounded-sm hover:bg-accent hover:text-accent-foreground cursor-pointer"
            @click="toggleOption(option.value)"
          >
            <Check
              :class="cn(
                'mr-2 h-4 w-4',
                isSelected(option.value) ? 'opacity-100' : 'opacity-0'
              )"
            />
            {{ option.label }}
          </button>
        </div>
      </PopoverContent>
    </Popover>

    <div v-if="selectedValues.length > 0" class="flex flex-wrap gap-1">
      <Badge
        v-for="(label, index) in selectedLabels"
        :key="selectedValues[index]"
        variant="secondary"
        class="gap-1"
      >
        {{ label }}
        <button
          type="button"
          class="ml-1 rounded-full outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2"
          @click="removeOption(selectedValues[index])"
        >
          <X class="h-3 w-3" />
        </button>
      </Badge>
    </div>
  </div>
</template>
