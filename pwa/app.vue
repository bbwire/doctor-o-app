<template>
  <div>
    <ApiStatusBanner />
    <UNotifications />
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
    <UModals />
  </div>
</template>

<script setup>
const theme = useState('theme', () => 'light')

onMounted(() => {
  const saved = window.localStorage.getItem('theme')
  if (saved === 'light' || saved === 'dark') {
    theme.value = saved
  } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    theme.value = 'dark'
  }

  document.documentElement.classList.toggle('dark', theme.value === 'dark')
})

watch(theme, (value) => {
  if (typeof window === 'undefined') return
  document.documentElement.classList.toggle('dark', value === 'dark')
  window.localStorage.setItem('theme', value)
})

useHead({
  meta: [
    { name: 'viewport', content: 'width=device-width, initial-scale=1, viewport-fit=cover' },
    { name: 'theme-color', content: '#0f172a' },
    { name: 'apple-mobile-web-app-capable', content: 'yes' },
    { name: 'apple-mobile-web-app-status-bar-style', content: 'black-translucent' },
    { name: 'mobile-web-app-capable', content: 'yes' }
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
