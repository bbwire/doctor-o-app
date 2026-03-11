<template>
  <div class="space-y-6">
    <UButton
      variant="ghost"
      icon="i-lucide-arrow-left"
      size="sm"
      @click="router.push('/doctor/consultations')"
    >
      Back
    </UButton>

    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
      Consultation #{{ consultation?.id }}
    </h1>

    <UAlert
      v-if="errorMessage"
      color="red"
      icon="i-lucide-alert-triangle"
      variant="soft"
      :title="errorMessage"
      class="mb-4"
    />

    <div v-else-if="loading" class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
      <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin mx-auto mb-2 text-primary-500" />
      <p>Loading consultation details...</p>
    </div>

    <UCard
      v-else-if="consultation"
      :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }"
    >
      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-4">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 flex-1 min-w-0">
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Patient</dt>
            <dd class="text-sm text-gray-900 dark:text-gray-100">
              {{ consultation.patient?.name || `Patient #${consultation.patient_id}` }}
            </dd>
          </div>
          <div v-if="consultation.patient?.chronic_conditions?.length" class="sm:col-span-2">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Patient chronic conditions</dt>
            <dd class="mt-0.5 flex flex-wrap gap-1">
              <UBadge v-for="c in (consultation.patient.chronic_conditions || [])" :key="c" size="xs" color="neutral" variant="soft">{{ c }}</UBadge>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Scheduled at</dt>
            <dd class="text-sm text-gray-900 dark:text-gray-100">
              {{ formatDate(consultation.scheduled_at) }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</dt>
            <dd class="text-sm text-gray-900 dark:text-gray-100">
              {{ consultation.consultation_type }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
            <dd class="text-sm text-gray-900 dark:text-gray-100">
              <UBadge :color="statusColor(consultation.status)" variant="soft" size="sm">
                {{ consultation.status }}
              </UBadge>
            </dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Reason</dt>
            <dd
              class="text-sm text-gray-900 dark:text-gray-100 prose prose-sm prose-slate dark:prose-invert max-w-none"
              v-html="consultation.reason || '<p>–</p>'"
            />
          </div>
          <div class="sm:col-span-2">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Notes</dt>
            <dd class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-line">
              {{ consultation.notes || '–' }}
            </dd>
          </div>
        </dl>

        <div v-if="consultation.status === 'scheduled'" class="flex flex-col gap-2 shrink-0 w-full sm:w-auto">
          <UButton
            :to="`/doctor/consultations/${consultation.id}/room`"
            :icon="startConsultationIcon"
            size="sm"
            class="w-full sm:w-auto justify-center"
          >
            Start {{ consultation.consultation_type }} consultation
          </UButton>
          <UButton
            color="green"
            variant="soft"
            size="sm"
            icon="i-lucide-check-circle"
            :loading="actionLoading"
            class="w-full sm:w-auto justify-center"
            @click="markCompleted"
          >
            Mark completed
          </UButton>
          <UButton
            color="red"
            variant="soft"
            size="sm"
            icon="i-lucide-x-circle"
            :loading="actionLoading"
            class="w-full sm:w-auto justify-center"
            @click="markCancelled"
          >
            Cancel
          </UButton>
        </div>
      </div>

      <div v-if="consultation.status === 'scheduled'" class="pt-4 border-t border-gray-200 dark:border-gray-800">
        <UFormGroup label="Update notes">
          <UTextarea
            v-model="notesDraft"
            placeholder="Add or update consultation notes..."
            :rows="3"
          />
        </UFormGroup>
        <UButton
          class="mt-2"
          size="sm"
          :loading="actionLoading"
          :disabled="notesDraft === (consultation.notes || '')"
          @click="updateNotes"
        >
          Save notes
        </UButton>
      </div>
    </UCard>

    <!-- Clinical notes -->
    <UCard
      v-if="consultation && (consultation.status === 'scheduled' || consultation.status === 'completed')"
      :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }"
    >
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Clinical notes
        </h3>
        <UButton
          size="sm"
          icon="i-lucide-clipboard-list"
          @click="showClinicalNotesModal = true"
        >
          {{ hasClinicalNotes ? 'Edit clinical notes' : 'Add clinical notes' }}
        </UButton>
      </div>
      <div v-if="hasClinicalNotes" class="space-y-3 text-sm">
        <div v-if="consultation.clinical_notes?.summary_of_history">
          <p class="font-medium text-gray-500 dark:text-gray-400">Summary of history</p>
          <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">
            {{ consultation.clinical_notes.summary_of_history }}
          </p>
        </div>
        <div v-if="consultation.clinical_notes?.differential_diagnosis">
          <p class="font-medium text-gray-500 dark:text-gray-400">Differential diagnosis</p>
          <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">
            {{ consultation.clinical_notes.differential_diagnosis }}
          </p>
        </div>
        <div v-if="consultation.clinical_notes?.final_diagnosis">
          <p class="font-medium text-gray-500 dark:text-gray-400">Final diagnosis</p>
          <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">
            {{ consultation.clinical_notes.final_diagnosis }}
          </p>
        </div>
        <div v-if="hasStructuredManagementPlan" class="space-y-2">
          <p class="font-medium text-gray-500 dark:text-gray-400">Management plan</p>
          <div v-if="mp.treatment" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">Treatment</p>
            <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ mp.treatment }}</p>
          </div>
          <div v-if="mp.investigation_radiology || mp.investigation_laboratory || mp.investigation_interventional" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">Investigation</p>
            <p v-if="mp.investigation_radiology" class="text-gray-900 dark:text-gray-100 whitespace-pre-line">Radiology: {{ mp.investigation_radiology }}</p>
            <p v-if="mp.investigation_laboratory" class="text-gray-900 dark:text-gray-100 whitespace-pre-line">Laboratory: {{ mp.investigation_laboratory }}</p>
            <p v-if="mp.investigation_interventional" class="text-gray-900 dark:text-gray-100 whitespace-pre-line">Interventional: {{ mp.investigation_interventional }}</p>
          </div>
          <div v-if="mp.referrals" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">Referrals</p>
            <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ mp.referrals }}</p>
          </div>
          <div v-if="hasInPersonVisitContent" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">In-person visit</p>
            <div v-if="ipv.revisit_history" class="mt-1">
              <p class="text-xs text-gray-500 dark:text-gray-400">Doctor revisits history</p>
              <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ ipv.revisit_history }}</p>
            </div>
            <div v-if="ipv.general_examination" class="mt-1">
              <p class="text-xs text-gray-500 dark:text-gray-400">General examination</p>
              <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ ipv.general_examination }}</p>
            </div>
            <div v-if="ipv.system_examination" class="mt-1">
              <p class="text-xs text-gray-500 dark:text-gray-400">System examination</p>
              <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ ipv.system_examination }}</p>
            </div>
          </div>
        </div>
        <div v-else-if="consultation.clinical_notes?.management_plan && typeof consultation.clinical_notes.management_plan === 'string'" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
          <p class="font-medium text-gray-500 dark:text-gray-400">Management plan</p>
          <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ consultation.clinical_notes.management_plan }}</p>
        </div>
      </div>
      <p v-else class="text-sm text-gray-500 dark:text-gray-400">
        No clinical notes yet. Add notes during or after the consultation.
      </p>

      <UModal v-model="showClinicalNotesModal" :ui="{ width: 'max-w-2xl' }">
        <UCard :ui="{ ring: '', divide: 'divide-y divide-gray-200 dark:divide-gray-800' }">
          <template #header>
            <h3 class="text-lg font-semibold">Clinical notes</h3>
          </template>
          <div class="min-h-[400px]">
            <ClinicalNotesForm
              v-model="clinicalNotesData"
              :patient-date-of-birth="consultation?.patient?.date_of_birth"
              :consultation-id="id"
              :on-save="saveClinicalNotes"
              @done="showClinicalNotesModal = false; fetchConsultation()"
            />
          </div>
        </UCard>
      </UModal>
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
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'doctor'
})

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const { token } = useAuth()
const toast = useToast()
const tokenCookie = useCookie('auth_token')

const id = route.params.id as string

const loading = ref(false)
const actionLoading = ref(false)
const prescriptionLoading = ref(false)
const errorMessage = ref('')
const consultation = ref<any | null>(null)
const notesDraft = ref('')
const showIssuePrescription = ref(false)
const showClinicalNotesModal = ref(false)
const clinicalNotesData = ref<Record<string, unknown>>({})

const hasClinicalNotes = computed(() => {
  const notes = consultation.value?.clinical_notes
  if (!notes) return false
  const hasManagementPlan = notes.management_plan && typeof notes.management_plan === 'object'
    ? Object.values(notes.management_plan).some((v) => v && String(v).trim())
    : !!notes.management_plan
  return !!notes.summary_of_history || !!notes.differential_diagnosis || hasManagementPlan || !!notes.final_diagnosis
})

const mp = computed(() => {
  const m = consultation.value?.clinical_notes?.management_plan
  return (typeof m === 'object' && m) ? m : {}
})

const ipv = computed(() => (typeof mp.value.in_person_visit === 'object' && mp.value.in_person_visit) ? mp.value.in_person_visit : {})

const hasInPersonVisitContent = computed(() =>
  ipv.value.revisit_history || ipv.value.general_examination || ipv.value.system_examination
)

const hasStructuredManagementPlan = computed(() => {
  const m = mp.value
  if (m.treatment || m.investigation_radiology || m.investigation_laboratory || m.investigation_interventional || m.referrals) return true
  return hasInPersonVisitContent.value
})

const formOptions = [
  { label: 'Tablet', value: 'Tablet' },
  { label: 'Capsule', value: 'Capsule' },
  { label: 'Suppository', value: 'Suppository' },
  { label: 'Syrup', value: 'Syrup' }
]

const prescriptionForm = reactive({
  consultation_id: 0,
  medications: [{ name: '', form: '', dosage: '', frequency: '', duration: '', instructions: '' }],
  instructions: ''
})

const statusColor = (status: string) => {
  switch (status) {
    case 'scheduled': return 'blue'
    case 'completed': return 'green'
    case 'cancelled': return 'red'
    default: return 'gray'
  }
}

const startConsultationIcon = computed(() => {
  const type = consultation.value?.consultation_type
  switch (type) {
    case 'video': return 'i-lucide-video'
    case 'audio': return 'i-lucide-phone'
    case 'text': return 'i-lucide-message-square'
    default: return 'i-lucide-play'
  }
})

const formatDate = (value: string) => value ? new Date(value).toLocaleString() : '–'

function getHeaders () {
  const authToken = token.value || tokenCookie.value
  return {
    Authorization: `Bearer ${authToken || ''}`,
    Accept: 'application/json'
  }
}

onMounted(() => {
  prescriptionForm.consultation_id = Number(id)
  fetchConsultation()
})

watch(consultation, (c) => {
  if (c) {
    notesDraft.value = c.notes || ''
    if (c.clinical_notes) clinicalNotesData.value = { ...c.clinical_notes }
  }
}, { immediate: true })

async function fetchConsultation () {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await $fetch<{ data: any }>(`/doctor/consultations/${id}`, {
      baseURL: config.public.apiBase,
      headers: getHeaders()
    })
    consultation.value = res?.data ?? null
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'Failed to load consultation.'
  } finally {
    loading.value = false
  }
}

async function markCompleted () {
  await updateStatus('completed')
}

async function markCancelled () {
  await updateStatus('cancelled')
}

async function updateStatus (status: string) {
  actionLoading.value = true
  errorMessage.value = ''
  try {
    const res = await $fetch<{ data: any }>(`/doctor/consultations/${id}`, {
      baseURL: config.public.apiBase,
      method: 'PATCH',
      body: { status },
      headers: getHeaders()
    })
    consultation.value = res?.data ?? consultation.value
    toast.add({ title: 'Status updated', color: 'green' })
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'Failed to update status.'
  } finally {
    actionLoading.value = false
  }
}

async function updateNotes () {
  actionLoading.value = true
  errorMessage.value = ''
  try {
    const res = await $fetch<{ data: any }>(`/doctor/consultations/${id}`, {
      baseURL: config.public.apiBase,
      method: 'PATCH',
      body: { notes: notesDraft.value },
      headers: getHeaders()
    })
    consultation.value = res?.data ?? consultation.value
    toast.add({ title: 'Notes saved', color: 'green' })
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'Failed to save notes.'
  } finally {
    actionLoading.value = false
  }
}

async function saveClinicalNotes (data: Record<string, unknown>) {
  const res = await $fetch<{ data: any }>(`/doctor/consultations/${id}`, {
    baseURL: config.public.apiBase,
    method: 'PATCH',
    body: { clinical_notes: data },
    headers: getHeaders()
  })
  consultation.value = res?.data ?? consultation.value
  toast.add({ title: 'Clinical notes saved', color: 'green' })
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

  prescriptionLoading.value = true
  try {
    await $fetch('/doctor/prescriptions', {
      baseURL: config.public.apiBase,
      method: 'POST',
      body: {
        consultation_id: Number(id),
        medications: meds,
        instructions: prescriptionForm.instructions?.trim() || null
      },
      headers: getHeaders()
    })
    showIssuePrescription.value = false
    prescriptionForm.medications = [{ name: '', form: '', dosage: '', frequency: '', duration: '', instructions: '' }]
    prescriptionForm.instructions = ''
    await fetchConsultation()
    toast.add({ title: 'Prescription issued', color: 'green' })
  } catch (e: any) {
    toast.add({
      title: 'Failed to issue prescription',
      description: e?.data?.message || 'Please try again.',
      color: 'red'
    })
  } finally {
    prescriptionLoading.value = false
  }
}
</script>
