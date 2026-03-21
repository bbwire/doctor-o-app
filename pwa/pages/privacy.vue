<template>
  <LegalDocument
    title="Privacy Policy"
    subtitle="Dr. O Virtual Consultations — how we collect, use, and protect your data (Document 2)"
    last-updated="March 2026"
    :body="privacyBody"
  />
</template>

<script setup lang="ts">
import privacyBody from '~/content/legal/privacy-policy.md?raw'

definePageMeta({
  layout: 'default'
})

useSeoMeta({
  title: 'Privacy Policy - Dr. O Virtual Consultations',
  description:
    'Privacy Policy for Dr. O Virtual Consultations. Data controller, legal bases, rights, retention, and complaints under Ugandan law.'
})
</script>
