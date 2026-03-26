<script setup lang="ts">
export interface PatientInvestigationUpload {
  id: string
  category: 'radiology' | 'laboratory'
  file_url: string
  storage_path?: string | null
  original_filename?: string | null
  label?: string | null
  uploaded_at?: string | null
}

const props = withDefaults(
  defineProps<{
    upload: PatientInvestigationUpload
    /** Slightly denser layout for nested summary section */
    compact?: boolean
    /** Show remove control (patient-only flows) */
    deletable?: boolean
    deleting?: boolean
  }>(),
  { compact: false, deletable: false, deleting: false }
)

const emit = defineEmits<{
  delete: []
}>()

const { resolvePublicFileUrl } = useResolvePublicFileUrl()
const resolvedFileUrl = computed(() => resolvePublicFileUrl(props.upload.file_url))

const imageFailed = ref(false)

watch(() => props.upload.id, () => {
  imageFailed.value = false
})

function displayName (u: PatientInvestigationUpload): string {
  return u.label || u.original_filename || 'Investigation file'
}

function isImageUpload (u: PatientInvestigationUpload): boolean {
  const name = (u.original_filename || u.label || '').toLowerCase()
  const path = resolvedFileUrl.value.toLowerCase()
  return /\.(jpe?g|png)(\?|#|$)/.test(name) || /\.(jpe?g|png)(\?|#|$)/.test(path)
}

function isPdfUpload (u: PatientInvestigationUpload): boolean {
  const name = (u.original_filename || u.label || '').toLowerCase()
  const path = resolvedFileUrl.value.toLowerCase()
  return /\.pdf(\?|#|$)/.test(name) || /\.pdf(\?|#|$)/.test(path)
}

function formatUploadedAt (iso: string | null | undefined): string {
  if (!iso) return ''
  try {
    return new Date(iso).toLocaleString(undefined, { dateStyle: 'short', timeStyle: 'short' })
  } catch {
    return iso
  }
}

const showImage = computed(() => isImageUpload(props.upload) && !imageFailed.value)
const showPdf = computed(() => isPdfUpload(props.upload))
const thumbClass = computed(() =>
  props.compact ? 'h-16 w-16 rounded-lg' : 'h-24 w-24 sm:h-28 sm:w-28 rounded-xl'
)
</script>

<template>
  <div
    class="group flex gap-1 sm:gap-2 rounded-2xl border border-gray-200 bg-gradient-to-br from-white to-gray-50/80 p-3 shadow-sm transition-all hover:border-primary-300 hover:shadow-md dark:border-gray-700 dark:from-gray-800/90 dark:to-gray-900/60 dark:hover:border-primary-500/50"
  >
    <a
      :href="resolvedFileUrl"
      target="_blank"
      rel="noopener noreferrer"
      class="flex min-w-0 flex-1 gap-3 sm:gap-4"
    >
    <div class="relative shrink-0">
      <img
        v-if="showImage"
        :src="resolvedFileUrl"
        :alt="displayName(upload)"
        :class="[thumbClass, 'object-cover border border-gray-200 shadow-sm dark:border-gray-600']"
        loading="lazy"
        @error="imageFailed = true"
      >
      <div
        v-else-if="showPdf"
        :class="[
          thumbClass,
          'flex flex-col items-center justify-center gap-1 border border-gray-200 bg-gradient-to-br from-red-50 to-gray-100 shadow-inner dark:border-gray-600 dark:from-red-950/50 dark:to-gray-800',
        ]"
      >
        <UIcon name="i-lucide-file-text" class="w-9 h-9 text-red-600 dark:text-red-400 sm:w-10 sm:h-10" />
        <span class="text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">PDF</span>
      </div>
      <div
        v-else
        :class="[
          thumbClass,
          'flex flex-col items-center justify-center gap-1 border border-gray-200 bg-gray-100 dark:border-gray-600 dark:bg-gray-800/80',
        ]"
      >
        <UIcon name="i-lucide-file" class="w-9 h-9 text-gray-500 dark:text-gray-400 sm:w-10 sm:h-10" />
        <span class="text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">File</span>
      </div>
      <div
        class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 opacity-0 transition group-hover:bg-black/20 group-hover:opacity-100"
        :class="thumbClass"
      >
        <span class="rounded-full bg-white/95 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-gray-900 shadow dark:bg-gray-900/95 dark:text-white">
          Open
        </span>
      </div>
    </div>
    <div class="min-w-0 flex-1 flex flex-col justify-center gap-1.5">
      <div class="flex flex-wrap items-center gap-2">
        <UBadge size="xs" color="primary" variant="soft" class="capitalize">
          {{ upload.category === 'radiology' ? 'Radiology' : 'Laboratory' }}
        </UBadge>
      </div>
      <p class="text-sm font-medium text-gray-900 dark:text-gray-100 line-clamp-2 group-hover:text-primary-700 dark:group-hover:text-primary-300">
        {{ displayName(upload) }}
      </p>
      <p v-if="upload.uploaded_at" class="text-xs text-gray-500 dark:text-gray-400">
        {{ formatUploadedAt(upload.uploaded_at) }}
      </p>
      <p v-if="!compact" class="text-xs text-primary-600 dark:text-primary-400 font-medium flex items-center gap-1 opacity-90 group-hover:opacity-100">
        <span>View full file</span>
        <UIcon name="i-lucide-external-link" class="w-3.5 h-3.5 shrink-0" />
      </p>
    </div>
    </a>
    <UButton
      v-if="deletable"
      variant="ghost"
      color="red"
      size="xs"
      icon="i-lucide-trash-2"
      class="shrink-0 self-start"
      :loading="deleting"
      :disabled="deleting"
      :aria-label="'Remove ' + displayName(upload)"
      @click.stop.prevent="emit('delete')"
    />
  </div>
</template>
