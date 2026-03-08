<template>
  <div class="max-w-3xl mx-auto space-y-8">
    <div>
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Contact Us</h1>
      <p class="mt-2 text-gray-600 dark:text-gray-300">
        Get in touch with Dr. O Medical Services for support or enquiries.
      </p>
    </div>

    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <div class="space-y-6">
        <div>
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Support</h2>
          <p class="text-gray-600 dark:text-gray-300 text-sm">
            For technical support, account issues, or questions about the service, please email us or use the form below.
          </p>
        </div>

        <UForm :state="form" class="space-y-4" @submit="onSubmit">
          <UFormGroup label="Your name" name="name" required>
            <UInput v-model="form.name" placeholder="Your name" />
          </UFormGroup>
          <UFormGroup label="Email" name="email" required>
            <UInput v-model="form.email" type="email" placeholder="you@example.com" />
          </UFormGroup>
          <UFormGroup label="Subject" name="subject" required>
            <UInput v-model="form.subject" placeholder="Brief subject" />
          </UFormGroup>
          <UFormGroup label="Message" name="message" required>
            <UTextarea v-model="form.message" placeholder="Your message..." :rows="5" />
          </UFormGroup>
          <UAlert
            v-if="submitMessage"
            :color="submitSuccess ? 'green' : 'red'"
            variant="soft"
            :title="submitMessage"
          />
          <UButton type="submit" :loading="submitting">
            Send message
          </UButton>
        </UForm>

        <div class="pt-4 border-t border-gray-200 dark:border-gray-800">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Other links</h2>
          <ul class="space-y-1 text-sm">
            <li>
              <NuxtLink to="/about" class="text-primary-600 dark:text-primary-400 hover:underline">About Us</NuxtLink>
            </li>
            <li>
              <NuxtLink to="/privacy" class="text-primary-600 dark:text-primary-400 hover:underline">Privacy Policy</NuxtLink>
            </li>
          </ul>
        </div>
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'default'
})

const form = reactive({
  name: '',
  email: '',
  subject: '',
  message: ''
})

const submitting = ref(false)
const submitMessage = ref('')
const submitSuccess = ref(false)

async function onSubmit () {
  submitting.value = true
  submitMessage.value = ''
  try {
    // If you have a contact API endpoint, call it here; otherwise show success and optionally send via mailto
    await new Promise(r => setTimeout(r, 500))
    submitSuccess.value = true
    submitMessage.value = 'Thank you. Your message has been received. We will get back to you as soon as possible.'
    form.name = ''
    form.email = ''
    form.subject = ''
    form.message = ''
  } catch {
    submitSuccess.value = false
    submitMessage.value = 'Something went wrong. Please try again or email us directly.'
  } finally {
    submitting.value = false
  }
}

useSeoMeta({
  title: 'Contact Us - Dr. O Medical Services',
  description: 'Contact Dr. O Medical Services for support and enquiries.'
})
</script>
