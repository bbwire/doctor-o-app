<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-950 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <NuxtLink to="/" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-300">
          <UIcon name="i-lucide-arrow-left" class="w-4 h-4 mr-1" />
          Back to home
        </NuxtLink>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
          Sign in to your account
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-300">
          Or
          <NuxtLink to="/register" class="font-medium text-primary-600 hover:text-primary-500">
            create a new account
          </NuxtLink>
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

          <UFormGroup label="Password" name="password" required>
            <UInput
              v-model="state.password"
              type="password"
              placeholder="••••••••"
              size="lg"
            />
          </UFormGroup>

          <div class="flex items-center justify-between">
            <UCheckbox v-model="state.remember" label="Remember me" />
            <NuxtLink to="/forgot-password" class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-300 dark:hover:text-primary-200">
              Forgot password?
            </NuxtLink>
          </div>

          <UButton
            type="submit"
            block
            size="lg"
            :loading="loading"
            :disabled="isApiOffline"
          >
            Sign in
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

const { login } = useAuth()
const router = useRouter()
const toast = useToast()
const { isApiReachable, hasApiStatusChecked } = useApiHealth()

const state = reactive({
  email: '',
  password: '',
  remember: false
})

const loading = ref(false)
const isApiOffline = computed(() => hasApiStatusChecked.value && !isApiReachable.value)

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
    await login(state.email, state.password)
    await router.push('/dashboard')
  } catch (error) {
    toast.add({
      title: 'Unable to sign in',
      description: error?.message || 'Please check your credentials and try again.',
      color: 'red'
    })
  } finally {
    loading.value = false
  }
}
</script>
