<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        Consultations
      </h1>
      <p class="text-gray-600 dark:text-gray-300">
        All appointments and consultations.
      </p>
    </div>

    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <div class="mb-4 flex flex-wrap items-center gap-4">
        <USelectMenu
          v-model="selectedStatus"
          :options="statusOptions"
          placeholder="Status"
          class="w-40"
        />
        <USelectMenu
          v-model="selectedType"
          :options="typeOptions"
          placeholder="Type"
          class="w-40"
        />
      </div>

      <UAlert
        v-if="errorMessage"
        icon="i-lucide-alert-triangle"
        color="red"
        variant="soft"
        :title="errorMessage"
        class="mb-4"
      />

      <UTable
        :rows="rows"
        :columns="columns"
        :loading="loading"
      >
        <template #scheduled_at-data="{ row }">
          {{ formatDate(row.scheduled_at) }}
        </template>
        <template #patient-data="{ row }">
          {{ row.patient?.name || '—' }}
        </template>
        <template #doctor-data="{ row }">
          {{ row.doctor?.name || '—' }}
        </template>
        <template #status-data="{ row }">
          <UBadge :color="statusColor(row.status)" variant="soft">
            {{ row.status }}
          </UBadge>
        </template>
        <template #actions-data="{ row }">
          <UButton icon="i-lucide-eye" size="sm" variant="ghost" @click="navigateTo(`/consultations/${row.id}`)" />
        </template>
      </UTable>

      <div class="mt-4 flex justify-center">
        <UPagination v-model="page" :total="total" :page-size="pageSize" />
      </div>
    </UCard>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth-admin'
})

const { get } = useAdminApi()

const selectedStatus = ref(null)
const selectedType = ref(null)
const page = ref(1)
const pageSize = 15
const loading = ref(false)
const rows = ref([])
const total = ref(0)
const errorMessage = ref('')

const statusOptions = [
  { label: 'All', value: null },
  { label: 'Scheduled', value: 'scheduled' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' }
]

const typeOptions = [
  { label: 'All', value: null },
  { label: 'Text', value: 'text' },
  { label: 'Audio', value: 'audio' },
  { label: 'Video', value: 'video' }
]

const columns = [
  { key: 'id', label: 'ID' },
  { key: 'scheduled_at', label: 'Scheduled' },
  { key: 'patient', label: 'Patient' },
  { key: 'doctor', label: 'Doctor' },
  { key: 'consultation_type', label: 'Type' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: 'Actions' }
]

function formatDate (val) {
  if (!val) return '—'
  try {
    return new Date(val).toLocaleString()
  } catch (_) {
    return val
  }
}

function statusColor (s) {
  const map = { scheduled: 'blue', completed: 'green', cancelled: 'red' }
  return map[s] || 'gray'
}

async function fetchList () {
  loading.value = true
  errorMessage.value = ''
  try {
    const params = { page: String(page.value), per_page: String(pageSize) }
    if (selectedStatus.value) params.status = selectedStatus.value
    if (selectedType.value) params.consultation_type = selectedType.value

    const res = await get('admin/consultations', { query: params })
    const data = res?.data ?? []
    const meta = res?.meta ?? {}
    rows.value = Array.isArray(data) ? data : []
    total.value = typeof meta.total === 'number' ? meta.total : rows.value.length
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to load consultations.'
  } finally {
    loading.value = false
  }
}

watch([selectedStatus, selectedType, page], () => {
  fetchList()
})

onMounted(() => {
  fetchList()
})
</script>
