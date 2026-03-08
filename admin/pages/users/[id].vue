<template>
  <div class="space-y-6">
    <AdminBreadcrumbs :items="[{ label: 'Users', to: '/users' }, { label: user?.name || 'User details' }]" />
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <UButton to="/users" variant="ghost" icon="i-lucide-arrow-left" size="sm">
          Back
        </UButton>
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            User details
          </h1>
          <p class="text-gray-600 dark:text-gray-300">
            View and edit user profile
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
      Loading user...
    </div>

    <template v-else-if="user">
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div v-if="!editing" class="space-y-4">
          <div class="flex items-center gap-4">
            <UAvatar :alt="user.name" size="lg" />
            <div>
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ user.name }}
              </h2>
              <UBadge :color="roleColor(user.role)" variant="soft" class="mt-1">
                {{ user.role }}
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
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ID</dt>
              <dd class="mt-0.5 text-gray-900 dark:text-white">{{ user.id }}</dd>
            </div>
            <div v-if="user.role === 'admin' && Array.isArray(user.permissions) && user.permissions.length" class="sm:col-span-2">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Permissions</dt>
              <dd class="mt-0.5 flex flex-wrap gap-1">
                <UBadge v-for="p in user.permissions" :key="p" size="xs" color="primary" variant="soft">{{ p }}</UBadge>
              </dd>
            </div>
            <div v-if="user.role === 'super_admin'">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Access</dt>
              <dd class="mt-0.5 text-gray-900 dark:text-white">Full access (Super Admin)</dd>
            </div>
            <div v-if="user.role === 'patient'" class="sm:col-span-2">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Wallet balance</dt>
              <dd class="mt-0.5 text-gray-900 dark:text-white">{{ formatUgx(user.wallet_balance ?? 0) }}</dd>
            </div>
          </dl>
          <div v-if="user.role === 'patient' && !editing" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-800">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Top-up credit</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Add wallet credit for this patient (testing / before payment integration).</p>
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
          <div v-if="user.healthcare_professional" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-800">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Healthcare professional</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              {{ user.healthcare_professional?.institution?.name || '—' }}
            </p>
          </div>
        </div>

        <UForm v-else :state="form" @submit="onSubmit" class="space-y-4">
          <UFormGroup label="Name" name="name" required>
            <UInput v-model="form.name" />
          </UFormGroup>
          <UFormGroup label="Email" name="email" required>
            <UInput v-model="form.email" type="email" />
          </UFormGroup>
          <UFormGroup label="Role" name="role">
            <USelectMenu v-model="form.role" :options="roleOptions" value-attribute="value" option-attribute="label" />
          </UFormGroup>
          <UFormGroup v-if="form.role === 'admin'" label="Permissions" name="permissions">
            <USelectMenu
              v-model="form.permissions"
              :options="permissionOptions"
              value-attribute="key"
              option-attribute="label"
              multiple
              placeholder="Select permissions"
            />
          </UFormGroup>
          <UFormGroup label="Phone" name="phone">
            <UInput v-model="form.phone" type="tel" />
          </UFormGroup>
          <UFormGroup label="Date of birth" name="date_of_birth">
            <UInput v-model="form.date_of_birth" type="date" />
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
      <p class="text-gray-500 dark:text-gray-400">User not found.</p>
      <UButton to="/users" variant="ghost" class="mt-3">Back to users</UButton>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth-admin'
})

const route = useRoute()
const toast = useToast()
const { get, patch, post } = useAdminApi()

const userId = computed(() => route.params.id)
const user = ref(null)
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
  role: 'patient',
  permissions: [],
  phone: '',
  date_of_birth: ''
})

const permissionOptions = ref([])

const roleOptions = [
  { label: 'Patient', value: 'patient' },
  { label: 'Doctor', value: 'doctor' },
  { label: 'Admin', value: 'admin' },
  { label: 'Super Admin', value: 'super_admin' }
]

const roleColor = (role) => {
  const colors = { patient: 'blue', doctor: 'green', admin: 'purple', super_admin: 'amber' }
  return colors[role] || 'gray'
}

function syncFormFromUser () {
  if (!user.value) return
  form.name = user.value.name || ''
  form.email = user.value.email || ''
  form.role = user.value.role || 'patient'
  form.permissions = Array.isArray(user.value.permissions) ? [...user.value.permissions] : []
  form.phone = user.value.phone || ''
  form.date_of_birth = user.value.date_of_birth || ''
}

async function fetchUser () {
  loading.value = true
  errorMessage.value = ''
  try {
    const data = await get(`admin/users/${userId.value}`)
    user.value = data?.data ?? data
    syncFormFromUser()
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to load user.'
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
      role: form.role,
      phone: form.phone || null,
      date_of_birth: form.date_of_birth || null
    }
    const data = await patch(`admin/users/${userId.value}`, payload)
    user.value = data?.data ?? data
    syncFormFromUser()
    editing.value = false
    toast.add({ title: 'User updated', color: 'green' })
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to update user.'
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

onMounted(async () => {
  try {
    const res = await get('admin/permissions')
    const data = res?.data ?? []
    permissionOptions.value = Array.isArray(data) ? data : []
  } catch {
    permissionOptions.value = []
  }
  await fetchUser()
})
</script>
