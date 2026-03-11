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
export interface InPersonVisitData {
  revisit_history?: string
  general_examination?: string
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
  management_plan?: ManagementPlanData | string
  final_diagnosis?: string
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
    { key: 'in_person_visit_system_examination', label: 'In-person visit: System examination', placeholder: 'System-specific examination findings...' },
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
  if (mp.in_person_visit && (mp.in_person_visit.revisit_history || mp.in_person_visit.general_examination || mp.in_person_visit.system_examination)) {
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
    if (k === 'management_plan' && v && typeof v === 'object') {
      if (Object.values(v).some((x) => x && String(x).trim())) return true
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
