<template>
  <div class="space-y-6">
    <AdminBreadcrumbs :items="breadcrumbItems" />
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <UButton to="/patients" variant="ghost" icon="i-lucide-arrow-left" size="sm">
          Back
        </UButton>
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Patient details
          </h1>
          <p class="text-gray-600 dark:text-gray-300">
            View and edit patient profile and chronic conditions
          </p>
        </div>
      </div>
      <UButton
        v-if="user && !editing"
        icon="i-lucide-edit"
        size="sm"
        @click="editing = true"
      >
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

    <div v-if="loading && !user" class="py-10 text-center text-gray-500 dark:text-gray-400">
      Loading patient...
    </div>

    <template v-else-if="user">
      <div
        v-if="user.patient_number"
        class="flex flex-col gap-2 rounded-xl border-2 border-primary-200 bg-primary-50/90 p-4 dark:border-primary-700 dark:bg-primary-950/40 sm:flex-row sm:items-center sm:justify-between"
      >
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-primary-800 dark:text-primary-200">
            Patient number
          </p>
          <p class="mt-1 text-2xl font-bold font-mono tracking-tight text-primary-900 dark:text-primary-50">
            {{ user.patient_number }}
          </p>
        </div>
        <div class="text-sm text-primary-800/80 dark:text-primary-200/90">
          Use this number to identify the patient across visits and records.
        </div>
      </div>

      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div v-if="!editing" class="space-y-4">
          <div class="flex items-center gap-4">
            <UAvatar :alt="user.name" size="lg" />
            <div class="min-w-0 flex-1">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ user.name }}
              </h2>
              <UBadge color="blue" variant="soft" class="mt-2">
                Patient
              </UBadge>
            </div>
          </div>
          <dl class="grid gap-3 sm:grid-cols-2">
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
              <dd class="mt-0.5 text-gray-900 dark:text-white">{{ user.email }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
              <dd class="mt-0.5 text-gray-900 dark:text-white">{{ user.phone || '—' }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Date of birth</dt>
              <dd class="mt-0.5 text-gray-900 dark:text-white">{{ user.date_of_birth || '—' }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">User ID</dt>
              <dd class="mt-0.5 text-gray-900 dark:text-white">{{ user.id }}</dd>
            </div>
            <div class="sm:col-span-2">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Wallet balance</dt>
              <dd class="mt-0.5 text-gray-900 dark:text-white">{{ formatUgx(user.wallet_balance ?? 0) }}</dd>
            </div>
            <div class="sm:col-span-2">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Chronic conditions</dt>
              <dd class="mt-0.5 flex flex-wrap gap-1">
                <template v-if="Array.isArray(user.chronic_conditions) && user.chronic_conditions.length">
                  <UBadge v-for="c in user.chronic_conditions" :key="c" size="xs" color="neutral" variant="soft">{{ c }}</UBadge>
                </template>
                <span v-else class="text-gray-400">None recorded</span>
              </dd>
            </div>
          </dl>
          <div v-if="!editing" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-800">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Top-up credit</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Add wallet credit for this patient.</p>
            <form class="flex flex-wrap items-end gap-2" @submit.prevent="submitTopUp">
              <UFormGroup label="Amount (UGX)" class="min-w-[120px]">
                <UInput v-model.number="topUpAmount" type="number" min="1" step="1" placeholder="e.g. 10000" />
              </UFormGroup>
              <UButton type="submit" :loading="topUpLoading">
                Add credit
              </UButton>
            </form>
            <p v-if="topUpError" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ topUpError }}</p>
          </div>
        </div>

        <UForm v-else :state="form" @submit="onSubmit" class="space-y-4">
          <UFormGroup label="Name" name="name" required>
            <UInput v-model="form.name" />
          </UFormGroup>
          <UFormGroup label="Email" name="email" required>
            <UInput v-model="form.email" type="email" />
          </UFormGroup>
          <UFormGroup label="Phone" name="phone">
            <UInput v-model="form.phone" type="tel" />
          </UFormGroup>
          <UFormGroup label="Date of birth" name="date_of_birth">
            <UInput v-model="form.date_of_birth" type="date" />
          </UFormGroup>
          <UFormGroup label="Chronic conditions" name="chronic_conditions" hint="Select all that apply. Visible in patient profile and to doctors during consultations.">
            <USelectMenu
              v-model="form.chronic_conditions"
              :options="chronicDiseaseOptions"
              value-attribute="value"
              option-attribute="label"
              multiple
              placeholder="Select conditions"
            />
          </UFormGroup>
          <div class="flex gap-2">
            <UButton type="submit" :loading="saving">
              Save changes
            </UButton>
            <UButton variant="outline" @click="cancelEdit">
              Cancel
            </UButton>
          </div>
        </UForm>
      </UCard>
    </template>

    <div v-else-if="!loading" class="rounded-lg border border-gray-200 bg-white p-8 text-center dark:border-gray-800 dark:bg-gray-900">
      <p class="text-gray-500 dark:text-gray-400">Patient not found.</p>
      <UButton to="/patients" variant="ghost" class="mt-3">Back to patients</UButton>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth-admin'
})

const route = useRoute()
const router = useRouter()
const toast = useToast()
const { get, patch, post } = useAdminApi()
const { options: chronicDiseaseOptions } = useChronicDiseases()

const userId = computed(() => route.params.id)
const user = ref(null)
const breadcrumbItems = computed(() => {
  const u = user.value as { name?: string; patient_number?: string | null } | null
  const label = u?.patient_number
    ? `${u?.name || 'Patient'} · ${u.patient_number}`
    : (u?.name || 'Patient details')
  return [{ label: 'Patients', to: '/patients' }, { label }]
})
const loading = ref(true)
const errorMessage = ref('')
const editing = ref(false)
const saving = ref(false)
const topUpAmount = ref(null)
const topUpLoading = ref(false)
const topUpError = ref('')

function formatUgx (value) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Number(value) ?? 0)
}

const form = reactive({
  name: '',
  email: '',
  phone: '',
  date_of_birth: '',
  chronic_conditions: [] as string[]
})

function syncFormFromUser () {
  if (!user.value) return
  form.name = user.value.name || ''
  form.email = user.value.email || ''
  form.phone = user.value.phone || ''
  form.date_of_birth = user.value.date_of_birth || ''
  form.chronic_conditions = Array.isArray(user.value.chronic_conditions) ? [...user.value.chronic_conditions] : []
}

async function fetchUser () {
  loading.value = true
  errorMessage.value = ''
  try {
    const data = await get(`admin/users/${userId.value}`)
    const u = data?.data ?? data
    if (u?.role !== 'patient') {
      await router.replace(`/users/${userId.value}`)
      return
    }
    user.value = u
    syncFormFromUser()
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to load patient.'
    user.value = null
  } finally {
    loading.value = false
  }
}

function cancelEdit () {
  editing.value = false
  syncFormFromUser()
}

async function onSubmit () {
  saving.value = true
  errorMessage.value = ''
  try {
    const payload = {
      name: form.name,
      email: form.email,
      phone: form.phone || null,
      date_of_birth: form.date_of_birth || null,
      chronic_conditions: Array.isArray(form.chronic_conditions) ? form.chronic_conditions : []
    }
    const data = await patch(`admin/users/${userId.value}`, payload)
    user.value = data?.data ?? data
    syncFormFromUser()
    editing.value = false
    toast.add({ title: 'Patient updated', color: 'green' })
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to update patient.'
  } finally {
    saving.value = false
  }
}

async function submitTopUp () {
  const amount = Number(topUpAmount.value)
  if (!amount || amount < 1) {
    topUpError.value = 'Enter a valid amount (UGX).'
    return
  }
  topUpLoading.value = true
  topUpError.value = ''
  try {
    await post(`admin/users/${userId.value}/top-up`, { amount })
    user.value = { ...user.value, wallet_balance: (user.value?.wallet_balance ?? 0) + amount }
    topUpAmount.value = null
    toast.add({ title: 'Credit added', description: `${formatUgx(amount)} added to wallet.`, color: 'green' })
  } catch (e) {
    topUpError.value = e?.data?.message || e?.data?.errors?.amount?.[0] || 'Failed to add credit.'
  } finally {
    topUpLoading.value = false
  }
}

onMounted(() => {
  fetchUser()
})
</script>
