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
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="space-y-4">
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
          </div>
          <div class="space-y-4">
            <UFormGroup label="Phone" name="phone">
              <UInput v-model="form.phone" type="tel" />
            </UFormGroup>
            <UFormGroup label="Email" name="email">
              <UInput v-model="form.email" type="email" />
            </UFormGroup>
            <UFormGroup label="Active" name="is_active">
              <UCheckbox v-model="form.is_active" />
            </UFormGroup>
            <UFormGroup label="Registration certificate" name="registration_certificate" hint="Optional. PDF or image (JPEG, PNG, WebP). Max 10 MB.">
              <CertificateDropzone v-model="registrationCertificateFile" :disabled="saving" />
            </UFormGroup>
            <UFormGroup label="Operating License" name="operating_license" hint="Optional. PDF or image (JPEG, PNG, WebP). Max 10 MB.">
              <CertificateDropzone v-model="operatingLicenseFile" :disabled="saving" />
            </UFormGroup>
          </div>
        </div>
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
const config = useRuntimeConfig()
const tokenCookie = useCookie('auth_token')
const { post } = useAdminApi()

const registrationCertificateFile = ref(null)
const operatingLicenseFile = ref(null)

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
  { label: 'Nursing Home', value: 'nursing_home' },
  { label: 'Radiology Center', value: 'radiology_center' }
]

const serviceOptions = [
  { label: 'Consultation', value: 'consultation' },
  { label: 'Pharmacy', value: 'pharmacy' },
  { label: 'Lab', value: 'lab' },
  { label: 'Radiology', value: 'radiology' },
  { label: 'Dental', value: 'dental' },
  { label: 'Interventional Unit', value: 'interventional_unit' },
  { label: 'Nursing care', value: 'nursing_care' }
]

function serviceLabel (value) {
  const found = serviceOptions.find(o => o.value === value)
  return found ? found.label : value
}

const errorMessage = ref('')
const saving = ref(false)

async function onSubmit () {
  errorMessage.value = ''
  saving.value = true
  try {
    const res = await post('admin/institutions', {
      name: form.name,
      type: form.type,
      services: (form.type === 'hospital' || form.type === 'clinic') ? form.services : [],
      address: form.address || null,
      location: form.location || null,
      phone: form.phone || null,
      email: form.email || null,
      is_active: form.is_active
    })
    const createdId = res?.data?.id ?? res?.id
    if (createdId) {
      if (registrationCertificateFile.value) {
        const formData = new FormData()
        formData.append('file', registrationCertificateFile.value)
        await $fetch(`/admin/institutions/${createdId}/registration-certificate`, {
          method: 'POST',
          baseURL: config.public.apiBase,
          headers: {
            Authorization: `Bearer ${tokenCookie.value || ''}`,
            Accept: 'application/json'
          },
          body: formData
        })
      }
      if (operatingLicenseFile.value) {
        const formData = new FormData()
        formData.append('file', operatingLicenseFile.value)
        await $fetch(`/admin/institutions/${createdId}/operating-license`, {
          method: 'POST',
          baseURL: config.public.apiBase,
          headers: {
            Authorization: `Bearer ${tokenCookie.value || ''}`,
            Accept: 'application/json'
          },
          body: formData
        })
      }
    }
    toast.add({ title: 'Institution created', color: 'green' })
    await router.push('/institutions')
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to create institution.'
  } finally {
    saving.value = false
  }
}
</script>
