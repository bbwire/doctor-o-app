<template>
  <div class="max-w-3xl mx-auto space-y-8">
    <div>
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">About Us</h1>
      <p class="mt-2 text-gray-600 dark:text-gray-300">
        Learn more about Dr. O Medical Services and our mission.
      </p>
    </div>

    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <div class="prose prose-slate dark:prose-invert max-w-none">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Our Mission</h2>
        <p class="text-gray-600 dark:text-gray-300">
          Dr. O Medical Services connects patients with healthcare professionals through secure text, audio, and video consultations. We aim to make quality care accessible and convenient.
        </p>

        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-8">What We Offer</h2>
        <ul class="list-disc pl-6 space-y-2 text-gray-600 dark:text-gray-300">
          <li>Secure consultations via text, audio, or video</li>
          <li>Digital prescriptions and referrals</li>
          <li>Easy booking and appointment management</li>
          <li>Wallet and payment integration for seamless transactions</li>
        </ul>

        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-8">Contact</h2>
        <p class="text-gray-600 dark:text-gray-300">
          For questions or support, please visit our <NuxtLink to="/contact" class="text-primary-600 dark:text-primary-400 hover:underline">Contact Us</NuxtLink> page.
        </p>
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'default'
})

useSeoMeta({
  title: 'About Us - Dr. O Medical Services',
  description: 'Learn about Dr. O Medical Services and our mission to connect patients with healthcare professionals.'
})
</script>
