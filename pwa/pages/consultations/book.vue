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
            <UFormGroup label="Doctor" required>
              <USelectMenu
                v-model="state.doctor_id"
                :options="doctorOptions"
                option-attribute="label"
                value-attribute="value"
                searchable
                :loading="loadingDoctors"
                placeholder="Select a doctor"
              />
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

            <UFormGroup label="Reason for Consultation">
              <UTextarea v-model="state.reason" :rows="4" required />
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
  doctor_id: null as number | null,
  scheduled_at: '',
  consultation_type: 'video',
  reason: ''
})

const doctorOptions = ref<DoctorOption[]>([])

const canSubmit = computed(() => {
  if (!state.doctor_id || !state.scheduled_at || !state.reason.trim()) {
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

    if (typeof validationMessage === 'string' && validationMessage.includes('already booked') && state.doctor_id) {
      await fetchSuggestedSlots(state.doctor_id, new Date(state.scheduled_at).toISOString())
    }
  } finally {
    submitting.value = false
  }
}

const fetchSuggestedSlots = async (doctorId: number, from: string) => {
  try {
    const response = await $fetch<{ data?: { available_slots?: string[] } }>(`/doctors/${doctorId}/availability`, {
      baseURL: config.public.apiBase,
      headers: apiHeaders.value,
      query: {
        from,
        limit: 5
      }
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

watch(() => state.doctor_id, () => {
  suggestedSlots.value = []
})

watch(() => state.scheduled_at, () => {
  if (suggestedSlots.value.length > 0) {
    suggestedSlots.value = []
  }
})
</script>

