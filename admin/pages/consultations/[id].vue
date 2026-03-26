<template>
  <div class="space-y-6">
    <AdminBreadcrumbs :items="[{ label: 'Consultations', to: '/consultations' }, { label: consultation ? (consultation.consultation_number || `#${consultation.id}`) : 'Consultation' }]" />
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
          <div v-if="consultation.consultation_number">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Consultation no.</dt>
            <dd class="mt-1">
              <AdminHumanId variant="lg" :value="consultation.consultation_number" :show-dash="false" />
            </dd>
          </div>
          <div v-if="consultation.referral_number">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Referral no.</dt>
            <dd class="mt-1">
              <AdminHumanId variant="lg" :value="consultation.referral_number" :show-dash="false" />
            </dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Patient</dt>
            <dd class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
              <AdminPatientNumber :patient-number="consultation.patient?.patient_number" />
              <div class="min-w-0">
                <p class="font-semibold text-gray-900 dark:text-white">
                  {{ consultation.patient?.name || '—' }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  {{ consultation.patient?.email || '—' }}
                </p>
              </div>
            </dd>
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
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Clinical notes &amp; conversation
          </h3>
          <UButton
            size="sm"
            icon="i-lucide-download"
            :loading="downloadingPdf"
            @click="downloadClinicalPdf"
          >
            Download clinical record (PDF)
          </UButton>
        </div>

        <div v-if="hasClinicalNotes" class="space-y-3 text-sm mb-6">
          <div v-if="consultation.clinical_notes?.presenting_complaint">
            <p class="font-medium text-gray-500 dark:text-gray-400">Presenting complaint</p>
            <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">
              {{ consultation.clinical_notes.presenting_complaint }}
            </p>
          </div>
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
          <div v-if="consultation.clinical_notes?.investigation_results || adminPatientInvestigationUploads.length">
            <p class="font-medium text-gray-500 dark:text-gray-400">Investigation results</p>
            <ul v-if="adminPatientInvestigationUploads.length" class="mt-2 space-y-2">
              <li
                v-for="u in adminPatientInvestigationUploads"
                :key="u.id"
                class="flex flex-wrap items-center gap-2"
              >
                <UBadge size="xs" color="primary" variant="soft" class="capitalize">
                  {{ u.category === 'radiology' ? 'Radiology' : 'Laboratory' }}
                </UBadge>
                <a
                  :href="resolvePublicFileUrl(u.file_url)"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-primary-600 dark:text-primary-400 underline break-all"
                >
                  {{ u.label || u.original_filename || 'Open file' }}
                </a>
              </li>
            </ul>
            <p
              v-if="consultation.clinical_notes?.investigation_results"
              class="text-gray-900 dark:text-gray-100 whitespace-pre-line mt-2"
            >
              {{ consultation.clinical_notes.investigation_results }}
            </p>
          </div>
          <div v-if="consultation.clinical_notes?.final_diagnosis">
            <p class="font-medium text-gray-500 dark:text-gray-400">Final diagnosis</p>
            <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">
              {{ consultation.clinical_notes.final_diagnosis }}
            </p>
          </div>
          <div v-if="consultation.clinical_notes?.final_treatment">
            <p class="font-medium text-gray-500 dark:text-gray-400">Final treatment</p>
            <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">
              {{ consultation.clinical_notes.final_treatment }}
            </p>
          </div>
          <div v-if="hasStructuredManagementPlan" class="space-y-2">
            <p class="font-medium text-gray-500 dark:text-gray-400">Management plan</p>
            <div v-if="hasClinicalNotesPrescription" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
              <p class="text-xs text-gray-500 dark:text-gray-400">Prescription (clinical notes)</p>
              <ul class="mt-1 space-y-1 text-gray-900 dark:text-gray-100">
                <li v-for="(med, i) in (adminMp.prescription?.medications || []).filter(m => m?.name?.trim())" :key="i">
                  {{ med.name }}
                  <span v-if="med.form"> ({{ med.form }})</span>
                  <span v-if="med.dosage"> — {{ med.dosage }}</span>
                  <span v-if="med.frequency">, {{ med.frequency }}</span>
                  <span v-if="med.duration"> ({{ med.duration }})</span>
                  <span v-if="med.instructions" class="block text-gray-500 dark:text-gray-400 text-xs mt-0.5">{{ med.instructions }}</span>
                </li>
              </ul>
              <p v-if="adminMp.prescription?.instructions" class="mt-2 text-xs text-gray-500 dark:text-gray-400 whitespace-pre-line">
                {{ adminMp.prescription.instructions }}
              </p>
            </div>
            <div v-if="adminMp.treatment" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
              <p class="text-xs text-gray-500 dark:text-gray-400">Treatment</p>
              <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ adminMp.treatment }}</p>
            </div>
            <div v-if="adminMp.investigation_radiology || adminMp.investigation_laboratory || adminMp.investigation_interventional" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
              <p class="text-xs text-gray-500 dark:text-gray-400">Investigation</p>
              <p v-if="adminMp.investigation_radiology" class="text-gray-900 dark:text-gray-100 whitespace-pre-line">Radiology: {{ adminMp.investigation_radiology }}</p>
              <p v-if="adminMp.investigation_laboratory" class="text-gray-900 dark:text-gray-100 whitespace-pre-line">Laboratory: {{ adminMp.investigation_laboratory }}</p>
              <p v-if="adminMp.investigation_interventional" class="text-gray-900 dark:text-gray-100 whitespace-pre-line">Interventional: {{ adminMp.investigation_interventional }}</p>
            </div>
            <div v-if="adminMp.referrals" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
              <p class="text-xs text-gray-500 dark:text-gray-400">Referrals</p>
              <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ adminMp.referrals }}</p>
            </div>
            <div v-if="hasInPersonVisitContent" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
              <p class="text-xs text-gray-500 dark:text-gray-400">In-person visit</p>
              <div v-if="adminIpv.revisit_history" class="mt-1">
                <p class="text-xs text-gray-500 dark:text-gray-400">Doctor revisits history</p>
                <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ adminIpv.revisit_history }}</p>
              </div>
              <div v-if="hasGeneralExaminationContent(adminIpv.general_examination)" class="mt-1">
                <p class="text-xs text-gray-500 dark:text-gray-400">General examination</p>
                <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ formatGeneralExamination(adminIpv.general_examination) }}</p>
              </div>
              <div v-if="hasSystemExaminationContent(adminIpv.system_examination)" class="mt-1">
                <p class="text-xs text-gray-500 dark:text-gray-400">System examination</p>
                <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ formatSystemExamination(adminIpv.system_examination) }}</p>
              </div>
            </div>
          </div>
          <div v-else-if="consultation.clinical_notes?.management_plan && typeof consultation.clinical_notes.management_plan === 'string'" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
            <p class="font-medium text-gray-500 dark:text-gray-400">Management plan</p>
            <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ consultation.clinical_notes.management_plan }}</p>
          </div>
        </div>
        <p v-else-if="!consultation.messages?.length && !adminPatientInvestigationUploads.length" class="text-sm text-gray-500 dark:text-gray-400 mb-4">
          No structured clinical notes on file.
        </p>

        <div v-if="consultation.messages?.length" class="border-t border-gray-200 dark:border-gray-800 pt-4">
          <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
            Chat transcript
          </h4>
          <ul class="space-y-3 max-h-[480px] overflow-y-auto">
            <li
              v-for="m in consultation.messages"
              :key="m.id"
              class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-sm"
            >
              <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                {{ String(m.sender || '').toUpperCase() }} &middot; {{ formatDate(m.at) }}
              </p>
              <p class="text-gray-900 dark:text-gray-100 whitespace-pre-wrap">
                {{ m.text }}
              </p>
              <a
                v-if="m.attachment_url"
                :href="m.attachment_url"
                target="_blank"
                rel="noopener noreferrer"
                class="text-xs text-primary-600 dark:text-primary-400 underline mt-2 inline-block"
              >
                Attachment
              </a>
            </li>
          </ul>
        </div>
        <p v-else class="text-sm text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-800 pt-4">
          No chat messages for this consultation.
        </p>
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
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="flex flex-col gap-1">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  Issued {{ formatDate(p.issued_at) }}
                </p>
                <AdminHumanId v-if="p.prescription_number" :value="p.prescription_number" :show-dash="false" />
              </div>
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
const config = useRuntimeConfig()
const { get, post, downloadBlob } = useAdminApi()

const id = computed(() => route.params.id)
const consultation = ref(null)
const loading = ref(true)
const prescriptionLoading = ref(false)
const downloadingPdf = ref(false)
const errorMessage = ref('')
const showIssuePrescription = ref(false)

function resolvePublicFileUrl (fileUrl) {
  if (!fileUrl || typeof fileUrl !== 'string') return ''
  const raw = fileUrl.trim()
  if (!raw) return ''
  const pub = config.public || {}
  const override = typeof pub.apiFilesBase === 'string' && pub.apiFilesBase.trim()
    ? pub.apiFilesBase.trim().replace(/\/$/, '')
    : ''
  const publicBase = override || String(pub.apiBase || '')
    .replace(/\/?api\/v1\/?$/i, '')
    .replace(/\/$/, '')
  const joinBase = (path) => {
    const p = path.startsWith('/') ? path : `/${path}`
    return `${publicBase}${p}`
  }
  if (raw.startsWith('/')) return joinBase(raw)
  if (/^https?:\/\//i.test(raw)) {
    try {
      const parsed = new URL(raw)
      const idx = parsed.pathname.indexOf('/storage/')
      if (idx !== -1) return joinBase(parsed.pathname.slice(idx))
    } catch (_) {
      return raw
    }
    return raw
  }
  return joinBase(raw)
}

function hasPrescriptionShape (p) {
  if (!p || typeof p !== 'object') return false
  const meds = p.medications
  if (!Array.isArray(meds)) return false
  return meds.some(m => typeof m?.name === 'string' && m.name.trim().length > 0)
}

const adminPatientInvestigationUploads = computed(() => {
  const list = consultation.value?.patient_investigation_uploads
  if (!Array.isArray(list)) return []
  return list.filter(u => u && typeof u.id === 'string' && typeof u.file_url === 'string')
})

const adminMp = computed(() => {
  const m = consultation.value?.clinical_notes?.management_plan
  return (typeof m === 'object' && m) ? m : {}
})

const adminIpv = computed(() =>
  (typeof adminMp.value.in_person_visit === 'object' && adminMp.value.in_person_visit)
    ? adminMp.value.in_person_visit
    : {}
)

function hasGeneralExaminationContent (ge) {
  if (!ge) return false
  if (typeof ge === 'string') return ge.trim().length > 0
  if (typeof ge !== 'object' || Array.isArray(ge)) return false
  return Object.values(ge).some(v => typeof v === 'string' && v.trim().length > 0)
}

function formatGeneralExamination (ge) {
  if (!ge) return ''
  if (typeof ge === 'string') return ge
  if (typeof ge !== 'object' || Array.isArray(ge)) return ''
  const g = ge
  const lines = []
  const maybePush = (key, label) => {
    const v = g[key]
    if (typeof v === 'string' && v.trim().length > 0) lines.push(`${label}: ${v}`)
  }
  maybePush('appearance', 'General appearance')
  maybePush('jaundice', 'Jaundice')
  maybePush('anemia', 'Anemia')
  maybePush('cyanosis', 'Cyanosis')
  maybePush('clubbing', 'Clubbing')
  maybePush('oedema', 'Oedema')
  maybePush('lymphadenopathy', 'Lymphadenopathy')
  maybePush('dehydration', 'Dehydration')
  return lines.join('\n')
}

function hasSystemExaminationContent (se) {
  if (!se) return false
  if (typeof se === 'string') return se.trim().length > 0
  if (typeof se !== 'object' || Array.isArray(se)) return false
  return Object.values(se).some(v => typeof v === 'string' && v.trim().length > 0)
}

function formatSystemExamination (se) {
  if (!se) return ''
  if (typeof se === 'string') return se
  if (typeof se !== 'object' || Array.isArray(se)) return ''
  const labels = [['cns', 'CNS'], ['respiratory', 'Respiratory'], ['cardiovascular', 'Cardiovascular'], ['abdomen', 'Abdomen'], ['musculoskeletal', 'Musculoskeletal'], ['mental_state', 'Mental state'], ['ophthalmic', 'Ophthalmic'], ['ent', 'ENT'], ['vocal', 'Vocal'], ['dental', 'Dental']]
  const o = se
  const lines = []
  for (const [key, label] of labels) {
    const v = o[key]
    if (typeof v === 'string' && v.trim().length > 0) lines.push(`${label}: ${v}`)
  }
  return lines.join('\n')
}

const hasInPersonVisitContent = computed(() =>
  Boolean(adminIpv.value.revisit_history || hasGeneralExaminationContent(adminIpv.value.general_examination) || hasSystemExaminationContent(adminIpv.value.system_examination))
)

const hasClinicalNotesPrescription = computed(() => hasPrescriptionShape(adminMp.value.prescription))

const hasStructuredManagementPlan = computed(() => {
  const m = adminMp.value
  if (m.treatment || m.investigation_radiology || m.investigation_laboratory || m.investigation_interventional || m.referrals) return true
  if (hasPrescriptionShape(m.prescription)) return true
  return hasInPersonVisitContent.value
})

const hasClinicalNotes = computed(() => {
  const notes = consultation.value?.clinical_notes
  if (adminPatientInvestigationUploads.value.length) return true
  if (!notes) return false
  const hasManagementPlan = notes.management_plan && typeof notes.management_plan === 'object'
    ? Object.values(notes.management_plan).some(v => v && String(v).trim())
    : !!notes.management_plan
  return !!notes.presenting_complaint
    || !!notes.summary_of_history
    || !!notes.differential_diagnosis
    || !!notes.investigation_results
    || !!notes.final_treatment
    || hasManagementPlan
    || !!notes.final_diagnosis
    || hasPrescriptionShape(notes.management_plan?.prescription)
})

async function downloadClinicalPdf () {
  downloadingPdf.value = true
  try {
    await downloadBlob(`admin/consultations/${id.value}/clinical-notes/pdf`, `consultation-${id.value}-clinical-record.pdf`)
    toast.add({ title: 'Download started', color: 'green' })
  } catch (e) {
    const status = e && typeof e === 'object' ? e.status : undefined
    toast.add({
      title: 'Could not export PDF',
      description: status === 404 ? 'No clinical record, uploads, or messages to include yet.' : 'Please try again.',
      color: 'red'
    })
  } finally {
    downloadingPdf.value = false
  }
}

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
