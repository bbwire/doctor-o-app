<template>
  <div class="space-y-6">
    <AdminBreadcrumbs :items="[{ label: 'Patients', to: '/patients' }, { label: 'New patient' }]" />
    <div class="flex items-center gap-3">
      <UButton to="/patients" variant="ghost" icon="i-lucide-arrow-left" size="sm">
        Back
      </UButton>
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          New patient
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
          Create a patient account. You can add chronic conditions here or edit them later.
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
      <UForm :state="form" @submit="onSubmit" class="space-y-4">
        <UFormGroup label="Name" name="name" required>
          <UInput v-model="form.name" />
        </UFormGroup>
        <UFormGroup label="Email" name="email" required>
          <UInput v-model="form.email" type="email" />
        </UFormGroup>
        <UFormGroup label="Password" name="password" required>
          <UInput
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            :ui="{ icon: { trailing: { pointer: '' } } }"
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
        <UFormGroup label="Phone" name="phone">
          <UInput v-model="form.phone" type="tel" />
        </UFormGroup>
        <UFormGroup label="Date of birth" name="date_of_birth">
          <UInput v-model="form.date_of_birth" type="date" />
        </UFormGroup>
        <UFormGroup label="Chronic conditions" name="chronic_conditions" hint="Select all that apply. Visible in the patient's profile and to doctors during consultations.">
          <USelectMenu
            v-model="form.chronic_conditions"
            :options="chronicDiseaseOptions"
            value-attribute="value"
            option-attribute="label"
            multiple
            placeholder="Select conditions (optional)"
          />
        </UFormGroup>
        <div class="flex gap-2">
          <UButton type="submit" :loading="saving">
            Create patient
          </UButton>
          <UButton variant="outline" to="/patients">
            Cancel
          </UButton>
        </div>
      </UForm>
    </UCard>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth-admin'
})

const router = useRouter()
const toast = useToast()
const showPassword = ref(false)
const { options: chronicDiseaseOptions } = useChronicDiseases()

const form = reactive({
  name: '',
  email: '',
  password: '',
  phone: '',
  date_of_birth: '',
  chronic_conditions: [] as string[]
})

const { post } = useAdminApi()
const errorMessage = ref('')
const saving = ref(false)

async function onSubmit () {
  errorMessage.value = ''
  saving.value = true
  try {
    const payload = {
      name: form.name,
      email: form.email,
      password: form.password,
      role: 'patient',
      phone: form.phone || null,
      date_of_birth: form.date_of_birth || null,
      chronic_conditions: Array.isArray(form.chronic_conditions) ? form.chronic_conditions : []
    }
    const data = await post('admin/users', payload)
    const user = data?.data ?? data
    toast.add({ title: 'Patient created', color: 'green' })
    await router.push(user?.id ? `/patients/${user.id}` : '/patients')
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to create patient.'
  } finally {
    saving.value = false
  }
}
</script>
