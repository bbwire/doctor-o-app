<template>
  <div ref="rootRef" class="relative" @click.stop>
    <UButton
      type="button"
      variant="ghost"
      color="neutral"
      size="sm"
      icon="i-lucide-smile"
      aria-label="Add emoji"
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
        class="absolute bottom-full left-0 mb-1 z-50 p-2 w-64 max-h-48 overflow-y-auto rounded-lg border border-gray-700 bg-gray-900 shadow-xl"
      >
        <div class="grid grid-cols-8 gap-1">
          <button
            v-for="e in emojis"
            :key="e"
            type="button"
            class="text-xl p-1.5 rounded hover:bg-gray-700 transition-colors"
            @click="pick(e)"
          >
            {{ e }}
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
const emit = defineEmits<{ pick: [emoji: string] }>()
const open = ref(false)
const rootRef = ref<HTMLElement | null>(null)

watch(open, (isOpen) => {
  if (!isOpen) return
  const close = (e: MouseEvent) => {
    if (rootRef.value && !rootRef.value.contains(e.target as Node)) {
      open.value = false
      document.removeEventListener('click', close)
    }
  }
  setTimeout(() => document.addEventListener('click', close), 0)
})

const emojis = [
  '😀', '😊', '😅', '😂', '👍', '❤️', '🙏', '💪',
  '😷', '🤒', '🤕', '💊', '🩺', '🏥', '🩹', '💉',
  '✅', '❌', '⚠️', '📋', '📝', '🔔', '⏰', '📅',
  '😴', '🤔', '😌', '🥰', '😢', '😤', '🤗', '👋'
]

function pick (emoji: string) {
  emit('pick', emoji)
  open.value = false
}
</script>
