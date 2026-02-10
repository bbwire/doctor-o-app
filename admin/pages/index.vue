<template>
  <div>
    <UPageHero
      title="Admin Dashboard"
      description="Manage users, institutions, consultations, and platform settings"
    />
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth-admin'
})
</script>
