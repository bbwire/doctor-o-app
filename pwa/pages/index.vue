<template>
  <div class="space-y-12">
    <section class="relative overflow-hidden rounded-2xl border border-primary-200/60 dark:border-primary-900/50 bg-gradient-to-br from-primary-50 via-white to-indigo-50 dark:from-primary-950/40 dark:via-gray-900 dark:to-indigo-950/30 text-center py-12 px-6">
      <div class="absolute inset-0 pointer-events-none opacity-50 dark:opacity-40 bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.18),_transparent_40%)]" />
      <p class="relative inline-flex items-center rounded-full border border-primary-200 dark:border-primary-800 bg-white/80 dark:bg-gray-900/80 px-3 py-1 text-xs font-medium text-primary-700 dark:text-primary-300">
        Virtual care platform
      </p>
      <h1 class="relative mt-4 text-3xl md:text-5xl font-bold tracking-tight text-gray-900 dark:text-white">
        Dr. O Medical Services
      </h1>
      <p class="relative mt-4 text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
        Connect with healthcare professionals through secure text, audio, and video consultations. Get digital prescriptions, investigations, and referrals in one place.
      </p>
      <div class="relative mt-7 flex items-center justify-center gap-3">
        <UButton to="/register" size="lg" icon="i-lucide-arrow-right">
          Get Started
        </UButton>
        <UButton to="/login" size="lg" color="gray" variant="soft">
          Sign In
        </UButton>
      </div>
    </section>

    <section id="features">
      <div class="text-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Everything you need for virtual healthcare</h2>
        <p class="text-gray-600 dark:text-gray-300 mt-2">Dr. O provides a comprehensive platform for patients to consult with healthcare professionals seamlessly.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <UCard
          v-for="feature in features"
          :key="feature.title"
          :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }"
        >
          <div class="flex items-start gap-3">
            <span class="rounded-lg bg-primary-100 dark:bg-primary-900/40 p-2">
              <UIcon :name="feature.icon" class="w-5 h-5 text-primary-600 dark:text-primary-300" />
            </span>
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ feature.title }}</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ feature.description }}</p>
            </div>
          </div>
        </UCard>
      </div>
    </section>
  </div>
</template>

<script setup>
const features = [
  {
    icon: 'i-lucide-message-circle',
    title: 'Text Consultations',
    description: 'Chat with doctors in real-time through secure messaging. Share symptoms, ask questions, and get medical advice.'
  },
  {
    icon: 'i-lucide-phone',
    title: 'Audio & Video Calls',
    description: 'Have face-to-face consultations with healthcare professionals through high-quality audio and video calls.'
  },
  {
    icon: 'i-lucide-file-text',
    title: 'Digital Prescriptions',
    description: 'Receive and manage your prescriptions digitally. Download, share, and track your medications easily.'
  },
  {
    icon: 'i-lucide-clipboard-list',
    title: 'Investigations & Referrals',
    description: 'Get lab orders, imaging requests, and specialist referrals, all managed through your dashboard.'
  },
  {
    icon: 'i-lucide-shield-check',
    title: 'Secure & Private',
    description: 'Your health data is encrypted and protected. We follow strict privacy and security standards.'
  },
  {
    icon: 'i-lucide-clock',
    title: '24/7 Access',
    description: 'Access your medical records, prescriptions, and consultation history anytime, anywhere.'
  }
]
</script>
