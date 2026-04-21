<template>
  <div class="clinical-notes-form flex flex-col h-full min-h-0 flex-1">
    <!-- Progress + jump to section -->
    <div class="shrink-0 space-y-2 px-2 py-2 border-b border-gray-700">
      <div class="flex items-center gap-2">
        <span class="text-xs text-gray-400 shrink-0 tabular-nums">
          Step {{ visibleStepIndex + 1 }} / {{ visibleSteps.length }}
        </span>
        <div class="flex-1 min-w-0 h-1.5 rounded-full bg-gray-800 overflow-hidden">
          <div
            class="h-full bg-primary-500 transition-all duration-300"
            :style="{ width: `${progressPercent}%` }"
          />
        </div>
      </div>
      <USelectMenu
        :model-value="visibleStepIndex"
        :options="stepJumpOptions"
        value-attribute="value"
        option-attribute="label"
        searchable
        searchable-placeholder="Filter sections…"
        placeholder="Jump to section…"
        size="xs"
        class="w-full"
        @update:model-value="onJumpToStep"
      />
    </div>

    <div class="flex-1 min-h-0 flex flex-col overflow-hidden">
      <div class="shrink-0 px-4 pt-3 pb-2 border-b border-gray-800/70">
        <h3 class="text-sm font-semibold text-gray-200 mb-0.5">
          {{ currentStep?.label }}
        </h3>
        <p v-if="currentStep?.hint" class="text-xs text-gray-500 leading-snug">
          {{ currentStep.hint }}
        </p>
      </div>

      <div ref="stepBodyScrollEl" class="flex-1 min-h-0 overflow-y-auto overscroll-contain px-4 py-3">
      <!-- Multi-select for management plan categories -->
      <div v-if="currentStep?.key === 'management_plan_select'" class="space-y-3 pb-2">
        <p class="text-xs text-gray-400 mb-3">Select one or more options. You can edit later.</p>
        <div class="space-y-2">
          <label
            v-for="opt in MANAGEMENT_PLAN_OPTIONS"
            :key="opt.value"
            class="flex items-center gap-3 rounded-lg border border-gray-700 p-3 cursor-pointer hover:bg-gray-800/50 transition-colors"
          >
            <input
              type="checkbox"
              :checked="selectedMpCategories.includes(opt.value)"
              class="rounded border-gray-600 text-primary-600 focus:ring-primary-500"
              @change="toggleMpCategory(opt.value)"
            >
            <span class="text-sm text-gray-200">{{ opt.label }}</span>
          </label>
        </div>
      </div>

      <!-- Text input for other steps -->
      <div v-else-if="currentStep?.key === 'in_person_visit_general_examination'" class="flex-1 min-h-0 flex flex-col overflow-y-auto">
        <div class="space-y-4 p-1">
          <p class="text-xs text-gray-400">Apply the general examination checks below</p>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="flex flex-col gap-1">
              <label class="text-xs text-gray-400">General appearance</label>
              <USelectMenu
                v-model="appearanceValue"
                :options="generalAppearanceOptions"
                value-attribute="value"
                option-attribute="label"
                placeholder="Select"
              />
            </div>

            <div class="flex flex-col gap-1">
              <label class="text-xs text-gray-400">Jaundice</label>
              <USelectMenu
                v-model="jaundiceValue"
                :options="jaundiceOptions"
                value-attribute="value"
                option-attribute="label"
                placeholder="Select"
              />
            </div>

            <div class="flex flex-col gap-1">
              <label class="text-xs text-gray-400">Anemia</label>
              <USelectMenu
                v-model="anemiaValue"
                :options="presentAbsentOptions"
                value-attribute="value"
                option-attribute="label"
                placeholder="Select"
              />
            </div>

            <div class="flex flex-col gap-1">
              <label class="text-xs text-gray-400">Cyanosis</label>
              <USelectMenu
                v-model="cyanosisValue"
                :options="presentAbsentOptions"
                value-attribute="value"
                option-attribute="label"
                placeholder="Select"
              />
            </div>

            <div class="flex flex-col gap-1">
              <label class="text-xs text-gray-400">Clubbing</label>
              <USelectMenu
                v-model="clubbingValue"
                :options="presentAbsentOptions"
                value-attribute="value"
                option-attribute="label"
                placeholder="Select"
              />
            </div>

            <div class="flex flex-col gap-1">
              <label class="text-xs text-gray-400">Oedema</label>
              <USelectMenu
                v-model="oedemaValue"
                :options="oedemaOptions"
                value-attribute="value"
                option-attribute="label"
                placeholder="Select"
              />
            </div>

            <div class="flex flex-col gap-1">
              <label class="text-xs text-gray-400">Lymphadenopathy</label>
              <USelectMenu
                v-model="lymphadenopathyValue"
                :options="lymphadenopathyOptions"
                value-attribute="value"
                option-attribute="label"
                placeholder="Select"
              />
            </div>

            <div class="flex flex-col gap-1">
              <label class="text-xs text-gray-400">Dehydration</label>
              <USelectMenu
                v-model="dehydrationValue"
                :options="dehydrationOptions"
                value-attribute="value"
                option-attribute="label"
                placeholder="Select"
              />
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="currentStep?.key === 'in_person_visit_system_examination'" class="pb-2">
        <div class="space-y-4 p-1">
          <UAlert
            v-if="legacySystemExaminationString"
            color="amber"
            variant="soft"
            title="Previous system examination (free text)"
            :description="legacySystemExaminationString"
            class="text-left"
          />
          <p class="text-xs text-gray-400">
            Use one field per system. Earlier free-text notes appear above until you replace them with these fields.
          </p>
          <div class="space-y-3">
            <div v-for="row in systemExaminationFieldDefs" :key="row.field">
              <label class="text-xs text-gray-400">{{ row.label }}</label>
              <p v-if="row.hint" class="text-[11px] text-gray-500 mt-0.5 mb-1">{{ row.hint }}</p>
              <UTextarea
                :model-value="getSystemExaminationField(row.field)"
                :placeholder="row.placeholder"
                :rows="3"
                class="mt-1 w-full resize-none"
                @update:model-value="setSystemExaminationField(row.field, $event)"
              />
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="currentStep?.key === 'treatment'" class="pb-2">
        <div class="space-y-4 p-1">
          <UAlert
            v-if="legacyFreeTextTreatment"
            color="amber"
            variant="soft"
            title="Previous treatment (free text)"
            :description="legacyFreeTextTreatment"
          />
          <p class="text-xs text-gray-400">Match the prescription layout used when issuing a prescription from consultation details.</p>
          <div class="space-y-4">
            <div
              v-for="(med, i) in prescriptionMedicationRows"
              :key="i"
              class="rounded-lg border border-gray-700 p-3 space-y-3"
            >
              <div class="flex gap-2 items-start flex-wrap">
                <UInput
                  :model-value="med.name"
                  placeholder="Drug name"
                  class="flex-1 min-w-[8rem]"
                  @update:model-value="updatePrescriptionMedField(i, 'name', $event)"
                />
                <USelectMenu
                  :model-value="med.form"
                  :options="medicationFormOptions"
                  value-attribute="value"
                  option-attribute="label"
                  placeholder="Form"
                  class="w-36"
                  @update:model-value="updatePrescriptionMedField(i, 'form', $event)"
                />
                <UButton
                  v-if="prescriptionMedicationRows.length > 1"
                  color="red"
                  variant="ghost"
                  icon="i-lucide-trash-2"
                  size="xs"
                  @click.prevent="removePrescriptionMedRow(i)"
                />
              </div>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <UInput
                  :model-value="med.dosage"
                  placeholder="Dosage"
                  @update:model-value="updatePrescriptionMedField(i, 'dosage', $event)"
                />
                <UInput
                  :model-value="med.frequency"
                  placeholder="Frequency"
                  @update:model-value="updatePrescriptionMedField(i, 'frequency', $event)"
                />
                <UInput
                  :model-value="med.duration"
                  placeholder="Duration"
                  @update:model-value="updatePrescriptionMedField(i, 'duration', $event)"
                />
              </div>
              <UInput
                :model-value="med.instructions"
                placeholder="Instructions (e.g. Take with food)"
                @update:model-value="updatePrescriptionMedField(i, 'instructions', $event)"
              />
            </div>
            <UButton
              variant="outline"
              size="sm"
              icon="i-lucide-plus"
              @click.prevent="addPrescriptionMedRow"
            >
              Add medication
            </UButton>
          </div>
          <div class="mt-2 space-y-1">
            <label class="text-xs text-gray-400">General instructions (optional)</label>
            <UTextarea
              :model-value="prescriptionGeneralInstructions"
              placeholder="Additional notes for the patient..."
              :rows="2"
              class="w-full"
              @update:model-value="updatePrescriptionGeneralInstructions"
            />
          </div>
        </div>
      </div>

      <div v-else-if="currentStep?.key === 'investigation_results'" class="space-y-4 pb-2">
        <div
          v-if="patientInvestigationUploads.length"
          class="rounded-lg border border-primary-700/50 bg-primary-950/30 p-3 space-y-2"
        >
          <p class="text-xs font-medium text-primary-200">
            Patient-uploaded lab & radiology files
          </p>
          <ul class="space-y-2">
            <li
              v-for="u in patientInvestigationUploads"
              :key="u.id"
              class="flex flex-col sm:flex-row sm:items-center sm:flex-wrap gap-1 sm:gap-2 text-xs"
            >
              <UBadge size="xs" color="primary" variant="soft" class="capitalize w-fit">
                {{ u.category === 'radiology' ? 'Radiology' : 'Laboratory' }}
              </UBadge>
              <a
                :href="resolvePublicFileUrl(u.file_url)"
                target="_blank"
                rel="noopener noreferrer"
                class="text-primary-300 hover:text-primary-200 underline break-all"
              >
                {{ u.label || u.original_filename || 'Open file' }}
              </a>
              <span v-if="u.uploaded_at" class="text-gray-500">{{ formatUploadTime(u.uploaded_at) }}</span>
            </li>
          </ul>
        </div>
        <p class="text-xs text-gray-400">
          Add your clinical interpretation below. Patient files (if any) are listed above.
        </p>
        <UTextarea
          :model-value="currentValue"
          :placeholder="currentStep?.placeholder"
          :rows="8"
          class="w-full min-h-[140px] resize-none"
          @update:model-value="onInput"
        />
      </div>

      <div v-else-if="currentStep?.key === 'final_diagnosis'" class="pb-2">
        <div class="space-y-4 p-1">
          <div class="space-y-2">
            <p class="text-xs text-gray-400">ICD-11 search (English) - select one code</p>
            <UInput
              v-model="icd11FinalQuery"
              placeholder="Type disease/condition to search..."
            />
            <div v-if="icd11FinalLoading" class="text-xs text-gray-500">Searching...</div>
            <div v-if="icd11FinalSuggestions.length" class="space-y-2">
              <div
                v-for="s in icd11FinalSuggestions"
                :key="s.code"
                class="rounded-lg border border-gray-700 bg-gray-800/30 px-3 py-2 flex items-start justify-between gap-3"
              >
                <div class="min-w-0">
                  <p class="text-sm text-gray-200 truncate">{{ s.title }}</p>
                  <p class="text-xs text-gray-400">ICD-11 {{ s.code }}</p>
                </div>
                <UButton size="xs" variant="soft" @click="selectFinalIcd11(s)">Select</UButton>
              </div>
            </div>
          </div>

          <div v-if="finalIcd11?.code" class="rounded-lg border border-gray-700 p-3 space-y-2">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="text-sm text-gray-200 truncate">{{ finalIcd11.title }}</p>
                <p class="text-xs text-gray-400">ICD-11 {{ finalIcd11.code }}</p>
              </div>
              <UButton size="xs" variant="ghost" icon="i-lucide-trash-2" @click="clearFinalIcd11" />
            </div>
          </div>

          <UTextarea
            :model-value="currentValue"
            :placeholder="currentStep?.placeholder"
            :rows="6"
            class="flex-1 min-h-[120px] resize-none"
            @update:model-value="onInput"
          />
        </div>
      </div>

      <div v-else-if="currentStep?.key === 'differential_diagnosis'" class="pb-2">
        <div class="space-y-4 p-1">
          <div class="space-y-2">
            <p class="text-xs text-gray-400">ICD-11 search (English) - select multiple codes</p>
            <UInput
              v-model="icd11DifferentialQuery"
              placeholder="Type disease/condition to search..."
            />
            <div v-if="icd11DifferentialLoading" class="text-xs text-gray-500">Searching...</div>
            <div v-if="icd11DifferentialSuggestions.length" class="space-y-2">
              <div
                v-for="s in icd11DifferentialSuggestions"
                :key="s.code"
                class="rounded-lg border border-gray-700 bg-gray-800/30 px-3 py-2 flex items-start justify-between gap-3"
              >
                <div class="min-w-0">
                  <p class="text-sm text-gray-200 truncate">{{ s.title }}</p>
                  <p class="text-xs text-gray-400">ICD-11 {{ s.code }}</p>
                </div>
                <UButton size="xs" variant="soft" @click="addDifferentialIcd11(s)">Add</UButton>
              </div>
            </div>
          </div>

          <div v-if="differentialIcd11.length" class="rounded-lg border border-gray-700 p-3 space-y-2">
            <p class="text-xs text-gray-400">Selected ICD-11 codes</p>
            <div class="space-y-2">
              <div
                v-for="d in differentialIcd11"
                :key="d.code"
                class="flex items-start justify-between gap-3 rounded-md bg-gray-800/20 px-2 py-2"
              >
                <div class="min-w-0">
                  <p class="text-sm text-gray-200 truncate">{{ d.title }}</p>
                  <p class="text-xs text-gray-400">ICD-11 {{ d.code }}</p>
                </div>
                <UButton size="xs" variant="ghost" icon="i-lucide-trash-2" @click="removeDifferentialIcd11(d.code)" />
              </div>
            </div>
          </div>

          <UTextarea
            :model-value="currentValue"
            :placeholder="currentStep?.placeholder"
            :rows="6"
            class="flex-1 min-h-[120px] resize-none"
            @update:model-value="onInput"
          />
        </div>
      </div>

      <div v-else-if="currentStep?.key === 'presenting_complaints'" class="pb-2 space-y-3">
        <p class="text-xs text-gray-400">
          Enter each presenting complaint and how long it has been present. Add another row if there is more than one complaint.
        </p>
        <div
          v-for="(row, i) in presentingComplaintRows"
          :key="i"
          class="rounded-lg border border-gray-700 p-3 space-y-3"
        >
          <div class="flex items-center justify-between gap-2">
            <span class="text-xs text-gray-500">Complaint {{ i + 1 }}</span>
            <UButton
              v-if="presentingComplaintRows.length > 1"
              color="red"
              variant="ghost"
              size="xs"
              icon="i-lucide-trash-2"
              @click.prevent="removePresentingComplaintRow(i)"
            />
          </div>
          <UFormGroup label="Complaint" :ui="{ label: { base: 'text-xs text-gray-400' } }">
            <UTextarea
              :model-value="row.complaint"
              :placeholder="currentStep?.placeholder"
              :rows="3"
              class="w-full min-h-[72px] resize-none"
              @update:model-value="updatePresentingComplaintField(i, 'complaint', $event)"
            />
          </UFormGroup>
          <UFormGroup label="Duration" :ui="{ label: { base: 'text-xs text-gray-400' } }">
            <UInput
              :model-value="row.duration"
              placeholder="e.g. 3 days, 2 weeks, since childhood"
              class="w-full"
              @update:model-value="updatePresentingComplaintField(i, 'duration', $event)"
            />
          </UFormGroup>
        </div>
        <UButton
          variant="outline"
          size="sm"
          icon="i-lucide-plus"
          @click.prevent="addPresentingComplaintRow"
        >
          Add another complaint
        </UButton>
      </div>

      <div v-else-if="currentStep?.key === 'review_of_systems'" class="pb-2 space-y-3">
        <p class="text-xs text-gray-400">
          Enter findings for each system. Leave blank if not applicable.
        </p>
        <div
          v-for="row in reviewOfSystemsFieldDefs"
          :key="row.key"
          class="rounded-lg border border-gray-700 p-3 space-y-2"
        >
          <UFormGroup :label="row.label" :ui="{ label: { base: 'text-xs text-gray-400' } }">
            <UTextarea
              :model-value="getReviewOfSystemsField(row.key)"
              :placeholder="row.placeholder"
              :rows="3"
              class="w-full min-h-[72px] resize-none"
              @update:model-value="setReviewOfSystemsField(row.key, $event)"
            />
          </UFormGroup>
        </div>
      </div>

      <div v-else class="pb-2">
        <UTextarea
          :model-value="currentValue"
          :placeholder="currentStep?.placeholder"
          :rows="8"
          class="w-full min-h-[120px] resize-none"
          @update:model-value="onInput"
        />
      </div>
      </div>

      <!-- Navigation: pinned below scroll area -->
      <div class="shrink-0 px-3 py-3 border-t border-gray-700 bg-gray-900/98 flex justify-between gap-2 gap-y-2 flex-wrap safe-area-bottom">
        <UButton
          variant="ghost"
          size="sm"
          icon="i-lucide-chevron-left"
          :disabled="visibleStepIndex === 0"
          @click="prevStep"
        >
          Previous
        </UButton>
        <UButton
          v-if="visibleStepIndex < visibleSteps.length - 1"
          size="sm"
          icon="i-lucide-chevron-right"
          icon-position="right"
          :loading="saving"
          @click="nextStep"
        >
          Next
        </UButton>
        <UButton
          v-else
          size="sm"
          icon="i-lucide-check"
          :loading="saving"
          @click="saveAndClose"
        >
          Done
        </UButton>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
export interface GeneralExaminationData {
  appearance?: 'Good' | 'Sick' | 'Very sick'
  jaundice?: 'Nil' | 'Mild' | 'Severe'
  anemia?: 'Present' | 'Absent'
  cyanosis?: 'Present' | 'Absent'
  clubbing?: 'Present' | 'Absent'
  oedema?: 'Grade I' | 'Grade II' | 'Grade III' | 'Grade IV'
  lymphadenopathy?: 'Nil' | 'Present'
  dehydration?: 'Nil' | 'Some' | 'Severe'
}

export interface SystemExaminationFields {
  cns?: string
  respiratory?: string
  cardiovascular?: string
  abdomen?: string
  musculoskeletal?: string
  mental_state?: string
  ophthalmic?: string
  ent?: string
  vocal?: string
  dental?: string
}

export interface PrescriptionMedicationRow {
  name: string
  form: string
  dosage: string
  frequency: string
  duration: string
  instructions: string
}

export interface ManagementPlanPrescription {
  medications: PrescriptionMedicationRow[]
  instructions: string
}

export interface InPersonVisitData {
  revisit_history?: string
  general_examination?: GeneralExaminationData | string
  system_examination?: string | SystemExaminationFields
}

export interface ManagementPlanData {
  selected_categories?: string[]
  treatment?: string
  prescription?: ManagementPlanPrescription | null
  investigation_radiology?: string
  investigation_laboratory?: string
  investigation_interventional?: string
  referrals?: string
  in_person_visit?: InPersonVisitData
}

export interface Icd11Diagnosis {
  code?: string
  title?: string
}

export interface PatientInvestigationUpload {
  id: string
  category: 'radiology' | 'laboratory'
  file_url: string
  original_filename?: string | null
  label?: string | null
  uploaded_at?: string | null
}

export interface ClinicalOutcomeNotes {
  doctor_notes?: string
  patient_reports_improved?: boolean | null
  patient_reported_at?: string | null
}

/** One presenting complaint with its duration (stored in clinical_notes.presenting_complaints). */
export interface PresentingComplaintRow {
  complaint?: string
  duration?: string
}

/** Structured review of systems (stored in clinical_notes.review_of_systems). */
export type ReviewOfSystemsFieldKey =
  | 'cns'
  | 'respiratory'
  | 'cardiovascular'
  | 'digestive'
  | 'genitourinary'
  | 'locomotor'
  | 'other'

export interface ReviewOfSystemsFields {
  cns?: string
  respiratory?: string
  cardiovascular?: string
  digestive?: string
  genitourinary?: string
  locomotor?: string
  other?: string
}

export interface ClinicalNotesData {
  /** @deprecated Prefer presenting_complaints; kept in sync for exports and legacy readers */
  presenting_complaint?: string
  /** Each entry is { complaint, duration } or legacy plain string. */
  presenting_complaints?: Array<string | PresentingComplaintRow>
  history_of_presenting_complaint?: string
  /** Structured fields per system, or legacy single string (normalized on save). */
  review_of_systems?: string | ReviewOfSystemsFields
  past_medical_history?: string
  past_surgical_history?: string
  growth_and_development?: string
  immunization_history?: string
  family_history?: string
  social_history?: string
  summary_of_history?: string
  differential_diagnosis?: string
  differential_diagnoses_icd11?: Icd11Diagnosis[]
  investigation_results?: string
  management_plan?: ManagementPlanData | string
  final_diagnosis?: string
  final_diagnosis_icd11?: Icd11Diagnosis | null
  final_treatment?: string
  outcome?: ClinicalOutcomeNotes
}

const props = withDefaults(
  defineProps<{
    modelValue: ClinicalNotesData
    patientDateOfBirth?: string | null
    consultationId: string | number
    onSave?: (data: ClinicalNotesData) => Promise<void>
    compact?: boolean
    /** Patient-uploaded lab/radiology files (from consultation metadata); shown on Investigation results step. */
    patientInvestigationUploads?: PatientInvestigationUpload[]
  }>(),
  { compact: false, patientInvestigationUploads: () => [] }
)

function formatUploadTime (iso: string) {
  try {
    return new Date(iso).toLocaleString(undefined, { dateStyle: 'short', timeStyle: 'short' })
  } catch {
    return iso
  }
}

const emit = defineEmits<{
  (e: 'update:modelValue', value: ClinicalNotesData): void
  (e: 'done'): void
}>()

const saving = ref(false)

const config = useRuntimeConfig()
const tokenCookie = useCookie<string | null>('auth_token')
const { resolvePublicFileUrl } = useResolvePublicFileUrl()

// ------------------------
// ICD-11 (code + title)
// ------------------------
const icd11FinalQuery = ref('')
const icd11FinalSuggestions = ref<Icd11Diagnosis[]>([])
const icd11FinalLoading = ref(false)
let icd11FinalSearchTimer: ReturnType<typeof setTimeout> | null = null

const icd11DifferentialQuery = ref('')
const icd11DifferentialSuggestions = ref<Icd11Diagnosis[]>([])
const icd11DifferentialLoading = ref(false)
let icd11DifferentialSearchTimer: ReturnType<typeof setTimeout> | null = null

const finalIcd11 = computed<Icd11Diagnosis | null>(() => {
  const v = props.modelValue.final_diagnosis_icd11 as unknown
  if (!v || typeof v !== 'object' || Array.isArray(v)) return null
  const obj = v as Record<string, unknown>
  if (typeof obj.code !== 'string' || typeof obj.title !== 'string') return null
  return { code: obj.code, title: obj.title }
})

const differentialIcd11 = computed<Icd11Diagnosis[]>(() => {
  const v = props.modelValue.differential_diagnoses_icd11 as unknown
  if (!Array.isArray(v)) return []
  return v
    .map((x) => {
      if (!x || typeof x !== 'object' || Array.isArray(x)) return null
      const obj = x as Record<string, unknown>
      if (typeof obj.code !== 'string' || typeof obj.title !== 'string') return null
      return { code: obj.code, title: obj.title }
    })
    .filter((x): x is Icd11Diagnosis => x !== null)
})

async function fetchIcd11Suggestions (terms: string, maxList: number): Promise<Icd11Diagnosis[]> {
  const token = tokenCookie.value || ''
  const res = await $fetch<{ results?: Icd11Diagnosis[] }>(
    '/doctor/icd11/search',
    {
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
      query: { terms, maxList }
    }
  )

  if (!res || !Array.isArray(res.results)) return []
  return res.results
}

function selectFinalIcd11 (s: Icd11Diagnosis) {
  if (!s?.code || !s?.title) return
  const next: ClinicalNotesData = { ...props.modelValue }
  next.final_diagnosis_icd11 = { code: s.code, title: s.title }
  next.final_diagnosis = `${s.title} (ICD-11 ${s.code})`
  emit('update:modelValue', next)

  icd11FinalQuery.value = ''
  icd11FinalSuggestions.value = []
}

function clearFinalIcd11 () {
  const next: ClinicalNotesData = { ...props.modelValue }
  next.final_diagnosis_icd11 = null
  emit('update:modelValue', next)
}

function addDifferentialIcd11 (s: Icd11Diagnosis) {
  if (!s?.code || !s?.title) return
  const current = differentialIcd11.value
  if (current.some((x) => x.code === s.code)) return

  const nextDiagnoses = [...current, { code: s.code, title: s.title }]
  const next: ClinicalNotesData = { ...props.modelValue }
  next.differential_diagnoses_icd11 = nextDiagnoses
  next.differential_diagnosis = nextDiagnoses.map((d) => `${d.title} (ICD-11 ${d.code})`).join('\n')
  emit('update:modelValue', next)

  icd11DifferentialQuery.value = ''
  icd11DifferentialSuggestions.value = []
}

function removeDifferentialIcd11 (code: string) {
  const current = differentialIcd11.value
  const nextDiagnoses = current.filter((x) => x.code !== code)
  const next: ClinicalNotesData = { ...props.modelValue }
  next.differential_diagnoses_icd11 = nextDiagnoses.length ? nextDiagnoses : undefined
  next.differential_diagnosis = nextDiagnoses.map((d) => `${d.title} (ICD-11 ${d.code})`).join('\n')
  emit('update:modelValue', next)
}

watch(icd11FinalQuery, (q) => {
  const query = q.trim()
  if (icd11FinalSearchTimer) clearTimeout(icd11FinalSearchTimer)
  if (query.length < 2) {
    icd11FinalSuggestions.value = []
    return
  }

  icd11FinalSearchTimer = setTimeout(async () => {
    icd11FinalLoading.value = true
    try {
      icd11FinalSuggestions.value = await fetchIcd11Suggestions(query, 7)
    } catch (e) {
      icd11FinalSuggestions.value = []
    } finally {
      icd11FinalLoading.value = false
    }
  }, 450)
})

watch(icd11DifferentialQuery, (q) => {
  const query = q.trim()
  if (icd11DifferentialSearchTimer) clearTimeout(icd11DifferentialSearchTimer)
  if (query.length < 2) {
    icd11DifferentialSuggestions.value = []
    return
  }

  icd11DifferentialSearchTimer = setTimeout(async () => {
    icd11DifferentialLoading.value = true
    try {
      icd11DifferentialSuggestions.value = await fetchIcd11Suggestions(query, 7)
    } catch (e) {
      icd11DifferentialSuggestions.value = []
    } finally {
      icd11DifferentialLoading.value = false
    }
  }, 450)
})

function isGeneralExaminationObject (val: unknown): val is GeneralExaminationData {
  return typeof val === 'object' && val !== null && !Array.isArray(val)
}

function hasGeneralExaminationContent (val: unknown): boolean {
  if (typeof val === 'string') return val.trim().length > 0
  if (!isGeneralExaminationObject(val)) return false
  return Object.values(val).some((x) => typeof x === 'string' && x.trim().length > 0)
}

function hasSystemExaminationContent (val: unknown): boolean {
  if (!val) return false
  if (typeof val === 'string') return val.trim().length > 0
  if (typeof val !== 'object' || Array.isArray(val)) return false
  return Object.values(val as Record<string, unknown>).some((v) => typeof v === 'string' && v.trim().length > 0)
}

function getGeneralExaminationObject (): GeneralExaminationData | null {
  const mp = props.modelValue.management_plan
  if (typeof mp !== 'object' || !mp) return null
  const ipv = (mp as ManagementPlanData).in_person_visit
  if (!ipv || typeof ipv !== 'object') return null
  const ge = (ipv as InPersonVisitData).general_examination
  return isGeneralExaminationObject(ge) ? ge : null
}

function setGeneralExaminationField<K extends keyof GeneralExaminationData> (field: K, value: GeneralExaminationData[K] | '') {
  const currentMp = props.modelValue.management_plan
  const mp = typeof currentMp === 'object' && currentMp ? { ...currentMp } : {}

  const ipvCurrent = (mp as ManagementPlanData).in_person_visit
  const ipv = ipvCurrent && typeof ipvCurrent === 'object'
    ? { ...(ipvCurrent as InPersonVisitData) }
    : {}

  const geExisting = (ipv as InPersonVisitData).general_examination
  const ge = isGeneralExaminationObject(geExisting) ? { ...geExisting } : {}

  if (!value) {
    delete (ge as Partial<GeneralExaminationData>)[field]
  } else {
    ;(ge as Partial<GeneralExaminationData>)[field] = value as GeneralExaminationData[K]
  }

  const hasAny = Object.values(ge).some((x) => typeof x === 'string' && x.trim().length > 0)
  if (hasAny) {
    ;(ipv as InPersonVisitData).general_examination = ge
  } else {
    delete (ipv as InPersonVisitData).general_examination
  }

  ;(mp as ManagementPlanData).in_person_visit = ipv
  emit('update:modelValue', { ...props.modelValue, management_plan: mp })
}

const generalAppearanceOptions = [
  { label: 'Not set', value: '' },
  { label: 'Good', value: 'Good' },
  { label: 'Sick', value: 'Sick' },
  { label: 'Very sick', value: 'Very sick' },
] as const

const jaundiceOptions = [
  { label: 'Not set', value: '' },
  { label: 'Nil', value: 'Nil' },
  { label: 'Mild', value: 'Mild' },
  { label: 'Severe', value: 'Severe' },
] as const

const presentAbsentOptions = [
  { label: 'Not set', value: '' },
  { label: 'Present', value: 'Present' },
  { label: 'Absent', value: 'Absent' },
] as const

const oedemaOptions = [
  { label: 'Not set', value: '' },
  { label: 'Grade I', value: 'Grade I' },
  { label: 'Grade II', value: 'Grade II' },
  { label: 'Grade III', value: 'Grade III' },
  { label: 'Grade IV', value: 'Grade IV' },
] as const

const lymphadenopathyOptions = [
  { label: 'Not set', value: '' },
  { label: 'Nil', value: 'Nil' },
  { label: 'Present', value: 'Present' },
] as const

const dehydrationOptions = [
  { label: 'Not set', value: '' },
  { label: 'Nil', value: 'Nil' },
  { label: 'Some', value: 'Some' },
  { label: 'Severe', value: 'Severe' },
] as const

const appearanceValue = computed<string>({
  get: () => getGeneralExaminationObject()?.appearance ?? '',
  set: (val) => setGeneralExaminationField('appearance', val as GeneralExaminationData['appearance'] | '')
})

const jaundiceValue = computed<string>({
  get: () => getGeneralExaminationObject()?.jaundice ?? '',
  set: (val) => setGeneralExaminationField('jaundice', val as GeneralExaminationData['jaundice'] | '')
})

const anemiaValue = computed<string>({
  get: () => getGeneralExaminationObject()?.anemia ?? '',
  set: (val) => setGeneralExaminationField('anemia', val as GeneralExaminationData['anemia'] | '')
})

const cyanosisValue = computed<string>({
  get: () => getGeneralExaminationObject()?.cyanosis ?? '',
  set: (val) => setGeneralExaminationField('cyanosis', val as GeneralExaminationData['cyanosis'] | '')
})

const clubbingValue = computed<string>({
  get: () => getGeneralExaminationObject()?.clubbing ?? '',
  set: (val) => setGeneralExaminationField('clubbing', val as GeneralExaminationData['clubbing'] | '')
})

const oedemaValue = computed<string>({
  get: () => getGeneralExaminationObject()?.oedema ?? '',
  set: (val) => setGeneralExaminationField('oedema', val as GeneralExaminationData['oedema'] | '')
})

const lymphadenopathyValue = computed<string>({
  get: () => getGeneralExaminationObject()?.lymphadenopathy ?? '',
  set: (val) => setGeneralExaminationField('lymphadenopathy', val as GeneralExaminationData['lymphadenopathy'] | '')
})

const dehydrationValue = computed<string>({
  get: () => getGeneralExaminationObject()?.dehydration ?? '',
  set: (val) => setGeneralExaminationField('dehydration', val as GeneralExaminationData['dehydration'] | '')
})

const systemExaminationFieldDefs: Array<{ field: keyof SystemExaminationFields; label: string; hint?: string; placeholder?: string }> = [
  { field: 'cns', label: 'CNS', hint: 'Consciousness, response, motor, etc.', placeholder: 'e.g. GCS, focal signs...' },
  { field: 'respiratory', label: 'Respiratory', hint: 'RR, effort, air entry, wheeze/crackles', placeholder: 'Respiratory findings...' },
  { field: 'cardiovascular', label: 'Cardiovascular', hint: 'Pulse, rhythm, BP, heart sounds', placeholder: 'Cardiovascular findings...' },
  { field: 'abdomen', label: 'Abdomen', hint: 'Inspection, palpation, auscultation', placeholder: 'Abdominal findings...' },
  { field: 'musculoskeletal', label: 'Musculoskeletal', hint: 'Joints, limbs, ROM, tenderness', placeholder: 'MSK findings...' },
  { field: 'mental_state', label: 'Mental state', hint: 'Mood, affect, speech, behaviour', placeholder: 'Mental state findings...' },
  { field: 'ophthalmic', label: 'Ophthalmic', hint: 'Pupils, conjunctiva/sclera (if needed)', placeholder: 'Eye exam findings...' },
  { field: 'ent', label: 'ENT', hint: 'Throat, ear, nose', placeholder: 'ENT findings...' },
  { field: 'vocal', label: 'Vocal', hint: 'Voice quality, stridor (if present)', placeholder: 'Vocal findings...' },
  { field: 'dental', label: 'Dental', hint: 'Gums, teeth, oral lesions', placeholder: 'Dental findings...' },
]

const legacySystemExaminationString = computed(() => {
  const mp = props.modelValue.management_plan
  if (typeof mp !== 'object' || !mp) return ''
  const ipv = mp.in_person_visit
  if (!ipv || typeof ipv !== 'object') return ''
  const se = ipv.system_examination
  return typeof se === 'string' && se.trim() ? se : ''
})

function getSystemExaminationField (field: keyof SystemExaminationFields): string {
  const mp = props.modelValue.management_plan
  if (typeof mp !== 'object' || !mp) return ''
  const ipv = mp.in_person_visit
  if (!ipv || typeof ipv !== 'object') return ''
  const se = ipv.system_examination
  if (se && typeof se === 'object' && !Array.isArray(se)) {
    const v = (se as SystemExaminationFields)[field]
    return typeof v === 'string' ? v : ''
  }
  return ''
}

function setSystemExaminationField (field: keyof SystemExaminationFields, val: string) {
  const mp = typeof props.modelValue.management_plan === 'object' && props.modelValue.management_plan
    ? { ...props.modelValue.management_plan }
    : {}
  const ipv = mp.in_person_visit && typeof mp.in_person_visit === 'object' ? { ...mp.in_person_visit } : {}
  const prev = ipv.system_examination
  const base: SystemExaminationFields = prev && typeof prev === 'object' && !Array.isArray(prev) ? { ...(prev as SystemExaminationFields) } : {}
  const nextVal = val?.trim() || ''
  if (nextVal) {
    base[field] = nextVal
  } else {
    delete base[field]
  }
  const hasAnyField = Object.values(base).some((v) => typeof v === 'string' && v.trim().length > 0)
  if (hasAnyField) {
    ipv.system_examination = base
  } else if (typeof prev === 'string' && prev.trim()) {
    ipv.system_examination = prev
  } else {
    delete ipv.system_examination
  }
  mp.in_person_visit = ipv
  emit('update:modelValue', { ...props.modelValue, management_plan: mp })
}

const medicationFormOptions = [
  { label: 'Tablet', value: 'Tablet' },
  { label: 'Capsule', value: 'Capsule' },
  { label: 'Suppository', value: 'Suppository' },
  { label: 'Syrup', value: 'Syrup' },
] as const

function emptyPrescriptionMed (): PrescriptionMedicationRow {
  return { name: '', form: '', dosage: '', frequency: '', duration: '', instructions: '' }
}

function hasPrescriptionContent (p: unknown): boolean {
  if (!p || typeof p !== 'object') return false
  const meds = (p as ManagementPlanPrescription).medications
  if (!Array.isArray(meds)) return false
  return meds.some((m) => typeof m?.name === 'string' && m.name.trim().length > 0)
}

const prescriptionMedicationRows = computed((): PrescriptionMedicationRow[] => {
  const mp = props.modelValue.management_plan
  if (typeof mp !== 'object' || !mp) return [emptyPrescriptionMed()]
  const p = mp.prescription
  if (p && typeof p === 'object' && Array.isArray(p.medications) && p.medications.length) {
    return p.medications.map((m) => ({
      ...emptyPrescriptionMed(),
      name: m.name ?? '',
      form: m.form ?? '',
      dosage: m.dosage ?? '',
      frequency: m.frequency ?? '',
      duration: m.duration ?? '',
      instructions: m.instructions ?? '',
    }))
  }
  return [emptyPrescriptionMed()]
})

const prescriptionGeneralInstructions = computed(() => {
  const mp = props.modelValue.management_plan
  if (typeof mp !== 'object' || !mp?.prescription || typeof mp.prescription !== 'object') return ''
  return (mp.prescription as ManagementPlanPrescription).instructions || ''
})

const legacyFreeTextTreatment = computed(() => {
  const mp = props.modelValue.management_plan
  if (typeof mp !== 'object' || !mp?.treatment?.trim()) return ''
  return mp.treatment.trim()
})

function commitPrescription (next: ManagementPlanPrescription) {
  const mp = typeof props.modelValue.management_plan === 'object' && props.modelValue.management_plan
    ? { ...props.modelValue.management_plan }
    : {}
  const meds = next.medications.length ? next.medications.map((m) => ({
    name: m.name || '',
    form: m.form || '',
    dosage: m.dosage || '',
    frequency: m.frequency || '',
    duration: m.duration || '',
    instructions: m.instructions || '',
  })) : [emptyPrescriptionMed()]
  const instructions = next.instructions?.trim() || ''
  const hasAny = meds.some((m) => m.name.trim().length > 0) || Boolean(instructions)
  if (!hasAny) {
    delete mp.prescription
  } else {
    mp.prescription = { medications: meds, instructions }
  }
  emit('update:modelValue', { ...props.modelValue, management_plan: mp })
}

function updatePrescriptionMedField (i: number, field: keyof PrescriptionMedicationRow, value: string) {
  const rows = prescriptionMedicationRows.value.map((r, idx) => idx === i ? { ...r, [field]: value } : { ...r })
  commitPrescription({ medications: rows, instructions: prescriptionGeneralInstructions.value })
}

function addPrescriptionMedRow () {
  commitPrescription({
    medications: [...prescriptionMedicationRows.value, emptyPrescriptionMed()],
    instructions: prescriptionGeneralInstructions.value,
  })
}

function removePrescriptionMedRow (i: number) {
  const rows = prescriptionMedicationRows.value.filter((_, idx) => idx !== i)
  commitPrescription({
    medications: rows.length ? rows : [emptyPrescriptionMed()],
    instructions: prescriptionGeneralInstructions.value,
  })
}

function updatePrescriptionGeneralInstructions (val: string) {
  commitPrescription({
    medications: prescriptionMedicationRows.value,
    instructions: val,
  })
}

const MANAGEMENT_PLAN_OPTIONS = [
  { value: 'treatment', label: 'Treatment (Prescribe drugs or other interventions)' },
  { value: 'investigation_radiology', label: 'Investigation – Radiology (CT Scan, X-ray, MRI...)' },
  { value: 'investigation_laboratory', label: 'Investigation – Laboratory' },
  { value: 'investigation_interventional', label: 'Investigation – Interventional (Endoscopy...)' },
  { value: 'referrals', label: 'Referrals' },
  { value: 'in_person_visit', label: 'In-person visit (Doctor revisits history, examination)' },
] as const

const MP_FIELD_STEPS: Record<string, Array<{ key: string; label: string; hint?: string; placeholder?: string }>> = {
  treatment: [
    { key: 'treatment', label: 'Treatment (prescription)', hint: 'Structured prescription matching the issue-prescription form.', placeholder: '' },
  ],
  investigation_radiology: [
    { key: 'investigation_radiology', label: 'Investigation – Radiology', hint: 'CT Scan, X-ray, MRI, etc.', placeholder: 'Radiology investigations ordered...' },
  ],
  investigation_laboratory: [
    { key: 'investigation_laboratory', label: 'Investigation – Laboratory', placeholder: 'Lab tests ordered (blood, urine, etc.)...' },
  ],
  investigation_interventional: [
    { key: 'investigation_interventional', label: 'Investigation – Interventional', hint: 'Endoscopy, etc.', placeholder: 'Interventional investigations (endoscopy, etc.)...' },
  ],
  referrals: [
    { key: 'referrals', label: 'Referrals', placeholder: 'Specialist or other referrals...' },
  ],
  in_person_visit: [
    { key: 'in_person_visit_revisit_history', label: 'In-person visit: Doctor revisits history', hint: 'Doctor reviews patient medical history', placeholder: 'Notes on history review...' },
    { key: 'in_person_visit_general_examination', label: 'In-person visit: General examination', placeholder: 'General examination findings...' },
    {
      key: 'in_person_visit_system_examination',
      label: 'In-person visit: System examination',
      hint: 'Enter findings for each system in its own field.',
    },
  ],
}

type StepKey = keyof ClinicalNotesData | 'management_plan_select' | string

const BASE_STEP_DEFINITIONS: Array<{
  key: StepKey
  label: string
  hint?: string
  placeholder?: string
  required?: boolean
  showWhen?: (ctx: { patientAgeYears: number | null }) => boolean
}> = [
  { key: 'presenting_complaints', label: 'Presenting complaint(s)', required: true, placeholder: 'Describe the complaint…' },
  { key: 'history_of_presenting_complaint', label: 'History of presenting complaint', hint: 'Optional - if applicable', placeholder: 'History, onset, duration, associated symptoms...' },
  { key: 'review_of_systems', label: 'Review of Systems', required: true, placeholder: '' },
  { key: 'past_medical_history', label: 'Past medical history', required: true, placeholder: 'Chronic conditions, previous diagnoses...' },
  { key: 'past_surgical_history', label: 'Past surgical history', required: true, placeholder: 'Previous surgeries, procedures...' },
  { key: 'growth_and_development', label: 'Growth and Development', showWhen: (ctx) => ctx.patientAgeYears !== null && ctx.patientAgeYears <= 5, placeholder: 'Milestones, growth parameters (if child ≤5 years)...' },
  { key: 'immunization_history', label: 'Immunization history', showWhen: (ctx) => ctx.patientAgeYears !== null && ctx.patientAgeYears <= 5, placeholder: 'Vaccination status (if child ≤5 years)...' },
  { key: 'family_history', label: 'Family history', required: true, placeholder: 'Relevant family medical history...' },
  { key: 'social_history', label: 'Social history', required: true, placeholder: 'Lifestyle, occupation, substance use...' },
  { key: 'summary_of_history', label: 'Summary of history', required: true, placeholder: 'Concise summary of the history...' },
  { key: 'differential_diagnosis', label: 'Differential diagnosis', required: true, placeholder: 'Working diagnoses...' },
  { key: 'management_plan_select', label: 'Management plan: Select options', hint: 'Choose one or more. You can edit later.' },
  { key: 'investigation_results', label: 'Investigation results', hint: 'Laboratory, imaging, and other investigation results available at this visit.', placeholder: 'Document investigation results (labs, radiology, point-of-care tests, etc.)...' },
  { key: 'final_diagnosis', label: 'Final diagnosis', required: true, placeholder: 'Confirmed diagnosis...' },
  { key: 'final_treatment', label: 'Final treatment', required: true, hint: 'Overall treatment plan and follow-up after diagnosis.', placeholder: 'Final treatment plan, follow-up, and patient advice...' },
  {
    key: 'outcome_doctor_notes',
    label: 'Outcome',
    hint: 'Clinical outcome, recovery, or follow-up. The patient can later report whether their symptoms improved.',
    placeholder: 'e.g. Resolving on treatment; review if fever returns...',
  },
]

const patientAgeYears = computed(() => {
  const dob = props.patientDateOfBirth
  if (!dob) return null
  const birth = new Date(dob)
  const now = new Date()
  const age = now.getFullYear() - birth.getFullYear()
  const monthDiff = now.getMonth() - birth.getMonth()
  if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < birth.getDate())) {
    return age - 1
  }
  return age
})

const selectedMpCategories = computed(() => {
  const mp = props.modelValue.management_plan
  if (typeof mp !== 'object' || !mp) return []
  const sel = mp.selected_categories
  if (Array.isArray(sel) && sel.length) return sel
  const derived: string[] = []
  if (mp.treatment?.trim()) derived.push('treatment')
  if (hasPrescriptionContent(mp.prescription) && !derived.includes('treatment')) derived.push('treatment')
  if (mp.investigation_radiology) derived.push('investigation_radiology')
  if (mp.investigation_laboratory) derived.push('investigation_laboratory')
  if (mp.investigation_interventional) derived.push('investigation_interventional')
  if (mp.referrals) derived.push('referrals')
  if (mp.in_person_visit && (
    mp.in_person_visit.revisit_history
    || hasGeneralExaminationContent(mp.in_person_visit.general_examination)
    || hasSystemExaminationContent(mp.in_person_visit.system_examination)
  )) {
    derived.push('in_person_visit')
  }
  return derived
})

function toggleMpCategory (value: string) {
  const mp = typeof props.modelValue.management_plan === 'object' && props.modelValue.management_plan
    ? { ...props.modelValue.management_plan }
    : {}
  const current = selectedMpCategories.value
  const next = current.includes(value) ? current.filter((c) => c !== value) : [...current, value]
  mp.selected_categories = next
  emit('update:modelValue', { ...props.modelValue, management_plan: mp })
}

const visibleSteps = computed(() => {
  const ctx = { patientAgeYears: patientAgeYears.value }
  const base = BASE_STEP_DEFINITIONS.filter((s) => s.showWhen == null || s.showWhen(ctx))
  const result: Array<{ key: StepKey; label: string; hint?: string; placeholder?: string }> = []
  for (const s of base) {
    if (s.key === 'management_plan_select') {
      result.push(s)
      for (const cat of selectedMpCategories.value) {
        const steps = MP_FIELD_STEPS[cat]
        if (steps) {
          for (const fs of steps) {
            result.push({ key: fs.key, label: fs.label, hint: fs.hint, placeholder: fs.placeholder })
          }
        }
      }
    } else {
      result.push(s)
    }
  }
  return result
})

const visibleStepIndex = ref(0)

const stepJumpOptions = computed(() =>
  visibleSteps.value.map((s, i) => ({
    value: i,
    label: `${i + 1}. ${s.label}`,
  }))
)

const stepBodyScrollEl = ref<HTMLElement | null>(null)

function onJumpToStep (val: unknown) {
  let i: number
  if (typeof val === 'number') {
    i = val
  } else if (val && typeof val === 'object' && 'value' in val && typeof (val as { value: unknown }).value === 'number') {
    i = (val as { value: number }).value
  } else {
    return
  }
  if (!Number.isFinite(i) || i < 0 || i >= visibleSteps.value.length) return
  visibleStepIndex.value = i
  void nextTick(() => {
    stepBodyScrollEl.value?.scrollTo({ top: 0 })
  })
}

watch(visibleSteps, (steps) => {
  if (visibleStepIndex.value >= steps.length) {
    visibleStepIndex.value = Math.max(0, steps.length - 1)
  }
})

const currentStep = computed(() => visibleSteps.value[visibleStepIndex.value])

function getValueForKey (key: StepKey): string {
  if (!key) return ''
  if (key === 'outcome_doctor_notes') {
    const o = props.modelValue.outcome
    if (typeof o === 'object' && o && typeof o.doctor_notes === 'string') {
      return o.doctor_notes
    }
    return ''
  }
  if (key.startsWith('in_person_visit_')) {
    const mp = props.modelValue.management_plan
    if (typeof mp !== 'object' || !mp) return ''
    const ipv = mp.in_person_visit
    if (!ipv || typeof ipv !== 'object') return ''
    const subKey = key.replace('in_person_visit_', '') as keyof InPersonVisitData
    const v = (ipv as InPersonVisitData)[subKey]
    return typeof v === 'string' ? v : ''
  }
  if (key.startsWith('management_plan_')) {
    const mp = props.modelValue.management_plan
    if (typeof mp === 'string') return key === 'management_plan_treatment' ? mp : ''
    const subKey = key.replace('management_plan_', '') as keyof ManagementPlanData
    const mv = (mp as ManagementPlanData)?.[subKey]
    return typeof mv === 'string' ? mv : ''
  }
  if (key === 'presenting_complaints' || key === 'review_of_systems') return ''
  const plain = props.modelValue[key as keyof ClinicalNotesData]
  return typeof plain === 'string' ? plain : ''
}

const currentValue = computed(() => getValueForKey(currentStep.value?.key as StepKey))

function normalizePresentingComplaintRows (raw: ClinicalNotesData['presenting_complaints'], legacySingle?: string): PresentingComplaintRow[] {
  if (Array.isArray(raw) && raw.length > 0) {
    return raw.map((item) => {
      if (typeof item === 'string') {
        return { complaint: item, duration: '' }
      }
      if (item && typeof item === 'object' && !Array.isArray(item)) {
        const o = item as Record<string, unknown>
        return {
          complaint: typeof o.complaint === 'string' ? o.complaint : '',
          duration: typeof o.duration === 'string' ? o.duration : '',
        }
      }
      return { complaint: '', duration: '' }
    })
  }
  if (typeof legacySingle === 'string' && legacySingle.trim()) {
    return [{ complaint: legacySingle.trim(), duration: '' }]
  }
  return [{ complaint: '', duration: '' }]
}

function presentingComplaintsToLegacyString (rows: PresentingComplaintRow[]): string {
  const lines = rows
    .map((r) => {
      const c = (r.complaint ?? '').trim()
      const d = (r.duration ?? '').trim()
      if (!c && !d) return ''
      if (!d) return c
      if (!c) return `(duration: ${d})`
      return `${c} (duration: ${d})`
    })
    .filter(Boolean)
  if (lines.length === 0) return ''
  if (lines.length === 1) return lines[0]
  return lines.map((line, idx) => `${idx + 1}. ${line}`).join('\n')
}

const presentingComplaintRows = computed((): PresentingComplaintRow[] => {
  return normalizePresentingComplaintRows(
    props.modelValue.presenting_complaints,
    props.modelValue.presenting_complaint
  )
})

function commitPresentingComplaints (rows: PresentingComplaintRow[]) {
  const stored: Array<PresentingComplaintRow> = rows.map(r => ({
    complaint: (r.complaint ?? '').trim(),
    duration: (r.duration ?? '').trim(),
  }))
  emit('update:modelValue', {
    ...props.modelValue,
    presenting_complaints: stored,
    presenting_complaint: presentingComplaintsToLegacyString(stored) || undefined,
  })
}

function updatePresentingComplaintField (i: number, field: 'complaint' | 'duration', val: string) {
  const next = presentingComplaintRows.value.map((r, j) =>
    j === i ? { ...r, [field]: typeof val === 'string' ? val : '' } : { ...r }
  )
  commitPresentingComplaints(next)
}

function addPresentingComplaintRow () {
  commitPresentingComplaints([...presentingComplaintRows.value, { complaint: '', duration: '' }])
}

function removePresentingComplaintRow (i: number) {
  if (presentingComplaintRows.value.length <= 1) return
  const next = presentingComplaintRows.value.filter((_, j) => j !== i)
  commitPresentingComplaints(next.length ? next : [{ complaint: '', duration: '' }])
}

const reviewOfSystemsFieldDefs: Array<{
  key: ReviewOfSystemsFieldKey
  label: string
  placeholder: string
}> = [
  { key: 'cns', label: 'Central nervous system', placeholder: 'Headache, weakness, sensory changes, seizures…' },
  { key: 'respiratory', label: 'Respiratory system', placeholder: 'Cough, dyspnea, wheeze, chest pain…' },
  { key: 'cardiovascular', label: 'Cardiovascular system', placeholder: 'Chest pain, palpitations, edema, claudication…' },
  { key: 'digestive', label: 'Digestive system', placeholder: 'Nausea, vomiting, abdominal pain, change in bowel habit…' },
  { key: 'genitourinary', label: 'Genital–urinary system', placeholder: 'Dysuria, hematuria, discharge, menstrual…' },
  { key: 'locomotor', label: 'Locomotor system', placeholder: 'Joint pain, swelling, back pain, stiffness…' },
  { key: 'other', label: 'Other systems', placeholder: 'Endocrine, skin, HEENT, or other relevant systems…' },
]

function emptyReviewOfSystems (): ReviewOfSystemsFields {
  return {
    cns: '',
    respiratory: '',
    cardiovascular: '',
    digestive: '',
    genitourinary: '',
    locomotor: '',
    other: '',
  }
}

function normalizeReviewOfSystemsFields (raw: ClinicalNotesData['review_of_systems']): ReviewOfSystemsFields {
  const e = emptyReviewOfSystems()
  if (typeof raw === 'string') {
    if (raw.trim()) e.other = raw
    return e
  }
  if (!raw || typeof raw !== 'object' || Array.isArray(raw)) return e
  const o = raw as Record<string, unknown>
  for (const k of Object.keys(e) as ReviewOfSystemsFieldKey[]) {
    const v = o[k]
    if (typeof v === 'string') e[k] = v
  }
  return e
}

function getReviewOfSystemsField (key: ReviewOfSystemsFieldKey): string {
  return normalizeReviewOfSystemsFields(props.modelValue.review_of_systems)[key] ?? ''
}

function setReviewOfSystemsField (key: ReviewOfSystemsFieldKey, val: string) {
  const merged = normalizeReviewOfSystemsFields(props.modelValue.review_of_systems)
  merged[key] = typeof val === 'string' ? val : ''
  const trimmed: ReviewOfSystemsFields = {}
  for (const k of Object.keys(merged) as ReviewOfSystemsFieldKey[]) {
    const t = (merged[k] ?? '').trim()
    if (t) trimmed[k] = t
  }
  emit('update:modelValue', {
    ...props.modelValue,
    review_of_systems: Object.keys(trimmed).length ? trimmed : undefined,
  })
}

const progressPercent = computed(() => {
  if (visibleSteps.value.length === 0) return 0
  return ((visibleStepIndex.value + 1) / visibleSteps.value.length) * 100
})

function onInput (val: string) {
  const key = currentStep.value?.key as StepKey
  if (!key) return
  if (key === 'outcome_doctor_notes') {
    const prev = typeof props.modelValue.outcome === 'object' && props.modelValue.outcome
      ? { ...props.modelValue.outcome }
      : {}
    const t = typeof val === 'string' ? val.trim() : ''
    if (t) {
      prev.doctor_notes = val
    } else {
      delete prev.doctor_notes
    }
    const next: ClinicalNotesData = { ...props.modelValue }
    if (Object.keys(prev).length === 0) {
      delete next.outcome
    } else {
      next.outcome = prev
    }
    emit('update:modelValue', next)
    return
  }
  const mp = typeof props.modelValue.management_plan === 'object' && props.modelValue.management_plan
    ? { ...props.modelValue.management_plan }
    : {}
  if (key.startsWith('in_person_visit_')) {
    if (key === 'in_person_visit_system_examination' || key === 'in_person_visit_general_examination') return
    const subKey = key.replace('in_person_visit_', '') as keyof InPersonVisitData
    const ipv = mp.in_person_visit ? { ...mp.in_person_visit } : {}
    ipv[subKey] = val || ''
    mp.in_person_visit = ipv
    emit('update:modelValue', { ...props.modelValue, management_plan: mp })
  } else if (['investigation_radiology', 'investigation_laboratory', 'investigation_interventional', 'referrals'].includes(key)) {
    ;(mp as Record<string, string>)[key] = val || ''
    emit('update:modelValue', { ...props.modelValue, management_plan: mp })
  } else {
    emit('update:modelValue', { ...props.modelValue, [key]: val || '' })
  }
}

function prevStep () {
  if (visibleStepIndex.value > 0) {
    visibleStepIndex.value--
  }
}

async function nextStep () {
  if (visibleStepIndex.value < visibleSteps.value.length - 1) {
    visibleStepIndex.value++
  }
}

async function saveAndClose () {
  if (!props.onSave) {
    emit('done')
    return
  }
  saving.value = true
  try {
    await props.onSave(props.modelValue)
    emit('done')
  } finally {
    saving.value = false
  }
}

async function saveDraft () {
  if (!props.onSave) return
  saving.value = true
  try {
    await props.onSave(props.modelValue)
  } finally {
    saving.value = false
  }
}

function hasAnyContent (data: ClinicalNotesData): boolean {
  for (const [k, v] of Object.entries(data)) {
    if (k === 'management_plan') {
      if (typeof v === 'string') return v.trim().length > 0
      if (v && typeof v === 'object') {
        const hasNonEmptyValue = (val: unknown): boolean => {
          if (val == null) return false
          if (typeof val === 'string') return val.trim().length > 0
          if (Array.isArray(val)) return val.some(hasNonEmptyValue)
          if (typeof val === 'object') return Object.values(val as Record<string, unknown>).some(hasNonEmptyValue)
          return Boolean(val)
        }
        if (hasNonEmptyValue(v)) return true
      }
    } else if (k === 'presenting_complaints' && Array.isArray(v)) {
      const hasRow = v.some((x) => {
        if (typeof x === 'string') return x.trim().length > 0
        if (x && typeof x === 'object' && !Array.isArray(x)) {
          const c = (x as PresentingComplaintRow).complaint
          const d = (x as PresentingComplaintRow).duration
          return (typeof c === 'string' && c.trim().length > 0)
            || (typeof d === 'string' && d.trim().length > 0)
        }
        return false
      })
      if (hasRow) return true
    } else if (k === 'review_of_systems' && v && typeof v === 'object' && !Array.isArray(v)) {
      if (Object.values(v as Record<string, unknown>).some(
        x => typeof x === 'string' && x.trim().length > 0
      )) return true
    } else if (k === 'outcome' && v && typeof v === 'object' && !Array.isArray(v)) {
      const doc = (v as ClinicalOutcomeNotes).doctor_notes
      if (typeof doc === 'string' && doc.trim().length > 0) return true
    } else if (v && typeof v === 'string' && v.trim()) {
      return true
    }
  }
  return false
}

async function saveOnStepChange () {
  if (props.onSave && hasAnyContent(props.modelValue)) {
    await saveDraft()
  }
}

watch(visibleStepIndex, () => {
  saveOnStepChange()
})

defineExpose({ saveDraft })
</script>
