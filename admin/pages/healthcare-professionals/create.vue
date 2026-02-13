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
          Link a doctor user to an institution and set profile.
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
        <UFormGroup label="Doctor (user)" name="user_id" required>
          <USelectMenu
            v-model="form.user_id"
            :options="doctorOptions"
            value-attribute="value"
            placeholder="Select a doctor user"
          />
        </UFormGroup>
        <UFormGroup label="Institution" name="institution_id">
          <USelectMenu
            v-model="form.institution_id"
            :options="institutionOptions"
            value-attribute="value"
            placeholder="Select institution (optional)"
          />
        </UFormGroup>
        <UFormGroup label="Speciality" name="speciality">
          <UInput v-model="form.speciality" />
        </UFormGroup>
        <UFormGroup label="License number" name="license_number">
          <UInput v-model="form.license_number" />
        </UFormGroup>
        <UFormGroup label="Bio" name="bio">
          <UTextarea v-model="form.bio" />
        </UFormGroup>
        <UFormGroup label="Active" name="is_active">
          <UCheckbox v-model="form.is_active" />
        </UFormGroup>
        <div class="flex gap-2">
          <UButton type="submit" :loading="saving">Create</UButton>
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

const form = reactive({
  user_id: null,
  institution_id: null,
  speciality: '',
  license_number: '',
  bio: '',
  is_active: true
})

const doctorOptions = ref([])
const institutionOptions = ref([])
const errorMessage = ref('')
const saving = ref(false)

onMounted(async () => {
  try {
    const [usersRes, instRes] = await Promise.all([
      get('admin/users', { query: { role: 'doctor', per_page: '100' } }),
      get('admin/institutions', { query: { per_page: '100' } })
    ])
    const users = usersRes?.data ?? []
    const insts = instRes?.data ?? []
    doctorOptions.value = users.map(u => ({ label: `${u.name} (${u.email})`, value: u.id }))
    institutionOptions.value = [{ label: '— None —', value: null }, ...insts.map(i => ({ label: i.name, value: i.id }))]
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to load options.'
  }
})

async function onSubmit () {
  errorMessage.value = ''
  saving.value = true
  try {
    await post('admin/healthcare-professionals', {
      user_id: form.user_id,
      institution_id: form.institution_id || null,
      speciality: form.speciality || null,
      license_number: form.license_number || null,
      bio: form.bio || null,
      is_active: form.is_active
    })
    toast.add({ title: 'Healthcare professional created', color: 'green' })
    await router.push('/healthcare-professionals')
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to create.'
  } finally {
    saving.value = false
  }
}
</script>
