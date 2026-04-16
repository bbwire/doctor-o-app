<template>
  <div class="h-dvh h-screen flex flex-col overflow-hidden bg-gray-950 text-gray-100">
    <!-- Minimal room header - fixed at top -->
    <header class="sticky top-0 left-0 right-0 z-50 flex items-center justify-between px-4 py-2 bg-gray-900/95 border-b border-gray-800 safe-area-top">
      <UButton
        variant="ghost"
        size="sm"
        color="neutral"
        icon="i-lucide-arrow-left"
        @click="router.push(`/doctor/consultations/${id}`)"
      >
        Exit
      </UButton>
      <div v-if="consultation" class="flex min-w-0 flex-1 flex-col items-center gap-1 px-2 sm:flex-row sm:justify-center sm:gap-3">
        <div class="flex items-center gap-2 shrink-0">
          <span class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">Patient no.</span>
          <PatientNumberBadge
            v-if="consultation.patient?.patient_number"
            size="lg"
            :patient-number="consultation.patient.patient_number"
          />
          <span v-else class="rounded-md border border-gray-700 bg-gray-800/80 px-2 py-0.5 font-mono text-sm font-bold text-gray-400">
            —
          </span>
        </div>
        <span class="hidden h-4 w-px shrink-0 bg-gray-700 sm:block" aria-hidden="true" />
        <div v-if="consultation.consultation_number" class="flex items-center gap-1.5 shrink-0">
          <span class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">CN</span>
          <HumanIdBadge :value="consultation.consultation_number" />
        </div>
        <span class="hidden h-4 w-px shrink-0 bg-gray-700 sm:block" aria-hidden="true" />
        <span class="max-w-[min(50vw,14rem)] truncate text-center text-sm font-medium text-gray-200 sm:max-w-none sm:text-left">
          {{ consultation.patient?.name || `Patient #${consultation.patient_id}` }}
        </span>
        <UBadge color="primary" variant="soft" size="xs" class="capitalize shrink-0">
          {{ consultation.consultation_type }}
        </UBadge>
      </div>
      <div class="w-20" />
    </header>

    <!-- Header is sticky, so no extra spacer is needed -->

    <!-- Loading -->
    <div v-if="loading" class="flex-1 flex items-center justify-center min-h-0">
      <div class="text-center">
        <UIcon name="i-lucide-loader-2" class="w-12 h-12 animate-spin mx-auto text-primary-500 mb-3" />
        <p class="text-gray-400">Joining consultation room...</p>
      </div>
    </div>

    <!-- Error -->
    <div v-else-if="errorMessage" class="flex-1 flex items-center justify-center p-6">
      <UAlert
        color="red"
        icon="i-lucide-alert-triangle"
        variant="soft"
        :title="errorMessage"
      />
    </div>

    <!-- Video / Audio room (Jitsi Meet) -->
    <div
      v-else-if="isVideoOrAudio"
      class="flex-1 flex flex-col min-h-0 bg-black"
    >
      <!-- Pre-join -->
      <div
        v-if="!jitsiContainerVisible"
        class="flex-1 flex items-center justify-center p-6 sm:p-8"
      >
        <div class="w-full max-w-2xl rounded-2xl bg-gray-900/95 flex flex-col items-center py-12 px-8 sm:py-16 sm:px-12 border border-gray-800 shadow-xl">
          <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-gray-800/80 flex items-center justify-center mb-8">
            <UIcon
              :name="consultation?.consultation_type === 'video' ? 'i-lucide-video' : 'i-lucide-phone'"
              class="w-10 h-10 sm:w-12 sm:h-12 text-gray-400"
            />
          </div>
          <div class="text-center mb-8 space-y-2">
            <p class="text-lg sm:text-xl font-semibold text-gray-100">
              {{ consultation?.consultation_type === 'video' ? 'Video' : 'Audio' }} consultation
            </p>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Patient no.</p>
            <p class="text-base sm:text-lg font-mono font-bold text-primary-400">
              {{ consultation?.patient?.patient_number || '—' }}
            </p>
            <p class="text-sm text-gray-500">Room {{ id }}</p>
          </div>
          <UAlert
            v-if="jitsiErrorText"
            color="red"
            variant="soft"
            :title="jitsiErrorText"
            class="mb-4 w-full max-w-sm"
          />
          <UButton
            :icon="consultation?.consultation_type === 'video' ? 'i-lucide-video' : 'i-lucide-phone'"
            size="lg"
            class="w-full sm:w-auto min-w-[200px]"
            :loading="jitsi.isJoining.value"
            :disabled="jitsi.isJoining.value"
            @click="joinJitsi"
          >
            Start {{ consultation?.consultation_type === 'video' ? 'video' : 'audio' }} call
          </UButton>
        </div>
      </div>

      <!-- In-call: Jitsi container + controls -->
      <div
        v-else
        class="flex-1 flex flex-col min-h-0 relative transition-[padding] duration-200"
        :class="showClinicalNotes ? 'md:pr-[28rem]' : ''"
      >
        <div class="flex-1 min-h-0 relative">
          <div
            v-if="jitsi.isJoining.value"
            class="absolute inset-0 flex items-center justify-center bg-gray-900 z-10"
          >
            <div class="text-center">
              <UIcon name="i-lucide-loader-2" class="w-12 h-12 animate-spin text-primary-500 mx-auto mb-3" />
              <p class="text-gray-400">Starting call...</p>
            </div>
          </div>
          <div
            ref="jitsiContainerRef"
            class="w-full h-full min-h-[240px]"
          />
        </div>
        <div class="shrink-0 p-3 border-t border-gray-800 bg-gray-900/95 flex flex-wrap justify-center gap-2 pb-safe">
          <UButton
            color="red"
            variant="soft"
            size="md"
            icon="i-lucide-phone-off"
            @click="leaveJitsi"
          >
            End call
          </UButton>
          <UButton
            color="primary"
            variant="soft"
            size="md"
            icon="i-lucide-clipboard-list"
            @click="showClinicalNotes = !showClinicalNotes"
          >
            {{ showClinicalNotes ? 'Hide notes' : 'Clinical notes' }}
          </UButton>
          <UButton
            color="neutral"
            variant="soft"
            size="md"
            icon="i-lucide-message-square"
            @click="showCallChat = !showCallChat"
          >
            {{ showCallChat ? 'Hide chat' : 'Open chat' }}
          </UButton>
        </div>

        <!-- Clinical notes slide-out panel -->
        <div
          v-if="showClinicalNotes"
          class="absolute inset-y-0 right-0 z-20 w-full max-w-md bg-gray-900 border-l border-gray-800 shadow-xl flex flex-col min-h-0"
        >
          <div class="shrink-0 flex items-center justify-between px-4 py-2 border-b border-gray-800">
            <span class="text-sm font-medium text-gray-200">Clinical notes</span>
            <UButton variant="ghost" size="xs" icon="i-lucide-x" @click="showClinicalNotes = false" />
          </div>
          <ClinicalNotesForm
            class="flex-1 min-h-0 min-w-0"
            v-model="clinicalNotesData"
            :patient-date-of-birth="consultation?.patient?.date_of_birth"
            :consultation-id="id"
            :patient-investigation-uploads="patientInvestigationUploads"
            :on-save="saveClinicalNotes"
            @done="showClinicalNotes = false"
          />
        </div>

        <!-- Inline chat: on small screens use a bottom sheet so it is not clipped by overflow-hidden -->
        <div
          v-if="showCallChat"
          class="flex min-h-0 flex-col border-t border-gray-800 bg-gray-900/80
            max-md:fixed max-md:left-0 max-md:right-0 max-md:z-40 max-md:rounded-t-2xl max-md:border-x max-md:shadow-2xl
            max-md:bottom-[calc(3.75rem+env(safe-area-inset-bottom,0px))] max-md:max-h-[min(52vh,380px)]
            md:relative md:shrink-0 md:max-h-none md:rounded-none md:border-x-0 md:shadow-none"
        >
          <div
            ref="messagesContainer"
            class="min-h-0 flex-1 overflow-y-auto overflow-x-hidden overscroll-contain p-4 space-y-3 md:max-h-72 md:flex-none"
          >
            <div
              v-for="(msg, i) in messagesNewestFirst"
              :key="msg.id ?? `${msg.at}-${i}`"
              class="flex"
              :class="msg.sender === 'doctor' ? 'justify-end' : 'justify-start'"
            >
              <div
                class="max-w-[80%] rounded-2xl px-3 py-2"
                :class="msg.sender === 'doctor'
                  ? 'bg-primary-600 text-white rounded-br-md'
                  : 'bg-gray-800 text-gray-300 rounded-bl-md'"
              >
                <img
                  v-if="msg.attachment_url"
                  :src="chatAttachmentSrc(msg.attachment_url)"
                  alt="Shared image"
                  class="rounded-lg max-w-full max-h-48 object-contain mb-1.5"
                />
                <p v-if="msg.text && msg.text.trim()" class="whitespace-pre-wrap text-xs sm:text-sm break-words">
                  <ChatMessageText :text="msg.text" />
                </p>
                <p class="text-[10px] mt-0.5 opacity-70 text-right">{{ formatTime(msg.at) }}</p>
              </div>
            </div>
            <div v-if="!messages.length" class="flex items-center justify-center py-6">
              <p class="text-gray-500 text-xs text-center">
                No messages yet. Start the conversation below.
              </p>
            </div>
          </div>
          <form
            ref="chatFormRef"
            class="border-t border-gray-800 bg-gray-900/95 px-3 py-2 flex items-center gap-2"
            @submit.prevent="sendMessage"
          >
            <div class="flex items-center gap-1 shrink-0">
              <EmojiPicker @pick="(e) => { newMessage += e }" />
              <UButton
                type="button"
                variant="ghost"
                color="neutral"
                size="xs"
                icon="i-lucide-image-plus"
                aria-label="Upload image"
                :loading="uploadingImage"
                @click="triggerImageInput"
              />
            </div>
            <input
              ref="imageInputRef"
              type="file"
              accept="image/*"
              class="hidden"
              @change="onImageSelect"
            >
            <div class="flex-1 min-w-0">
              <textarea
                ref="chatInputRef"
                v-model="newMessage"
                placeholder="Type a message..."
                rows="1"
                class="w-full min-h-[36px] max-h-24 py-2 px-3 rounded-2xl bg-gray-800 border border-gray-700 text-xs sm:text-sm resize-none focus:outline-none focus:ring-2 focus:ring-primary-500/50 placeholder:text-gray-500"
                @keydown.enter.exact.prevent="sendMessage"
                @focus="scrollInputIntoView"
              />
            </div>
            <button
              type="submit"
              :disabled="(!newMessage.trim() && !selectedImage) || sending"
              class="shrink-0 flex items-center justify-center rounded-lg bg-primary-600 text-white hover:bg-primary-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors p-2"
              aria-label="Send"
            >
              <UIcon
                v-if="sending"
                name="i-lucide-loader-2"
                class="w-4 h-4 animate-spin"
              />
              <UIcon
                v-else
                name="i-lucide-send"
                class="w-4 h-4"
              />
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Text room - full-height chat -->
    <div
      v-else-if="consultation?.consultation_type === 'text'"
      class="flex-1 flex flex-col min-h-0 overflow-hidden relative transition-[padding] duration-200"
      :class="showClinicalNotes ? 'md:pr-[28rem]' : ''"
    >
      <div class="absolute top-14 right-4 z-10 flex gap-2">
        <UButton
          color="primary"
          variant="soft"
          size="sm"
          icon="i-lucide-clipboard-list"
          @click="showClinicalNotes = !showClinicalNotes"
        >
          {{ showClinicalNotes ? 'Hide notes' : 'Clinical notes' }}
        </UButton>
      </div>
      <div
        v-if="showClinicalNotes"
        class="absolute top-0 bottom-24 right-0 z-[60] w-full max-w-md bg-gray-900 border-l border-gray-800 shadow-xl flex flex-col min-h-0 md:bottom-0"
      >
        <div class="shrink-0 flex items-center justify-between px-4 py-2 border-b border-gray-800">
          <span class="text-sm font-medium text-gray-200">Clinical notes</span>
          <UButton variant="ghost" size="xs" icon="i-lucide-x" @click="showClinicalNotes = false" />
        </div>
        <ClinicalNotesForm
          class="flex-1 min-h-0 min-w-0"
          v-model="clinicalNotesData"
          :patient-date-of-birth="consultation?.patient?.date_of_birth"
          :consultation-id="id"
          :patient-investigation-uploads="patientInvestigationUploads"
          :on-save="saveClinicalNotes"
          @done="showClinicalNotes = false"
        />
      </div>
      <div class="flex-1 flex flex-col min-h-0 bg-gray-900/50 overflow-hidden">
        <div
          ref="messagesContainer"
          class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden overscroll-contain p-4 pb-4 md:pb-32 space-y-4"
        >
          <div
            v-for="(msg, i) in messagesNewestFirst"
            :key="msg.id ?? `${msg.at}-${i}`"
            class="flex"
            :class="msg.sender === 'doctor' ? 'justify-end' : 'justify-start'"
          >
            <div
              class="max-w-[80%] rounded-2xl px-4 py-2.5"
              :class="msg.sender === 'doctor'
                ? 'bg-primary-600 text-white rounded-br-md'
                : 'bg-gray-800 text-gray-300 rounded-bl-md'"
            >
              <img
                v-if="msg.attachment_url"
                :src="chatAttachmentSrc(msg.attachment_url)"
                alt="Shared image"
                class="rounded-lg max-w-full max-h-64 object-contain mb-2"
              />
              <p v-if="msg.text && msg.text.trim()" class="whitespace-pre-wrap text-sm break-words">
                <ChatMessageText :text="msg.text" />
              </p>
              <p class="text-xs mt-1 opacity-70">{{ formatTime(msg.at) }}</p>
            </div>
          </div>
          <div v-if="!messages.length" class="flex items-center justify-center py-16">
            <p class="text-gray-500 text-center">
              No messages yet.<br />
              <span class="text-sm">Type below to start the conversation.</span>
            </p>
          </div>
        </div>
        <form
          ref="chatFormRef"
          class="shrink-0 z-50 flex flex-col border-t border-gray-800 bg-gray-900/95 safe-area-bottom md:fixed md:left-0 md:right-0 md:bottom-0 md:transition-[bottom] md:duration-150"
          :style="chatFormStyle"
          :class="showClinicalNotes ? 'md:right-[28rem] md:w-[calc(100%-28rem)]' : ''"
          @submit.prevent="sendMessage"
        >
          <div v-if="imagePreview" class="shrink-0 px-4 pt-2">
            <div class="relative inline-block">
              <img :src="imagePreview" alt="Preview" class="h-20 w-20 object-cover rounded-lg border border-gray-600" />
              <button
                type="button"
                class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-red-500 text-white text-xs flex items-center justify-center"
                @click="clearImage"
              >
                ×
              </button>
            </div>
          </div>
          <div class="flex gap-2 p-3 items-center">
            <!-- Mobile: single + menu -->
            <div class="md:hidden shrink-0">
              <ChatAttachmentMenu
                @pick="(e) => { newMessage += e }"
                @image="triggerImageInput"
                @location="shareLocation"
              />
            </div>
            <!-- Desktop: separate buttons -->
            <div class="hidden md:flex gap-1 shrink-0">
              <EmojiPicker @pick="(e) => { newMessage += e }" />
              <UButton
                type="button"
                variant="ghost"
                color="neutral"
                size="sm"
                icon="i-lucide-image-plus"
                aria-label="Upload image"
                :loading="uploadingImage"
                @click="triggerImageInput"
              />
              <UButton
                type="button"
                variant="ghost"
                color="neutral"
                size="sm"
                icon="i-lucide-map-pin"
                aria-label="Share location"
                :loading="gettingLocation"
                @click="shareLocation"
              />
            </div>
            <input
              ref="imageInputRef"
              type="file"
              accept="image/*"
              class="hidden"
              @change="onImageSelect"
            >
            <div class="flex-1 min-w-0">
              <textarea
                ref="chatInputRef"
                v-model="newMessage"
                placeholder="Type your message..."
                rows="1"
                class="w-full min-h-[44px] max-h-28 py-3 px-4 rounded-2xl bg-gray-800 border border-gray-700 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-primary-500/50 placeholder:text-gray-500"
                @keydown.enter.exact.prevent="sendMessage"
                @focus="scrollInputIntoView"
              />
            </div>
            <button
              type="submit"
              :disabled="(!newMessage.trim() && !selectedImage) || sending"
              class="shrink-0 flex items-center justify-center rounded-lg bg-primary-600 text-white hover:bg-primary-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors p-2.5 md:p-3"
              aria-label="Send"
            >
              <UIcon
                v-if="sending"
                name="i-lucide-loader-2"
                class="w-5 h-5 md:w-6 md:h-6 animate-spin"
              />
              <UIcon
                v-else
                name="i-lucide-send"
                class="w-5 h-5 md:w-6 md:h-6"
              />
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Unknown type -->
    <div v-else-if="consultation" class="flex-1 flex items-center justify-center">
      <p class="text-gray-500">Unknown consultation type</p>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'doctor',
  layout: false
})

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const { token, user } = useAuth()
const { resolvePublicFileUrl } = useResolvePublicFileUrl()

function chatAttachmentSrc (url: string | null | undefined) {
  if (!url) return undefined
  return resolvePublicFileUrl(url) || undefined
}

const toast = useToast()
const tokenCookie = useCookie('auth_token')

const id = route.params.id as string

/** Must match API `ConsultationMessageController` image max (kilobytes → bytes). */
const MAX_CHAT_IMAGE_BYTES = 2 * 1024 * 1024

const isVideoOrAudio = computed(() => {
  const t = consultation.value?.consultation_type
  return t === 'video' || t === 'audio'
})

const loading = ref(true)
const errorMessage = ref('')
const consultation = ref<any | null>(null)
const newMessage = ref('')
const messagesContainer = ref<HTMLElement | null>(null)
const sending = ref(false)
const gettingLocation = ref(false)

const messages = ref<Array<{ id?: number; text: string; sender: 'doctor' | 'patient'; at: string; attachment_url?: string | null }>>([])
const messagesNewestFirst = useMessagesNewestFirst(messages)
const chatInputRef = ref<HTMLTextAreaElement | null>(null)
const chatFormRef = ref<HTMLFormElement | null>(null)
const imageInputRef = ref<HTMLInputElement | null>(null)
const keyboardOffset = ref(0)

const chatFormStyle = computed(() => ({
  bottom: `${keyboardOffset.value}px`
}))
const selectedImage = ref<File | null>(null)
const imagePreview = ref<string | null>(null)
const uploadingImage = ref(false)
let pollInterval: ReturnType<typeof setInterval> | null = null
const showCallChat = ref(false)
const showClinicalNotes = ref(false)
const clinicalNotesData = ref<Record<string, unknown>>({})

const patientInvestigationUploads = computed(() => {
  const meta = consultation.value?.metadata
  if (!meta || typeof meta !== 'object') return []
  const raw = (meta as Record<string, unknown>).patient_investigation_uploads
  if (!Array.isArray(raw)) return []
  return raw.filter((u): u is { id: string; category: 'radiology' | 'laboratory'; file_url: string; original_filename?: string | null; label?: string | null; uploaded_at?: string | null } =>
    u != null && typeof u === 'object' && typeof (u as any).id === 'string' && typeof (u as any).file_url === 'string'
  )
})

const jitsi = useJitsiMeeting()
const jitsiContainerRef = ref<HTMLElement | null>(null)
const jitsiContainerVisible = ref(false)

function getJitsiRoomName () {
  return `doctoro-consult-${id}`
}

function getDoctorDisplayName () {
  const name = consultation.value?.doctor?.name || user.value?.name
  return name ? `Dr. ${name}` : 'Doctor'
}

const jitsiErrorText = computed(() => {
  const err = jitsi.error.value
  if (err == null) return ''
  if (typeof err === 'string') return err
  if (err && typeof err === 'object' && 'message' in err) return String((err as Error).message)
  return 'Could not start meeting'
})

async function joinJitsi () {
  jitsiContainerVisible.value = true
  await nextTick()
  const parent = jitsiContainerRef.value
  if (!parent) {
    jitsiContainerVisible.value = false
    toast.add({ title: 'Could not start call', description: 'Please try again.', color: 'red' })
    return
  }
  try {
    await jitsi.startMeeting({
      roomName: getJitsiRoomName(),
      displayName: getDoctorDisplayName(),
      parentNode: parent,
      video: consultation.value?.consultation_type === 'video',
      isDoctor: true,
      consultationId: id,
    })
  } catch (e: any) {
    const msg = e?.message ?? (typeof e === 'string' ? e : 'Please allow microphone (and camera for video).')
    toast.add({
      title: 'Could not start call',
      description: String(msg),
      color: 'red',
    })
    jitsiContainerVisible.value = false
  }
}

function leaveJitsi () {
  jitsi.endMeeting()
  jitsiContainerVisible.value = false
  router.push(`/doctor/consultations/${id}`)
}

function getHeaders () {
  const authToken = token.value || tokenCookie.value
  return {
    Authorization: `Bearer ${authToken || ''}`,
    Accept: 'application/json'
  }
}

function formatTime (iso: string) {
  return new Date(iso).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })
}

function scrollToLatest (opts?: { smooth?: boolean }) {
  nextTick(() => {
    const el = messagesContainer.value
    if (!el) return
    const behavior = opts?.smooth === false ? 'auto' : 'smooth'
    el.scrollTo({ top: 0, behavior })
  })
}

function isNearLatest () {
  const el = messagesContainer.value
  if (!el) return true
  const threshold = 80
  return el.scrollTop < threshold
}

async function fetchMessages () {
  if (!id) return
  const wasViewingLatest = isNearLatest()
  try {
    const res = await $fetch(`/doctor/consultations/${id}/messages`, {
      baseURL: config.public.apiBase,
      headers: getHeaders()
    })
    messages.value = ((res as any)?.data ?? []) as typeof messages.value
    if (wasViewingLatest) scrollToLatest()
  } catch {
    // ignore fetch errors for polling
  }
}

function updateKeyboardOffset () {
  if (typeof window === 'undefined') return
  if (window.innerWidth >= 768) {
    keyboardOffset.value = 0
    return
  }
  const vv = window.visualViewport
  if (!vv) {
    keyboardOffset.value = 0
    return
  }
  // Include offsetTop so fixed composers stay above the keyboard when the visual viewport shifts (iOS Safari).
  const inset = window.innerHeight - vv.height - vv.offsetTop
  keyboardOffset.value = Math.max(0, inset)
}

function scrollInputIntoView () {
  if (typeof window === 'undefined') return
  if (window.innerWidth >= 768) return
  setTimeout(() => {
    chatInputRef.value?.scrollIntoView({ block: 'center', behavior: 'smooth' })
  }, 300)
}

function triggerImageInput () {
  imageInputRef.value?.click()
}

function onImageSelect (e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file || !file.type.startsWith('image/')) return
  if (file.size > MAX_CHAT_IMAGE_BYTES) {
    toast.add({
      title: 'Image too large',
      description: 'Chat images must be 2 MB or smaller.',
      color: 'red'
    })
    input.value = ''
    return
  }
  selectedImage.value = file
  imagePreview.value = URL.createObjectURL(file)
  input.value = ''
}

function clearImage () {
  selectedImage.value = null
  if (imagePreview.value) URL.revokeObjectURL(imagePreview.value)
  imagePreview.value = null
}

async function sendMessage () {
  const text = newMessage.value.trim()
  const image = selectedImage.value
  if (!text && !image) return

  sending.value = true
  try {
    let res: any
    if (image) {
      const formData = new FormData()
      formData.append('text', text)
      formData.append('image', image)
      res = await $fetch(`/doctor/consultations/${id}/messages`, {
        baseURL: config.public.apiBase,
        method: 'POST',
        headers: {
          ...getHeaders(),
          Accept: 'application/json'
        },
        body: formData
      })
      clearImage()
    } else {
      res = await $fetch(`/doctor/consultations/${id}/messages`, {
        baseURL: config.public.apiBase,
        method: 'POST',
        headers: getHeaders(),
        body: { text }
      })
    }
    const msg = (res as any)?.data ?? { text, sender: 'doctor' as const, at: new Date().toISOString() }
    messages.value = [...messages.value, msg]
    newMessage.value = ''
    scrollToLatest()
  } catch (e: any) {
    const d = e?.data
    const fieldErr = d?.errors && typeof d.errors === 'object'
      ? (Object.values(d.errors).flat().find((x): x is string => typeof x === 'string'))
      : undefined
    toast.add({
      title: 'Failed to send',
      description: (typeof d?.message === 'string' ? d.message : fieldErr) || 'Please try again.',
      color: 'red'
    })
  } finally {
    sending.value = false
  }
}

async function shareLocation () {
  if (gettingLocation.value) return
  gettingLocation.value = true
  try {
    const pos = await new Promise<GeolocationPosition>((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(resolve, reject, {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 60000
      })
    })
    const { latitude, longitude } = pos.coords
    const mapsUrl = `https://www.google.com/maps?q=${latitude},${longitude}`
    const text = `📍 Patient location\n${mapsUrl}`
    const res = await $fetch(`/doctor/consultations/${id}/messages`, {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: getHeaders(),
      body: { text }
    })
    const msg = (res as any)?.data ?? { text, sender: 'doctor' as const, at: new Date().toISOString() }
    messages.value = [...messages.value, msg]
    scrollToLatest()
  } catch (e: any) {
    const msg = e?.message || ''
    if (/denied|permission/i.test(msg)) {
      toast.add({ title: 'Location denied', description: 'Please enable location access to share.', color: 'red' })
    } else {
      toast.add({ title: 'Could not get location', description: msg || 'Please try again.', color: 'red' })
    }
  } finally {
    gettingLocation.value = false
  }
}

async function fetchConsultation () {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await $fetch(`/doctor/consultations/${id}`, {
      baseURL: config.public.apiBase,
      headers: getHeaders()
    })
    consultation.value = (res as any)?.data ?? null
    if (consultation.value?.clinical_notes) {
      clinicalNotesData.value = { ...consultation.value.clinical_notes }
    }
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'Failed to load consultation.'
  } finally {
    loading.value = false
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

onMounted(async () => {
  await fetchConsultation()
  await fetchMessages()
  scrollToLatest({ smooth: false })
  pollInterval = setInterval(fetchMessages, 3000)
  updateKeyboardOffset()
  window.visualViewport?.addEventListener('resize', updateKeyboardOffset)
  window.visualViewport?.addEventListener('scroll', updateKeyboardOffset)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
  window.visualViewport?.removeEventListener('resize', updateKeyboardOffset)
  window.visualViewport?.removeEventListener('scroll', updateKeyboardOffset)
})
</script>

