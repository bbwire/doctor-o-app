<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Users
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
          Manage patients, doctors, and administrators.
        </p>
      </div>
      <div class="flex items-center gap-2">
        <UButton to="/users/create" icon="i-lucide-plus" size="sm">
          Create user
        </UButton>
        <UButton
          icon="i-lucide-download"
          variant="outline"
          size="sm"
          :loading="exporting"
          @click="exportCsv"
        >
          Export CSV
        </UButton>
      </div>
    </div>

    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <div class="mb-4 flex items-center justify-between gap-4">
        <UInput
          v-model="search"
          placeholder="Search by name, email, or patient no...."
          icon="i-lucide-search"
          class="max-w-xs"
        />
        <USelectMenu
          v-model="selectedRole"
          :options="roleOptions"
          placeholder="Filter by role"
          class="w-48"
        />
      </div>

      <UAlert
        v-if="errorMessage"
        icon="i-lucide-alert-triangle"
        color="red"
        variant="soft"
        :title="errorMessage"
        class="mb-4"
      />

      <UTable
        :rows="users"
        :columns="columns"
        :loading="loading"
      >
        <template #patient_number-data="{ row }">
          <AdminPatientNumber v-if="row.role === 'patient'" :patient-number="row.patient_number" />
          <span v-else class="text-gray-400">—</span>
        </template>
        <template #role-data="{ row }">
          <UBadge :color="getRoleColor(row.role)">
            {{ row.role }}
          </UBadge>
        </template>

        <template #actions-data="{ row }">
          <UButton
            icon="i-lucide-eye"
            size="sm"
            variant="ghost"
            @click="viewUser(row)"
          />
          <UButton
            icon="i-lucide-edit"
            size="sm"
            variant="ghost"
            @click="editUser(row)"
          />
        </template>
      </UTable>

      <div class="mt-4 flex justify-center">
        <UPagination
          v-model="page"
          :total="total"
          :page-size="15"
        />
      </div>
    </UCard>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth-admin'
})

const config = useRuntimeConfig()
const { get } = useAdminApi()
const toast = useToast()

const search = ref('')
const selectedRole = ref(null)
const page = ref(1)
const loading = ref(false)
const exporting = ref(false)
const users = ref([])
const total = ref(0)
const errorMessage = ref('')

const roleOptions = [
  { label: 'All Roles', value: null },
  { label: 'Super Admin', value: 'super_admin' },
  { label: 'Patient', value: 'patient' },
  { label: 'Doctor', value: 'doctor' },
  { label: 'Admin', value: 'admin' }
]

const columns = [
  { key: 'id', label: 'ID' },
  { key: 'patient_number', label: 'Patient no.' },
  { key: 'name', label: 'Name' },
  { key: 'email', label: 'Email' },
  { key: 'role', label: 'Role' },
  { key: 'phone', label: 'Phone' },
  { key: 'actions', label: 'Actions' }
]

const getRoleColor = (role) => {
  const colors = {
    patient: 'blue',
    doctor: 'green',
    admin: 'purple',
    super_admin: 'amber'
  }
  return colors[role] || 'gray'
}

const fetchUsers = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const params = new URLSearchParams({
      page: page.value.toString(),
      per_page: '15'
    })
    if (search.value) params.append('search', search.value)
    if (selectedRole.value) params.append('role', selectedRole.value)

    const response = await $fetch(`/admin/users?${params}`, {
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${useCookie('auth_token').value || ''}`,
        Accept: 'application/json'
      }
    })

    const anyResponse = response || {}
    const data = anyResponse.data || []
    const meta = anyResponse.meta || {}

    users.value = data
    total.value = typeof meta.total === 'number' ? meta.total : users.value.length
  } catch (error) {
    const err = error && typeof error === 'object' ? error : null
    const message = err && 'data' in err && err.data && typeof err.data === 'object' && 'message' in err.data
      ? err.data.message
      : null

    if (typeof message === 'string') {
      errorMessage.value = message
    } else {
      errorMessage.value = 'Failed to fetch users.'
    }
  } finally {
    loading.value = false
  }
}

const viewUser = (user) => {
  // Navigate to user detail page
  navigateTo(`/users/${user.id}`)
}

const editUser = (u) => {
  navigateTo(`/users/${u.id}`)
}

function escapeCsv (val) {
  if (val == null) return ''
  const s = String(val)
  if (s.includes(',') || s.includes('"') || s.includes('\n')) {
    return `"${s.replace(/"/g, '""')}"`
  }
  return s
}

async function exportCsv () {
  exporting.value = true
  errorMessage.value = ''
  try {
    const all = []
    let pageNum = 1
    const perPage = 100
    let hasMore = true
    while (hasMore) {
      const params = { page: String(pageNum), per_page: String(perPage) }
      if (search.value) params.search = search.value
      if (selectedRole.value) params.role = selectedRole.value
      const res = await get('admin/users', { query: params })
      const data = res?.data ?? []
      const meta = res?.meta ?? {}
      all.push(...data)
      const totalCount = typeof meta.total === 'number' ? meta.total : all.length
      hasMore = all.length < totalCount
      pageNum += 1
    }
    const headers = ['id', 'patient_number', 'name', 'email', 'role', 'phone', 'date_of_birth']
    const rows = [headers.join(',')]
    for (const u of all) {
      rows.push(headers.map(h => escapeCsv(u[h])).join(','))
    }
    const blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `users-export-${new Date().toISOString().slice(0, 10)}.csv`
    a.click()
    URL.revokeObjectURL(url)
    toast.add({ title: 'Export complete', description: `${all.length} users exported.`, color: 'green' })
  } catch (e) {
    toast.add({ title: 'Export failed', description: e?.data?.message || 'Could not export users.', color: 'red' })
  } finally {
    exporting.value = false
  }
}

watch([search, selectedRole, page], () => {
  fetchUsers()
})

onMounted(() => {
  fetchUsers()
})
</script>
