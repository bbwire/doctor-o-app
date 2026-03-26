<template>
  <div class="space-y-6">
    <AdminBreadcrumbs :items="[{ label: 'Prescriptions', to: '/prescriptions' }, { label: prescription ? (prescription.prescription_number || `#${prescription.id}`) : 'Prescription' }]" />
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
          <div v-if="prescription.prescription_number" class="sm:col-span-2">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Prescription no.</dt>
            <dd class="mt-1">
              <AdminHumanId variant="lg" :value="prescription.prescription_number" :show-dash="false" />
            </dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Patient</dt>
            <dd class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
              <AdminPatientNumber :patient-number="prescription.patient?.patient_number" />
              <div class="min-w-0">
                <p class="font-semibold text-gray-900 dark:text-white">
                  {{ prescription.patient?.name || '—' }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  {{ prescription.patient?.email || '—' }}
                </p>
              </div>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Doctor</dt>
            <dd class="mt-0.5 text-gray-900 dark:text-white">{{ prescription.doctor?.name || '—' }} ({{ prescription.doctor?.email || '—' }})</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Consultation</dt>
            <dd class="mt-0.5 text-gray-900 dark:text-white">
              <NuxtLink v-if="prescription.consultation" :to="`/consultations/${prescription.consultation.id}`" class="text-primary-600 hover:underline dark:text-primary-400">
                <template v-if="prescription.consultation.consultation_number">{{ prescription.consultation.consultation_number }}</template>
                <template v-else>#{{ prescription.consultation.id }}</template>
              </NuxtLink>
              <span v-else>—</span>
            </dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Medications</dt>
            <dd class="mt-0.5">
              <ul v-if="prescription.medications?.length" class="space-y-2 text-gray-900 dark:text-white">
                <li
                  v-for="(med, i) in prescription.medications"
                  :key="i"
                  class="rounded bg-gray-100 dark:bg-gray-800 p-2 text-sm"
                >
                  <span class="font-medium">{{ med.name }}</span>
                  <span v-if="med.form"> ({{ med.form }})</span>
                  <span v-if="med.dosage"> — {{ med.dosage }}</span>
                  <span v-if="med.frequency">, {{ med.frequency }}</span>
                  <span v-if="med.duration"> ({{ med.duration }})</span>
                  <p v-if="med.instructions" class="mt-1 text-gray-600 dark:text-gray-400">{{ med.instructions }}</p>
                </li>
              </ul>
              <span v-else class="text-gray-900 dark:text-white">—</span>
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
