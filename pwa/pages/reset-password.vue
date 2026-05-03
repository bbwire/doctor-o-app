<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-950 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <NuxtLink to="/login" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-300">
          <UIcon name="i-lucide-arrow-left" class="w-4 h-4 mr-1" />
          Back to sign in
        </NuxtLink>
        <div class="mt-5 flex justify-center">
          <NuxtLink to="/" aria-label="Dr. O — Home">
            <AppLogo height-class="h-12 sm:h-14 md:h-16" width-class="w-auto max-w-[min(92vw,20rem)]" />
          </NuxtLink>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
          Set a new password
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-300">
          Use the reset link from your email to set a new password.
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
              :disabled="hasPrefilledEmail"
            />
          </UFormGroup>

          <div
            v-if="hasPrefilledToken"
            class="rounded-md bg-emerald-50 px-3 py-2 text-xs text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300"
          >
            Reset link verified for <span class="font-semibold">{{ state.email || 'your account' }}</span>.
          </div>

          <UFormGroup
            v-else
            label="Reset token"
            name="token"
            required
            help="Paste the token from your reset email if the link did not open this page automatically."
          >
            <UInput
              v-model="state.token"
              placeholder="Paste token from email"
              size="lg"
            />
          </UFormGroup>

          <UFormGroup label="New password" name="password" required>
            <UInput
              v-model="state.password"
              :type="showPasswords ? 'text' : 'password'"
              placeholder="••••••••"
              size="lg"
            />
          </UFormGroup>

          <UFormGroup label="Confirm new password" name="password_confirmation" required>
            <UInput
              v-model="state.password_confirmation"
              :type="showPasswords ? 'text' : 'password'"
              placeholder="••••••••"
              size="lg"
            />
          </UFormGroup>

          <UCheckbox v-model="showPasswords" label="Show passwords" />

          <UButton
            type="submit"
            block
            size="lg"
            :loading="loading"
            :disabled="isApiOffline"
          >
            Reset password
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

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const toast = useToast()
const { isApiReachable, hasApiStatusChecked } = useApiHealth()

const queryEmail = typeof route.query.email === 'string' ? route.query.email : ''
const queryToken = typeof route.query.token === 'string' ? route.query.token : ''

const loading = ref(false)
const showPasswords = ref(false)
const isApiOffline = computed(() => hasApiStatusChecked.value && !isApiReachable.value)
const hasPrefilledEmail = computed(() => !!queryEmail)
const hasPrefilledToken = computed(() => !!queryToken)

const state = reactive({
  email: queryEmail,
  token: queryToken,
  password: '',
  password_confirmation: ''
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
    const response = await $fetch<{ message: string }>('/reset-password', {
      method: 'POST',
      baseURL: config.public.apiBase,
      headers: {
        Accept: 'application/json'
      },
      body: {
        email: state.email,
        token: state.token,
        password: state.password,
        password_confirmation: state.password_confirmation
      }
    })

    toast.add({
      title: 'Password reset complete',
      description: response.message || 'You can now sign in with your new password.',
      color: 'green'
    })

    await router.push('/login')
  } catch (error) {
    const err = error && typeof error === 'object' ? error : null
    const message = err && 'data' in err && typeof err.data === 'object' && err.data && 'message' in err.data
      ? err.data.message
      : null

    toast.add({
      title: 'Unable to reset password',
      description: typeof message === 'string' ? message : 'Please verify token, email, and password.',
      color: 'red'
    })
  } finally {
    loading.value = false
  }
}
</script>

