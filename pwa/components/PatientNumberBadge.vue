<template>
  <span
    v-if="patientNumber"
    class="inline-flex max-w-full items-center rounded-md border font-mono font-bold tracking-wide text-primary-900 dark:text-primary-100"
    :class="size === 'lg'
      ? 'border-primary-300 bg-primary-100 px-3 py-1 text-base dark:border-primary-600 dark:bg-primary-950/70'
      : 'border-primary-200 bg-primary-50 px-2 py-0.5 text-sm dark:border-primary-700 dark:bg-primary-950/50'"
  >
    {{ patientNumber }}
  </span>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    patientNumber?: string | null
    size?: 'sm' | 'lg'
  }>(),
  { size: 'sm' }
)
</script>
