<template>
  <span
    v-if="patientNumber"
    :class="[
      'inline-flex max-w-full items-center rounded-md border font-mono font-bold tracking-wide text-primary-900 dark:text-primary-100',
      variant === 'hero'
        ? 'border-primary-300 bg-primary-100 px-3 py-1.5 text-lg dark:border-primary-600 dark:bg-primary-950/70'
        : 'border-primary-200 bg-primary-50 px-2 py-0.5 text-sm dark:border-primary-700 dark:bg-primary-950/50'
    ]"
  >
    {{ patientNumber }}
  </span>
  <span v-else-if="showDash" class="text-gray-400">—</span>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    patientNumber?: string | null
    /** Larger banner style for profile headers */
    variant?: 'badge' | 'hero'
    /** Show em dash when empty (default true for tables) */
    showDash?: boolean
  }>(),
  { variant: 'badge', showDash: true }
)
</script>
