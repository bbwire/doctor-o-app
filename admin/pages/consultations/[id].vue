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

      <UCard
        v-if="consultation"
        :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }"
      >
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Prescriptions
          </h3>
          <UButton
            v-if="consultation.status === 'scheduled' || consultation.status === 'completed'"
            size="sm"
            icon="i-lucide-plus"
            @click="showIssuePrescription = true"
          >
            Issue prescription
          </UButton>
        </div>

        <div v-if="consultation.prescriptions?.length" class="space-y-3">
          <div
            v-for="p in consultation.prescriptions"
            :key="p.id"
            class="rounded-lg border border-gray-200 dark:border-gray-800 p-4"
          >
            <div class="flex items-center justify-between">
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                Issued {{ formatDate(p.issued_at) }}
              </p>
              <UBadge :color="p.status === 'active' ? 'green' : 'gray'" variant="soft" size="xs">
                {{ p.status }}
              </UBadge>
            </div>
            <ul class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
              <li v-for="(med, i) in (p.medications || [])" :key="i">
                {{ med.name }}
                <span v-if="med.form"> ({{ med.form }})</span>
                <span v-if="med.dosage"> — {{ med.dosage }}</span>
                <span v-if="med.frequency">, {{ med.frequency }}</span>
                <span v-if="med.duration"> ({{ med.duration }})</span>
                <span v-if="med.instructions" class="block text-gray-500 dark:text-gray-500 mt-0.5">{{ med.instructions }}</span>
              </li>
            </ul>
            <p v-if="p.instructions" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
              {{ p.instructions }}
            </p>
          </div>
        </div>
        <p v-else class="text-sm text-gray-500 dark:text-gray-400">
          No prescriptions for this consultation.
        </p>
      </UCard>

      <UModal v-model="showIssuePrescription" :ui="{ width: 'max-w-lg' }">
        <UCard :ui="{ ring: '', divide: 'divide-y divide-gray-200 dark:divide-gray-800' }">
          <template #header>
            <h3 class="text-lg font-semibold">Issue prescription</h3>
          </template>

          <UForm :state="prescriptionForm" @submit="submitPrescription" class="space-y-4">
            <UFormGroup label="Medications" required>
              <div class="space-y-4">
                <div
                  v-for="(med, i) in prescriptionForm.medications"
                  :key="i"
                  class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 space-y-3"
                >
                  <div class="flex gap-2 items-start">
                    <UInput
                      v-model="med.name"
                      placeholder="Drug name"
                      class="flex-1"
                    />
                    <USelectMenu
                      v-model="med.form"
                      :options="formOptions"
                      value-attribute="value"
                      option-attribute="label"
                      placeholder="Form"
                      class="w-32"
                    />
                    <UButton
                      v-if="prescriptionForm.medications.length > 1"
                      color="red"
                      variant="ghost"
                      icon="i-lucide-trash-2"
                      size="xs"
                      @click.prevent="prescriptionForm.medications.splice(i, 1)"
                    />
                  </div>
                  <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <UInput v-model="med.dosage" placeholder="Dosage" />
                    <UInput v-model="med.frequency" placeholder="Frequency" />
                    <UInput v-model="med.duration" placeholder="Duration" />
                  </div>
                  <UInput
                    v-model="med.instructions"
                    placeholder="Instructions (e.g. Take with food)"
                  />
                </div>
                <UButton
                  variant="outline"
                  size="sm"
                  icon="i-lucide-plus"
                  @click.prevent="prescriptionForm.medications.push({ name: '', form: '', dosage: '', frequency: '', duration: '', instructions: '' })"
                >
                  Add medication
                </UButton>
              </div>
            </UFormGroup>

            <UFormGroup label="General instructions (optional)">
              <UTextarea
                v-model="prescriptionForm.instructions"
                placeholder="Additional notes for the patient..."
                :rows="2"
              />
            </UFormGroup>

            <div class="flex justify-end gap-2 pt-2">
              <UButton variant="ghost" @click="showIssuePrescription = false">
                Cancel
              </UButton>
              <UButton
                type="submit"
                :loading="prescriptionLoading"
                :disabled="!prescriptionForm.medications.some(m => m.name?.trim())"
              >
                Issue prescription
              </UButton>
            </div>
          </UForm>
        </UCard>
      </UModal>
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
const toast = useToast()
const { get, post } = useAdminApi()

const id = computed(() => route.params.id)
const consultation = ref(null)
const loading = ref(true)
const prescriptionLoading = ref(false)
const errorMessage = ref('')
const showIssuePrescription = ref(false)

const formOptions = [
  { label: 'Tablet', value: 'Tablet' },
  { label: 'Capsule', value: 'Capsule' },
  { label: 'Suppository', value: 'Suppository' },
  { label: 'Syrup', value: 'Syrup' }
]

const prescriptionForm = reactive({
  medications: [{ name: '', form: '', dosage: '', frequency: '', duration: '', instructions: '' }],
  instructions: ''
})

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

async function submitPrescription () {
  const meds = prescriptionForm.medications
    .filter(m => m.name?.trim())
    .map(m => ({
      name: m.name.trim(),
      form: m.form?.trim() || null,
      dosage: m.dosage?.trim() || null,
      frequency: m.frequency?.trim() || null,
      duration: m.duration?.trim() || null,
      instructions: m.instructions?.trim() || null
    }))

  if (!meds.length) {
    toast.add({ title: 'Add at least one medication', color: 'amber' })
    return
  }

  const c = consultation.value
  if (!c?.consultation_id && !c?.id) {
    toast.add({ title: 'Consultation not loaded', color: 'red' })
    return
  }

  prescriptionLoading.value = true
  try {
    await post('admin/prescriptions', {
      consultation_id: c.id,
      doctor_id: c.doctor_id,
      patient_id: c.patient_id,
      medications: meds,
      instructions: prescriptionForm.instructions?.trim() || null,
      issued_at: new Date().toISOString(),
      status: 'active'
    })
    showIssuePrescription.value = false
    prescriptionForm.medications = [{ name: '', form: '', dosage: '', frequency: '', duration: '', instructions: '' }]
    prescriptionForm.instructions = ''
    await fetchConsultation()
    toast.add({ title: 'Prescription issued', color: 'green' })
  } catch (e) {
    toast.add({
      title: 'Failed to issue prescription',
      description: e?.data?.message || 'Please try again.',
      color: 'red'
    })
  } finally {
    prescriptionLoading.value = false
  }
}

onMounted(() => {
  fetchConsultation()
})
</script>
