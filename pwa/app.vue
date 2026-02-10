<template>
  <div>
    <ApiStatusBanner />
    <UNotifications />
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </div>
</template>

<script setup>
useHead({
  meta: [
    { name: 'viewport', content: 'width=device-width, initial-scale=1' }
  ],
  link: [
    { rel: 'icon', href: '/favicon.ico' }
  ],
  htmlAttrs: {
    lang: 'en'
  }
})

useSeoMeta({
  title: 'Dr. O Medical Services',
  description: 'Connect with healthcare professionals through secure text, audio, and video consultations.',
  ogTitle: 'Dr. O Medical Services',
  ogDescription: 'Connect with healthcare professionals through secure text, audio, and video consultations.'
})
</script>
