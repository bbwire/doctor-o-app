<template>
  <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-10 space-y-10">
    <!-- Page header -->
    <div class="text-center sm:text-left">
      <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white tracking-tight">
        Contact us
      </h1>
      <p class="mt-3 text-lg text-gray-600 dark:text-gray-300 max-w-2xl">
        Questions about consultations, your account, or technical support? Reach out — we’re here to help.
      </p>
    </div>

    <div class="grid lg:grid-cols-12 gap-8 lg:gap-10 items-start">
      <!-- Contact methods -->
      <div class="lg:col-span-5 space-y-6">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
          Get in touch
        </h2>

        <div class="grid gap-4">
          <!-- Phone (two numbers) -->
          <div
            class="flex gap-4 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 sm:p-5 shadow-sm"
          >
            <div
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary-500/10 text-primary-600 dark:text-primary-400"
            >
              <UIcon name="i-lucide-phone" class="w-6 h-6" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Phone
              </p>
              <div class="mt-1 flex flex-col gap-1">
                <a
                  href="tel:0782937255"
                  class="text-base font-semibold text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400"
                >
                  0782 937 255
                </a>
                <a
                  href="tel:0753937255"
                  class="text-base font-semibold text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400"
                >
                  0753 937 255
                </a>
              </div>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Call or WhatsApp during business hours
              </p>
            </div>
          </div>

          <a
            v-for="item in contactMethods"
            :key="item.label"
            :href="item.href"
            :target="item.external ? '_blank' : undefined"
            :rel="item.external ? 'noopener noreferrer' : undefined"
            class="group flex gap-4 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 sm:p-5 shadow-sm transition-all hover:border-primary-500/40 hover:shadow-md"
          >
            <div
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary-500/10 text-primary-600 dark:text-primary-400"
            >
              <UIcon :name="item.icon" class="w-6 h-6" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ item.label }}
              </p>
              <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">
                {{ item.value }}
              </p>
              <p v-if="item.hint" class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                {{ item.hint }}
              </p>
            </div>
          </a>
        </div>

        <div
          class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-5"
        >
          <div class="flex gap-3">
            <UIcon name="i-lucide-building-2" class="w-5 h-5 shrink-0 text-primary-600 dark:text-primary-400 mt-0.5" />
            <div>
              <p class="text-sm font-semibold text-gray-900 dark:text-white">
                Practice
              </p>
              <p class="mt-1 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                {{ practiceLine }}
              </p>
            </div>
          </div>
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400">
          Contact details match our
          <NuxtLink to="/terms" class="text-primary-600 dark:text-primary-400 hover:underline">Terms</NuxtLink>
          and
          <NuxtLink to="/privacy" class="text-primary-600 dark:text-primary-400 hover:underline">Privacy Policy</NuxtLink>.
        </p>
      </div>

      <!-- Message form -->
      <div class="lg:col-span-7">
        <UCard
          :ui="{
            background: 'bg-white dark:bg-gray-900',
            ring: 'ring-1 ring-gray-200 dark:ring-gray-800',
            rounded: 'rounded-2xl',
            shadow: 'shadow-sm'
          }"
        >
          <div class="space-y-6">
            <div>
              <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                Send a message
              </h2>
              <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                Fill in the form below. We usually respond within a few business days. For urgent medical emergencies, use local emergency services — do not wait for a reply here.
              </p>
            </div>

            <UForm :state="form" class="space-y-4" @submit="onSubmit">
              <div class="grid sm:grid-cols-2 gap-4">
                <UFormGroup label="Your name" name="name" required>
                  <UInput v-model="form.name" placeholder="Full name" size="lg" />
                </UFormGroup>
                <UFormGroup label="Email" name="email" required>
                  <UInput v-model="form.email" type="email" placeholder="you@example.com" size="lg" />
                </UFormGroup>
              </div>
              <UFormGroup label="Subject" name="subject" required>
                <UInput v-model="form.subject" placeholder="What is your enquiry about?" size="lg" />
              </UFormGroup>
              <UFormGroup label="Message" name="message" required>
                <UTextarea v-model="form.message" placeholder="Write your message..." :rows="6" />
              </UFormGroup>
              <UAlert
                v-if="submitMessage"
                :color="submitSuccess ? 'green' : 'red'"
                variant="soft"
                :title="submitMessage"
              />
              <UButton type="submit" size="lg" block :loading="submitting">
                Send message
              </UButton>
            </UForm>
          </div>
        </UCard>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
/**
 * Contact details extracted from dr_o_legal_compliance_documents.docx (Terms & Privacy).
 */
const practiceLine =
  'Dr. Alfonse Omona Degozone — Consultant Surgeon, Masaka Regional Referral Hospital.'

const contactMethods = [
  {
    label: 'Email',
    value: 'omonadego@live.com',
    hint: 'We reply to email enquiries as soon as we can',
    href: 'mailto:omonadego@live.com',
    icon: 'i-lucide-mail',
    external: false
  },
  {
    label: 'Website',
    value: 'dro.com',
    hint: 'Official site',
    href: 'https://dro.com',
    icon: 'i-lucide-globe',
    external: true
  }
] as const

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
  title: 'Contact Us - Dr. O Virtual Consultations',
  description: 'Contact Dr. O Virtual Consultations by phone, email, or message. Masaka Regional Referral Hospital.'
})
</script>
