<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
      <p class="text-gray-600 dark:text-gray-300">Welcome back, {{ user?.name || 'there' }}</p>
    </div>

    <div
      v-if="displayPatientNumber"
      class="rounded-xl border-2 border-primary-200 bg-primary-50/90 p-4 dark:border-primary-700 dark:bg-primary-950/40"
    >
      <p class="text-xs font-semibold uppercase tracking-wide text-primary-800 dark:text-primary-200">
        Your patient number
      </p>
      <p class="mt-1 text-2xl font-bold font-mono tracking-tight text-primary-900 dark:text-primary-50">
        {{ displayPatientNumber }}
      </p>
      <p class="mt-2 text-sm text-primary-800/80 dark:text-primary-200/90">
        Use this number when speaking with clinic staff or support so they can find your record quickly.
      </p>
    </div>

    <section>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Upcoming Consultations</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.upcoming_consultations }}</p>
            </div>
            <UIcon name="i-lucide-calendar" class="w-8 h-8 text-primary-500" />
          </div>
        </UCard>

        <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Prescriptions</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.prescriptions }}</p>
            </div>
            <UIcon name="i-lucide-file-text" class="w-8 h-8 text-primary-500" />
          </div>
        </UCard>

        <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Completed Consultations</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.completed_consultations }}</p>
            </div>
            <UIcon name="i-lucide-clipboard-list" class="w-8 h-8 text-primary-500" />
          </div>
        </UCard>
      </div>

      <UAlert
        v-if="errorMessage"
        icon="i-lucide-alert-triangle"
        color="red"
        variant="soft"
        :title="errorMessage"
        class="mb-6"
      />

      <UCard
        v-else
        :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }"
        class="mb-6"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Next Appointment</h3>
            <p v-if="loading" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Loading...</p>
            <div v-else-if="summary.next_consultation" class="mt-1">
              <p class="text-sm text-gray-700 dark:text-gray-300">
                {{ formatDateTime(summary.next_consultation.scheduled_at) }}
              </p>
              <p class="text-sm text-gray-600 dark:text-gray-400 capitalize">
                {{ summary.next_consultation.consultation_type }} with
                {{ summary.next_consultation.doctor?.name || 'Unknown Doctor' }}
              </p>
            </div>
            <p v-else class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              No upcoming appointments yet.
            </p>
          </div>

          <div v-if="summary.next_consultation" class="flex gap-2">
            <UButton
              :to="`/consultations/${summary.next_consultation.id}/room`"
              size="sm"
              icon="i-lucide-log-in"
            >
              Join
            </UButton>
            <UButton
              :to="`/consultations/${summary.next_consultation.id}`"
              size="sm"
              variant="outline"
              icon="i-lucide-arrow-right"
            >
              View
            </UButton>
          </div>
        </div>
      </UCard>


      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Quick Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <UButton to="/consultations/book" block size="lg" icon="i-lucide-calendar-plus">
            Book Consultation
          </UButton>
          <UButton to="/consultations" block size="lg" variant="soft" icon="i-lucide-calendar-days">
            My Consultations
          </UButton>
          <UButton to="/prescriptions" block size="lg" variant="outline" icon="i-lucide-file-text">
            View Prescriptions
          </UButton>
        </div>
      </UCard>
    </section>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth'
})

const { user } = useAuth()
const { formatDateTime } = useDateFormat()
const { isApiReachable, hasApiStatusChecked } = useApiHealth()
const config = useRuntimeConfig()
const tokenCookie = useCookie('auth_token')
const toast = useToast()

const loading = ref(true)
const errorMessage = ref('')
const retryWhenOnline = ref(false)
const reconnectRetryInProgress = ref(false)
const summary = reactive({
  patient_number: null,
  upcoming_consultations: 0,
  prescriptions: 0,
  completed_consultations: 0,
  next_consultation: null
})

const displayPatientNumber = computed(() => summary.patient_number || user.value?.patient_number || null)

const fetchDashboardSummary = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await $fetch('/dashboard/summary', {
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${tokenCookie.value || ''}`,
        Accept: 'application/json'
      }
    })

    const data = response?.data || {}
    summary.patient_number = data.patient_number ?? null
    summary.upcoming_consultations = Number(data.upcoming_consultations || 0)
    summary.prescriptions = Number(data.prescriptions || 0)
    summary.completed_consultations = Number(data.completed_consultations || 0)
    summary.next_consultation = data.next_consultation || null
    retryWhenOnline.value = false

    if (reconnectRetryInProgress.value) {
      toast.add({
        title: 'Connection restored',
        description: 'Dashboard data has been refreshed.',
        color: 'green'
      })
    }
  } catch (error) {
    const err = error && typeof error === 'object' ? error : null
    const message = err && 'data' in err && err.data && typeof err.data === 'object' && 'message' in err.data
      ? err.data.message
      : null

    if (hasApiStatusChecked.value && !isApiReachable.value) {
      retryWhenOnline.value = true
      errorMessage.value = 'API is currently unreachable. Dashboard will retry when connection is restored.'
    } else {
      errorMessage.value = typeof message === 'string'
        ? message
        : 'Unable to load dashboard metrics.'
    }
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await fetchDashboardSummary()
})

watch([isApiReachable, hasApiStatusChecked], async ([reachable, checked]) => {
  if (checked && reachable && retryWhenOnline.value && !loading.value) {
    reconnectRetryInProgress.value = true
    await fetchDashboardSummary()
    reconnectRetryInProgress.value = false
  }
})
</script>
