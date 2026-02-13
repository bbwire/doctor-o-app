<template>
  <div class="space-y-6">
    <AdminBreadcrumbs :items="[{ label: 'Institutions', to: '/institutions' }, { label: 'New institution' }]" />
    <div class="flex items-center gap-3">
      <UButton to="/institutions" variant="ghost" icon="i-lucide-arrow-left" size="sm">
        Back
      </UButton>
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          New institution
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
          Add a clinic, hospital, or healthcare facility.
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
        <UFormGroup label="Type" name="type" required>
          <USelectMenu v-model="form.type" :options="typeOptions" value-attribute="value" />
        </UFormGroup>
        <UFormGroup label="Address" name="address">
          <UInput v-model="form.address" />
        </UFormGroup>
        <UFormGroup label="Phone" name="phone">
          <UInput v-model="form.phone" type="tel" />
        </UFormGroup>
        <UFormGroup label="Email" name="email">
          <UInput v-model="form.email" type="email" />
        </UFormGroup>
        <UFormGroup label="Active" name="is_active">
          <UCheckbox v-model="form.is_active" />
        </UFormGroup>
        <div class="flex gap-2">
          <UButton type="submit" :loading="saving">
            Create institution
          </UButton>
          <UButton variant="outline" to="/institutions">
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
  type: 'clinic',
  address: '',
  phone: '',
  email: '',
  is_active: true
})

const typeOptions = [
  { label: 'Clinic', value: 'clinic' },
  { label: 'Hospital', value: 'hospital' },
  { label: 'Other', value: 'other' }
]

const errorMessage = ref('')
const saving = ref(false)

async function onSubmit () {
  errorMessage.value = ''
  saving.value = true
  try {
    await post('admin/institutions', {
      name: form.name,
      type: form.type,
      address: form.address || null,
      phone: form.phone || null,
      email: form.email || null,
      is_active: form.is_active
    })
    toast.add({ title: 'Institution created', color: 'green' })
    await router.push('/institutions')
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to create institution.'
  } finally {
    saving.value = false
  }
}
</script>
