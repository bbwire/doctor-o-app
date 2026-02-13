<template>
  <nav aria-label="Breadcrumb" class="mb-4 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
    <NuxtLink to="/" class="hover:text-primary-600 dark:hover:text-primary-400">
      Dashboard
    </NuxtLink>
    <template v-for="(item, i) in items" :key="i">
      <span class="text-gray-400 dark:text-gray-500">/</span>
      <NuxtLink
        v-if="item.to"
        :to="item.to"
        class="hover:text-primary-600 dark:hover:text-primary-400"
      >
        {{ item.label }}
      </NuxtLink>
      <span v-else class="font-medium text-gray-900 dark:text-white">
        {{ item.label }}
      </span>
    </template>
  </nav>
</template>

<script setup>
defineProps({
  items: {
    type: Array,
    default: () => []
  }
})
</script>
