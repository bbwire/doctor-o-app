<template>
  <div class="space-y-6">
    <AdminBreadcrumbs :items="[{ label: 'Professionals', to: '/healthcare-professionals' }, { label: item?.user?.name || 'Professional' }]" />
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <UButton to="/healthcare-professionals" variant="ghost" icon="i-lucide-arrow-left" size="sm">
          Back
        </UButton>
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Healthcare professional
          </h1>
          <p class="text-gray-600 dark:text-gray-300">
            View and edit
          </p>
        </div>
      </div>
      <UButton v-if="item && !editing" icon="i-lucide-edit" size="sm" @click="editing = true">
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

    <div v-if="loading && !item" class="py-10 text-center text-gray-500 dark:text-gray-400">
      Loading...
    </div>

    <template v-else-if="item">
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div v-if="!editing" class="space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ item.user?.name || '—' }}
            </h2>
            <UBadge :color="item.is_active ? 'green' : 'gray'" variant="soft">
              {{ item.is_active ? 'Active' : 'Inactive' }}
            </UBadge>
          </div>
          <dl class="grid gap-3 sm:grid-cols-2">
            <div><dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt><dd class="mt-0.5 text-gray-900 dark:text-white">{{ item.user?.email || '—' }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Institution</dt><dd class="mt-0.5 text-gray-900 dark:text-white">{{ item.institution?.name || '—' }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Speciality</dt><dd class="mt-0.5 text-gray-900 dark:text-white">{{ item.speciality || '—' }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500 dark:text-gray-400">License number</dt><dd class="mt-0.5 text-gray-900 dark:text-white">{{ item.license_number || '—' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Bio</dt><dd class="mt-0.5 text-gray-900 dark:text-white">{{ item.bio || '—' }}</dd></div>
          </dl>
        </div>

        <UForm v-else :state="form" @submit="onSubmit" class="space-y-4">
          <UFormGroup label="Doctor (user)" name="user_id">
            <USelectMenu v-model="form.user_id" :options="doctorOptions" value-attribute="value" />
          </UFormGroup>
          <UFormGroup label="Institution" name="institution_id">
            <USelectMenu v-model="form.institution_id" :options="institutionOptions" value-attribute="value" />
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
            <UButton type="submit" :loading="saving">Save</UButton>
            <UButton variant="outline" @click="cancelEdit">Cancel</UButton>
          </div>
        </UForm>
      </UCard>
    </template>

    <div v-else-if="!loading" class="rounded-lg border border-gray-200 bg-white p-8 text-center dark:border-gray-800 dark:bg-gray-900">
      <p class="text-gray-500 dark:text-gray-400">Not found.</p>
      <UButton to="/healthcare-professionals" variant="ghost" class="mt-3">Back to list</UButton>
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
const item = ref(null)
const loading = ref(true)
const errorMessage = ref('')
const editing = ref(false)
const saving = ref(false)
const doctorOptions = ref([])
const institutionOptions = ref([])

const form = reactive({
  user_id: null,
  institution_id: null,
  speciality: '',
  license_number: '',
  bio: '',
  is_active: true
})

function syncForm () {
  if (!item.value) return
  form.user_id = item.value.user_id ?? item.value.user?.id
  form.institution_id = item.value.institution_id ?? item.value.institution?.id ?? null
  form.speciality = item.value.speciality || ''
  form.license_number = item.value.license_number || ''
  form.bio = item.value.bio || ''
  form.is_active = item.value.is_active !== false
}

async function fetchItem () {
  loading.value = true
  errorMessage.value = ''
  try {
    const data = await get(`admin/healthcare-professionals/${id.value}`)
    item.value = data?.data ?? data
    syncForm()
    await loadOptions()
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to load.'
    item.value = null
  } finally {
    loading.value = false
  }
}

async function loadOptions () {
  try {
    const [usersRes, instRes] = await Promise.all([
      get('admin/users', { query: { role: 'doctor', per_page: '100' } }),
      get('admin/institutions', { query: { per_page: '100' } })
    ])
    const users = usersRes?.data ?? []
    const insts = instRes?.data ?? []
    doctorOptions.value = users.map(u => ({ label: `${u.name} (${u.email})`, value: u.id }))
    institutionOptions.value = [{ label: '— None —', value: null }, ...insts.map(i => ({ label: i.name, value: i.id }))]
  } catch (_) {}
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
      user_id: form.user_id,
      institution_id: form.institution_id || null,
      speciality: form.speciality || null,
      license_number: form.license_number || null,
      bio: form.bio || null,
      is_active: form.is_active
    }
    const data = await put(`admin/healthcare-professionals/${id.value}`, payload)
    item.value = data?.data ?? data
    syncForm()
    editing.value = false
    toast.add({ title: 'Updated', color: 'green' })
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to update.'
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchItem()
})
</script>
