<template>
  <div class="h-dvh h-screen flex flex-col overflow-hidden bg-gray-950 text-gray-100">
    <!-- Minimal room header - fixed at top -->
    <header class="fixed top-0 left-0 right-0 z-50 flex items-center justify-between px-4 py-2 bg-gray-900/95 border-b border-gray-800 safe-area-top">
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

    <!-- Spacer for fixed header -->
    <div class="h-12 shrink-0" aria-hidden="true" />

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
              <div
                class="text-sm text-gray-200 prose prose-sm prose-invert max-w-none"
                v-html="consultation.reason"
              />
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
            <UButton
              color="neutral"
              variant="soft"
              size="md"
              icon="i-lucide-message-square"
              class="ml-auto"
              @click="showCallChat = !showCallChat"
            >
              {{ showCallChat ? 'Hide chat' : 'Open chat' }}
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
            <div
              class="text-sm text-gray-200 prose prose-sm prose-invert max-w-none"
              v-html="consultation.reason"
            />
          </div>
        </div>

        <!-- Inline chat panel for video/audio -->
        <div
          v-if="showCallChat"
          class="shrink-0 border-t border-gray-800 bg-gray-900/80"
        >
          <div
            ref="messagesContainer"
            class="max-h-72 overflow-y-auto overflow-x-hidden overscroll-contain p-4 space-y-3"
          >
            <div
              v-for="(msg, i) in messages"
              :key="msg.id ?? i"
              class="flex"
              :class="msg.sender === 'doctor' ? 'justify-start' : 'justify-end'"
            >
              <div
                class="max-w-[80%] rounded-2xl px-3 py-2"
                :class="msg.sender === 'doctor'
                  ? 'bg-gray-800 text-gray-300 rounded-bl-md'
                  : 'bg-primary-600 text-white rounded-br-md'"
              >
                <img
                  v-if="msg.attachment_url"
                  :src="msg.attachment_url"
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
            <div ref="scrollAnchor" class="h-0" aria-hidden="true" />
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

    <!-- Audio room -->
    <div
      v-else-if="consultation?.consultation_type === 'audio'"
      class="flex-1 flex flex-col min-h-0"
    >
      <div v-if="!isCallActive" class="flex-1 flex items-center justify-center p-6 sm:p-8">
        <div class="w-full max-w-sm rounded-2xl bg-gray-900/95 flex flex-col items-center py-12 px-8 sm:py-16 sm:px-10 border border-gray-800 shadow-xl">
          <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-gray-800/80 flex items-center justify-center ring-4 ring-primary-500/20 mb-8">
            <UIcon name="i-lucide-phone" class="w-10 h-10 sm:w-12 sm:h-12 text-primary-400" />
          </div>
          <div class="text-center mb-8">
            <p class="text-lg sm:text-xl font-semibold text-gray-100">Audio consultation</p>
            <p class="text-sm text-gray-500 mt-1">Room {{ id }}</p>
          </div>
          <UAlert
            v-if="callError"
            color="red"
            variant="soft"
            :title="String(callError || '')"
            class="mb-4 w-full"
          />
          <UButton
            icon="i-lucide-phone"
            size="lg"
            class="w-full sm:w-auto min-w-[200px]"
            :loading="webrtcStarting"
            @click="startAudioCall"
          >
            Join audio call
          </UButton>
        </div>
      </div>
      <div class="flex-1 flex flex-col min-h-0">
        <div v-if="!isCallActive" class="flex-1 flex items-center justify-center p-6 sm:p-8">
          <div class="w-full max-w-sm rounded-2xl bg-gray-900/95 flex flex-col items-center py-12 px-8 sm:py-16 sm:px-10 border border-gray-800 shadow-xl">
            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-gray-800/80 flex items-center justify-center ring-4 ring-primary-500/20 mb-8">
              <UIcon name="i-lucide-phone" class="w-10 h-10 sm:w-12 sm:h-12 text-primary-400" />
            </div>
            <div class="text-center mb-8">
              <p class="text-lg sm:text-xl font-semibold text-gray-100">Audio consultation</p>
              <p class="text-sm text-gray-500 mt-1">Room {{ id }}</p>
            </div>
            <UAlert
              v-if="callError"
              color="red"
              variant="soft"
              :title="String(callError || '')"
              class="mb-4 w-full"
            />
            <UButton
              icon="i-lucide-phone"
              size="lg"
              class="w-full sm:w-auto min-w-[200px]"
              :loading="webrtcStarting"
              @click="startAudioCall"
            >
              Join audio call
            </UButton>
          </div>
        </div>
        <div v-else class="flex-1 flex flex-col items-center justify-center p-6 gap-6">
          <audio ref="remoteAudioEl" autoplay class="hidden" />
          <div class="w-24 h-24 rounded-full bg-primary-500/20 flex items-center justify-center">
            <UIcon name="i-lucide-phone" class="w-12 h-12 text-primary-400" />
          </div>
          <div class="text-center">
            <p class="text-lg font-medium text-gray-200">Dr. {{ consultation?.doctor?.name || 'Doctor' }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ isConnected ? 'Connected' : 'Connecting...' }}</p>
          </div>
          <div class="flex gap-2">
            <UButton
              :icon="isMuted ? 'i-lucide-mic-off' : 'i-lucide-mic'"
              :color="isMuted ? 'red' : 'neutral'"
              variant="soft"
              size="lg"
              @click="webrtc.toggleMute()"
            >
              {{ isMuted ? 'Unmute' : 'Mute' }}
            </UButton>
            <UButton
              color="red"
              variant="soft"
              size="lg"
              icon="i-lucide-phone-off"
              @click="webrtc.endCall()"
            >
              End call
            </UButton>
            <UButton
              color="neutral"
              variant="soft"
              size="lg"
              icon="i-lucide-message-square"
              @click="showCallChat = !showCallChat"
            >
              {{ showCallChat ? 'Hide chat' : 'Open chat' }}
            </UButton>
          </div>
          <!-- Reuse same inline chat panel for audio -->
          <div
            v-if="showCallChat"
            class="w-full mt-4 border-t border-gray-800 bg-gray-900/80 rounded-xl overflow-hidden"
          >
            <div
              ref="messagesContainer"
              class="max-h-72 overflow-y-auto overflow-x-hidden overscroll-contain p-4 space-y-3"
            >
              <div
                v-for="(msg, i) in messages"
                :key="msg.id ?? i"
                class="flex"
                :class="msg.sender === 'doctor' ? 'justify-start' : 'justify-end'"
              >
                <div
                  class="max-w-[80%] rounded-2xl px-3 py-2"
                  :class="msg.sender === 'doctor'
                    ? 'bg-gray-800 text-gray-300 rounded-bl-md'
                    : 'bg-primary-600 text-white rounded-br-md'"
                >
                  <img
                    v-if="msg.attachment_url"
                    :src="msg.attachment_url"
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
              <div ref="scrollAnchor" class="h-0" aria-hidden="true" />
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
    </div>

    <!-- Text room - full-height chat (shared state with doctor) -->
    <div
      v-else-if="consultation?.consultation_type === 'text'"
      class="flex-1 flex flex-col min-h-0 overflow-hidden"
    >
      <div class="flex-1 flex flex-col min-h-0 overflow-hidden bg-gray-900/50">
        <!-- Messages - scrollable only, pb for fixed input -->
        <div
          ref="messagesContainer"
          class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden overscroll-contain p-4 pb-32 space-y-4"
        >
          <div
            v-for="(msg, i) in messages"
            :key="msg.id ?? i"
            class="flex"
            :class="msg.sender === 'doctor' ? 'justify-start' : 'justify-end'"
          >
            <div
              class="max-w-[80%] rounded-2xl px-4 py-2.5"
              :class="msg.sender === 'doctor'
                ? 'bg-gray-800 text-gray-300 rounded-bl-md'
                : 'bg-primary-600 text-white rounded-br-md'"
            >
              <img
                v-if="msg.attachment_url"
                :src="msg.attachment_url"
                alt="Shared image"
                class="rounded-lg max-w-full max-h-64 object-contain mb-2"
              />
              <p v-if="msg.text && msg.text.trim()" class="whitespace-pre-wrap text-sm break-words">
                <ChatMessageText :text="msg.text" />
              </p>
              <p class="text-xs mt-1 opacity-70 text-right">{{ formatTime(msg.at) }}</p>
            </div>
          </div>
          <div v-if="!messages.length" class="flex items-center justify-center py-16">
            <p class="text-gray-500 text-center">
              No messages yet.<br />
              <span class="text-sm">Type below to start the conversation.</span>
            </p>
          </div>
          <div ref="scrollAnchor" class="h-0" aria-hidden="true" />
        </div>

        <!-- Input bar - fixed at bottom, adjusts for keyboard on mobile -->
        <form
          ref="chatFormRef"
          class="fixed left-0 right-0 z-50 flex flex-col border-t border-gray-800 bg-gray-900/95 safe-area-bottom transition-[bottom] duration-150"
          :style="chatFormStyle"
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
const scrollAnchor = ref<HTMLElement | null>(null)
const sending = ref(false)
const gettingLocation = ref(false)

const messages = ref<Array<{ id?: number; text: string; sender: 'doctor' | 'patient'; at: string; attachment_url?: string | null }>>([])
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
const isConnected = computed(() => webrtc.isConnected?.value ?? false)
const callError = computed(() => webrtc.callError?.value ?? null)
const isMuted = computed(() => webrtc.isMuted?.value ?? false)
const isVideoOff = computed(() => webrtc.isVideoOff?.value ?? false)
const localStreamProp = computed(() => webrtc.localStream?.value ?? null)
const remoteStreamProp = computed(() => webrtc.remoteStream?.value ?? null)
const remoteAudioEl = ref<HTMLAudioElement | null>(null)
const showCallChat = ref(false)

async function startAudioCall () {
  webrtcStarting.value = true
  try {
    await webrtc.startCall(false)
  } catch (e: any) {
    toast.add({ title: 'Could not join call', description: e?.message || 'Please allow microphone access.', color: 'red' })
  } finally {
    webrtcStarting.value = false
  }
}

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

function scrollToBottom () {
  nextTick(() => {
    scrollAnchor.value?.scrollIntoView({ behavior: 'smooth', block: 'end' })
  })
}

function isNearBottom () {
  const el = messagesContainer.value
  if (!el) return true
  const threshold = 80
  return el.scrollHeight - el.scrollTop - el.clientHeight < threshold
}

async function fetchMessages () {
  if (!id) return
  const wasAtBottom = isNearBottom()
  try {
    const res = await $fetch(`/consultations/${id}/messages`, {
      baseURL: config.public.apiBase,
      headers: getHeaders()
    })
    messages.value = ((res as any)?.data ?? []) as typeof messages.value
    if (wasAtBottom) scrollToBottom()
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
  const offset = vv ? Math.max(0, window.innerHeight - vv.height) : 0
  keyboardOffset.value = offset
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
      formData.append('text', text || ' ')
      formData.append('image', image)
      res = await $fetch(`/consultations/${id}/messages`, {
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
      res = await $fetch(`/consultations/${id}/messages`, {
        baseURL: config.public.apiBase,
        method: 'POST',
        headers: getHeaders(),
        body: { text }
      })
    }
    const msg = (res as any)?.data ?? { text: text || ' ', sender: 'patient' as const, at: new Date().toISOString() }
    messages.value = [...messages.value, msg]
    newMessage.value = ''
    scrollToBottom()
  } catch (e: any) {
    toast.add({ title: 'Failed to send', description: e?.data?.message || 'Please try again.', color: 'red' })
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
    const text = `📍 My location\n${mapsUrl}`
    const res = await $fetch(`/consultations/${id}/messages`, {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: getHeaders(),
      body: { text }
    })
    const msg = (res as any)?.data ?? { text, sender: 'patient' as const, at: new Date().toISOString() }
    messages.value = [...messages.value, msg]
    scrollToBottom()
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

watch([remoteStreamProp, remoteAudioEl], ([stream, el]) => {
  if (el && stream) (el as HTMLAudioElement).srcObject = stream
}, { flush: 'post' })

onMounted(async () => {
  await fetchConsultation()
  await fetchMessages()
  scrollToBottom()
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

