<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        Settings
      </h1>
      <p class="text-gray-600 dark:text-gray-300">
        General application and preferences. More options will appear here as the API supports them.
      </p>
    </div>

    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
        General
      </h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        App name, support email, maintenance mode, and other global settings can be added when the API provides a settings or config endpoint.
      </p>
    </UCard>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth-admin'
})
</script>
