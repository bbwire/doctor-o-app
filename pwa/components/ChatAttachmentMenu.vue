<template>
  <div ref="rootRef" class="relative shrink-0" @click.stop>
    <UButton
      type="button"
      variant="ghost"
      color="neutral"
      size="sm"
      icon="i-lucide-plus"
      aria-label="Attach"
      aria-haspopup="true"
      :aria-expanded="open"
      @click="open = !open"
    />
    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-show="open"
        class="absolute bottom-full left-0 mb-1 z-50 rounded-xl border border-gray-700 bg-gray-900 shadow-xl overflow-hidden"
      >
        <div v-if="!showEmojiGrid" class="p-3 min-w-[180px]">
          <div class="grid grid-cols-3 gap-3">
            <button
              type="button"
              class="flex flex-col items-center gap-2 p-3 rounded-lg hover:bg-gray-800 transition-colors"
              @click="showEmojiGrid = true"
            >
              <span class="text-2xl">😀</span>
              <span class="text-xs text-gray-400">Emoji</span>
            </button>
            <button
              type="button"
              class="flex flex-col items-center gap-2 p-3 rounded-lg hover:bg-gray-800 transition-colors"
              @click="emit('image'); open = false"
            >
              <UIcon name="i-lucide-image-plus" class="w-6 h-6 text-gray-300" />
              <span class="text-xs text-gray-400">Image</span>
            </button>
            <button
              type="button"
              class="flex flex-col items-center gap-2 p-3 rounded-lg hover:bg-gray-800 transition-colors"
              @click="emit('location'); open = false"
            >
              <UIcon name="i-lucide-map-pin" class="w-6 h-6 text-gray-300" />
              <span class="text-xs text-gray-400">Location</span>
            </button>
          </div>
        </div>
        <div v-else class="p-2 w-56 max-h-44 overflow-y-auto">
          <button
            type="button"
            class="mb-2 text-xs text-gray-400 hover:text-gray-300 flex items-center gap-1"
            @click="showEmojiGrid = false"
          >
            <UIcon name="i-lucide-arrow-left" class="w-3 h-3" />
            Back
          </button>
          <div class="grid grid-cols-8 gap-1">
            <button
              v-for="e in emojis"
              :key="e"
              type="button"
              class="text-xl p-1.5 rounded hover:bg-gray-700 transition-colors"
              @click="emit('pick', e); open = false"
            >
              {{ e }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
const emit = defineEmits<{ pick: [emoji: string]; image: []; location: [] }>()
const open = ref(false)
const showEmojiGrid = ref(false)
const rootRef = ref<HTMLElement | null>(null)

const emojis = [
  '😀', '😊', '😅', '😂', '👍', '❤️', '🙏', '💪',
  '😷', '🤒', '🤕', '💊', '🩺', '🏥', '🩹', '💉',
  '✅', '❌', '⚠️', '📋', '📝', '🔔', '⏰', '📅',
  '😴', '🤔', '😌', '🥰', '😢', '😤', '🤗', '👋'
]

watch(open, (isOpen) => {
  if (!isOpen) {
    showEmojiGrid.value = false
    return
  }
  const close = (e: MouseEvent) => {
    if (rootRef.value && !rootRef.value.contains(e.target as Node)) {
      open.value = false
      document.removeEventListener('click', close)
    }
  }
  setTimeout(() => document.addEventListener('click', close), 0)
})
</script>
