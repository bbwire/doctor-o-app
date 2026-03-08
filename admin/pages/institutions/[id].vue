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
            <div v-if="institution.type === 'hospital' || institution.type === 'clinic'">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Services</dt>
              <dd class="mt-0.5 text-gray-900 dark:text-white">
                <span v-if="Array.isArray(institution.services) && institution.services.length">
                  {{ formatServices(institution.services) }}
                </span>
                <span v-else>—</span>
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Address</dt>
              <dd class="mt-0.5 text-gray-900 dark:text-white">{{ institution.address || '—' }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Location</dt>
              <dd class="mt-0.5 text-gray-900 dark:text-white">{{ institution.location || '—' }}</dd>
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
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Practicing certificate</dt>
              <dd class="mt-0.5 text-gray-900 dark:text-white">
                <template v-if="institution.practicing_certificate_url">
                  <div class="flex items-center gap-2">
                    <UButton
                      :to="institution.practicing_certificate_url"
                      target="_blank"
                      size="xs"
                      variant="outline"
                      icon="i-lucide-external-link"
                    >
                      View
                    </UButton>
                    <UButton
                      size="xs"
                      color="red"
                      variant="ghost"
                      icon="i-lucide-trash-2"
                      :loading="removingCertificate"
                      @click="askConfirmRemoveCertificate"
                    >
                      Remove
                    </UButton>
                  </div>
                </template>
                <span v-else>—</span>
              </dd>
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
          <UFormGroup
            v-if="form.type === 'hospital' || form.type === 'clinic'"
            label="Services"
            name="services"
            :hint="form.type === 'hospital' ? 'Select all services this hospital offers.' : 'Select all services this clinic offers.'"
          >
            <USelectMenu
              v-model="form.services"
              :options="serviceOptions"
              value-attribute="value"
              option-attribute="label"
              multiple
              placeholder="Select services"
            />
            <div
              v-if="form.services && form.services.length"
              class="mt-2 flex flex-wrap gap-1"
            >
              <UBadge
                v-for="value in form.services"
                :key="value"
                size="xs"
                color="primary"
                variant="soft"
              >
                {{ serviceLabel(value) }}
              </UBadge>
            </div>
          </UFormGroup>
          <UFormGroup label="Address" name="address">
            <UInput v-model="form.address" />
          </UFormGroup>
          <UFormGroup label="Location" name="location" hint="e.g. city, district, or area">
            <UInput v-model="form.location" placeholder="e.g. Kampala, Central" />
          </UFormGroup>
          <UFormGroup label="Phone" name="phone">
            <UInput v-model="form.phone" type="tel" />
          </UFormGroup>
          <UFormGroup label="Email" name="email">
            <UInput v-model="form.email" type="email" />
          </UFormGroup>
          <UFormGroup label="Practicing certificate">
            <div v-if="institution?.practicing_certificate_url" class="mb-3 flex items-center gap-2">
              <span class="text-sm text-gray-600 dark:text-gray-400">Current:</span>
              <UButton
                :to="institution.practicing_certificate_url"
                target="_blank"
                size="xs"
                variant="outline"
                icon="i-lucide-external-link"
              >
                View
              </UButton>
              <UButton
                size="xs"
                color="red"
                variant="ghost"
                icon="i-lucide-trash-2"
                :loading="removingCertificate"
                @click="askConfirmRemoveCertificate"
              >
                Remove
              </UButton>
            </div>
            <CertificateDropzone
              v-model="pendingCertificateFile"
              :disabled="uploadingCertificate"
            />
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

    <AdminConfirmModal
      v-model="confirmRemoveCertificateOpen"
      title="Remove practicing certificate?"
      description="The certificate file will be deleted. You can upload a new one at any time."
      confirm-label="Remove certificate"
      confirm-variant="danger"
      :loading="removingCertificate"
      @confirm="removeCertificate"
      @cancel="confirmRemoveCertificateOpen = false"
    />
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth-admin'
})

const route = useRoute()
const toast = useToast()
const { get, put } = useAdminApi()
const config = useRuntimeConfig()
const tokenCookie = useCookie('auth_token')

const id = computed(() => route.params.id)
const institution = ref(null)
const loading = ref(true)
const errorMessage = ref('')
const editing = ref(false)
const saving = ref(false)
const removingCertificate = ref(false)
const uploadingCertificate = ref(false)
const pendingCertificateFile = ref(null)
const confirmRemoveCertificateOpen = ref(false)

const form = reactive({
  name: '',
  type: 'hospital',
  services: [],
  address: '',
  location: '',
  phone: '',
  email: '',
  is_active: true
})

const typeOptions = [
  { label: 'Hospital', value: 'hospital' },
  { label: 'Clinic', value: 'clinic' },
  { label: 'Lab', value: 'lab' },
  { label: 'Drugshop', value: 'drugshop' },
  { label: 'Pharmacy', value: 'pharmacy' },
  { label: 'Nursing Home', value: 'nursing_home' }
]

const serviceOptions = [
  { label: 'Consultation', value: 'consultation' },
  { label: 'Pharmacy', value: 'pharmacy' },
  { label: 'Lab', value: 'lab' },
  { label: 'Radiology', value: 'radiology' },
  { label: 'Interventional Unit', value: 'interventional_unit' },
  { label: 'Nursing care', value: 'nursing_care' }
]

function serviceLabel (value) {
  const found = serviceOptions.find(o => o.value === value)
  return found ? found.label : value
}

function syncForm () {
  if (!institution.value) return
  form.name = institution.value.name || ''
  form.type = institution.value.type || 'hospital'
  form.services = Array.isArray(institution.value.services) ? institution.value.services : []
  form.address = institution.value.address || ''
  form.location = institution.value.location || ''
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
      services: (form.type === 'hospital' || form.type === 'clinic') ? form.services : [],
      address: form.address || null,
      location: form.location || null,
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

function formatServices (services) {
  const labels = {
    consultation: 'Consultation',
    pharmacy: 'Pharmacy',
    lab: 'Lab',
    radiology: 'Radiology',
    interventional_unit: 'Interventional Unit',
    nursing_care: 'Nursing care'
  }
  return services.map(s => labels[s] || s).join(', ')
}

async function uploadCertificate (file) {
  if (!institution.value) return
  uploadingCertificate.value = true
  try {
    const formData = new FormData()
    formData.append('file', file)
    await $fetch(`/admin/institutions/${institution.value.id}/practicing-certificate`, {
      method: 'POST',
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${tokenCookie.value || ''}`,
        Accept: 'application/json'
      },
      body: formData
    })
    await fetchInstitution()
    toast.add({
      title: 'Certificate uploaded',
      description: 'Practicing certificate has been updated for this institution.',
      color: 'green'
    })
  } catch (e) {
    toast.add({
      title: 'Upload failed',
      description: e?.data?.message || 'Unable to upload certificate.',
      color: 'red'
    })
  } finally {
    uploadingCertificate.value = false
  }
}

function askConfirmRemoveCertificate () {
  confirmRemoveCertificateOpen.value = true
}

async function removeCertificate () {
  if (!institution.value) return
  confirmRemoveCertificateOpen.value = false
  removingCertificate.value = true
  try {
    await $fetch(`/admin/institutions/${institution.value.id}/practicing-certificate`, {
      method: 'DELETE',
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${tokenCookie.value || ''}`,
        Accept: 'application/json'
      }
    })
    await fetchInstitution()
    toast.add({
      title: 'Certificate removed',
      description: 'Practicing certificate has been removed.',
      color: 'green'
    })
  } catch (e) {
    toast.add({
      title: 'Unable to remove certificate',
      description: e?.data?.message || 'Please try again.',
      color: 'red'
    })
  } finally {
    removingCertificate.value = false
  }
}

watch(pendingCertificateFile, async (file) => {
  if (!file || !institution.value) return
  await uploadCertificate(file)
  pendingCertificateFile.value = null
})

onMounted(() => {
  fetchInstitution()
})
</script>
