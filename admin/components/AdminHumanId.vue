<template>
  <span
    v-if="value"
    :class="[
      'inline-flex max-w-full items-center rounded-md border font-mono font-semibold tracking-wide text-gray-900 dark:text-gray-100',
      variant === 'lg'
        ? 'border-gray-300 bg-gray-100 px-3 py-1 text-base dark:border-gray-600 dark:bg-gray-800/80'
        : 'border-gray-200 bg-gray-50 px-2 py-0.5 text-xs dark:border-gray-700 dark:bg-gray-900/50'
    ]"
  >
    {{ value }}
  </span>
  <span v-else-if="showDash" class="text-gray-400">—</span>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    value?: string | null
    variant?: 'sm' | 'lg'
    showDash?: boolean
  }>(),
  { variant: 'sm', showDash: true }
)
</script>
