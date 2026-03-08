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
          <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ item.user?.name || '—' }}
            </h2>
            <div class="flex flex-wrap items-center gap-2">
              <UBadge :color="item.is_approved ? 'green' : 'amber'" variant="soft">
                {{ item.is_approved ? 'Approved' : 'Pending approval' }}
              </UBadge>
              <UBadge :color="item.is_active ? 'green' : 'gray'" variant="soft">
                {{ item.is_active ? 'Active' : 'Inactive' }}
              </UBadge>
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <UButton
              v-if="!item.is_approved"
              size="sm"
              color="primary"
              icon="i-lucide-check-circle"
              :loading="statusLoading"
              @click="askConfirmApprove"
            >
              Approve
            </UButton>
            <UButton
              v-else
              size="sm"
              variant="outline"
              color="amber"
              icon="i-lucide-x-circle"
              :loading="statusLoading"
              @click="askConfirmRevoke"
            >
              Revoke approval
            </UButton>
            <UButton
              v-if="item.is_active"
              size="sm"
              variant="outline"
              color="gray"
              icon="i-lucide-pause-circle"
              :loading="statusLoading"
              @click="askConfirmDeactivate"
            >
              Deactivate
            </UButton>
            <UButton
              v-else
              size="sm"
              variant="outline"
              color="green"
              icon="i-lucide-play-circle"
              :loading="statusLoading"
              @click="setStatus({ is_active: true })"
            >
              Activate
            </UButton>
          </div>
          <dl class="grid gap-3 sm:grid-cols-2">
            <div><dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt><dd class="mt-0.5 text-gray-900 dark:text-white">{{ item.user?.email || '—' }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Institution</dt><dd class="mt-0.5 text-gray-900 dark:text-white">{{ item.institution?.name || '—' }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Speciality</dt><dd class="mt-0.5 text-gray-900 dark:text-white">{{ item.speciality || '—' }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500 dark:text-gray-400">License number</dt><dd class="mt-0.5 text-gray-900 dark:text-white">{{ item.license_number || '—' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Motivational statement</dt><dd class="mt-0.5 text-gray-900 dark:text-white">{{ item.bio || '—' }}</dd></div>
          </dl>

          <div v-if="item.academic_documents && item.academic_documents.length" class="pt-4 border-t border-gray-200 dark:border-gray-800">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Documents</dt>
            <dd class="space-y-2">
              <div
                v-for="doc in item.academic_documents"
                :key="doc.id"
                class="flex items-center justify-between gap-2 rounded-lg bg-gray-50 dark:bg-gray-800/50 px-3 py-2"
              >
                <div class="min-w-0">
                  <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                    {{ doc.name || doc.type || 'Document' }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ doc.type }} · {{ doc.uploaded_at ? formatDate(doc.uploaded_at) : '' }}
                  </p>
                </div>
                <UButton
                  v-if="doc.url"
                  :to="doc.url"
                  target="_blank"
                  rel="noopener noreferrer"
                  size="xs"
                  variant="outline"
                  icon="i-lucide-external-link"
                >
                  View
                </UButton>
              </div>
            </dd>
          </div>
          <div v-else class="pt-4 border-t border-gray-200 dark:border-gray-800">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Documents</dt>
            <dd class="mt-1 text-sm text-gray-500 dark:text-gray-400">No documents uploaded.</dd>
          </div>
        </div>

        <UForm v-else :state="form" @submit="onSubmit" class="space-y-4">
          <UFormGroup label="Doctor (user)" name="user_id">
            <USelectMenu v-model="form.user_id" :options="doctorOptions" value-attribute="value" />
          </UFormGroup>
          <UFormGroup label="Institution" name="institution_id">
            <USelectMenu v-model="form.institution_id" :options="institutionOptions" value-attribute="value" />
          </UFormGroup>
          <UFormGroup label="Speciality" name="speciality">
            <USelectMenu
              v-model="form.speciality"
              :options="specialityOptions"
              value-attribute="value"
              option-attribute="label"
              placeholder="Select speciality"
            />
          </UFormGroup>
          <UFormGroup label="License number" name="license_number">
            <UInput v-model="form.license_number" />
          </UFormGroup>
          <UFormGroup label="Motivational statement" name="bio">
            <UTextarea v-model="form.bio" placeholder="e.g. What drives you in your practice" />
          </UFormGroup>
          <UFormGroup label="Approved" name="is_approved">
            <UCheckbox v-model="form.is_approved" />
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

    <AdminConfirmModal
      v-model="confirmOpen"
      :title="confirmTitle"
      :description="confirmDescription"
      :confirm-label="confirmButtonLabel"
      :confirm-variant="confirmVariant"
      :loading="statusLoading"
      @confirm="runPendingConfirm"
      @cancel="clearPendingConfirm"
    />
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth-admin'
})

const route = useRoute()
const toast = useToast()
const { get, put, patch } = useAdminApi()

const id = computed(() => route.params.id)
const item = ref(null)
const loading = ref(true)
const errorMessage = ref('')
const editing = ref(false)
const saving = ref(false)
const doctorOptions = ref([])
const institutionOptions = ref([])

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

const form = reactive({
  user_id: null,
  institution_id: null,
  speciality: '',
  license_number: '',
  bio: '',
  is_active: true,
  is_approved: false
})

const statusLoading = ref(false)
const confirmOpen = ref(false)
const confirmTitle = ref('')
const confirmDescription = ref('')
const confirmButtonLabel = ref('Confirm')
const confirmVariant = ref('primary')
let pendingStatusPayload = null

function formatDate (val) {
  if (!val) return '—'
  try {
    return new Date(val).toLocaleString()
  } catch (_) {
    return String(val)
  }
}

function syncForm () {
  if (!item.value) return
  form.user_id = item.value.user_id ?? item.value.user?.id
  form.institution_id = item.value.institution_id ?? item.value.institution?.id ?? null
  form.speciality = item.value.speciality || ''
  form.license_number = item.value.license_number || ''
  form.bio = item.value.bio || ''
  form.is_active = item.value.is_active !== false
  form.is_approved = item.value.is_approved === true
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
      is_active: form.is_active,
      is_approved: form.is_approved
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

function askConfirmApprove () {
  confirmTitle.value = 'Approve professional?'
  confirmDescription.value = 'They will be marked as approved and can be used for bookings.'
  confirmButtonLabel.value = 'Approve'
  confirmVariant.value = 'primary'
  pendingStatusPayload = { is_approved: true }
  confirmOpen.value = true
}

function askConfirmRevoke () {
  confirmTitle.value = 'Revoke approval?'
  confirmDescription.value = 'This professional will no longer be approved and may need to be reviewed again before they can be used for bookings.'
  confirmButtonLabel.value = 'Revoke approval'
  confirmVariant.value = 'danger'
  pendingStatusPayload = { is_approved: false }
  confirmOpen.value = true
}

function askConfirmDeactivate () {
  confirmTitle.value = 'Deactivate professional?'
  confirmDescription.value = 'They will not appear as available for new bookings until activated again.'
  confirmButtonLabel.value = 'Deactivate'
  confirmVariant.value = 'danger'
  pendingStatusPayload = { is_active: false }
  confirmOpen.value = true
}

function clearPendingConfirm () {
  pendingStatusPayload = null
  confirmOpen.value = false
}

async function runPendingConfirm () {
  const payload = pendingStatusPayload
  clearPendingConfirm()
  if (payload) await setStatus(payload)
}

async function setStatus (payload) {
  if (!item.value) return
  statusLoading.value = true
  errorMessage.value = ''
  try {
    const data = await patch(`admin/healthcare-professionals/${id.value}/status`, payload)
    item.value = data?.data ?? data
    syncForm()
    toast.add({ title: 'Status updated', color: 'green' })
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to update status.'
    toast.add({ title: 'Update failed', description: errorMessage.value, color: 'red' })
  } finally {
    statusLoading.value = false
  }
}

onMounted(() => {
  fetchItem()
})
</script>
