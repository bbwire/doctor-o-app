<template>
  <div class="clinical-notes-form flex flex-col h-full">
    <!-- Progress -->
    <div class="shrink-0 flex items-center gap-1 px-2 py-2 border-b border-gray-700">
      <span class="text-xs text-gray-400">
        Step {{ visibleStepIndex + 1 }} of {{ visibleSteps.length }}
      </span>
      <div class="flex-1 h-1.5 rounded-full bg-gray-800 overflow-hidden">
        <div
          class="h-full bg-primary-500 transition-all duration-300"
          :style="{ width: `${progressPercent}%` }"
        />
      </div>
    </div>

    <!-- Current step content -->
    <div class="flex-1 min-h-0 flex flex-col overflow-hidden p-4">
      <h3 class="text-sm font-semibold text-gray-200 mb-2">
        {{ currentStep?.label }}
      </h3>
      <p v-if="currentStep?.hint" class="text-xs text-gray-500 mb-3">
        {{ currentStep.hint }}
      </p>

      <!-- Multi-select for management plan categories -->
      <div v-if="currentStep?.key === 'management_plan_select'" class="flex-1 min-h-0 flex flex-col overflow-y-auto">
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
      <div v-else-if="currentStep?.key === 'final_diagnosis'" class="flex-1 min-h-0 flex flex-col overflow-y-auto">
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

      <div v-else-if="currentStep?.key === 'differential_diagnosis'" class="flex-1 min-h-0 flex flex-col overflow-y-auto">
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

      <div v-else class="flex-1 min-h-0 flex flex-col">
        <UTextarea
          :model-value="currentValue"
          :placeholder="currentStep?.placeholder"
          :rows="6"
          class="flex-1 min-h-[120px] resize-none"
          @update:model-value="onInput"
        />
      </div>

      <!-- Navigation -->
      <div class="flex justify-between gap-2 mt-4 shrink-0">
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

export interface InPersonVisitData {
  revisit_history?: string
  general_examination?: GeneralExaminationData | string
  system_examination?: string
}

export interface ManagementPlanData {
  selected_categories?: string[]
  treatment?: string
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

export interface ClinicalNotesData {
  presenting_complaint?: string
  history_of_presenting_complaint?: string
  review_of_systems?: string
  past_medical_history?: string
  past_surgical_history?: string
  growth_and_development?: string
  immunization_history?: string
  family_history?: string
  social_history?: string
  summary_of_history?: string
  differential_diagnosis?: string
  differential_diagnoses_icd11?: Icd11Diagnosis[]
  management_plan?: ManagementPlanData | string
  final_diagnosis?: string
  final_diagnosis_icd11?: Icd11Diagnosis | null
}

const props = withDefaults(
  defineProps<{
    modelValue: ClinicalNotesData
    patientDateOfBirth?: string | null
    consultationId: string | number
    onSave?: (data: ClinicalNotesData) => Promise<void>
    compact?: boolean
  }>(),
  { compact: false }
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: ClinicalNotesData): void
  (e: 'done'): void
}>()

const saving = ref(false)

const config = useRuntimeConfig()
const tokenCookie = useCookie<string | null>('auth_token')

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
    { key: 'treatment', label: 'Treatment', hint: 'Prescribe drugs or other interventions', placeholder: 'Medications, dosages, duration, other interventions...' },
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
      hint: 'Free-text: cover CNS, Respiratory, Cardiovascular, Abdomen, Musculoskeletal, Mental state, Ophthalmic, ENT, Vocal, Dental.',
      placeholder: [
        'CNS: consciousness/response, motor, etc.',
        'Respiratory: RR, effort, air entry, wheeze/crackles',
        'Cardiovascular: pulse, rhythm, BP, heart sounds',
        'Abdomen: inspection/palpation/auscultation',
        'Musculoskeletal: joints/limbs, ROM, tenderness',
        'Mental state: mood/affect, speech/behavior',
        'Ophthalmic exam: pupils, conjunctiva/sclera (if needed)',
        'ENT exam: throat/ear/nose findings',
        'Vocal exam: voice quality/stridor (if present)',
        'Dental: gums/teeth/oral lesions'
      ].join('\n')
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
  { key: 'presenting_complaint', label: 'Presenting complaint', required: true, placeholder: 'Chief complaint or reason for visit...' },
  { key: 'history_of_presenting_complaint', label: 'History of presenting complaint', hint: 'Optional - if applicable', placeholder: 'History, onset, duration, associated symptoms...' },
  { key: 'review_of_systems', label: 'Review of Systems', required: true, placeholder: 'Relevant systems review...' },
  { key: 'past_medical_history', label: 'Past medical history', required: true, placeholder: 'Chronic conditions, previous diagnoses...' },
  { key: 'past_surgical_history', label: 'Past surgical history', required: true, placeholder: 'Previous surgeries, procedures...' },
  { key: 'growth_and_development', label: 'Growth and Development', showWhen: (ctx) => ctx.patientAgeYears !== null && ctx.patientAgeYears <= 5, placeholder: 'Milestones, growth parameters (if child ≤5 years)...' },
  { key: 'immunization_history', label: 'Immunization history', showWhen: (ctx) => ctx.patientAgeYears !== null && ctx.patientAgeYears <= 5, placeholder: 'Vaccination status (if child ≤5 years)...' },
  { key: 'family_history', label: 'Family history', required: true, placeholder: 'Relevant family medical history...' },
  { key: 'social_history', label: 'Social history', required: true, placeholder: 'Lifestyle, occupation, substance use...' },
  { key: 'summary_of_history', label: 'Summary of history', required: true, placeholder: 'Concise summary of the history...' },
  { key: 'differential_diagnosis', label: 'Differential diagnosis', required: true, placeholder: 'Working diagnoses...' },
  { key: 'management_plan_select', label: 'Management plan: Select options', hint: 'Choose one or more. You can edit later.' },
  { key: 'final_diagnosis', label: 'Final diagnosis', required: true, placeholder: 'Confirmed diagnosis...' },
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
  if (mp.treatment) derived.push('treatment')
  if (mp.investigation_radiology) derived.push('investigation_radiology')
  if (mp.investigation_laboratory) derived.push('investigation_laboratory')
  if (mp.investigation_interventional) derived.push('investigation_interventional')
  if (mp.referrals) derived.push('referrals')
  if (mp.in_person_visit && (mp.in_person_visit.revisit_history || hasGeneralExaminationContent(mp.in_person_visit.general_examination) || mp.in_person_visit.system_examination)) {
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

const currentStepIndex = ref(0)
const visibleStepIndex = ref(0)

const currentStep = computed(() => visibleSteps.value[visibleStepIndex.value])

function getValueForKey (key: StepKey): string {
  if (!key) return ''
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
    return (mp as ManagementPlanData)?.[subKey] ?? ''
  }
  return (props.modelValue[key as keyof ClinicalNotesData] as string) ?? ''
}

const currentValue = computed(() => getValueForKey(currentStep.value?.key as StepKey))

const progressPercent = computed(() => {
  if (visibleSteps.value.length === 0) return 0
  return ((visibleStepIndex.value + 1) / visibleSteps.value.length) * 100
})

function onInput (val: string) {
  const key = currentStep.value?.key as StepKey
  if (!key) return
  const mp = typeof props.modelValue.management_plan === 'object' && props.modelValue.management_plan
    ? { ...props.modelValue.management_plan }
    : {}
  if (key.startsWith('in_person_visit_')) {
    const subKey = key.replace('in_person_visit_', '') as keyof InPersonVisitData
    const ipv = mp.in_person_visit ? { ...mp.in_person_visit } : {}
    ipv[subKey] = val || ''
    mp.in_person_visit = ipv
    emit('update:modelValue', { ...props.modelValue, management_plan: mp })
  } else if (['treatment', 'investigation_radiology', 'investigation_laboratory', 'investigation_interventional', 'referrals'].includes(key)) {
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
