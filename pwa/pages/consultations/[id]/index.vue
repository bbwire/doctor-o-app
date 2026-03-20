<template>
  <div class="space-y-6">
    <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="min-w-0">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Consultation Details</h1>
        <p class="text-sm sm:text-base text-gray-600 dark:text-gray-300">View consultation information and progress timeline</p>
      </div>

      <UButton to="/consultations" variant="ghost" icon="i-lucide-arrow-left" class="self-start sm:self-auto shrink-0">
        Back to consultations
      </UButton>
    </div>

    <UAlert
      v-if="errorMessage"
      icon="i-lucide-alert-triangle"
      color="red"
      variant="soft"
      :title="errorMessage"
    />

    <div v-else-if="loading" class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
      Loading consultation details...
    </div>

    <div v-else-if="consultation" class="space-y-5">
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
          <div class="min-w-0">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(consultation.scheduled_at) }}</p>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
              Dr. {{ consultation.doctor?.name || 'Unknown Doctor' }}
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 capitalize">
              {{ consultation.consultation_type }} consultation
            </p>
          </div>

          <UBadge :color="statusColor(consultation.status)" variant="soft">
            {{ consultation.status }}
          </UBadge>
        </div>

        <div v-if="consultation.status === 'scheduled'" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-800">
          <p class="text-sm font-medium text-gray-900 dark:text-white mb-3">Manage Appointment</p>
          <div class="flex flex-col sm:flex-row sm:flex-wrap gap-2 mb-4">
            <UButton
              :icon="joinConsultationIcon"
              size="sm"
              class="w-full sm:w-auto justify-center"
              @click="showConsentModal = true"
            >
              Join {{ consultation.consultation_type }} consultation
            </UButton>
          </div>

          <UModal v-model="showConsentModal" :ui="{ width: 'max-w-md' }">
            <UCard :ui="{ ring: '', divide: 'divide-y divide-gray-200 dark:divide-gray-800' }">
              <template #header>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Consent to consultation</h3>
              </template>
              <p class="text-sm text-gray-600 dark:text-gray-300">
                By joining this consultation you consent to the collection and use of your data (including voice and video where applicable) for the purpose of this consultation and related documentation, in line with our
                <NuxtLink to="/privacy" target="_blank" class="text-primary-600 dark:text-primary-400 hover:underline">Privacy Policy</NuxtLink>.
              </p>
              <template #footer>
                <div class="flex justify-end gap-2">
                  <UButton variant="ghost" color="neutral" @click="showConsentModal = false">
                    Cancel
                  </UButton>
                  <UButton
                    :icon="joinConsultationIcon"
                    @click="onConsentAndJoin"
                  >
                    I agree & Join
                  </UButton>
                </div>
              </template>
            </UCard>
          </UModal>
          <div class="grid gap-3 grid-cols-1 md:grid-cols-[1fr_auto_auto] md:items-end">
            <UFormGroup label="New Date & Time">
              <UInput v-model="rescheduleAt" type="datetime-local" :min="minimumRescheduleDateTime" />
            </UFormGroup>

            <UButton
              color="red"
              variant="soft"
              icon="i-lucide-x-circle"
              :loading="actionLoading"
              :disabled="actionLoading || isApiOffline"
              class="w-full md:w-auto justify-center"
              @click="cancelConsultation"
            >
              Cancel
            </UButton>

            <UButton
              icon="i-lucide-calendar-sync"
              :loading="actionLoading"
              :disabled="actionLoading || isApiOffline || !rescheduleAt"
              class="w-full md:w-auto justify-center"
              @click="rescheduleConsultation"
            >
              Reschedule
            </UButton>
          </div>

          <div v-if="suggestedSlots.length" class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/20">
            <p class="text-xs font-medium text-amber-800 dark:text-amber-300 mb-2">
              Suggested available slots
            </p>
            <div class="flex flex-wrap gap-2">
              <UButton
                v-for="slot in suggestedSlots"
                :key="slot"
                size="xs"
                color="amber"
                variant="soft"
                @click="applySuggestedSlot(slot)"
              >
                {{ formatDate(slot) }}
              </UButton>
            </div>
          </div>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2">
          <div class="rounded-lg bg-gray-50 dark:bg-gray-800/60 p-3">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Reason</p>
            <div
              class="text-sm text-gray-800 dark:text-gray-200 mt-1 prose prose-sm prose-slate dark:prose-invert max-w-none"
              v-html="consultation.reason || '<p>No reason provided</p>'"
            />
          </div>
          <div class="rounded-lg bg-gray-50 dark:bg-gray-800/60 p-3">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Notes</p>
            <p class="text-sm text-gray-800 dark:text-gray-200 mt-1">
              {{ consultation.notes || 'No notes yet' }}
            </p>
          </div>
        </div>
      </UCard>

      <!-- Consultation summary (patient-facing: summary of history, differential diagnosis, management plan) -->
      <UCard
        v-if="consultation.status === 'completed' && hasConsultationSummary"
        :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }"
      >
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-base font-semibold text-gray-900 dark:text-white">Consultation summary</h3>
          <UButton
            size="sm"
            icon="i-lucide-download"
            :loading="downloadingSummary"
            @click="downloadConsultationSummary"
          >
            Download PDF
          </UButton>
        </div>
        <div class="space-y-4">
          <div v-if="consultation.consultation_summary?.summary_of_history">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Summary of history</p>
            <p class="text-sm text-gray-800 dark:text-gray-200 mt-1 whitespace-pre-line">
              {{ consultation.consultation_summary.summary_of_history }}
            </p>
          </div>
          <div v-if="consultation.consultation_summary?.differential_diagnosis">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Differential diagnosis</p>
            <p class="text-sm text-gray-800 dark:text-gray-200 mt-1 whitespace-pre-line">
              {{ consultation.consultation_summary.differential_diagnosis }}
            </p>
          </div>
          <div v-if="consultation.consultation_summary?.final_diagnosis">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Final diagnosis</p>
            <p class="text-sm text-gray-800 dark:text-gray-200 mt-1 whitespace-pre-line">
              {{ consultation.consultation_summary.final_diagnosis }}
            </p>
          </div>
          <div v-if="hasStructuredPatientMp" class="space-y-2">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Management plan</p>
            <div v-if="patientMp.treatment" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
              <p class="text-xs text-gray-500 dark:text-gray-400">Treatment</p>
              <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ patientMp.treatment }}</p>
            </div>
            <div v-if="patientMp.investigation_radiology || patientMp.investigation_laboratory || patientMp.investigation_interventional" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
              <p class="text-xs text-gray-500 dark:text-gray-400">Investigation</p>
              <p v-if="patientMp.investigation_radiology" class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">Radiology: {{ patientMp.investigation_radiology }}</p>
              <p v-if="patientMp.investigation_laboratory" class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">Laboratory: {{ patientMp.investigation_laboratory }}</p>
              <p v-if="patientMp.investigation_interventional" class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">Interventional: {{ patientMp.investigation_interventional }}</p>
            </div>
            <div v-if="patientMp.referrals" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
              <p class="text-xs text-gray-500 dark:text-gray-400">Referrals</p>
              <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ patientMp.referrals }}</p>
            </div>
            <div v-if="hasStructuredPatientIpv" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
              <p class="text-xs text-gray-500 dark:text-gray-400">In-person visit</p>
              <div v-if="patientIpv.revisit_history" class="mt-1">
                <p class="text-xs text-gray-500 dark:text-gray-400">Doctor revisits history</p>
                <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ patientIpv.revisit_history }}</p>
              </div>
              <div v-if="hasGeneralExaminationContent(patientIpv.general_examination)" class="mt-1">
                <p class="text-xs text-gray-500 dark:text-gray-400">General examination</p>
                <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ formatGeneralExamination(patientIpv.general_examination) }}</p>
              </div>
              <div v-if="patientIpv.system_examination" class="mt-1">
                <p class="text-xs text-gray-500 dark:text-gray-400">System examination</p>
                <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ patientIpv.system_examination }}</p>
              </div>
            </div>
          </div>
          <div v-else-if="consultation.consultation_summary?.management_plan && typeof consultation.consultation_summary.management_plan === 'string'" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Management plan</p>
            <p class="text-sm text-gray-800 dark:text-gray-200 mt-1 whitespace-pre-line">
              {{ consultation.consultation_summary.management_plan }}
            </p>
          </div>
        </div>
      </UCard>

      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Status Timeline</h3>

        <div class="mt-4 space-y-3">
          <div
            v-for="step in timelineSteps"
            :key="step.key"
            class="flex items-start gap-3 rounded-lg border p-3"
            :class="step.reached ? 'border-primary-200 bg-primary-50/60 dark:border-primary-700 dark:bg-primary-900/10' : 'border-gray-200 dark:border-gray-800'"
          >
            <div
              class="mt-0.5 h-6 w-6 rounded-full flex items-center justify-center text-xs font-semibold"
              :class="step.reached ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
            >
              {{ step.index }}
            </div>

            <div class="flex-1">
              <p class="font-medium text-gray-900 dark:text-white">{{ step.title }}</p>
              <p class="text-sm text-gray-600 dark:text-gray-300">{{ step.description }}</p>
            </div>

            <UIcon
              v-if="step.reached"
              name="i-lucide-check"
              class="h-5 w-5 text-primary-600 dark:text-primary-400"
            />
          </div>
        </div>
      </UCard>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth'
})

interface InPersonVisitSummary {
  revisit_history?: string | null
  general_examination?: Record<string, unknown> | string | null
  system_examination?: string | null
}

interface ManagementPlanSummary {
  treatment?: string | null
  investigation_radiology?: string | null
  investigation_laboratory?: string | null
  investigation_interventional?: string | null
  referrals?: string | null
  in_person_visit?: InPersonVisitSummary | string | null
}

interface ConsultationSummary {
  summary_of_history?: string | null
  differential_diagnosis?: string | null
  management_plan?: ManagementPlanSummary | string | null
  final_diagnosis?: string | null
}

interface ConsultationItem {
  id: number
  scheduled_at: string
  consultation_type: 'text' | 'audio' | 'video'
  status: 'scheduled' | 'completed' | 'cancelled'
  reason?: string
  notes?: string
  consultation_summary?: ConsultationSummary | null
  doctor?: {
    id?: number
    name?: string
  }
}

type TimelineStep = {
  key: string
  index: number
  title: string
  description: string
  reached: boolean
}

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const tokenCookie = useCookie<string | null>('auth_token')
const { isApiReachable, hasApiStatusChecked } = useApiHealth()
const toast = useToast()
const { formatDate, formatDateTime } = useDateFormat()
const isApiOffline = computed(() => hasApiStatusChecked.value && !isApiReachable.value)

const loading = ref(true)
const actionLoading = ref(false)
const errorMessage = ref('')
const retryWhenOnline = ref(false)
const reconnectRetryInProgress = ref(false)
const consultation = ref<ConsultationItem | null>(null)
const rescheduleAt = ref('')
const suggestedSlots = ref<string[]>([])
const showConsentModal = ref(false)
const downloadingSummary = ref(false)

const consultationId = computed(() => route.params.id)

const hasConsultationSummary = computed(() => {
  const s = consultation.value?.consultation_summary
  if (!s) return false
  const hasMp = hasStructuredPatientMp.value
  return !!(s.summary_of_history || s.differential_diagnosis || hasMp || s.final_diagnosis)
})

const patientMp = computed(() => {
  const m = consultation.value?.consultation_summary?.management_plan
  return (typeof m === 'object' && m) ? m : {}
})

const patientIpv = computed(() =>
  (typeof patientMp.value.in_person_visit === 'object' && patientMp.value.in_person_visit)
    ? patientMp.value.in_person_visit
    : {}
)

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
    if (typeof v === 'string' && v.trim().length > 0) {
      lines.push(`${label}: ${v}`)
    }
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

const hasStructuredPatientIpv = computed(() =>
  !!(patientIpv.value.revisit_history || hasGeneralExaminationContent(patientIpv.value.general_examination) || patientIpv.value.system_examination)
)

const hasStructuredPatientMp = computed(() => {
  const m = patientMp.value
  if (m.treatment || m.investigation_radiology || m.investigation_laboratory || m.investigation_interventional || m.referrals) return true
  return hasStructuredPatientIpv.value
})
const apiHeaders = computed(() => ({
  Authorization: `Bearer ${tokenCookie.value || ''}`,
  Accept: 'application/json'
}))
const minimumRescheduleDateTime = computed(() => {
  const now = new Date()
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
  return now.toISOString().slice(0, 16)
})

const joinConsultationIcon = computed(() => {
  const type = consultation.value?.consultation_type
  switch (type) {
    case 'video': return 'i-lucide-video'
    case 'audio': return 'i-lucide-phone'
    case 'text': return 'i-lucide-message-square'
    default: return 'i-lucide-log-in'
  }
})

const timelineSteps = computed<TimelineStep[]>(() => {
  const status = consultation.value?.status
  const scheduledAt = consultation.value?.scheduled_at
    ? formatDateTime(consultation.value.scheduled_at)
    : 'Scheduled time not available'

  return [
    {
      key: 'booked',
      index: 1,
      title: 'Booked',
      description: 'Your consultation request was created successfully.',
      reached: Boolean(status)
    },
    {
      key: 'scheduled',
      index: 2,
      title: 'Scheduled',
      description: `Appointment time: ${scheduledAt}.`,
      reached: status === 'scheduled' || status === 'completed'
    },
    {
      key: 'completed',
      index: 3,
      title: status === 'cancelled' ? 'Cancelled' : 'Completed',
      description: status === 'cancelled'
        ? 'This consultation was cancelled.'
        : 'Consultation completed and ready for follow-up.',
      reached: status === 'completed' || status === 'cancelled'
    }
  ]
})

const fetchConsultation = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await $fetch<{ data: ConsultationItem }>(`/consultations/${consultationId.value}`, {
      baseURL: config.public.apiBase,
      headers: apiHeaders.value
    })

    consultation.value = response.data
    rescheduleAt.value = toLocalDateTimeInput(response.data.scheduled_at)
    suggestedSlots.value = []
    retryWhenOnline.value = false

    if (reconnectRetryInProgress.value) {
      toast.add({
        title: 'Connection restored',
        description: 'Consultation details have been refreshed.',
        color: 'green'
      })
    }
  } catch (error) {
    const err = error && typeof error === 'object' ? error : null

    if (hasApiStatusChecked.value && !isApiReachable.value) {
      retryWhenOnline.value = true
      errorMessage.value = 'API is currently unreachable. Details will retry when connection is restored.'
      return
    }

    if (err && 'status' in err && err.status === 404) {
      errorMessage.value = 'Consultation not found.'
      return
    }

    if (err && 'data' in err && typeof err.data === 'string') {
      errorMessage.value = 'Unexpected non-JSON response from API. Please sign in again and retry.'
      return
    }

    const message = err && 'data' in err && err.data && typeof err.data === 'object' && 'message' in err.data
      ? err.data.message
      : null
    errorMessage.value = typeof message === 'string' ? message : 'Unable to load consultation details.'
  } finally {
    loading.value = false
  }
}

const cancelConsultation = async () => {
  if (!consultation.value) {
    return
  }

  if (isApiOffline.value) {
    errorMessage.value = 'API is currently unreachable. Please retry when connection is restored.'
    return
  }

  actionLoading.value = true
  errorMessage.value = ''
  suggestedSlots.value = []

  try {
    const response = await $fetch<{ data: ConsultationItem }>(`/consultations/${consultation.value.id}/cancel`, {
      method: 'PATCH',
      baseURL: config.public.apiBase,
      headers: apiHeaders.value
    })

    consultation.value = response.data
    rescheduleAt.value = toLocalDateTimeInput(response.data.scheduled_at)

    toast.add({
      title: 'Consultation cancelled',
      description: 'Your appointment has been cancelled successfully.',
      color: 'green'
    })
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, 'Unable to cancel consultation.')
  } finally {
    actionLoading.value = false
  }
}

const rescheduleConsultation = async () => {
  if (!consultation.value || !rescheduleAt.value) {
    return
  }

  if (isApiOffline.value) {
    errorMessage.value = 'API is currently unreachable. Please retry when connection is restored.'
    return
  }

  actionLoading.value = true
  errorMessage.value = ''
  suggestedSlots.value = []

  try {
    const response = await $fetch<{ data: ConsultationItem }>(`/consultations/${consultation.value.id}/reschedule`, {
      method: 'PATCH',
      baseURL: config.public.apiBase,
      headers: apiHeaders.value,
      body: {
        scheduled_at: new Date(rescheduleAt.value).toISOString()
      }
    })

    consultation.value = response.data
    rescheduleAt.value = toLocalDateTimeInput(response.data.scheduled_at)

    toast.add({
      title: 'Consultation rescheduled',
      description: 'Your appointment date and time has been updated.',
      color: 'green'
    })
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, 'Unable to reschedule consultation.')

    if (errorMessage.value.includes('already booked') && consultation.value?.doctor?.id) {
      await fetchSuggestedSlots(consultation.value.doctor.id, new Date(rescheduleAt.value).toISOString())
    }
  } finally {
    actionLoading.value = false
  }
}

const fetchSuggestedSlots = async (doctorId: number, from: string) => {
  try {
    const response = await $fetch<{ data?: { available_slots?: string[] } }>(`/doctors/${doctorId}/availability`, {
      baseURL: config.public.apiBase,
      headers: apiHeaders.value,
      query: {
        from,
        limit: 5
      }
    })

    suggestedSlots.value = response?.data?.available_slots || []
  } catch {
    suggestedSlots.value = []
  }
}

const applySuggestedSlot = (slot: string) => {
  rescheduleAt.value = toLocalDateTimeInput(slot)
}

const extractErrorMessage = (error: unknown, fallback: string): string => {
  const err = error && typeof error === 'object' ? error : null
  const validationErrors = err && 'data' in err && err.data && typeof err.data === 'object' && 'errors' in err.data
    ? err.data.errors
    : null

  if (validationErrors && typeof validationErrors === 'object') {
    const firstKey = Object.keys(validationErrors)[0]
    const firstErrors = firstKey ? validationErrors[firstKey as keyof typeof validationErrors] : null
    if (Array.isArray(firstErrors) && typeof firstErrors[0] === 'string') {
      return firstErrors[0]
    }
  }

  const message = err && 'data' in err && err.data && typeof err.data === 'object' && 'message' in err.data
    ? err.data.message
    : null

  return typeof message === 'string' ? message : fallback
}

const toLocalDateTimeInput = (value: string) => {
  const date = new Date(value)
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset())
  return date.toISOString().slice(0, 16)
}

const statusColor = (status: ConsultationItem['status']) => {
  if (status === 'scheduled') return 'blue'
  if (status === 'completed') return 'green'
  return 'gray'
}

async function downloadConsultationSummary () {
  if (!consultation.value?.id) return
  downloadingSummary.value = true
  try {
    const url = `${config.public.apiBase}/consultations/${consultation.value.id}/summary/download`
    const res = await fetch(url, {
      headers: { Authorization: `Bearer ${tokenCookie.value || ''}` }
    })
    if (!res.ok) throw new Error('Download failed')
    const blob = await res.blob()
    const a = document.createElement('a')
    a.href = URL.createObjectURL(blob)
    a.download = `consultation-${consultation.value.id}-summary.pdf`
    a.click()
    URL.revokeObjectURL(a.href)
    toast.add({ title: 'Download started', color: 'green' })
  } catch {
    toast.add({ title: 'Download failed', description: 'Summary may not be available yet.', color: 'red' })
  } finally {
    downloadingSummary.value = false
  }
}

function onConsentAndJoin () {
  if (!consultation.value) return
  showConsentModal.value = false
  router.push(`/consultations/${consultation.value.id}/room`)
}

watch(consultationId, async () => {
  await fetchConsultation()
})

watch(rescheduleAt, () => {
  if (suggestedSlots.value.length > 0) {
    suggestedSlots.value = []
  }
})

onMounted(async () => {
  await fetchConsultation()
})

watch([isApiReachable, hasApiStatusChecked], async ([reachable, checked]) => {
  if (checked && reachable && retryWhenOnline.value && !loading.value) {
    reconnectRetryInProgress.value = true
    await fetchConsultation()
    reconnectRetryInProgress.value = false
  }
})
</script>
