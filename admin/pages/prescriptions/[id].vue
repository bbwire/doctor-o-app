<template>
  <div class="space-y-6">
    <AdminBreadcrumbs :items="[{ label: 'Prescriptions', to: '/prescriptions' }, { label: prescription ? `#${prescription.id}` : 'Prescription' }]" />
    <div class="flex items-center gap-3">
      <UButton to="/prescriptions" variant="ghost" icon="i-lucide-arrow-left" size="sm">
        Back
      </UButton>
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Prescription details
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
          View prescription
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

    <div v-if="loading && !prescription" class="py-10 text-center text-gray-500 dark:text-gray-400">
      Loading...
    </div>

    <template v-else-if="prescription">
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div class="flex items-start justify-between gap-4">
          <div>
            <UBadge :color="statusColor(prescription.status)" variant="soft" class="mb-2">
              {{ prescription.status }}
            </UBadge>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(prescription.issued_at) }}</p>
          </div>
        </div>
        <dl class="mt-6 grid gap-3 sm:grid-cols-2">
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Patient</dt>
            <dd class="mt-0.5 text-gray-900 dark:text-white">{{ prescription.patient?.name || '—' }} ({{ prescription.patient?.email || '—' }})</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Doctor</dt>
            <dd class="mt-0.5 text-gray-900 dark:text-white">{{ prescription.doctor?.name || '—' }} ({{ prescription.doctor?.email || '—' }})</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Consultation</dt>
            <dd class="mt-0.5 text-gray-900 dark:text-white">
              <NuxtLink v-if="prescription.consultation" :to="`/consultations/${prescription.consultation.id}`" class="text-primary-600 hover:underline dark:text-primary-400">
                #{{ prescription.consultation.id }}
              </NuxtLink>
              <span v-else>—</span>
            </dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Medications</dt>
            <dd class="mt-0.5 text-gray-900 dark:text-white">
              <pre v-if="prescription.medications && (Array.isArray(prescription.medications) ? prescription.medications.length : Object.keys(prescription.medications).length)" class="whitespace-pre-wrap rounded bg-gray-100 p-2 text-sm dark:bg-gray-800">{{ JSON.stringify(prescription.medications, null, 2) }}</pre>
              <span v-else>—</span>
            </dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Instructions</dt>
            <dd class="mt-0.5 text-gray-900 dark:text-white">{{ prescription.instructions || '—' }}</dd>
          </div>
        </dl>
      </UCard>
    </template>

    <div v-else-if="!loading" class="rounded-lg border border-gray-200 bg-white p-8 text-center dark:border-gray-800 dark:bg-gray-900">
      <p class="text-gray-500 dark:text-gray-400">Prescription not found.</p>
      <UButton to="/prescriptions" variant="ghost" class="mt-3">Back to prescriptions</UButton>
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
const prescription = ref(null)
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
  const map = { active: 'blue', completed: 'green', cancelled: 'red' }
  return map[s] || 'gray'
}

async function fetchPrescription () {
  loading.value = true
  errorMessage.value = ''
  try {
    const data = await get(`admin/prescriptions/${id.value}`)
    prescription.value = data?.data ?? data
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to load prescription.'
    prescription.value = null
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchPrescription()
})
</script>
