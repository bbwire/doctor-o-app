<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-950 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <NuxtLink to="/" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-300">
          <UIcon name="i-lucide-arrow-left" class="w-4 h-4 mr-1" />
          Back to home
        </NuxtLink>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
          Reset your password
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-300">
          Enter your email and we will send a link to open the reset page and choose a new password.
        </p>
      </div>

      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <UForm :state="state" @submit="onSubmit" class="space-y-4">
          <ApiOfflineInlineHint />

          <UFormGroup label="Email address" name="email" required>
            <UInput
              v-model="state.email"
              type="email"
              placeholder="you@example.com"
              size="lg"
            />
          </UFormGroup>

          <UButton
            type="submit"
            block
            size="lg"
            :loading="loading"
            :disabled="isApiOffline"
          >
            Send reset instructions
          </UButton>
        </UForm>
      </UCard>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  layout: false
})

const toast = useToast()
const config = useRuntimeConfig()
const loading = ref(false)
const { isApiReachable, hasApiStatusChecked } = useApiHealth()
const isApiOffline = computed(() => hasApiStatusChecked.value && !isApiReachable.value)

const state = reactive({
  email: ''
})

const onSubmit = async () => {
  if (isApiOffline.value) {
    toast.add({
      title: 'API unavailable',
      description: 'Please retry when API connection is restored.',
      color: 'amber'
    })
    return
  }

  loading.value = true

  try {
    const response = await $fetch<{ message: string }>('/forgot-password', {
      method: 'POST',
      baseURL: config.public.apiBase,
      headers: {
        Accept: 'application/json'
      },
      body: {
        email: state.email
      }
    })

    toast.add({
      title: 'Reset link sent',
      description: response.message || 'Please check your email for reset instructions.',
      color: 'green'
    })
  } catch (error) {
    const err = error && typeof error === 'object' ? error : null
    const message = err && 'data' in err && typeof err.data === 'object' && err.data && 'message' in err.data
      ? err.data.message
      : null

    toast.add({
      title: 'Unable to send reset link',
      description: typeof message === 'string' ? message : 'Please try again.',
      color: 'red'
    })
  } finally {
    loading.value = false
  }
}
</script>

