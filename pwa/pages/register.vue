<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-950 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <NuxtLink to="/" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-300">
          <UIcon name="i-lucide-arrow-left" class="w-4 h-4 mr-1" />
          Back to home
        </NuxtLink>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
          Create your account
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-300">
          Already have an account?
          <NuxtLink to="/login" class="font-medium text-primary-600 hover:text-primary-500">
            Sign in
          </NuxtLink>
        </p>
      </div>
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <UForm :state="state" @submit="onSubmit" class="space-y-4">
          <ApiOfflineInlineHint />

          <UFormGroup label="I am registering as" name="role" required>
            <div class="grid grid-cols-2 gap-3">
              <button
                type="button"
                class="relative rounded-xl border px-3 py-3 text-left transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/70"
                :class="state.role === 'patient'
                  ? 'border-primary-500 bg-primary-500/10 shadow-sm'
                  : 'border-gray-200 dark:border-gray-800 bg-gray-900/40 hover:border-primary-500/60'"
                @click="state.role = 'patient'"
              >
                <div class="flex items-center gap-3">
                  <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-500/15 text-primary-400">
                    <UIcon name="i-lucide-user-round" class="w-5 h-5" />
                  </div>
                  <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                      Patient
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      For people booking consultations.
                    </p>
                  </div>
                </div>
                <span
                  v-if="state.role === 'patient'"
                  class="pointer-events-none absolute -top-2 -right-2 inline-flex items-center justify-center rounded-full bg-primary-500 text-[10px] font-semibold text-white px-2 py-0.5 shadow-sm"
                >
                  Selected
                </span>
              </button>

              <button
                type="button"
                class="relative rounded-xl border px-3 py-3 text-left transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/70"
                :class="state.role === 'doctor'
                  ? 'border-primary-500 bg-primary-500/10 shadow-sm'
                  : 'border-gray-200 dark:border-gray-800 bg-gray-900/40 hover:border-primary-500/60'"
                @click="state.role = 'doctor'"
              >
                <div class="flex items-center gap-3">
                  <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-400">
                    <UIcon name="i-lucide-stethoscope" class="w-5 h-5" />
                  </div>
                  <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                      Doctor
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      For licensed healthcare professionals.
                    </p>
                  </div>
                </div>
                <span
                  v-if="state.role === 'doctor'"
                  class="pointer-events-none absolute -top-2 -right-2 inline-flex items-center justify-center rounded-full bg-primary-500 text-[10px] font-semibold text-white px-2 py-0.5 shadow-sm"
                >
                  Selected
                </span>
              </button>
            </div>
          </UFormGroup>

          <UFormGroup label="Full Name" name="name" required>
            <UInput
              v-model="state.name"
              placeholder="John Doe"
              size="lg"
            />
          </UFormGroup>

          <UFormGroup label="Email address" name="email" required>
            <UInput
              v-model="state.email"
              type="email"
              placeholder="you@example.com"
              size="lg"
            />
          </UFormGroup>

          <UFormGroup label="Date of birth" name="date_of_birth" required>
            <UInput
              v-model="state.date_of_birth"
              type="date"
              size="lg"
            />
          </UFormGroup>

          <UFormGroup label="Preferred Language" name="preferred_language">
            <USelectMenu
              v-model="state.preferred_language"
              :options="languageOptions"
              size="lg"
            />
          </UFormGroup>

          <UFormGroup label="Password" name="password" required>
            <UInput
              v-model="state.password"
              :type="showPasswords ? 'text' : 'password'"
              placeholder="••••••••"
              size="lg"
            />
          </UFormGroup>

          <UFormGroup label="Confirm Password" name="password_confirmation" required>
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
            Create account
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

const { register } = useAuth()
const router = useRouter()
const toast = useToast()
const { isApiReachable, hasApiStatusChecked } = useApiHealth()
const languageOptions = [
  'English',
  'Luganda',
  'Swahili',
  'Runyankole',
  'Rukiga',
  'Runyoro',
  'Rutooro',
  'Ateso',
  'Acholi',
  'Lango',
  'Lugbara',
  'Alur'
]
const showPasswords = ref(false)

const roleOptions = [
  { label: 'Patient', value: 'patient' },
  { label: 'Doctor', value: 'doctor' }
]

const state = reactive({
  role: 'patient',
  name: '',
  email: '',
  preferred_language: 'English',
  date_of_birth: '',
  password: '',
  password_confirmation: ''
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

  if (!state.date_of_birth) {
    toast.add({
      title: 'Date of birth required',
      description: 'Please provide your date of birth to continue.',
      color: 'red'
    })
    return
  }

  const today = new Date()
  const dob = new Date(state.date_of_birth)
  if (Number.isNaN(dob.getTime())) {
    toast.add({
      title: 'Invalid date of birth',
      description: 'Please provide a valid date of birth.',
      color: 'red'
    })
    return
  }
  const age = today.getFullYear() - dob.getFullYear() - (today < new Date(today.getFullYear(), dob.getMonth(), dob.getDate()) ? 1 : 0)
  if (age < 18) {
    toast.add({
      title: 'Age restriction',
      description: 'You must be at least 18 years old to register.',
      color: 'red'
    })
    return
  }

  loading.value = true
  try {
    await register({
      role: state.role,
      name: state.name,
      email: state.email,
      preferred_language: state.preferred_language,
      date_of_birth: state.date_of_birth,
      password: state.password,
      password_confirmation: state.password_confirmation
    })
    await router.push('/dashboard')
  } catch (error) {
    toast.add({
      title: 'Unable to create account',
      description: error?.message || 'Please verify your information and try again.',
      color: 'red'
    })
  } finally {
    loading.value = false
  }
}
</script>
