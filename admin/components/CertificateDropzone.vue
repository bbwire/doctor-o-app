<template>
  <div class="space-y-2">
    <input
      ref="inputEl"
      type="file"
      :accept="accept"
      class="sr-only"
      @change="onInputChange"
    >
    <button
      type="button"
      :disabled="disabled"
      class="w-full rounded-xl border-2 border-dashed transition-all duration-150 outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 disabled:opacity-50 disabled:cursor-not-allowed text-left min-h-[140px]"
      :class="[
        isDragOver
          ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/40'
          : modelValue
            ? 'border-primary-300 dark:border-primary-700 bg-primary-50/50 dark:bg-primary-950/20'
            : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 bg-gray-50/50 dark:bg-gray-900/50'
      ]"
      @click="inputEl?.click()"
      @dragover.prevent="isDragOver = true"
      @dragleave.prevent="isDragOver = false"
      @drop.prevent="onDrop"
    >
      <div v-if="modelValue" class="flex items-center gap-4 p-4">
        <div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-primary-100 dark:bg-primary-900/50">
          <UIcon name="i-lucide-file-check" class="size-6 text-primary-600 dark:text-primary-400" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="font-medium text-gray-900 dark:text-white truncate">
            {{ modelValue.name }}
          </p>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ formatSize(modelValue.size) }}
          </p>
        </div>
        <UButton
          type="button"
          variant="ghost"
          color="red"
          size="sm"
          icon="i-lucide-x"
          :disabled="disabled"
          @click.stop="clear"
        >
          Remove
        </UButton>
      </div>
      <div v-else class="flex flex-col items-center justify-center gap-2 p-6">
        <div class="flex size-14 shrink-0 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700">
          <UIcon name="i-lucide-upload-cloud" class="size-7 text-gray-500 dark:text-gray-400" />
        </div>
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
          {{ isDragOver ? 'Drop file here' : 'Drop certificate here or click to browse' }}
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          PDF or image (JPEG, PNG, WebP). Max 10 MB.
        </p>
      </div>
    </button>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: { type: File, default: null },
  disabled: { type: Boolean, default: false },
  accept: {
    type: String,
    default: '.pdf,image/jpeg,image/jpg,image/png,image/webp'
  }
})

const emit = defineEmits(['update:modelValue'])

const inputEl = ref(null)
const isDragOver = ref(false)

function onInputChange (event) {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (file) emit('update:modelValue', file)
}

function onDrop (event) {
  isDragOver.value = false
  const file = event.dataTransfer?.files?.[0]
  if (file) emit('update:modelValue', file)
}

function clear () {
  emit('update:modelValue', null)
  if (inputEl.value) inputEl.value.value = ''
}

function formatSize (bytes) {
  if (bytes == null || bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(1))} ${sizes[i]}`
}
</script>
