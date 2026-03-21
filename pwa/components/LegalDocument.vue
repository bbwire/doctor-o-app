<template>
  <div class="max-w-3xl mx-auto space-y-8">
    <div>
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
        {{ title }}
      </h1>
      <p v-if="subtitle" class="mt-2 text-gray-600 dark:text-gray-300">
        {{ subtitle }}
      </p>
      <p v-if="lastUpdated" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        Last updated: {{ lastUpdated }}
      </p>
    </div>

    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <div class="prose prose-slate dark:prose-invert max-w-none text-gray-600 dark:text-gray-300">
        <p
          v-for="(block, i) in blocks"
          :key="i"
          class="whitespace-pre-wrap text-sm sm:text-base leading-relaxed mb-4 last:mb-0"
        >
          {{ block }}
        </p>
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    title: string
    subtitle?: string
    lastUpdated?: string
    /** Raw text from .md / docx extract (paragraphs separated by blank lines). */
    body: string
  }>(),
  {
    subtitle: '',
    lastUpdated: ''
  }
)

const blocks = computed(() => {
  return props.body
    .split(/\n\s*\n/)
    .map((s) => s.trim())
    .filter(Boolean)
})
</script>
