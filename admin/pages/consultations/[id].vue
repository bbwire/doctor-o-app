<template>
  <div class="space-y-6">
    <AdminBreadcrumbs :items="[{ label: 'Consultations', to: '/consultations' }, { label: consultation ? `#${consultation.id}` : 'Consultation' }]" />
    <div class="flex items-center gap-3">
      <UButton to="/consultations" variant="ghost" icon="i-lucide-arrow-left" size="sm">
        Back
      </UButton>
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Consultation details
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
          View consultation
        </p>
      </div>
    </div>

    <UAlert
      v-if="errorMessage"
      icon="i-lucide-alert-triangle"
      color="red"
      variant="soft"
      :title="errorMessage"
    />

    <div v-if="loading && !consultation" class="py-10 text-center text-gray-500 dark:text-gray-400">
      Loading...
    </div>

    <template v-else-if="consultation">
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div class="flex items-start justify-between gap-4">
          <div>
            <UBadge :color="statusColor(consultation.status)" variant="soft" class="mb-2">
              {{ consultation.status }}
            </UBadge>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(consultation.scheduled_at) }}</p>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
              {{ consultation.consultation_type }} consultation
            </h2>
          </div>
        </div>
        <dl class="mt-6 grid gap-3 sm:grid-cols-2">
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Patient</dt>
            <dd class="mt-0.5 text-gray-900 dark:text-white">{{ consultation.patient?.name || '—' }} ({{ consultation.patient?.email || '—' }})</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Doctor</dt>
            <dd class="mt-0.5 text-gray-900 dark:text-white">{{ consultation.doctor?.name || '—' }} ({{ consultation.doctor?.email || '—' }})</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Reason</dt>
            <dd class="mt-0.5 text-gray-900 dark:text-white">{{ consultation.reason || '—' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Notes</dt>
            <dd class="mt-0.5 text-gray-900 dark:text-white">{{ consultation.notes || '—' }}</dd>
          </div>
        </dl>
      </UCard>
    </template>

    <div v-else-if="!loading" class="rounded-lg border border-gray-200 bg-white p-8 text-center dark:border-gray-800 dark:bg-gray-900">
      <p class="text-gray-500 dark:text-gray-400">Consultation not found.</p>
      <UButton to="/consultations" variant="ghost" class="mt-3">Back to consultations</UButton>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth-admin'
})

const route = useRoute()
const { get } = useAdminApi()

const id = computed(() => route.params.id)
const consultation = ref(null)
const loading = ref(true)
const errorMessage = ref('')

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

async function fetchConsultation () {
  loading.value = true
  errorMessage.value = ''
  try {
    const data = await get(`admin/consultations/${id.value}`)
    consultation.value = data?.data ?? data
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to load consultation.'
    consultation.value = null
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchConsultation()
})
</script>
