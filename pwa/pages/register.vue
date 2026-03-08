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
      <UCard :ui="cardUi">
        <UForm :state="state" @submit="onSubmit" class="space-y-4">
          <ApiOfflineInlineHint />

          <UFormGroup label="Account type" name="role" :required="true">
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

          <UFormGroup label="Phone number" name="phone">
            <UInput
              v-model="state.phone"
              type="tel"
              placeholder="+256..."
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

          <template v-if="state.role === 'doctor'">
            <div class="border-t border-gray-200 dark:border-gray-800 pt-4 mt-4">
              <p class="text-sm font-medium text-gray-900 dark:text-white mb-3">Professional details</p>
              <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                You can add or update academic documents in your Profile after signing in.
              </p>
            </div>
            <UFormGroup label="Speciality" name="speciality">
              <USelectMenu
                v-model="state.speciality"
                :options="specialityOptions"
                option-attribute="label"
                value-attribute="value"
                searchable
                placeholder="Select your speciality"
                size="lg"
              />
            </UFormGroup>
            <UFormGroup label="Current place of clinical work" name="institution_id">
              <USelectMenu
                v-model="state.institution_id"
                :options="institutionOptions"
                option-attribute="label"
                value-attribute="value"
                searchable
                placeholder="Select institution (optional)"
                size="lg"
              />
            </UFormGroup>
            <UFormGroup label="Registration / license number" name="license_number">
              <UInput
                v-model="state.license_number"
                placeholder="e.g. MDPC-12345"
                size="lg"
              />
            </UFormGroup>
            <UFormGroup label="Registration date" name="registration_date">
              <UInput
                v-model="state.registration_date"
                type="date"
                size="lg"
              />
            </UFormGroup>
            <UFormGroup label="Regulatory council" name="regulatory_council">
              <USelectMenu
                v-model="state.regulatory_council"
                :options="regulatoryCouncilOptions"
                option-attribute="label"
                value-attribute="value"
                searchable
                placeholder="Select council (optional)"
                size="lg"
              />
            </UFormGroup>
          </template>

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

          <UFormGroup name="consent" required>
            <UCheckbox v-model="state.consent" :ui="{ label: 'text-sm text-gray-600 dark:text-gray-300' }">
              <template #label>
                I agree to the
                <NuxtLink to="/privacy" target="_blank" class="text-primary-600 dark:text-primary-400 hover:underline">Privacy Policy</NuxtLink>
                and consent to the use of my data for this service.
              </template>
            </UCheckbox>
          </UFormGroup>

          <UButton
            type="submit"
            block
            size="lg"
            :loading="loading"
            :disabled="isApiOffline || !state.consent"
          >
            Create account
          </UButton>
        </UForm>
      </UCard>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: false
})

const { register } = useAuth()
const router = useRouter()
const toast = useToast()
const { isApiReachable, hasApiStatusChecked } = useApiHealth()

const cardUi = {
  background: 'bg-white dark:bg-gray-900',
  ring: 'ring-1 ring-gray-200 dark:ring-gray-800'
}

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
  phone: '',
  preferred_language: 'English',
  date_of_birth: '',
  password: '',
  password_confirmation: '',
  consent: false,
  speciality: '',
  institution_id: null,
  license_number: '',
  registration_date: '',
  regulatory_council: ''
})

const config = useRuntimeConfig()
const institutionOptions = ref<{ label: string; value: number }[]>([])

const specialityOptions = [
  { value: 'General Doctor', label: 'General Doctor' },
  { value: 'Physician', label: 'Physician' },
  { value: 'Surgeon', label: 'Surgeon' },
  { value: 'Paediatrician', label: 'Paediatrician' },
  { value: 'Nurse', label: 'Nurse' },
  { value: 'Pharmacist', label: 'Pharmacist' },
  { value: 'Gynecologist', label: 'Gynecologist' },
  { value: 'Dentist', label: 'Dentist' }
]

const regulatoryCouncilOptions = [
  { value: 'Uganda Medical and Dental Practitioners Council', label: 'Uganda Medical and Dental Practitioners Council' },
  { value: 'Uganda Nurses and Midwives Council', label: 'Uganda Nurses and Midwives Council' },
  { value: 'Allied Health Professionals Council', label: 'Allied Health Professionals Council' },
  { value: 'Uganda Pharmacy Board', label: 'Uganda Pharmacy Board' },
  { value: 'Other', label: 'Other' }
]

onMounted(async () => {
  try {
    const res = await $fetch<{ data: Array<{ id: number; name: string }> }>('/institutions', {
      baseURL: config.public.apiBase
    })
    institutionOptions.value = (res.data || []).map(i => ({ label: i.name, value: i.id }))
  } catch {
    institutionOptions.value = []
  }
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
    const payload = {
      role: state.role,
      name: state.name,
      email: state.email,
      phone: state.phone || undefined,
      preferred_language: state.preferred_language || undefined,
      date_of_birth: state.date_of_birth,
      password: state.password,
      password_confirmation: state.password_confirmation
    }
    if (state.role === 'doctor') {
      Object.assign(payload, {
        speciality: state.speciality || undefined,
        institution_id: state.institution_id || undefined,
        license_number: state.license_number || undefined,
        registration_date: state.registration_date || undefined,
        regulatory_council: state.regulatory_council || undefined
      })
    }
    await register(payload)
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
