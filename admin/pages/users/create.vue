<template>
  <div class="space-y-6">
    <AdminBreadcrumbs :items="[{ label: 'Users', to: '/users' }, { label: 'New user' }]" />
    <div class="flex items-center gap-3">
      <UButton to="/users" variant="ghost" icon="i-lucide-arrow-left" size="sm">
        Back
      </UButton>
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          New user
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
          Create a patient, doctor, or admin. Doctors can then be linked to institutions under Professionals.
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
          <UInput v-model="form.password" type="password" />
        </UFormGroup>
        <UFormGroup label="Role" name="role" required>
          <USelectMenu v-model="form.role" :options="roleOptions" value-attribute="value" />
        </UFormGroup>
        <UFormGroup label="Phone" name="phone">
          <UInput v-model="form.phone" type="tel" />
        </UFormGroup>
        <UFormGroup label="Date of birth" name="date_of_birth">
          <UInput v-model="form.date_of_birth" type="date" />
        </UFormGroup>
        <div class="flex gap-2">
          <UButton type="submit" :loading="saving">
            Create user
          </UButton>
          <UButton variant="outline" to="/users">
            Cancel
          </UButton>
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
const { post } = useAdminApi()

const form = reactive({
  name: '',
  email: '',
  password: '',
  role: 'patient',
  phone: '',
  date_of_birth: ''
})

const roleOptions = [
  { label: 'Patient', value: 'patient' },
  { label: 'Doctor', value: 'doctor' },
  { label: 'Admin', value: 'admin' }
]

const errorMessage = ref('')
const saving = ref(false)

async function onSubmit () {
  errorMessage.value = ''
  saving.value = true
  try {
    const data = await post('admin/users', {
      name: form.name,
      email: form.email,
      password: form.password,
      role: form.role,
      phone: form.phone || null,
      date_of_birth: form.date_of_birth || null
    })
    const user = data?.data ?? data
    toast.add({ title: 'User created', color: 'green' })
    await router.push(user?.id ? `/users/${user.id}` : '/users')
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to create user.'
  } finally {
    saving.value = false
  }
}
</script>
