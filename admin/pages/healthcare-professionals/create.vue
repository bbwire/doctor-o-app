<template>
  <div class="space-y-6">
    <AdminBreadcrumbs :items="[{ label: 'Professionals', to: '/healthcare-professionals' }, { label: 'New professional' }]" />
    <div class="flex items-center gap-3">
      <UButton to="/healthcare-professionals" variant="ghost" icon="i-lucide-arrow-left" size="sm">
        Back
      </UButton>
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          New healthcare professional
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
          Create a new doctor account and professional profile.
        </p>
      </div>
    </div>

    <UAlert
      v-if="errorMessage"
      icon="i-lucide-alert-triangle"
      color="red"
      variant="soft"
      :title="errorMessage"
    />

    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <UForm :state="form" @submit="onSubmit" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="space-y-4">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">
              Account (user)
            </h2>
            <UFormGroup label="Full name" name="name" required>
              <UInput v-model="form.name" placeholder="Dr. Jane Doe" />
            </UFormGroup>
            <UFormGroup label="Email" name="email" required>
              <UInput v-model="form.email" type="email" placeholder="doctor@example.com" />
            </UFormGroup>
            <UFormGroup label="Password" name="password" required hint="Min 8 characters">
              <UInput
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
              >
                <template #trailing>
                  <UButton
                    :icon="showPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                    variant="ghost"
                    size="xs"
                    color="neutral"
                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                    @click.prevent="showPassword = !showPassword"
                  />
                </template>
              </UInput>
            </UFormGroup>
            <UFormGroup label="Confirm password" name="password_confirmation" required>
              <UInput
                v-model="form.password_confirmation"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
              />
            </UFormGroup>
            <UFormGroup label="Phone" name="phone">
              <UInput v-model="form.phone" type="tel" placeholder="+256 ..." />
            </UFormGroup>
            <UFormGroup label="Date of birth" name="date_of_birth">
              <UInput v-model="form.date_of_birth" type="date" />
            </UFormGroup>
          </div>

          <div class="space-y-4">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">
              Professional profile
            </h2>
            <UFormGroup label="Institution" name="institution_id">
              <USelectMenu
                v-model="form.institution_id"
                :options="institutionOptions"
                value-attribute="value"
                option-attribute="label"
                placeholder="Select institution (optional)"
              />
            </UFormGroup>
            <UFormGroup label="Speciality" name="speciality">
              <USelectMenu
                v-model="form.speciality"
                :options="specialityOptions"
                value-attribute="value"
                option-attribute="label"
                searchable
                placeholder="Select speciality"
              />
            </UFormGroup>
            <UFormGroup label="Registration / license number" name="license_number">
              <UInput v-model="form.license_number" placeholder="e.g. MDPC-12345" />
            </UFormGroup>
            <UFormGroup label="Registration date" name="registration_date">
              <UInput v-model="form.registration_date" type="date" />
            </UFormGroup>
            <UFormGroup label="Regulatory council" name="regulatory_council">
              <USelectMenu
                v-model="form.regulatory_council"
                :options="regulatoryCouncilOptions"
                value-attribute="value"
                option-attribute="label"
                searchable
                placeholder="Select council (optional)"
              />
            </UFormGroup>
            <UFormGroup label="Active" name="is_active">
              <UCheckbox v-model="form.is_active" />
            </UFormGroup>
          </div>
        </div>

        <UFormGroup label="Bio" name="bio">
          <UTextarea v-model="form.bio" placeholder="Short professional bio" :rows="3" />
        </UFormGroup>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          The professional can upload academic documents (e.g. practicing license, certificates) in their Profile after signing in to the app.
        </p>
        <div class="flex gap-2 pt-2">
          <UButton type="submit" :loading="saving">Create professional</UButton>
          <UButton variant="outline" to="/healthcare-professionals">Cancel</UButton>
        </div>
      </UForm>
    </UCard>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth-admin'
})

const router = useRouter()
const toast = useToast()
const { get, post } = useAdminApi()

const showPassword = ref(false)
const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: '',
  date_of_birth: '',
  institution_id: null,
  speciality: '',
  license_number: '',
  registration_date: '',
  regulatory_council: '',
  bio: '',
  is_active: true
})

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

const institutionOptions = ref([{ label: '— None —', value: null }])
const errorMessage = ref('')
const saving = ref(false)

onMounted(async () => {
  try {
    const instRes = await get('admin/institutions', { query: { per_page: '100' } })
    const insts = instRes?.data ?? []
    institutionOptions.value = [{ label: '— None —', value: null }, ...insts.map(i => ({ label: i.name, value: i.id }))]
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to load institutions.'
  }
})

async function onSubmit () {
  errorMessage.value = ''
  saving.value = true
  try {
    await post('admin/healthcare-professionals', {
      name: form.name,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation,
      phone: form.phone || null,
      date_of_birth: form.date_of_birth || null,
      institution_id: form.institution_id || null,
      speciality: form.speciality || null,
      license_number: form.license_number || null,
      registration_date: form.registration_date || null,
      regulatory_council: form.regulatory_council || null,
      bio: form.bio || null,
      is_active: form.is_active
    })
    toast.add({ title: 'Healthcare professional created', description: 'The doctor can now sign in with the email and password you set.', color: 'green' })
    await router.push('/healthcare-professionals')
  } catch (e) {
    errorMessage.value = e?.data?.message || e?.data?.errors?.email?.[0] || e?.data?.errors?.password?.[0] || 'Failed to create.'
  } finally {
    saving.value = false
  }
}
</script>
