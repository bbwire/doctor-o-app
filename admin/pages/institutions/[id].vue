<template>
  <div class="space-y-6">
    <AdminBreadcrumbs :items="[{ label: 'Institutions', to: '/institutions' }, { label: institution?.name || 'Institution' }]" />
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <UButton to="/institutions" variant="ghost" icon="i-lucide-arrow-left" size="sm">
          Back
        </UButton>
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Institution details
          </h1>
          <p class="text-gray-600 dark:text-gray-300">
            View and edit institution
          </p>
        </div>
      </div>
      <UButton v-if="institution && !editing" icon="i-lucide-edit" size="sm" @click="editing = true">
        Edit
      </UButton>
    </div>

    <UAlert
      v-if="errorMessage"
      icon="i-lucide-alert-triangle"
      color="red"
      variant="soft"
      :title="errorMessage"
    />

    <div v-if="loading && !institution" class="py-10 text-center text-gray-500 dark:text-gray-400">
      Loading...
    </div>

    <template v-else-if="institution">
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div v-if="!editing" class="space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ institution.name }}
            </h2>
            <UBadge :color="institution.is_active ? 'green' : 'gray'" variant="soft">
              {{ institution.is_active ? 'Active' : 'Inactive' }}
            </UBadge>
          </div>
          <dl class="grid gap-3 sm:grid-cols-2">
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</dt>
              <dd class="mt-0.5 text-gray-900 dark:text-white capitalize">{{ institution.type }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Address</dt>
              <dd class="mt-0.5 text-gray-900 dark:text-white">{{ institution.address || '—' }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
              <dd class="mt-0.5 text-gray-900 dark:text-white">{{ institution.phone || '—' }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
              <dd class="mt-0.5 text-gray-900 dark:text-white">{{ institution.email || '—' }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ID</dt>
              <dd class="mt-0.5 text-gray-900 dark:text-white">{{ institution.id }}</dd>
            </div>
          </dl>
        </div>

        <UForm v-else :state="form" @submit="onSubmit" class="space-y-4">
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
            <UButton type="submit" :loading="saving">Save changes</UButton>
            <UButton variant="outline" @click="cancelEdit">Cancel</UButton>
          </div>
        </UForm>
      </UCard>
    </template>

    <div v-else-if="!loading" class="rounded-lg border border-gray-200 bg-white p-8 text-center dark:border-gray-800 dark:bg-gray-900">
      <p class="text-gray-500 dark:text-gray-400">Institution not found.</p>
      <UButton to="/institutions" variant="ghost" class="mt-3">Back to institutions</UButton>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth-admin'
})

const route = useRoute()
const toast = useToast()
const { get, put } = useAdminApi()

const id = computed(() => route.params.id)
const institution = ref(null)
const loading = ref(true)
const errorMessage = ref('')
const editing = ref(false)
const saving = ref(false)

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

function syncForm () {
  if (!institution.value) return
  form.name = institution.value.name || ''
  form.type = institution.value.type || 'clinic'
  form.address = institution.value.address || ''
  form.phone = institution.value.phone || ''
  form.email = institution.value.email || ''
  form.is_active = institution.value.is_active !== false
}

async function fetchInstitution () {
  loading.value = true
  errorMessage.value = ''
  try {
    const data = await get(`admin/institutions/${id.value}`)
    institution.value = data?.data ?? data
    syncForm()
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to load institution.'
    institution.value = null
  } finally {
    loading.value = false
  }
}

function cancelEdit () {
  editing.value = false
  syncForm()
}

async function onSubmit () {
  saving.value = true
  errorMessage.value = ''
  try {
    const payload = {
      name: form.name,
      type: form.type,
      address: form.address || null,
      phone: form.phone || null,
      email: form.email || null,
      is_active: form.is_active
    }
    const data = await put(`admin/institutions/${id.value}`, payload)
    institution.value = data?.data ?? data
    syncForm()
    editing.value = false
    toast.add({ title: 'Institution updated', color: 'green' })
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to update institution.'
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchInstitution()
})
</script>
