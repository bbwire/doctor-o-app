<template>
  <div class="max-w-3xl mx-auto space-y-8">
    <div>
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Privacy Policy</h1>
      <p class="mt-2 text-gray-600 dark:text-gray-300">
        Last updated: {{ lastUpdated }}
      </p>
    </div>

    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <div class="prose prose-slate dark:prose-invert max-w-none space-y-6 text-gray-600 dark:text-gray-300">
        <section>
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">1. Information We Collect</h2>
          <p>
            We collect information you provide when registering (name, email, date of birth, preferred language), when booking consultations (reason for visit, scheduled time), and when using the service (messages, call recordings where applicable). We use this to deliver consultations, process payments, and improve our services.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">2. How We Use Your Data</h2>
          <p>
            Your data is used to facilitate consultations with healthcare professionals, manage your account, send notifications, process payments, and comply with legal obligations. Consultation content may be used for clinical documentation and, where applicable, transcription of recordings.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">3. Data Sharing</h2>
          <p>
            We share information only as necessary with the healthcare professionals you consult with, payment processors, and where required by law. We do not sell your personal data.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">4. Security</h2>
          <p>
            We use technical and organisational measures to protect your data. Communications are transmitted over secure channels. You are responsible for keeping your login credentials safe.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">5. Your Rights</h2>
          <p>
            You may access, correct, or request deletion of your personal data where applicable. You may withdraw consent for certain processing where consent is the legal basis. Contact us for any such requests.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">6. Consent for Consultations</h2>
          <p>
            By starting a consultation, you consent to the collection and use of your data (including voice/video where applicable) for the purpose of that consultation and related documentation, in line with this Privacy Policy.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">7. Changes</h2>
          <p>
            We may update this policy from time to time. The “Last updated” date at the top will be revised. Continued use of the service after changes constitutes acceptance.
          </p>
        </section>

        <p class="pt-4">
          <NuxtLink to="/contact" class="text-primary-600 dark:text-primary-400 hover:underline">Contact us</NuxtLink> for any privacy-related questions.
        </p>
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'default'
})

const lastUpdated = 'March 2025'

useSeoMeta({
  title: 'Privacy Policy - Dr. O Medical Services',
  description: 'Privacy Policy for Dr. O Medical Services. How we collect, use, and protect your data.'
})
</script>
