<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Admin Login
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Sign in to access the admin dashboard
        </p>
      </div>
      <UCard>
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

const { login } = useAuth()
const router = useRouter()

const state = reactive({
  email: '',
  password: ''
})

const loading = ref(false)

const onSubmit = async () => {
  loading.value = true
  try {
    await login(state.email, state.password)
    // Check if user is admin
    const { user } = useAuth()
    if (user.value?.role !== 'admin') {
      throw new Error('Access denied. Admin role required.')
    }
    await router.push('/users')
  } catch (error: any) {
    // Error handling
    console.error(error)
  } finally {
    loading.value = false
  }
}
</script>
