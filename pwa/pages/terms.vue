<template>
  <LegalDocument
    title="Terms of Service"
    subtitle="Dr. O Virtual Consultations — legal & compliance (Document 1)"
    last-updated="March 2026"
    :body="termsBody"
  />
</template>

<script setup lang="ts">
import termsBody from '~/content/legal/terms-of-service.md?raw'

definePageMeta({
  layout: 'default'
})

useSeoMeta({
  title: 'Terms of Service - Dr. O Virtual Consultations',
  description:
    'Terms of Service for Dr. O Virtual Consultations. Eligibility, consultations, fees, privacy, liability, and contact.'
})
</script>
