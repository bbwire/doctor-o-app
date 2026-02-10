<template>
  <div
    v-if="hasApiStatusChecked && !isApiReachable"
    class="bg-red-50 border-b border-red-200 text-red-700 px-4 py-2 text-sm dark:bg-red-900/20 dark:border-red-800 dark:text-red-300"
  >
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <p>
        <span v-if="isCheckingApiHealth">Reconnecting to API...</span>
        <span v-else>API is currently unreachable. Some features may not work until connection is restored.</span>
        <span v-if="lastCheckedText" class="ml-2 opacity-80">Last check: {{ lastCheckedText }}</span>
      </p>

      <UButton
        color="red"
        variant="outline"
        size="xs"
        icon="i-lucide-refresh-cw"
        :loading="isCheckingApiHealth"
        @click="onRetry"
      >
        Retry
      </UButton>
    </div>
  </div>
</template>

<script setup lang="ts">
const {
  isApiReachable,
  hasApiStatusChecked,
  isCheckingApiHealth,
  lastApiHealthCheckAt,
  checkApiHealth
} = useApiHealth()

const lastCheckedText = computed(() => {
  if (!lastApiHealthCheckAt.value) {
    return ''
  }

  return new Date(lastApiHealthCheckAt.value).toLocaleTimeString()
})

const onRetry = async () => {
  await checkApiHealth()
}
</script>

