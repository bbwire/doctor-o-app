<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Consultation Details</h1>
        <p class="text-gray-600 dark:text-gray-300">View consultation information and progress timeline</p>
      </div>

      <UButton to="/consultations" variant="ghost" icon="i-lucide-arrow-left">
        Back to consultations
      </UButton>
    </div>

    <UAlert
      v-if="errorMessage"
      icon="i-lucide-alert-triangle"
      color="red"
      variant="soft"
      :title="errorMessage"
    />

    <div v-else-if="loading" class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
      Loading consultation details...
    </div>

    <div v-else-if="consultation" class="space-y-5">
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(consultation.scheduled_at) }}</p>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
              Dr. {{ consultation.doctor?.name || 'Unknown Doctor' }}
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 capitalize">
              {{ consultation.consultation_type }} consultation
            </p>
          </div>

          <UBadge :color="statusColor(consultation.status)" variant="soft">
            {{ consultation.status }}
          </UBadge>
        </div>

        <div v-if="consultation.status === 'scheduled'" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-800">
          <p class="text-sm font-medium text-gray-900 dark:text-white mb-3">Manage Appointment</p>
          <div class="grid gap-3 md:grid-cols-[1fr_auto_auto] md:items-end">
            <UFormGroup label="New Date & Time">
              <UInput v-model="rescheduleAt" type="datetime-local" :min="minimumRescheduleDateTime" />
            </UFormGroup>

            <UButton
              color="red"
              variant="soft"
              icon="i-lucide-x-circle"
              :loading="actionLoading"
              :disabled="actionLoading || isApiOffline"
              @click="cancelConsultation"
            >
              Cancel
            </UButton>

            <UButton
              icon="i-lucide-calendar-sync"
              :loading="actionLoading"
              :disabled="actionLoading || isApiOffline || !rescheduleAt"
              @click="rescheduleConsultation"
            >
              Reschedule
            </UButton>
          </div>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2">
          <div class="rounded-lg bg-gray-50 dark:bg-gray-800/60 p-3">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Reason</p>
            <p class="text-sm text-gray-800 dark:text-gray-200 mt-1">
              {{ consultation.reason || 'No reason provided' }}
            </p>
          </div>
          <div class="rounded-lg bg-gray-50 dark:bg-gray-800/60 p-3">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Notes</p>
            <p class="text-sm text-gray-800 dark:text-gray-200 mt-1">
              {{ consultation.notes || 'No notes yet' }}
            </p>
          </div>
        </div>
      </UCard>

      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Status Timeline</h3>

        <div class="mt-4 space-y-3">
          <div
            v-for="step in timelineSteps"
            :key="step.key"
            class="flex items-start gap-3 rounded-lg border p-3"
            :class="step.reached ? 'border-primary-200 bg-primary-50/60 dark:border-primary-700 dark:bg-primary-900/10' : 'border-gray-200 dark:border-gray-800'"
          >
            <div
              class="mt-0.5 h-6 w-6 rounded-full flex items-center justify-center text-xs font-semibold"
              :class="step.reached ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
            >
              {{ step.index }}
            </div>

            <div class="flex-1">
              <p class="font-medium text-gray-900 dark:text-white">{{ step.title }}</p>
              <p class="text-sm text-gray-600 dark:text-gray-300">{{ step.description }}</p>
            </div>

            <UIcon
              v-if="step.reached"
              name="i-lucide-check"
              class="h-5 w-5 text-primary-600 dark:text-primary-400"
            />
          </div>
        </div>
      </UCard>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth'
})

interface ConsultationItem {
  id: number
  scheduled_at: string
  consultation_type: 'text' | 'audio' | 'video'
  status: 'scheduled' | 'completed' | 'cancelled'
  reason?: string
  notes?: string
  doctor?: {
    name?: string
  }
}

type TimelineStep = {
  key: string
  index: number
  title: string
  description: string
  reached: boolean
}

const route = useRoute()
const config = useRuntimeConfig()
const tokenCookie = useCookie<string | null>('auth_token')
const { isApiReachable, hasApiStatusChecked } = useApiHealth()
const toast = useToast()
const isApiOffline = computed(() => hasApiStatusChecked.value && !isApiReachable.value)

const loading = ref(true)
const actionLoading = ref(false)
const errorMessage = ref('')
const retryWhenOnline = ref(false)
const reconnectRetryInProgress = ref(false)
const consultation = ref<ConsultationItem | null>(null)
const rescheduleAt = ref('')

const consultationId = computed(() => route.params.id)
const apiHeaders = computed(() => ({
  Authorization: `Bearer ${tokenCookie.value || ''}`,
  Accept: 'application/json'
}))
const minimumRescheduleDateTime = computed(() => {
  const now = new Date()
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
  return now.toISOString().slice(0, 16)
})

const timelineSteps = computed<TimelineStep[]>(() => {
  const status = consultation.value?.status
  const scheduledAt = consultation.value?.scheduled_at
    ? formatDate(consultation.value.scheduled_at)
    : 'Scheduled time not available'

  return [
    {
      key: 'booked',
      index: 1,
      title: 'Booked',
      description: 'Your consultation request was created successfully.',
      reached: Boolean(status)
    },
    {
      key: 'scheduled',
      index: 2,
      title: 'Scheduled',
      description: `Appointment time: ${scheduledAt}.`,
      reached: status === 'scheduled' || status === 'completed'
    },
    {
      key: 'completed',
      index: 3,
      title: status === 'cancelled' ? 'Cancelled' : 'Completed',
      description: status === 'cancelled'
        ? 'This consultation was cancelled.'
        : 'Consultation completed and ready for follow-up.',
      reached: status === 'completed' || status === 'cancelled'
    }
  ]
})

const fetchConsultation = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await $fetch<{ data: ConsultationItem }>(`/consultations/${consultationId.value}`, {
      baseURL: config.public.apiBase,
      headers: apiHeaders.value
    })

    consultation.value = response.data
    rescheduleAt.value = toLocalDateTimeInput(response.data.scheduled_at)
    retryWhenOnline.value = false

    if (reconnectRetryInProgress.value) {
      toast.add({
        title: 'Connection restored',
        description: 'Consultation details have been refreshed.',
        color: 'green'
      })
    }
  } catch (error) {
    const err = error && typeof error === 'object' ? error : null

    if (hasApiStatusChecked.value && !isApiReachable.value) {
      retryWhenOnline.value = true
      errorMessage.value = 'API is currently unreachable. Details will retry when connection is restored.'
      return
    }

    if (err && 'status' in err && err.status === 404) {
      errorMessage.value = 'Consultation not found.'
      return
    }

    if (err && 'data' in err && typeof err.data === 'string') {
      errorMessage.value = 'Unexpected non-JSON response from API. Please sign in again and retry.'
      return
    }

    const message = err && 'data' in err && err.data && typeof err.data === 'object' && 'message' in err.data
      ? err.data.message
      : null
    errorMessage.value = typeof message === 'string' ? message : 'Unable to load consultation details.'
  } finally {
    loading.value = false
  }
}

const cancelConsultation = async () => {
  if (!consultation.value) {
    return
  }

  if (isApiOffline.value) {
    errorMessage.value = 'API is currently unreachable. Please retry when connection is restored.'
    return
  }

  actionLoading.value = true
  errorMessage.value = ''

  try {
    const response = await $fetch<{ data: ConsultationItem }>(`/consultations/${consultation.value.id}/cancel`, {
      method: 'PATCH',
      baseURL: config.public.apiBase,
      headers: apiHeaders.value
    })

    consultation.value = response.data
    rescheduleAt.value = toLocalDateTimeInput(response.data.scheduled_at)

    toast.add({
      title: 'Consultation cancelled',
      description: 'Your appointment has been cancelled successfully.',
      color: 'green'
    })
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, 'Unable to cancel consultation.')
  } finally {
    actionLoading.value = false
  }
}

const rescheduleConsultation = async () => {
  if (!consultation.value || !rescheduleAt.value) {
    return
  }

  if (isApiOffline.value) {
    errorMessage.value = 'API is currently unreachable. Please retry when connection is restored.'
    return
  }

  actionLoading.value = true
  errorMessage.value = ''

  try {
    const response = await $fetch<{ data: ConsultationItem }>(`/consultations/${consultation.value.id}/reschedule`, {
      method: 'PATCH',
      baseURL: config.public.apiBase,
      headers: apiHeaders.value,
      body: {
        scheduled_at: new Date(rescheduleAt.value).toISOString()
      }
    })

    consultation.value = response.data
    rescheduleAt.value = toLocalDateTimeInput(response.data.scheduled_at)

    toast.add({
      title: 'Consultation rescheduled',
      description: 'Your appointment date and time has been updated.',
      color: 'green'
    })
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, 'Unable to reschedule consultation.')
  } finally {
    actionLoading.value = false
  }
}

const extractErrorMessage = (error: unknown, fallback: string): string => {
  const err = error && typeof error === 'object' ? error : null
  const validationErrors = err && 'data' in err && err.data && typeof err.data === 'object' && 'errors' in err.data
    ? err.data.errors
    : null

  if (validationErrors && typeof validationErrors === 'object') {
    const firstKey = Object.keys(validationErrors)[0]
    const firstErrors = firstKey ? validationErrors[firstKey as keyof typeof validationErrors] : null
    if (Array.isArray(firstErrors) && typeof firstErrors[0] === 'string') {
      return firstErrors[0]
    }
  }

  const message = err && 'data' in err && err.data && typeof err.data === 'object' && 'message' in err.data
    ? err.data.message
    : null

  return typeof message === 'string' ? message : fallback
}

const formatDate = (value: string) => new Date(value).toLocaleString()
const toLocalDateTimeInput = (value: string) => {
  const date = new Date(value)
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset())
  return date.toISOString().slice(0, 16)
}

const statusColor = (status: ConsultationItem['status']) => {
  if (status === 'scheduled') return 'blue'
  if (status === 'completed') return 'green'
  return 'gray'
}

watch(consultationId, async () => {
  await fetchConsultation()
})

onMounted(async () => {
  await fetchConsultation()
})

watch([isApiReachable, hasApiStatusChecked], async ([reachable, checked]) => {
  if (checked && reachable && retryWhenOnline.value && !loading.value) {
    reconnectRetryInProgress.value = true
    await fetchConsultation()
    reconnectRetryInProgress.value = false
  }
})
</script>

