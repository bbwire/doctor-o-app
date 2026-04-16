<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Book Consultation</h1>
      <p class="text-gray-600 dark:text-gray-300">Schedule your next consultation</p>
    </div>

    <section>
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div class="space-y-4">
          <ApiOfflineInlineHint />

          <UAlert
            v-if="errorMessage"
            icon="i-lucide-alert-triangle"
            color="red"
            variant="soft"
            :title="errorMessage"
          />

          <UForm :state="state" class="space-y-4" @submit="onSubmit">
            <UFormGroup label="Category" required>
              <USelectMenu
                v-model="state.category"
                :options="categoryOptions"
                option-attribute="label"
                value-attribute="value"
                placeholder="Select a healthcare category"
                @change="onCategoryChange"
              />
            </UFormGroup>

            <UFormGroup v-if="state.category" label="Choose a doctor (optional)">
              <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                Tap a doctor to book with them, or choose <span class="font-medium">any available doctor</span> to join the waiting room for <span class="font-medium">{{ state.category }}</span>.
              </p>

              <div v-if="loadingDoctors" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div
                  v-for="n in 3"
                  :key="n"
                  class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-100/80 dark:bg-gray-800/50 animate-pulse h-48"
                />
              </div>

              <div v-else class="space-y-3">
                <button
                  type="button"
                  class="group w-full text-left rounded-2xl border-2 p-4 transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900"
                  :class="waitingRoomSelected
                    ? 'border-primary-500 bg-primary-50/90 dark:bg-primary-950/40 shadow-md shadow-primary-500/10'
                    : 'border-dashed border-gray-300 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-800/30 hover:border-primary-400 dark:hover:border-primary-600'"
                  @click="selectWaitingRoom"
                >
                  <div class="flex items-start gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300">
                      <UIcon name="i-lucide-users" class="h-6 w-6" />
                    </div>
                    <div class="min-w-0 flex-1">
                      <p class="font-semibold text-gray-900 dark:text-white">
                        Any available doctor
                      </p>
                      <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">
                        First available clinician in this category will accept your request.
                      </p>
                      <div
                        v-if="state.category"
                        class="mt-3 rounded-xl border border-primary-200/80 bg-white/80 px-3 py-2 dark:border-primary-800/60 dark:bg-gray-900/60"
                      >
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">
                          Typical consultation fee
                        </p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white tabular-nums">
                          {{ formatConsultationFee(waitingRoomFeeDisplay) }}
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                          {{ waitingRoomFeeCaption }}
                        </p>
                      </div>
                    </div>
                    <UIcon
                      v-if="waitingRoomSelected"
                      name="i-lucide-circle-check"
                      class="h-6 w-6 shrink-0 text-primary-600 dark:text-primary-400"
                    />
                  </div>
                </button>

                <div
                  v-if="filteredDoctors.length === 0"
                  class="rounded-xl border border-amber-200 bg-amber-50/80 px-4 py-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200"
                >
                  No listed doctors for this category yet. You can still book with <strong>any available doctor</strong> above.
                </div>

                <div
                  v-else
                  class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
                >
                  <button
                    v-for="doctor in filteredDoctors"
                    :key="doctor.id"
                    type="button"
                    class="group text-left rounded-2xl border-2 p-4 transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 hover:shadow-lg hover:shadow-gray-900/5 dark:hover:shadow-black/20"
                    :class="state.doctor_id === doctor.id
                      ? 'border-primary-500 bg-white dark:bg-gray-900 ring-2 ring-primary-500/30'
                      : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900/80 hover:border-primary-300 dark:hover:border-primary-700'"
                    @click="selectDoctor(doctor.id)"
                  >
                    <div class="flex gap-3">
                      <div class="relative shrink-0">
                        <img
                          v-if="doctorPhotoUrl(doctor)"
                          :src="doctorPhotoUrl(doctor)!"
                          :alt="doctor.name"
                          class="h-20 w-20 rounded-xl object-cover ring-2 ring-gray-100 dark:ring-gray-800"
                          loading="lazy"
                          @error="onDoctorImgError(doctor.id)"
                        >
                        <div
                          v-else
                          class="flex h-20 w-20 items-center justify-center rounded-xl bg-gradient-to-br from-primary-100 to-primary-200 text-lg font-bold text-primary-800 dark:from-primary-900 dark:to-primary-950 dark:text-primary-200"
                        >
                          {{ doctorInitials(doctor.name) }}
                        </div>
                      </div>
                      <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex items-start justify-between gap-2">
                          <p class="font-semibold text-gray-900 dark:text-white leading-tight line-clamp-2">
                            {{ doctor.name }}
                          </p>
                          <UIcon
                            v-if="state.doctor_id === doctor.id"
                            name="i-lucide-circle-check"
                            class="h-5 w-5 shrink-0 text-primary-600 dark:text-primary-400"
                          />
                        </div>
                        <UBadge
                          v-if="doctor.speciality"
                          color="primary"
                          variant="soft"
                          size="xs"
                          class="capitalize"
                        >
                          {{ doctor.speciality }}
                        </UBadge>
                        <div class="mt-2 rounded-xl border border-gray-200 bg-gray-50/90 px-3 py-2 dark:border-gray-700 dark:bg-gray-800/80">
                          <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Consultation fee
                          </p>
                          <p class="text-lg font-bold text-primary-700 dark:text-primary-300 tabular-nums">
                            {{ formatConsultationFee(doctorEffectiveFee(doctor)) }}
                          </p>
                          <p class="text-xs text-gray-600 dark:text-gray-400">
                            per consultation
                          </p>
                        </div>
                        <p v-if="doctor.institution" class="text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1">
                          <UIcon name="i-lucide-building-2" class="h-3.5 w-3.5 shrink-0 opacity-70" />
                          <span class="line-clamp-2">{{ doctor.institution }}</span>
                        </p>
                        <p v-if="doctor.professional_number" class="text-[11px] text-gray-500 dark:text-gray-500 font-mono">
                          ID: {{ doctor.professional_number }}
                        </p>
                      </div>
                    </div>
                  </button>
                </div>
              </div>
            </UFormGroup>

            <UFormGroup label="Preferred Date & Time">
              <UInput v-model="state.scheduled_at" type="datetime-local" :min="minimumDateTime" required />
            </UFormGroup>

            <div v-if="suggestedSlots.length" class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/20">
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
                  {{ formatSlot(slot) }}
                </UButton>
              </div>
            </div>

            <UFormGroup label="Consultation Type">
              <USelectMenu v-model="state.consultation_type" :options="consultationTypes" />
            </UFormGroup>

            <UFormGroup label="Reason for Consultation" required>
              <div class="space-y-2">
                <p class="text-sm text-gray-700 dark:text-gray-200 leading-snug">
                  Describe your symptoms, history and questions. You can format text and attach images.
                </p>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                  <div class="flex items-center gap-1 px-2 py-1.5 border-b border-gray-200 dark:border-gray-800">
                    <button
                      type="button"
                      class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-300"
                      @click.prevent="execCommand('bold')"
                    >
                      <span class="text-xs font-semibold">B</span>
                    </button>
                    <button
                      type="button"
                      class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-300 italic"
                      @click.prevent="execCommand('italic')"
                    >
                      I
                    </button>
                    <button
                      type="button"
                      class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-300"
                      @click.prevent="execCommand('insertUnorderedList')"
                    >
                      <UIcon name="i-lucide-list" class="w-4 h-4" />
                    </button>
                    <button
                      type="button"
                      class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-300"
                      :disabled="uploadingReasonImage"
                      @click.prevent="triggerReasonImage"
                    >
                      <UIcon
                        :name="uploadingReasonImage ? 'i-lucide-loader-2' : 'i-lucide-image-plus'"
                        class="w-4 h-4"
                        :class="{ 'animate-spin': uploadingReasonImage }"
                      />
                    </button>
                    <button
                      type="button"
                      class="ml-auto p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-500 dark:text-gray-400 text-[11px]"
                      @click.prevent="clearReason"
                    >
                      Clear
                    </button>
                  </div>
                  <div
                    ref="reasonEditor"
                    class="min-h-[96px] max-h-60 overflow-y-auto px-3 py-2 text-sm bg-white dark:bg-gray-900 rounded-b-xl focus:outline-none prose prose-sm prose-invert prose-headings:text-gray-100 prose-p:text-gray-100 prose-strong:text-gray-100"
                    contenteditable="true"
                    :placeholder="'Describe your reason for this consultation...'"
                    @input="onReasonInput"
                    @blur="syncReasonFromDom"
                  />
                  <input
                    ref="reasonImageInput"
                    type="file"
                    class="hidden"
                    accept="image/*"
                    @change="onReasonImageSelected"
                  >
                </div>
              </div>
            </UFormGroup>

            <UFormGroup name="consent" label="">
              <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                  By proceeding with this consultation, I confirm that:
                </p>
                <ol class="text-sm text-gray-600 dark:text-gray-300 space-y-2 mb-4 list-decimal list-inside">
                  <li>I am the patient named above, OR I am the parent/legal guardian of the patient named above and am authorised to consent on their behalf.</li>
                  <li>I have read and understood the <NuxtLink to="/terms" class="text-primary-600 dark:text-primary-400 hover:underline">Terms of Service</NuxtLink>, <NuxtLink to="/privacy" class="text-primary-600 dark:text-primary-400 hover:underline">Privacy Policy</NuxtLink>, and <NuxtLink to="/consent" class="text-primary-600 dark:text-primary-400 hover:underline">Consent Form</NuxtLink>.</li>
                  <li>I have had the opportunity to ask questions and have had them answered to my satisfaction.</li>
                  <li>I voluntarily consent to virtual consultation services from Dr. O Virtual Consultations.</li>
                  <li>I understand that I may withdraw consent at any time, though this may affect the services I can receive.</li>
                </ol>
                <UCheckbox v-model="consent" :ui="{ label: 'text-sm text-gray-700 dark:text-gray-300' }">
                  <template #label>
                    I confirm and agree to all the statements above
                  </template>
                </UCheckbox>
              </div>
            </UFormGroup>

            <UButton
              type="submit"
              icon="i-lucide-calendar-plus"
              :loading="submitting"
              :disabled="!canSubmit || isApiOffline"
            >
              Book Consultation
            </UButton>
          </UForm>
        </div>
      </UCard>
    </section>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth'
})

const consultationTypes = ['text', 'audio', 'video']
const { isApiReachable, hasApiStatusChecked } = useApiHealth()
const config = useRuntimeConfig()
const toast = useToast()
const { resolvePublicFileUrl } = useResolvePublicFileUrl()
const tokenCookie = useCookie<string | null>('auth_token')
const router = useRouter()
const loadingDoctors = ref(false)
const submitting = ref(false)
const errorMessage = ref('')
const isApiOffline = computed(() => hasApiStatusChecked.value && !isApiReachable.value)
const retryDoctorsWhenOnline = ref(false)
const reconnectRetryInProgress = ref(false)
const suggestedSlots = ref<string[]>([])

interface DoctorForBooking {
  id: number
  name: string
  email?: string | null
  speciality?: string | null
  professional_number?: string | null
  institution?: string | null
  profile_photo_url?: string | null
  consultation_fee?: number | null
  effective_consultation_fee?: number
  consultation_fee_is_custom?: boolean
  bio?: string | null
  qualifications_summary?: string | null
  license_number?: string | null
  regulatory_council?: string | null
}

const state = reactive({
  category: null as string | null,
  doctor_id: null as number | null,
  scheduled_at: '',
  consultation_type: 'video',
  reason: ''
})
const consent = ref(false)

const categoryOptions = ref([
  { label: 'General Doctor', value: 'General Doctor' },
  { label: 'Physician', value: 'Physician' },
  { label: 'Surgeon', value: 'Surgeon' },
  { label: 'Paediatrician', value: 'Paediatrician' },
  { label: 'Nurse', value: 'Nurse' },
  { label: 'Pharmacist', value: 'Pharmacist' },
  { label: 'Gynecologist', value: 'Gynecologist' },
  { label: 'Dentist', value: 'Dentist' }
])

const doctors = ref<DoctorForBooking[]>([])
/** Admin-configured default consultation amounts per speciality (from doctors list meta). */
const pricingBySpeciality = ref<Record<string, number>>({})
/** Profile images that failed to load (show initials instead). */
const brokenDoctorPhotos = ref<Record<number, boolean>>({})

const filteredDoctors = computed(() => {
  if (!state.category) return []
  return doctors.value.filter(d => d.speciality === state.category)
})

const waitingRoomSelected = computed(() => state.doctor_id === null)

function doctorEffectiveFee (doctor: DoctorForBooking): number {
  const e = doctor.effective_consultation_fee
  if (typeof e === 'number' && !Number.isNaN(e)) {
    return e
  }
  const c = doctor.consultation_fee
  if (typeof c === 'number' && !Number.isNaN(c)) {
    return c
  }
  return 0
}

function doctorUsesCustomFee (doctor: DoctorForBooking): boolean {
  if (typeof doctor.consultation_fee_is_custom === 'boolean') {
    return doctor.consultation_fee_is_custom
  }
  return doctor.consultation_fee != null
}

/** Typical fee when booking “any available doctor”: category default, else lowest listed in category. */
const waitingRoomFeeDisplay = computed(() => {
  const cat = state.category
  if (!cat) return 0
  const metaVal = pricingBySpeciality.value[cat]
  if (metaVal != null && Number.isFinite(metaVal)) {
    return metaVal
  }
  const positives = filteredDoctors.value
    .map(d => doctorEffectiveFee(d))
    .filter(f => f > 0)
  if (positives.length) {
    return Math.min(...positives)
  }
  const anyListed = filteredDoctors.value.map(d => doctorEffectiveFee(d)).filter(f => Number.isFinite(f))
  if (anyListed.length) {
    return Math.min(...anyListed)
  }
  return 0
})

const waitingRoomFeeCaption = computed(() => {
  const cat = state.category
  if (!cat) return ''
  const hasMeta = pricingBySpeciality.value[cat] != null && Number.isFinite(pricingBySpeciality.value[cat])
  if (hasMeta) {
    return `Category default for ${cat}. The clinician who accepts your request may use this rate or their own if set.`
  }
  if (filteredDoctors.value.length) {
    return 'Lowest listed clinician amount in this category. Another doctor may charge differently.'
  }
  return 'Fee follows the category default or the clinician who accepts your request.'
})

function selectWaitingRoom () {
  state.doctor_id = null
}

function selectDoctor (id: number) {
  state.doctor_id = id
}

function doctorInitials (name: string) {
  const parts = name.trim().split(/\s+/).filter(Boolean)
  if (parts.length === 0) return '?'
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
}

function formatConsultationFee (fee: number | null | undefined) {
  if (fee == null || Number.isNaN(fee)) {
    return '—'
  }
  return new Intl.NumberFormat('en-UG', {
    style: 'currency',
    currency: 'UGX',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(fee)
}

function doctorPhotoUrl (doctor: DoctorForBooking) {
  if (brokenDoctorPhotos.value[doctor.id]) return null
  const u = doctor.profile_photo_url
  if (!u) return null
  return resolvePublicFileUrl(u) || u
}

function onDoctorImgError (id: number) {
  brokenDoctorPhotos.value = { ...brokenDoctorPhotos.value, [id]: true }
}

const reasonEditor = ref<HTMLElement | null>(null)
const reasonImageInput = ref<HTMLInputElement | null>(null)
const uploadingReasonImage = ref(false)

const hasReason = computed(() => {
  const html = state.reason || ''
  const text = html.replace(/<[^>]+>/g, '').replace(/&nbsp;/g, ' ').trim()
  return text.length > 0
})

const canSubmit = computed(() => {
  if (!state.category || !state.scheduled_at || !hasReason.value || !consent.value) {
    return false
  }

  return new Date(state.scheduled_at).getTime() > Date.now()
})

const minimumDateTime = computed(() => {
  const now = new Date()
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
  return now.toISOString().slice(0, 16)
})

const apiHeaders = computed(() => ({
  Authorization: `Bearer ${tokenCookie.value || ''}`,
  Accept: 'application/json'
}))

const onCategoryChange = () => {
  // Reset doctor selection when category changes
  state.doctor_id = null
  suggestedSlots.value = []
}

const fetchDoctors = async () => {
  loadingDoctors.value = true
  errorMessage.value = ''

  try {
    const response = await $fetch<{ data: DoctorForBooking[]; meta?: { pricing_by_speciality?: Record<string, number> } }>('/doctors', {
      baseURL: config.public.apiBase,
      headers: apiHeaders.value
    })

    doctors.value = Array.isArray(response.data) ? response.data : []
    pricingBySpeciality.value = response.meta?.pricing_by_speciality && typeof response.meta.pricing_by_speciality === 'object'
      ? response.meta.pricing_by_speciality
      : {}
    brokenDoctorPhotos.value = {}
    retryDoctorsWhenOnline.value = false

    if (reconnectRetryInProgress.value) {
      toast.add({
        title: 'Connection restored',
        description: 'Doctors list has been refreshed.',
        color: 'green'
      })
    }
  } catch (error) {
    const err = error && typeof error === 'object' ? error : null

    if (hasApiStatusChecked.value && !isApiReachable.value) {
      retryDoctorsWhenOnline.value = true
      errorMessage.value = 'API is currently unreachable. Doctors list will retry when connection is restored.'
      return
    }

    const message = err && 'data' in err && err.data && typeof err.data === 'object' && 'message' in err.data
      ? err.data.message
      : null
    errorMessage.value = typeof message === 'string' ? message : 'Unable to load doctors.'
  } finally {
    loadingDoctors.value = false
  }
}

const execCommand = (command: string) => {
  if (typeof document === 'undefined') return
  reasonEditor.value?.focus()
  try {
    document.execCommand(command, false)
  } catch {
    // ignore
  }
}

const onReasonInput = () => {
  if (!reasonEditor.value) return
  state.reason = reasonEditor.value.innerHTML
}

const syncReasonFromDom = () => {
  if (!reasonEditor.value) return
  state.reason = reasonEditor.value.innerHTML
}

const clearReason = () => {
  state.reason = ''
  if (reasonEditor.value) {
    reasonEditor.value.innerHTML = ''
  }
}

const triggerReasonImage = () => {
  if (uploadingReasonImage.value) return
  reasonImageInput.value?.click()
}

const onReasonImageSelected = async (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  input.value = ''

  const maxBytes = 5 * 1024 * 1024
  if (file.size > maxBytes) {
    toast.add({
      title: 'Image too large',
      description: 'Images must be 5MB or less.',
      color: 'red'
    })
    return
  }

  if (isApiOffline.value) {
    toast.add({
      title: 'API unavailable',
      description: 'Please retry when API connection is restored.',
      color: 'amber'
    })
    return
  }

  uploadingReasonImage.value = true
  try {
    const formData = new FormData()
    formData.append('image', file)

    const res = await $fetch<{ url: string }>('/consultations/reason-images', {
      method: 'POST',
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${tokenCookie.value || ''}`
      },
      body: formData
    })

    const url = res.url
    if (typeof document !== 'undefined' && url) {
      reasonEditor.value?.focus()
      document.execCommand('insertImage', false, url)
      syncReasonFromDom()
    }
  } catch (error) {
    const err = error as { data?: { message?: string } }
    toast.add({
      title: 'Failed to upload image',
      description: err?.data?.message || 'Please try again.',
      color: 'red'
    })
  } finally {
    uploadingReasonImage.value = false
  }
}

const onSubmit = async () => {
  if (!canSubmit.value) return
  if (isApiOffline.value) {
    errorMessage.value = 'API is currently unreachable. Please retry when connection is restored.'
    return
  }

  submitting.value = true
  errorMessage.value = ''
  suggestedSlots.value = []

  try {
    await $fetch('/consultations/book', {
      method: 'POST',
      baseURL: config.public.apiBase,
      headers: apiHeaders.value,
      body: {
        category: state.category,
        doctor_id: state.doctor_id,
        scheduled_at: new Date(state.scheduled_at).toISOString(),
        consultation_type: state.consultation_type,
        reason: state.reason
      }
    })

    toast.add({
      title: 'Consultation booked',
      description: 'Your consultation request has been submitted.',
      color: 'green'
    })

    await router.push('/consultations')
  } catch (error) {
    const err = error && typeof error === 'object' ? error : null
    const validationMessage = err && 'data' in err && err.data && typeof err.data === 'object' && 'errors' in err.data
      ? err.data.errors?.scheduled_at?.[0]
      : null
    const message = err && 'data' in err && err.data && typeof err.data === 'object' && 'message' in err.data
      ? err.data.message
      : null

    errorMessage.value = validationMessage || (typeof message === 'string' ? message : 'Failed to book consultation.')

    if (typeof validationMessage === 'string' && validationMessage.includes('already booked') && (state.doctor_id || state.category)) {
      await fetchSuggestedSlots(state.doctor_id || state.category!, new Date(state.scheduled_at).toISOString())
    }
  } finally {
    submitting.value = false
  }
}

const fetchSuggestedSlots = async (doctorIdOrCategory: number | string, from: string) => {
  try {
    let url: string
    if (typeof doctorIdOrCategory === 'string') {
      // For category-based availability, we'll need a new endpoint
      // For now, try to get availability for any doctor in this category
      url = `/doctors/availability?category=${encodeURIComponent(doctorIdOrCategory)}&from=${encodeURIComponent(from)}&limit=5`
    } else {
      // Existing doctor-specific endpoint
      url = `/doctors/${doctorIdOrCategory}/availability?from=${encodeURIComponent(from)}&limit=5`
    }
    
    const response = await $fetch<{ data?: { available_slots?: string[] } }>(url, {
      baseURL: config.public.apiBase,
      headers: apiHeaders.value
    })

    suggestedSlots.value = response?.data?.available_slots || []
  } catch {
    suggestedSlots.value = []
  }
}

const applySuggestedSlot = (slot: string) => {
  state.scheduled_at = toLocalDateTimeInput(slot)
}

const formatSlot = (slot: string) => new Date(slot).toLocaleString()

const toLocalDateTimeInput = (value: string) => {
  const date = new Date(value)
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset())
  return date.toISOString().slice(0, 16)
}

onMounted(async () => {
  await fetchDoctors()
})

watch([isApiReachable, hasApiStatusChecked], async ([reachable, checked]) => {
  if (checked && reachable && retryDoctorsWhenOnline.value && !loadingDoctors.value) {
    reconnectRetryInProgress.value = true
    await fetchDoctors()
    reconnectRetryInProgress.value = false
  }
})

watch(() => state.category, () => {
  suggestedSlots.value = []
})

watch(() => state.doctor_id, () => {
  suggestedSlots.value = []
})

watch(() => state.scheduled_at, () => {
  if (suggestedSlots.value.length > 0) {
    suggestedSlots.value = []
  }
})
</script>

