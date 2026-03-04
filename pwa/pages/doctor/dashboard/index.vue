<template>
  <div class="space-y-6">
    <header class="space-y-1">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        Doctor dashboard
      </h1>
      <p class="text-gray-600 dark:text-gray-300">
        Welcome back, {{ user?.name || 'Doctor' }}
      </p>
    </header>

    <UAlert
      v-if="errorMessage"
      color="red"
      icon="i-lucide-alert-triangle"
      variant="soft"
      :title="errorMessage"
      class="mb-4"
    />

    <section class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Today</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.today_consultations }}</p>
            </div>
            <UIcon name="i-lucide-calendar-clock" class="w-8 h-8 text-primary-500" />
          </div>
        </UCard>

        <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Upcoming</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.upcoming_consultations }}</p>
            </div>
            <UIcon name="i-lucide-calendar-days" class="w-8 h-8 text-primary-500" />
          </div>
        </UCard>

        <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Completed today</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.completed_today }}</p>
            </div>
            <UIcon name="i-lucide-clipboard-check" class="w-8 h-8 text-primary-500" />
          </div>
        </UCard>
      </div>

      <UCard
        v-if="summary.next_consultation"
        :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Next consultation
            </h3>
            <p v-if="loading" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              Loading...
            </p>
            <div v-else class="mt-1">
              <p class="text-sm text-gray-700 dark:text-gray-300">
                {{ formatDateTime(summary.next_consultation.scheduled_at) }}
              </p>
              <p class="text-sm text-gray-600 dark:text-gray-400 capitalize">
                {{ summary.next_consultation.consultation_type }} with
                {{ summary.next_consultation.patient?.name || 'Unknown Patient' }}
              </p>
            </div>
          </div>
          <UButton
            :to="`/doctor/consultations/${summary.next_consultation.id}`"
            size="sm"
            variant="outline"
            icon="i-lucide-arrow-right"
          >
            View
          </UButton>
        </div>
      </UCard>

      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
          Today’s and upcoming consultations
        </h3>
        <UTable
          :rows="consultations"
          :columns="columns"
          :loading="loading"
          class="cursor-pointer"
          @select="goToDetail"
        >
          <template #scheduled_at-data="{ row }">
          {{ formatDateTime(row.scheduled_at) }}
          </template>
          <template #patient-data="{ row }">
            {{ row.patient?.name || `Patient #${row.patient_id}` }}
          </template>
        </UTable>

        <div v-if="!consultations.length && !loading" class="mt-2 text-gray-500 dark:text-gray-400">
          No upcoming consultations.
        </div>
      </UCard>

      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
          Quick actions
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <UButton to="/doctor/consultations" block size="lg" icon="i-lucide-calendar-days">
            My consultations
          </UButton>
          <UButton to="/doctor/prescriptions" block size="lg" variant="soft" icon="i-lucide-file-text">
            My prescriptions
          </UButton>
        </div>
      </UCard>
    </section>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'doctor'
})

const config = useRuntimeConfig()
const { formatDateTime } = useDateFormat()
const { user, token } = useAuth()
const router = useRouter()
const tokenCookie = useCookie('auth_token')

const loading = ref(true)
const errorMessage = ref('')
const consultations = ref<any[]>([])
const summary = reactive({
  today_consultations: 0,
  upcoming_consultations: 0,
  completed_today: 0,
  next_consultation: null as any
})

const columns = [
  { key: 'scheduled_at', label: 'Time' },
  { key: 'patient', label: 'Patient' },
  { key: 'consultation_type', label: 'Type' },
  { key: 'status', label: 'Status' }
]

function goToDetail (row: any) {
  if (row?.id) {
    router.push(`/doctor/consultations/${row.id}`)
  }
}

async function fetchDashboard () {
  loading.value = true
  errorMessage.value = ''

  try {
    const authToken = token.value || tokenCookie.value
    const headers = {
      Authorization: `Bearer ${authToken || ''}`,
      Accept: 'application/json'
    }

    const [summaryRes, consultationsRes] = await Promise.all([
      $fetch<{ data: any }>('/doctor/dashboard/summary', {
        baseURL: config.public.apiBase,
        headers
      }),
      $fetch<{ data: any[] }>('/doctor/consultations', {
        baseURL: config.public.apiBase,
        query: { status: 'scheduled', per_page: 10 },
        headers
      })
    ])

    const data = summaryRes?.data || {}
    summary.today_consultations = Number(data.today_consultations ?? 0)
    summary.upcoming_consultations = Number(data.upcoming_consultations ?? 0)
    summary.completed_today = Number(data.completed_today ?? 0)
    summary.next_consultation = data.next_consultation ?? null

    consultations.value = consultationsRes?.data ?? []
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'Failed to load dashboard.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchDashboard)
</script>
