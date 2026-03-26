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
      <span v-if="consultation?.consultation_number">Consultation {{ consultation.consultation_number }}</span>
      <span v-else>Consultation #{{ consultation?.id }}</span>
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
          <div class="sm:col-span-2 rounded-xl border-2 border-primary-200 bg-primary-50/90 p-4 dark:border-primary-700 dark:bg-primary-950/40">
            <dt class="text-xs font-semibold uppercase tracking-wide text-primary-800 dark:text-primary-200">
              Patient number
            </dt>
            <dd class="mt-2">
              <PatientNumberBadge
                v-if="consultation.patient?.patient_number"
                size="lg"
                :patient-number="consultation.patient.patient_number"
              />
              <span v-else class="text-lg font-mono font-semibold text-gray-500 dark:text-gray-400">—</span>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Patient</dt>
            <dd class="text-sm text-gray-900 dark:text-gray-100">
              {{ consultation.patient?.name || `Patient #${consultation.patient_id}` }}
            </dd>
          </div>
          <div v-if="consultation.patient?.chronic_conditions?.length" class="sm:col-span-2">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Patient chronic conditions</dt>
            <dd class="mt-0.5 flex flex-wrap gap-1">
              <UBadge v-for="c in (consultation.patient.chronic_conditions || [])" :key="c" size="xs" color="gray" variant="soft">{{ c }}</UBadge>
            </dd>
          </div>
          <div v-if="consultation.consultation_number" class="sm:col-span-2">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Consultation no.</dt>
            <dd class="mt-1">
              <HumanIdBadge size="lg" :value="consultation.consultation_number" />
              <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">(ID {{ consultation.id }})</span>
            </dd>
          </div>
          <div v-if="consultation.referral_number" class="sm:col-span-2">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Referral no.</dt>
            <dd class="mt-1">
              <HumanIdBadge size="lg" :value="consultation.referral_number" />
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
      <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Clinical notes
        </h3>
        <div class="flex flex-wrap gap-2">
          <UButton
            size="sm"
            variant="soft"
            icon="i-lucide-download"
            :loading="downloadingPdf"
            @click="downloadClinicalRecordPdf"
          >
            Download PDF
          </UButton>
          <UButton
            size="sm"
            icon="i-lucide-clipboard-list"
            @click="showClinicalNotesModal = true"
          >
            {{ hasClinicalNotes ? 'Edit clinical notes' : 'Add clinical notes' }}
          </UButton>
        </div>
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
        <div v-if="consultation.clinical_notes?.investigation_results || patientInvestigationUploads.length">
          <p class="font-medium text-gray-500 dark:text-gray-400">Investigation results</p>
          <div v-if="patientInvestigationUploads.length" class="mt-2 space-y-2">
            <p class="text-xs text-gray-500 dark:text-gray-400">Patient-uploaded files</p>
            <ul class="space-y-2 text-sm">
              <li
                v-for="u in patientInvestigationUploads"
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
                <span v-if="u.uploaded_at" class="text-xs text-gray-500">{{ formatUploadMetaTime(u.uploaded_at) }}</span>
              </li>
            </ul>
          </div>
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
              <li v-for="(med, i) in (mp.prescription?.medications || []).filter((m: any) => m?.name?.trim())" :key="i">
                {{ med.name }}
                <span v-if="med.form"> ({{ med.form }})</span>
                <span v-if="med.dosage"> — {{ med.dosage }}</span>
                <span v-if="med.frequency">, {{ med.frequency }}</span>
                <span v-if="med.duration"> ({{ med.duration }})</span>
                <span v-if="med.instructions" class="block text-gray-500 dark:text-gray-400 text-xs mt-0.5">{{ med.instructions }}</span>
              </li>
            </ul>
            <p v-if="mp.prescription?.instructions" class="mt-2 text-xs text-gray-500 dark:text-gray-400 whitespace-pre-line">
              {{ mp.prescription.instructions }}
            </p>
          </div>
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
            <div v-if="hasGeneralExaminationContent(ipv.general_examination)" class="mt-1">
              <p class="text-xs text-gray-500 dark:text-gray-400">General examination</p>
              <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ formatGeneralExamination(ipv.general_examination) }}</p>
            </div>
            <div v-if="hasSystemExaminationContent(ipv.system_examination)" class="mt-1">
              <p class="text-xs text-gray-500 dark:text-gray-400">System examination</p>
              <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ formatSystemExamination(ipv.system_examination) }}</p>
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
          <div class="min-h-[400px] max-h-[min(85vh,760px)] flex flex-col min-h-0">
            <ClinicalNotesForm
              class="flex-1 min-h-0 min-w-0"
              v-model="clinicalNotesData"
              :patient-date-of-birth="consultation?.patient?.date_of_birth"
              :consultation-id="id"
              :patient-investigation-uploads="patientInvestigationUploads"
              :on-save="saveClinicalNotes"
              @done="showClinicalNotesModal = false; fetchConsultation()"
            />
          </div>
        </UCard>
      </UModal>
    </UCard>

    <div ref="conversationAnchor" class="h-0 scroll-mt-24" aria-hidden="true" />

    <UCard
      v-if="consultation"
      :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }"
    >
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        Conversation
      </h3>
      <ul
        v-if="(consultation.messages || []).length"
        class="space-y-3 max-h-[min(60vh,520px)] overflow-y-auto text-sm"
      >
        <li
          v-for="m in (consultation.messages || [])"
          :key="m.id"
          class="rounded-lg border border-gray-200 dark:border-gray-700 p-3"
        >
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
            {{ String(m.sender || '').toUpperCase() }} · {{ formatDate(m.at) }}
          </p>
          <p class="text-gray-900 dark:text-gray-100 whitespace-pre-wrap">
            {{ m.text }}
          </p>
          <a
            v-if="m.attachment_url"
            :href="resolvePublicFileUrl(m.attachment_url)"
            target="_blank"
            rel="noopener noreferrer"
            class="text-xs text-primary-600 dark:text-primary-400 underline mt-2 inline-block break-all"
          >
            Attachment
          </a>
        </li>
      </ul>

      <div
        v-else
        class="py-10 text-center text-sm text-gray-500 dark:text-gray-400"
      >
        No messages yet.
      </div>
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
              <HumanIdBadge
                v-if="p.prescription_number"
                :value="p.prescription_number"
              />
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
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'doctor'
})

import type { ClinicalNotesData } from '~/components/ClinicalNotesForm.vue'

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const { token } = useAuth()
const toast = useToast()
const tokenCookie = useCookie('auth_token')
const { resolvePublicFileUrl } = useResolvePublicFileUrl()

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
const downloadingPdf = ref(false)

const conversationAnchor = ref<HTMLElement | null>(null)

function hasPrescriptionShape (p: unknown): boolean {
  if (!p || typeof p !== 'object') return false
  const meds = (p as { medications?: unknown }).medications
  if (!Array.isArray(meds)) return false
  return meds.some((m: any) => typeof m?.name === 'string' && m.name.trim().length > 0)
}

const patientInvestigationUploads = computed(() => {
  const list = consultation.value?.patient_investigation_uploads
  if (!Array.isArray(list)) return []
  return list.filter((u: any) => u && typeof u.id === 'string' && typeof u.file_url === 'string')
})

function formatUploadMetaTime (iso: string) {
  try {
    return new Date(iso).toLocaleString(undefined, { dateStyle: 'short', timeStyle: 'short' })
  } catch {
    return iso
  }
}

const hasClinicalNotes = computed(() => {
  const notes = consultation.value?.clinical_notes
  if (patientInvestigationUploads.value.length) return true
  if (!notes) return false
  const hasManagementPlan = notes.management_plan && typeof notes.management_plan === 'object'
    ? Object.values(notes.management_plan).some((v) => v && String(v).trim())
    : !!notes.management_plan
  return !!notes.summary_of_history
    || !!notes.differential_diagnosis
    || !!notes.investigation_results
    || !!notes.final_treatment
    || hasManagementPlan
    || !!notes.final_diagnosis
    || hasPrescriptionShape((notes.management_plan as any)?.prescription)
})

const mp = computed(() => {
  const m = consultation.value?.clinical_notes?.management_plan
  return (typeof m === 'object' && m) ? m : {}
})

const hasClinicalNotesPrescription = computed(() => hasPrescriptionShape(mp.value.prescription))

const ipv = computed(() => (typeof mp.value.in_person_visit === 'object' && mp.value.in_person_visit) ? mp.value.in_person_visit : {})

function hasGeneralExaminationContent (ge: unknown): boolean {
  if (!ge) return false
  if (typeof ge === 'string') return ge.trim().length > 0
  if (typeof ge !== 'object' || Array.isArray(ge)) return false
  return Object.values(ge as Record<string, unknown>).some((v) => typeof v === 'string' && v.trim().length > 0)
}

function formatGeneralExamination (ge: unknown): string {
  if (!ge) return ''
  if (typeof ge === 'string') return ge
  if (typeof ge !== 'object' || Array.isArray(ge)) return ''

  const g = ge as Record<string, unknown>
  const lines: string[] = []
  const maybePush = (key: string, label: string) => {
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

function hasSystemExaminationContent (se: unknown): boolean {
  if (!se) return false
  if (typeof se === 'string') return se.trim().length > 0
  if (typeof se !== 'object' || Array.isArray(se)) return false
  return Object.values(se as Record<string, unknown>).some((v) => typeof v === 'string' && v.trim().length > 0)
}

function formatSystemExamination (se: unknown): string {
  if (!se) return ''
  if (typeof se === 'string') return se
  if (typeof se !== 'object' || Array.isArray(se)) return ''
  const labels: [string, string][] = [
    ['cns', 'CNS'],
    ['respiratory', 'Respiratory'],
    ['cardiovascular', 'Cardiovascular'],
    ['abdomen', 'Abdomen'],
    ['musculoskeletal', 'Musculoskeletal'],
    ['mental_state', 'Mental state'],
    ['ophthalmic', 'Ophthalmic'],
    ['ent', 'ENT'],
    ['vocal', 'Vocal'],
    ['dental', 'Dental'],
  ]
  const o = se as Record<string, unknown>
  const lines: string[] = []
  for (const [key, label] of labels) {
    const v = o[key]
    if (typeof v === 'string' && v.trim().length > 0) lines.push(`${label}: ${v}`)
  }
  return lines.join('\n')
}

const hasInPersonVisitContent = computed(() =>
  Boolean(ipv.value.revisit_history || hasGeneralExaminationContent(ipv.value.general_examination) || hasSystemExaminationContent(ipv.value.system_examination))
)

const hasStructuredManagementPlan = computed(() => {
  const m = mp.value
  if (m.treatment || m.investigation_radiology || m.investigation_laboratory || m.investigation_interventional || m.referrals) return true
  if (hasPrescriptionShape(m.prescription)) return true
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

    if (route.query.focus === 'conversation') {
      await nextTick()
      conversationAnchor.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
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

async function saveClinicalNotes (data: ClinicalNotesData) {
  const res = await $fetch<{ data: any }>(`/doctor/consultations/${id}`, {
    baseURL: config.public.apiBase,
    method: 'PATCH',
    body: { clinical_notes: data },
    headers: getHeaders()
  })
  consultation.value = res?.data ?? consultation.value
  toast.add({ title: 'Clinical notes saved', color: 'green' })
}

async function downloadClinicalRecordPdf () {
  downloadingPdf.value = true
  try {
    const url = `${config.public.apiBase}/doctor/consultations/${id}/clinical-notes/pdf`
    const res = await fetch(url, { headers: getHeaders() })
    if (!res.ok) {
      toast.add({
        title: 'Could not download PDF',
        description: res.status === 404 ? 'Nothing to export yet (add notes, uploads, or messages).' : 'Please try again.',
        color: 'red'
      })
      return
    }
    const blob = await res.blob()
    const a = document.createElement('a')
    const href = URL.createObjectURL(blob)
    a.href = href
    a.download = `consultation-${id}-clinical-record.pdf`
    a.click()
    URL.revokeObjectURL(href)
    toast.add({ title: 'Download started', color: 'green' })
  } catch {
    toast.add({ title: 'Download failed', color: 'red' })
  } finally {
    downloadingPdf.value = false
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
