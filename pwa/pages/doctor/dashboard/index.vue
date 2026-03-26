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
            <div v-else class="mt-1 space-y-2">
              <p class="text-sm text-gray-700 dark:text-gray-300">
                {{ formatDateTime(summary.next_consultation.scheduled_at) }}
              </p>
              <div v-if="summary.next_consultation.patient?.patient_number" class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Patient no.</span>
                <PatientNumberBadge size="lg" :patient-number="summary.next_consultation.patient.patient_number" />
              </div>
              <div v-if="summary.next_consultation.consultation_number" class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Consultation no.</span>
                <HumanIdBadge size="lg" :value="summary.next_consultation.consultation_number" />
              </div>
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
          <template #consultation_number-data="{ row }">
            <HumanIdBadge
              v-if="row.consultation_number"
              :value="row.consultation_number"
            />
            <span v-else class="font-mono text-xs text-gray-400 dark:text-gray-500">—</span>
          </template>
          <template #patient-data="{ row }">
            <div class="flex min-w-0 max-w-[220px] flex-col gap-1">
              <PatientNumberBadge
                v-if="row.patient?.patient_number"
                :patient-number="row.patient.patient_number"
              />
              <span class="truncate font-medium text-gray-900 dark:text-white">
                {{ row.patient?.name || `Patient #${row.patient_id}` }}
              </span>
            </div>
          </template>
        </UTable>

        <div v-if="!consultations.length && !loading" class="mt-2 text-gray-500 dark:text-gray-400">
          No upcoming consultations.
        </div>
      </UCard>

      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
          Waiting room
        </h3>

        <UTable
          :rows="waitingConsultations"
          :columns="waitingColumns"
          :loading="loading"
          class="cursor-default"
        >
          <template #scheduled_at-data="{ row }">
            {{ formatDateTime(row.scheduled_at) }}
          </template>

          <template #consultation_number-data="{ row }">
            <HumanIdBadge
              v-if="row.consultation_number"
              :value="row.consultation_number"
            />
            <span v-else class="font-mono text-xs text-gray-400 dark:text-gray-500">—</span>
          </template>

          <template #patient-data="{ row }">
            <div class="flex min-w-0 max-w-[220px] flex-col gap-1">
              <PatientNumberBadge
                v-if="row.patient?.patient_number"
                :patient-number="row.patient.patient_number"
              />
              <span class="truncate font-medium text-gray-900 dark:text-white">
                {{ row.patient?.name || `Patient #${row.patient_id}` }}
              </span>
            </div>
          </template>

          <template #category-data="{ row }">
            <UBadge color="amber" variant="soft" size="sm">
              {{ row.metadata?.requested_category || '—' }}
            </UBadge>
          </template>

          <template #action-data="{ row }">
            <UButton
              size="sm"
              variant="soft"
              color="green"
              icon="i-lucide-check-circle"
              :loading="waitingActionLoading[row.id]"
              @click.stop="claimWaiting(row)"
            >
              Accept
            </UButton>
          </template>
        </UTable>

        <div v-if="!waitingConsultations.length && !loading" class="mt-2 text-gray-500 dark:text-gray-400">
          No patients waiting in your category.
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
const toast = useToast()

const loading = ref(true)
const errorMessage = ref('')
const consultations = ref<any[]>([])
const waitingConsultations = ref<any[]>([])
const waitingActionLoading = ref<Record<number, boolean>>({})
const summary = reactive({
  today_consultations: 0,
  upcoming_consultations: 0,
  completed_today: 0,
  next_consultation: null as any
})

const columns = [
  { key: 'scheduled_at', label: 'Time' },
  { key: 'consultation_number', label: 'Consultation no.' },
  { key: 'patient', label: 'Patient' },
  { key: 'consultation_type', label: 'Type' },
  { key: 'status', label: 'Status' }
]

const waitingColumns = [
  { key: 'scheduled_at', label: 'Time' },
  { key: 'consultation_number', label: 'Consultation no.' },
  { key: 'patient', label: 'Patient' },
  { key: 'consultation_type', label: 'Type' },
  { key: 'category', label: 'Category' },
  { key: 'action', label: '' },
]

function goToDetail (row: any) {
  if (row?.id) {
    router.push(`/doctor/consultations/${row.id}`)
  }
}

function apiHeaders () {
  const authToken = token.value || tokenCookie.value
  return {
    Authorization: `Bearer ${authToken || ''}`,
    Accept: 'application/json'
  }
}

async function fetchDashboard () {
  loading.value = true
  errorMessage.value = ''

  try {
    const [summaryRes, consultationsRes, waitingRes] = await Promise.all([
      $fetch<{ data: any }>('/doctor/dashboard/summary', {
        baseURL: config.public.apiBase,
        headers: apiHeaders()
      }),
      $fetch<{ data: any[] }>('/doctor/consultations', {
        baseURL: config.public.apiBase,
        query: { status: 'scheduled', from: new Date().toISOString(), per_page: 10 },
        headers: apiHeaders()
      }),
      $fetch<{ data: any[] }>('/doctor/consultations/queue', {
        baseURL: config.public.apiBase,
        query: { per_page: 10 },
        headers: apiHeaders()
      }),
    ])

    const data = summaryRes?.data || {}
    summary.today_consultations = Number(data.today_consultations ?? 0)
    summary.upcoming_consultations = Number(data.upcoming_consultations ?? 0)
    summary.completed_today = Number(data.completed_today ?? 0)
    summary.next_consultation = data.next_consultation ?? null

    consultations.value = consultationsRes?.data ?? []
    waitingConsultations.value = waitingRes?.data ?? []
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'Failed to load dashboard.'
  } finally {
    loading.value = false
  }
}

async function claimWaiting (row: any) {
  if (!row?.id) return

  waitingActionLoading.value[row.id] = true
  try {
    await $fetch(`/doctor/consultations/${row.id}/claim`, {
      method: 'POST',
      baseURL: config.public.apiBase,
      headers: apiHeaders(),
    })

    toast.add({ title: 'Patient accepted', color: 'green' })
    await fetchDashboard()
  } catch (e: any) {
    toast.add({
      title: 'Could not accept patient',
      description: e?.data?.message || 'Please try again.',
      color: 'red',
    })
  } finally {
    waitingActionLoading.value[row.id] = false
  }
}

onMounted(fetchDashboard)
</script>
