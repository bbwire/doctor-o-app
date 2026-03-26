<template>
  <div class="space-y-6">
    <header class="space-y-1">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        My consultations
      </h1>
      <p class="text-gray-600 dark:text-gray-300">
        All consultations where you are the assigned doctor.
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

    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <div class="mb-4 flex items-center justify-between gap-4 flex-wrap">
        <USelect
          v-model="status"
          :options="statusOptions"
          placeholder="Filter by status"
          class="w-48"
        />

        <UButton
          to="/doctor/consultations/queue"
          variant="soft"
          icon="i-lucide-users"
          class="shrink-0"
        >
          Waiting room
        </UButton>
      </div>

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
        <template #patient_number-data="{ row }">
          <PatientNumberBadge
            v-if="row.patient?.patient_number"
            :patient-number="row.patient.patient_number"
          />
          <span v-else class="font-mono text-sm font-semibold text-gray-400 dark:text-gray-500">—</span>
        </template>
        <template #patient-data="{ row }">
          <span class="font-medium text-gray-900 dark:text-white">
            {{ row.patient?.name || `Patient #${row.patient_id}` }}
          </span>
        </template>

        <template #conversation-data="{ row }">
          <UButton
            size="sm"
            variant="soft"
            icon="i-lucide-message-square"
            @click.stop="goToConversation(row)"
          >
            Conversation
          </UButton>
        </template>
      </UTable>

      <div class="mt-4 flex justify-center">
        <UPagination
          v-model="page"
          :total="total"
          :page-size="perPage"
        />
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'doctor'
})

const config = useRuntimeConfig()
const { token } = useAuth()
const router = useRouter()
const { formatDateTime } = useDateFormat()

const status = ref<string>('')
const statusOptions = [
  { label: 'All statuses', value: '' },
  { label: 'Scheduled', value: 'scheduled' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' }
]

const page = ref(1)
const perPage = 15
const total = ref(0)
const loading = ref(false)
const errorMessage = ref('')
const consultations = ref<any[]>([])

const columns = [
  { key: 'scheduled_at', label: 'Time' },
  { key: 'consultation_number', label: 'Consultation no.' },
  { key: 'patient_number', label: 'Patient no.' },
  { key: 'patient', label: 'Patient' },
  { key: 'conversation', label: 'Conversation' },
  { key: 'consultation_type', label: 'Type' },
  { key: 'status', label: 'Status' }
]

watch([page, status], () => {
  fetchConsultations()
}, { immediate: true })

async function fetchConsultations () {
  loading.value = true
  errorMessage.value = ''
  try {
    const query: Record<string, string> = {
      page: String(page.value),
      per_page: String(perPage)
    }
    if (status.value) {
      query.status = status.value
    }

    const res = await $fetch<{ data: any[]; meta?: { total?: number } }>(
      '/doctor/consultations',
      {
        baseURL: config.public.apiBase,
        query,
        headers: {
          Authorization: `Bearer ${token.value || ''}`,
          Accept: 'application/json'
        }
      }
    )

    consultations.value = (res?.data ?? []).slice().sort((a, b) => {
      return new Date(b.scheduled_at).getTime() - new Date(a.scheduled_at).getTime()
    })
    total.value = res?.meta?.total ?? consultations.value.length
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'Failed to load consultations.'
  } finally {
    loading.value = false
  }
}

function goToDetail (row: any) {
  if (row?.id) {
    router.push(`/doctor/consultations/${row.id}`)
  }
}

function goToConversation (row: any) {
  if (row?.id) {
    router.push({
      path: `/doctor/consultations/${row.id}`,
      query: { focus: 'conversation' },
    })
  }
}
</script>

