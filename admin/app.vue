<template>
  <div class="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-50">
    <UNotifications />
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </div>
</template>

<script setup>
const theme = useState('theme', () => 'light')

onMounted(() => {
  if (!process.client) return

  const saved = window.localStorage.getItem('theme')
  if (saved === 'light' || saved === 'dark') {
    theme.value = saved
  } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    theme.value = 'dark'
  }

  document.documentElement.classList.toggle('dark', theme.value === 'dark')
})

watch(theme, (value) => {
  if (!process.client) return
  document.documentElement.classList.toggle('dark', value === 'dark')
  window.localStorage.setItem('theme', value)
})

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
  title: 'Dr. O Admin Dashboard',
  description: 'Admin dashboard for Dr. O Medical Services platform.'
})
</script>
