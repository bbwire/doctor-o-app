<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Book Consultation</h1>
      <p class="text-gray-600 dark:text-gray-300">Schedule your next consultation</p>
    </div>

    <section>
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div class="space-y-4">
          <ApiOfflineInlineHint />

          <UAlert
            v-if="errorMessage"
            icon="i-lucide-alert-triangle"
            color="red"
            variant="soft"
            :title="errorMessage"
          />

          <UForm :state="state" class="space-y-4" @submit="onSubmit">
            <UFormGroup label="Category" required>
              <USelectMenu
                v-model="state.category"
                :options="categoryOptions"
                option-attribute="label"
                value-attribute="value"
                placeholder="Select a healthcare category"
                @change="onCategoryChange"
              />
            </UFormGroup>

            <UFormGroup v-if="state.category" label="Doctor (Optional)">
              <USelectMenu
                v-model="state.doctor_id"
                :options="filteredDoctorOptions"
                option-attribute="label"
                value-attribute="value"
                searchable
                :loading="loadingDoctors"
                placeholder="Select a doctor (optional - system will assign available doctor)"
              />
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                If no doctor is selected, the system will assign an available doctor in the selected category for your chosen time slot.
              </p>
            </UFormGroup>

            <UFormGroup label="Preferred Date & Time">
              <UInput v-model="state.scheduled_at" type="datetime-local" :min="minimumDateTime" required />
            </UFormGroup>

            <div v-if="suggestedSlots.length" class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/20">
              <p class="text-xs font-medium text-amber-800 dark:text-amber-300 mb-2">
                Suggested available slots
              </p>
              <div class="flex flex-wrap gap-2">
                <UButton
                  v-for="slot in suggestedSlots"
                  :key="slot"
                  size="xs"
                  color="amber"
                  variant="soft"
                  @click="applySuggestedSlot(slot)"
                >
                  {{ formatSlot(slot) }}
                </UButton>
              </div>
            </div>

            <UFormGroup label="Consultation Type">
              <USelectMenu v-model="state.consultation_type" :options="consultationTypes" />
            </UFormGroup>

            <UFormGroup label="Reason for Consultation" required>
              <div class="space-y-2">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  Describe your symptoms, history and questions. You can format text and attach images.
                </p>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                  <div class="flex items-center gap-1 px-2 py-1.5 border-b border-gray-200 dark:border-gray-800">
                    <button
                      type="button"
                      class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-300"
                      @click.prevent="execCommand('bold')"
                    >
                      <span class="text-xs font-semibold">B</span>
                    </button>
                    <button
                      type="button"
                      class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-300 italic"
                      @click.prevent="execCommand('italic')"
                    >
                      I
                    </button>
                    <button
                      type="button"
                      class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-300"
                      @click.prevent="execCommand('insertUnorderedList')"
                    >
                      <UIcon name="i-lucide-list" class="w-4 h-4" />
                    </button>
                    <button
                      type="button"
                      class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-300"
                      :disabled="uploadingReasonImage"
                      @click.prevent="triggerReasonImage"
                    >
                      <UIcon
                        :name="uploadingReasonImage ? 'i-lucide-loader-2' : 'i-lucide-image-plus'"
                        class="w-4 h-4"
                        :class="{ 'animate-spin': uploadingReasonImage }"
                      />
                    </button>
                    <button
                      type="button"
                      class="ml-auto p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-500 dark:text-gray-400 text-[11px]"
                      @click.prevent="clearReason"
                    >
                      Clear
                    </button>
                  </div>
                  <div
                    ref="reasonEditor"
                    class="min-h-[96px] max-h-60 overflow-y-auto px-3 py-2 text-sm bg-white dark:bg-gray-900 rounded-b-xl focus:outline-none prose prose-sm prose-invert prose-headings:text-gray-100 prose-p:text-gray-100 prose-strong:text-gray-100"
                    contenteditable="true"
                    :placeholder="'Describe your reason for this consultation...'"
                    @input="onReasonInput"
                    @blur="syncReasonFromDom"
                  />
                  <input
                    ref="reasonImageInput"
                    type="file"
                    class="hidden"
                    accept="image/*"
                    @change="onReasonImageSelected"
                  >
                </div>
              </div>
            </UFormGroup>

            <UButton
              type="submit"
              icon="i-lucide-calendar-plus"
              :loading="submitting"
              :disabled="!canSubmit || isApiOffline"
            >
              Book Consultation
            </UButton>
          </UForm>
        </div>
      </UCard>
    </section>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth'
})

const consultationTypes = ['text', 'audio', 'video']
const { isApiReachable, hasApiStatusChecked } = useApiHealth()
const config = useRuntimeConfig()
const toast = useToast()
const tokenCookie = useCookie<string | null>('auth_token')
const router = useRouter()
const loadingDoctors = ref(false)
const submitting = ref(false)
const errorMessage = ref('')
const isApiOffline = computed(() => hasApiStatusChecked.value && !isApiReachable.value)
const retryDoctorsWhenOnline = ref(false)
const reconnectRetryInProgress = ref(false)
const suggestedSlots = ref<string[]>([])

interface DoctorOption {
  label: string
  value: number
}

const state = reactive({
  category: null as string | null,
  doctor_id: null as number | null,
  scheduled_at: '',
  consultation_type: 'video',
  reason: ''
})

const categoryOptions = ref([
  { label: 'General Doctor', value: 'General Doctor' },
  { label: 'Physician', value: 'Physician' },
  { label: 'Surgeon', value: 'Surgeon' },
  { label: 'Paediatrician', value: 'Paediatrician' },
  { label: 'Nurse', value: 'Nurse' },
  { label: 'Pharmacist', value: 'Pharmacist' },
  { label: 'Gynecologist', value: 'Gynecologist' },
  { label: 'Dentist', value: 'Dentist' }
])

const doctorOptions = ref<DoctorOption[]>([])
const filteredDoctorOptions = computed(() => {
  if (!state.category) return []
  return doctorOptions.value.filter(doctor => 
    doctor.label.includes(state.category)
  )
})

const reasonEditor = ref<HTMLElement | null>(null)
const reasonImageInput = ref<HTMLInputElement | null>(null)
const uploadingReasonImage = ref(false)

const canSubmit = computed(() => {
  if (!state.category || !state.scheduled_at || !state.reason.trim()) {
    return false
  }

  return new Date(state.scheduled_at).getTime() > Date.now()
})

const minimumDateTime = computed(() => {
  const now = new Date()
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
  return now.toISOString().slice(0, 16)
})

const apiHeaders = computed(() => ({
  Authorization: `Bearer ${tokenCookie.value || ''}`,
  Accept: 'application/json'
}))

const onCategoryChange = () => {
  // Reset doctor selection when category changes
  state.doctor_id = null
  suggestedSlots.value = []
}

const fetchDoctors = async () => {
  loadingDoctors.value = true
  errorMessage.value = ''

  try {
    const response = await $fetch<{ data: Array<{ id: number; name: string; speciality?: string | null; institution?: string | null }> }>('/doctors', {
      baseURL: config.public.apiBase,
      headers: apiHeaders.value
    })

    doctorOptions.value = (response.data || []).map(doctor => ({
      value: doctor.id,
      label: [doctor.name, doctor.speciality, doctor.institution].filter(Boolean).join(' - ')
    }))
    retryDoctorsWhenOnline.value = false

    if (reconnectRetryInProgress.value) {
      toast.add({
        title: 'Connection restored',
        description: 'Doctors list has been refreshed.',
        color: 'green'
      })
    }
  } catch (error) {
    const err = error && typeof error === 'object' ? error : null

    if (hasApiStatusChecked.value && !isApiReachable.value) {
      retryDoctorsWhenOnline.value = true
      errorMessage.value = 'API is currently unreachable. Doctors list will retry when connection is restored.'
      return
    }

    const message = err && 'data' in err && err.data && typeof err.data === 'object' && 'message' in err.data
      ? err.data.message
      : null
    errorMessage.value = typeof message === 'string' ? message : 'Unable to load doctors.'
  } finally {
    loadingDoctors.value = false
  }
}

const execCommand = (command: string) => {
  if (typeof document === 'undefined') return
  reasonEditor.value?.focus()
  try {
    document.execCommand(command, false)
  } catch {
    // ignore
  }
}

const onReasonInput = () => {
  if (!reasonEditor.value) return
  state.reason = reasonEditor.value.innerHTML
}

const syncReasonFromDom = () => {
  if (!reasonEditor.value) return
  state.reason = reasonEditor.value.innerHTML
}

const clearReason = () => {
  state.reason = ''
  if (reasonEditor.value) {
    reasonEditor.value.innerHTML = ''
  }
}

const triggerReasonImage = () => {
  if (uploadingReasonImage.value) return
  reasonImageInput.value?.click()
}

const onReasonImageSelected = async (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  input.value = ''

  const maxBytes = 5 * 1024 * 1024
  if (file.size > maxBytes) {
    toast.add({
      title: 'Image too large',
      description: 'Images must be 5MB or less.',
      color: 'red'
    })
    return
  }

  if (isApiOffline.value) {
    toast.add({
      title: 'API unavailable',
      description: 'Please retry when API connection is restored.',
      color: 'amber'
    })
    return
  }

  uploadingReasonImage.value = true
  try {
    const formData = new FormData()
    formData.append('image', file)

    const res = await $fetch<{ url: string }>('/consultations/reason-images', {
      method: 'POST',
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${tokenCookie.value || ''}`
      },
      body: formData
    })

    const url = res.url
    if (typeof document !== 'undefined' && url) {
      reasonEditor.value?.focus()
      document.execCommand('insertImage', false, url)
      syncReasonFromDom()
    }
  } catch (error) {
    const err = error as { data?: { message?: string } }
    toast.add({
      title: 'Failed to upload image',
      description: err?.data?.message || 'Please try again.',
      color: 'red'
    })
  } finally {
    uploadingReasonImage.value = false
  }
}

const onSubmit = async () => {
  if (!canSubmit.value) return
  if (isApiOffline.value) {
    errorMessage.value = 'API is currently unreachable. Please retry when connection is restored.'
    return
  }

  submitting.value = true
  errorMessage.value = ''
  suggestedSlots.value = []

  try {
    await $fetch('/consultations/book', {
      method: 'POST',
      baseURL: config.public.apiBase,
      headers: apiHeaders.value,
      body: {
        category: state.category,
        doctor_id: state.doctor_id,
        scheduled_at: new Date(state.scheduled_at).toISOString(),
        consultation_type: state.consultation_type,
        reason: state.reason
      }
    })

    toast.add({
      title: 'Consultation booked',
      description: 'Your consultation request has been submitted.',
      color: 'green'
    })

    await router.push('/consultations')
  } catch (error) {
    const err = error && typeof error === 'object' ? error : null
    const validationMessage = err && 'data' in err && err.data && typeof err.data === 'object' && 'errors' in err.data
      ? err.data.errors?.scheduled_at?.[0]
      : null
    const message = err && 'data' in err && err.data && typeof err.data === 'object' && 'message' in err.data
      ? err.data.message
      : null

    errorMessage.value = validationMessage || (typeof message === 'string' ? message : 'Failed to book consultation.')

    if (typeof validationMessage === 'string' && validationMessage.includes('already booked') && (state.doctor_id || state.category)) {
      await fetchSuggestedSlots(state.doctor_id || state.category!, new Date(state.scheduled_at).toISOString())
    }
  } finally {
    submitting.value = false
  }
}

const fetchSuggestedSlots = async (doctorIdOrCategory: number | string, from: string) => {
  try {
    let url: string
    if (typeof doctorIdOrCategory === 'string') {
      // For category-based availability, we'll need a new endpoint
      // For now, try to get availability for any doctor in this category
      url = `/doctors/availability?category=${encodeURIComponent(doctorIdOrCategory)}&from=${encodeURIComponent(from)}&limit=5`
    } else {
      // Existing doctor-specific endpoint
      url = `/doctors/${doctorIdOrCategory}/availability?from=${encodeURIComponent(from)}&limit=5`
    }
    
    const response = await $fetch<{ data?: { available_slots?: string[] } }>(url, {
      baseURL: config.public.apiBase,
      headers: apiHeaders.value
    })

    suggestedSlots.value = response?.data?.available_slots || []
  } catch {
    suggestedSlots.value = []
  }
}

const applySuggestedSlot = (slot: string) => {
  state.scheduled_at = toLocalDateTimeInput(slot)
}

const formatSlot = (slot: string) => new Date(slot).toLocaleString()

const toLocalDateTimeInput = (value: string) => {
  const date = new Date(value)
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset())
  return date.toISOString().slice(0, 16)
}

onMounted(async () => {
  await fetchDoctors()
})

watch([isApiReachable, hasApiStatusChecked], async ([reachable, checked]) => {
  if (checked && reachable && retryDoctorsWhenOnline.value && !loadingDoctors.value) {
    reconnectRetryInProgress.value = true
    await fetchDoctors()
    reconnectRetryInProgress.value = false
  }
})

watch(() => state.category, () => {
  suggestedSlots.value = []
})

watch(() => state.doctor_id, () => {
  suggestedSlots.value = []
})

watch(() => state.scheduled_at, () => {
  if (suggestedSlots.value.length > 0) {
    suggestedSlots.value = []
  }
})
</script>

