<template>
  <UAlert
    v-if="hasApiStatusChecked && !isApiReachable"
    icon="i-lucide-wifi-off"
    color="amber"
    variant="soft"
    title="API connection issue"
    description="The API is currently unreachable. Saving or loading data may fail until connection is restored."
  />
</template>

<script setup lang="ts">
const { isApiReachable, hasApiStatusChecked } = useApiHealth()
</script>

