<template>
  <div class="min-h-screen flex flex-col bg-gray-950 text-gray-100">
    <!-- Minimal room header -->
    <header class="shrink-0 flex items-center justify-between px-4 py-2 bg-gray-900/95 border-b border-gray-800">
      <UButton
        variant="ghost"
        size="sm"
        icon="i-lucide-arrow-left"
        @click="router.push(`/consultations/${id}`)"
      >
        Exit
      </UButton>
      <div v-if="consultation" class="flex items-center gap-3">
        <span class="text-sm font-medium text-gray-300">
          Dr. {{ consultation.doctor?.name || `Doctor #${consultation.doctor_id}` }}
        </span>
        <UBadge color="primary" variant="soft" size="xs" class="capitalize">
          {{ consultation.consultation_type }}
        </UBadge>
      </div>
      <div class="w-20" />
    </header>

    <!-- Loading -->
    <div v-if="loading" class="flex-1 flex items-center justify-center">
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

    <!-- Video room -->
    <div
      v-else-if="consultation?.consultation_type === 'video'"
      class="flex-1 flex flex-col min-h-0 bg-black"
    >
      <div v-if="!isCallActive" class="flex-1 flex items-center justify-center p-6 sm:p-8">
        <div class="w-full max-w-2xl rounded-2xl bg-gray-900/95 flex flex-col items-center py-12 px-8 sm:py-16 sm:px-12 border border-gray-800 shadow-xl">
          <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-gray-800/80 flex items-center justify-center mb-8">
            <UIcon name="i-lucide-video" class="w-10 h-10 sm:w-12 sm:h-12 text-gray-400" />
          </div>
          <div class="text-center mb-8">
            <p class="text-lg sm:text-xl font-semibold text-gray-100">Video consultation</p>
            <p class="text-sm text-gray-500 mt-1">Room {{ id }}</p>
          </div>
          <UAlert
            v-if="callError"
            color="red"
            variant="soft"
            :title="String(callError || '')"
            class="mb-4 w-full max-w-sm"
          />
          <UButton
            icon="i-lucide-video"
            size="lg"
            class="w-full sm:w-auto min-w-[200px]"
            :loading="webrtcStarting"
            @click="startVideoCall"
          >
            Join video call
          </UButton>
        </div>
      </div>
      <div v-else class="flex-1 flex flex-col min-h-0">
        <!-- Mobile: remote full-screen + local PiP -->
        <div class="lg:hidden relative flex-1 min-h-0 bg-black">
          <div class="absolute inset-0">
            <VideoDisplay
              :stream="remoteStreamProp"
              video-class="w-full h-full object-cover"
            />
            <div class="absolute top-4 left-4 text-white font-medium drop-shadow-lg">
              Dr. {{ consultation?.doctor?.name || 'Doctor' }}
            </div>
          </div>
          <div class="absolute top-4 right-4 w-24 h-32 sm:w-28 sm:h-36 rounded-xl overflow-hidden border-2 border-white/20 shadow-xl bg-gray-900">
            <VideoDisplay
              :stream="localStreamProp"
              muted
              video-class="w-full h-full object-cover scale-x-[-1]"
            />
            <div class="absolute bottom-1 left-1 px-1.5 py-0.5 rounded bg-black/60 text-[10px] text-gray-300">
              You
            </div>
          </div>
        </div>

        <!-- Desktop: side-by-side grid + scrollable content -->
        <div class="hidden lg:flex flex-1 min-h-0 overflow-y-auto overscroll-contain flex-col">
          <div class="p-4 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="relative rounded-xl overflow-hidden bg-gray-900 aspect-video min-h-[160px]">
                <VideoDisplay
                  :stream="remoteStreamProp"
                  video-class="w-full h-full object-cover"
                />
                <div class="absolute bottom-2 left-2 px-2 py-1 rounded bg-black/60 text-xs text-gray-300">
                  Dr. {{ consultation?.doctor?.name || 'Doctor' }}
                </div>
              </div>
              <div class="relative rounded-xl overflow-hidden bg-gray-900 aspect-video min-h-[160px]">
                <VideoDisplay
                  :stream="localStreamProp"
                  muted
                  video-class="w-full h-full object-cover scale-x-[-1]"
                />
                <div class="absolute bottom-2 left-2 px-2 py-1 rounded bg-black/60 text-xs text-gray-300">
                  You
                </div>
              </div>
            </div>

            <!-- Consultation reason (patient) – desktop -->
            <div
              v-if="consultation?.reason"
              class="rounded-xl border border-gray-800 bg-gray-900/60 p-4"
            >
              <h3 class="text-sm font-semibold text-gray-300 mb-2 flex items-center gap-2">
                <UIcon name="i-lucide-file-text" class="w-4 h-4" />
                Your reason for this visit
              </h3>
              <p class="text-sm text-gray-200">{{ consultation.reason }}</p>
            </div>
          </div>
        </div>

        <!-- Call controls -->
        <div class="shrink-0 p-4 border-t border-gray-800 bg-gray-900/95 pb-safe">
          <div class="flex flex-wrap justify-center gap-2">
            <UButton
              :icon="isMuted ? 'i-lucide-mic-off' : 'i-lucide-mic'"
              :color="isMuted ? 'red' : 'neutral'"
              variant="soft"
              size="md"
              @click="webrtc.toggleMute()"
            >
              {{ isMuted ? 'Unmute' : 'Mute' }}
            </UButton>
            <UButton
              :icon="isVideoOff ? 'i-lucide-video-off' : 'i-lucide-video'"
              :color="isVideoOff ? 'red' : 'neutral'"
              variant="soft"
              size="md"
              @click="webrtc.toggleVideo()"
            >
              {{ isVideoOff ? 'Camera on' : 'Camera off' }}
            </UButton>
            <UButton
              color="red"
              variant="soft"
              size="md"
              icon="i-lucide-phone-off"
              @click="webrtc.endCall()"
            >
              End call
            </UButton>
          </div>
        </div>

        <!-- Consultation reason (patient) – mobile -->
        <div
          v-if="consultation?.reason"
          class="lg:hidden shrink-0 max-h-40 overflow-y-auto border-t border-gray-800"
        >
          <div class="p-4 rounded-xl bg-gray-900/60">
            <h3 class="text-sm font-semibold text-gray-300 mb-2 flex items-center gap-2">
              <UIcon name="i-lucide-file-text" class="w-4 h-4" />
              Your reason for this visit
            </h3>
            <p class="text-sm text-gray-200">{{ consultation.reason }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Audio room -->
    <div
      v-else-if="consultation?.consultation_type === 'audio'"
      class="flex-1 flex flex-col min-h-0"
    >
      <div class="flex-1 flex items-center justify-center p-6 sm:p-8">
        <div class="w-full max-w-sm rounded-2xl bg-gray-900/95 flex flex-col items-center py-12 px-8 sm:py-16 sm:px-10 border border-gray-800 shadow-xl">
          <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-gray-800/80 flex items-center justify-center ring-4 ring-primary-500/20 mb-8">
            <UIcon name="i-lucide-phone" class="w-10 h-10 sm:w-12 sm:h-12 text-primary-400" />
          </div>
          <div class="text-center mb-8">
            <p class="text-lg sm:text-xl font-semibold text-gray-100">Audio consultation</p>
            <p class="text-sm text-gray-500 mt-1">Room {{ id }}</p>
          </div>
          <UButton
            icon="i-lucide-phone"
            size="lg"
            class="w-full sm:w-auto min-w-[200px]"
            @click="toast.add({ title: 'Audio call', description: 'Integrate WebRTC or Twilio for voice calls.', color: 'amber' })"
          >
            Join audio call
          </UButton>
        </div>
      </div>
    </div>

    <!-- Text room - full-height chat (shared state with doctor) -->
    <div
      v-else-if="consultation?.consultation_type === 'text'"
      class="flex-1 flex flex-col min-h-0"
    >
      <div class="flex-1 flex flex-col min-h-0 bg-gray-900/50">
        <div
          ref="messagesContainer"
          class="flex-1 overflow-y-auto p-4 space-y-4"
        >
          <div
            v-for="(msg, i) in messages"
            :key="msg.id ?? i"
            class="flex"
            :class="msg.sender === 'patient' ? 'justify-end' : 'justify-start'"
          >
            <div
              class="max-w-[80%] rounded-2xl px-4 py-2.5"
              :class="msg.sender === 'patient'
                ? 'bg-primary-600 text-white rounded-br-md'
                : 'bg-gray-800 text-gray-300 rounded-bl-md'"
            >
              <p class="whitespace-pre-wrap text-sm">{{ msg.text }}</p>
              <p class="text-xs mt-1 opacity-70">{{ formatTime(msg.at) }}</p>
            </div>
          </div>
          <div v-if="!messages.length" class="flex-1 flex items-center justify-center py-16">
            <p class="text-gray-500 text-center">
              No messages yet.<br />
              <span class="text-sm">Type below to start the conversation.</span>
            </p>
          </div>
        </div>
        <form
          class="shrink-0 p-4 border-t border-gray-800 bg-gray-900/80"
          @submit.prevent="sendMessage"
        >
          <div class="flex gap-2 max-w-4xl mx-auto">
            <UTextarea
              v-model="newMessage"
              placeholder="Type your message..."
              :rows="2"
              class="flex-1 min-h-0 bg-gray-800 border-gray-700"
              @keydown.enter.exact.prevent="sendMessage"
            />
            <UButton
              type="submit"
              :icon="sending ? undefined : 'i-lucide-send'"
              :loading="sending"
              :disabled="!newMessage.trim() || sending"
            >
              Send
            </UButton>
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
  middleware: 'auth',
  layout: false
})

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const toast = useToast()
const tokenCookie = useCookie('auth_token')

const id = route.params.id as string

const loading = ref(true)
const errorMessage = ref('')
const consultation = ref<any | null>(null)
const newMessage = ref('')
const messagesContainer = ref<HTMLElement | null>(null)
const sending = ref(false)

const messages = ref<Array<{ id?: number; text: string; sender: 'doctor' | 'patient'; at: string }>>([])
let pollInterval: ReturnType<typeof setInterval> | null = null
const webrtcStarting = ref(false)

async function fetchWebrtcSignals (since?: string) {
  const res = await $fetch(`/consultations/${id}/webrtc-signals`, {
    baseURL: config.public.apiBase,
    headers: getHeaders(),
    query: since ? { since } : {}
  })
  return (res as any)?.data ?? []
}

async function sendWebrtcSignal (type: string, payload: any) {
  await $fetch(`/consultations/${id}/webrtc-signals`, {
    baseURL: config.public.apiBase,
    method: 'POST',
    headers: getHeaders(),
    body: { type, payload }
  })
}

const webrtc = useWebRtcCall(
  id,
  'patient',
  fetchWebrtcSignals,
  sendWebrtcSignal
)

const isCallActive = computed(() => webrtc.isCallActive?.value ?? false)
const callError = computed(() => webrtc.callError?.value ?? null)
const isMuted = computed(() => webrtc.isMuted?.value ?? false)
const isVideoOff = computed(() => webrtc.isVideoOff?.value ?? false)
const localStreamProp = computed(() => webrtc.localStream?.value ?? null)
const remoteStreamProp = computed(() => webrtc.remoteStream?.value ?? null)

async function startVideoCall () {
  webrtcStarting.value = true
  try {
    await webrtc.startCall(true)
  } catch (e: any) {
    const msg = e?.message || ''
    const isVideoError = /video|source|camera|notReadable|not readable|could not start/i.test(msg)
    if (isVideoError) {
      try {
        await webrtc.startCall(false)
        toast.add({ title: 'Joined with audio only', description: 'Camera unavailable (may be in use). You can still hear and speak.', color: 'amber' })
      } catch (e2: any) {
        toast.add({ title: 'Could not join call', description: e2?.message || 'Please allow microphone access.', color: 'red' })
      }
    } else {
      toast.add({ title: 'Could not join call', description: msg || 'Please allow camera access.', color: 'red' })
    }
  } finally {
    webrtcStarting.value = false
  }
}

function getHeaders () {
  return {
    Authorization: `Bearer ${tokenCookie.value || ''}`,
    Accept: 'application/json'
  }
}

function formatTime (iso: string) {
  return new Date(iso).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })
}

async function fetchMessages () {
  if (!id) return
  try {
    const res = await $fetch(`/consultations/${id}/messages`, {
      baseURL: config.public.apiBase,
      headers: getHeaders()
    })
    messages.value = ((res as any)?.data ?? []) as typeof messages.value
  } catch {
    // ignore fetch errors for polling
  }
}

async function sendMessage () {
  const text = newMessage.value.trim()
  if (!text) return

  sending.value = true
  try {
    const res = await $fetch(`/consultations/${id}/messages`, {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: getHeaders(),
      body: { text }
    })
    const msg = (res as any)?.data ?? { text, sender: 'patient' as const, at: new Date().toISOString() }
    messages.value = [...messages.value, msg]
    newMessage.value = ''
    nextTick(() => {
      messagesContainer.value?.scrollTo({ top: messagesContainer.value.scrollHeight, behavior: 'smooth' })
    })
  } catch (e: any) {
    toast.add({ title: 'Failed to send', description: e?.data?.message || 'Please try again.', color: 'red' })
  } finally {
    sending.value = false
  }
}

async function fetchConsultation () {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await $fetch(`/consultations/${id}`, {
      baseURL: config.public.apiBase,
      headers: getHeaders()
    })
    consultation.value = (res as any)?.data ?? null
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'Failed to load consultation.'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await fetchConsultation()
  if (consultation.value?.consultation_type === 'text') {
    await fetchMessages()
    pollInterval = setInterval(fetchMessages, 3000)
  }
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>
