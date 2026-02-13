<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8 dark:bg-gray-950">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
          Admin Login
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-300">
          Sign in to access the admin dashboard
        </p>
      </div>
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <UForm :state="state" @submit="onSubmit" class="space-y-4">
          <UFormGroup label="Email address" name="email" required>
            <UInput
              v-model="state.email"
              type="email"
              placeholder="admin@dro.com"
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

          <UButton
            type="submit"
            block
            size="lg"
            :loading="loading"
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

const { login, fetchUser, logout, user } = useAuth()
const router = useRouter()
const toast = useToast()

const state = reactive({
  email: '',
  password: ''
})

const loading = ref(false)

const onSubmit = async () => {
  loading.value = true
  try {
    await login(state.email, state.password)
    await fetchUser()

    if (user.value?.role !== 'admin') {
      await logout()
      toast.add({
        title: 'Access denied',
        description: 'Admin role required to access the dashboard.',
        color: 'red'
      })
      return
    }

    await router.push('/')
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
