<template>
  <LegalDocument
    title="Patient Consent Form"
    subtitle="Dr. O Virtual Consultations — informed consent for virtual consultation"
    last-updated="March 2026"
    :body="consentBody"
  />
</template>

<script setup lang="ts">
import consentBody from '~/content/legal/patient-consent-form.md?raw'

definePageMeta({
  layout: 'default'
})

useSeoMeta({
  title: 'Consent Form - Dr. O Virtual Consultations',
  description:
    'Patient consent form for Dr. O Virtual Consultations, including virtual consultation limitations, privacy, communication, and declaration.'
})
</script>
