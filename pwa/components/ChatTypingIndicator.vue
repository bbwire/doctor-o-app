<template>
  <div
    class="inline-flex items-center gap-2 rounded-2xl px-3 py-2 bg-gray-800/90 text-gray-400 text-xs max-w-[90%]"
    role="status"
    aria-live="polite"
  >
    <span class="flex gap-0.5 px-0.5" aria-hidden="true">
      <span class="w-1.5 h-1.5 rounded-full bg-gray-500 animate-bounce [animation-delay:-0.25s]" />
      <span class="w-1.5 h-1.5 rounded-full bg-gray-500 animate-bounce [animation-delay:-0.125s]" />
      <span class="w-1.5 h-1.5 rounded-full bg-gray-500 animate-bounce" />
    </span>
    <span>{{ label }}</span>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  label: string
}>()
</script>
