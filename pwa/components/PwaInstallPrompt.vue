<template>
  <Teleport to="body">
    <Transition name="slide-up">
      <div
        v-if="showPrompt"
        class="fixed inset-x-0 bottom-0 z-[100] p-4 pb-safe safe-area-bottom"
      >
        <div
          class="max-w-md mx-auto rounded-2xl shadow-xl border border-gray-200/80 dark:border-gray-700/80 bg-white dark:bg-gray-900 overflow-hidden"
        >
          <div class="p-4 flex items-start gap-4">
            <div class="shrink-0 w-12 h-12 rounded-xl bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center">
              <UIcon name="i-lucide-download" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                Install Dr. O
              </h3>
              <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ isIos ? 'Tap Share, then Add to Home Screen for quick access.' : 'Install our app for quick access and a better experience.' }}
              </p>
            </div>
          </div>
          <div class="px-4 pb-4 flex gap-2">
            <UButton
              variant="ghost"
              color="neutral"
              block
              @click="dismiss"
            >
              Not now
            </UButton>
            <UButton
              v-if="isIos"
              block
              @click="dismiss"
            >
              Got it
            </UButton>
            <UButton
              v-else
              block
              :loading="installing"
              @click="install"
            >
              Install
            </UButton>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
const DISMISS_KEY = 'pwa-install-dismissed'
const DISMISS_DAYS = 7

const showPrompt = ref(false)
const installing = ref(false)
const isIos = ref(false)

const nuxtApp = useNuxtApp()

function isStandalone () {
  if (typeof window === 'undefined') return true
  const nav = window.navigator
  return window.matchMedia('(display-mode: standalone)').matches ||
    (nav && 'standalone' in nav && nav.standalone === true) ||
    document.referrer.includes('android-app://')
}

function isMobile () {
  if (typeof window === 'undefined') return false
  return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
    window.matchMedia('(max-width: 768px)').matches
}

function wasDismissed () {
  if (typeof window === 'undefined') return true
  try {
    const raw = localStorage.getItem(DISMISS_KEY)
    if (!raw) return false
    const { at } = JSON.parse(raw)
    const days = (Date.now() - at) / (24 * 60 * 60 * 1000)
    return days < DISMISS_DAYS
  } catch {
    return false
  }
}

function dismiss () {
  try {
    localStorage.setItem(DISMISS_KEY, JSON.stringify({ at: Date.now() }))
  } catch {}
  showPrompt.value = false
  if (nuxtApp.$pwa?.cancelInstall) {
    nuxtApp.$pwa.cancelInstall()
  }
}

async function install () {
  if (!nuxtApp.$pwa?.install) return
  installing.value = true
  try {
    await nuxtApp.$pwa.install()
    showPrompt.value = false
  } catch {
    // User cancelled or install failed
  } finally {
    installing.value = false
  }
}

onMounted(() => {
  if (!isMobile() || isStandalone() || wasDismissed()) return

  isIos.value = /iPad|iPhone|iPod/.test(navigator.userAgent) && !('MSStream' in window)

  if (isIos.value) {
    // iOS: show custom prompt after a short delay (beforeinstallprompt never fires)
    const timer = setTimeout(() => { showPrompt.value = true }, 3000)
    onUnmounted(() => clearTimeout(timer))
    return
  }

  // Chrome/Android: wait for beforeinstallprompt (via $pwa.showInstallPrompt)
  const pwa = nuxtApp.$pwa
  if (!pwa) return

  const check = () => {
    if (pwa.showInstallPrompt?.value && !pwa.isPWAInstalled?.value) {
      showPrompt.value = true
    }
  }

  check()
  watch(() => pwa.showInstallPrompt?.value, check, { immediate: true })
})
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.25s ease-out, opacity 0.2s;
}
.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
  opacity: 0;
}
</style>
