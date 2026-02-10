<template>
  <div>
    <UPageHeader
      title="Users"
      description="Manage patients, doctors, and administrators"
    />

    <UPageSection>
      <UCard>
        <div class="mb-4 flex items-center justify-between">
          <UInput
            v-model="search"
            placeholder="Search users..."
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

        <UTable
          :rows="users"
          :columns="columns"
          :loading="loading"
        >
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
    </UPageSection>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth-admin'
})

const { user: currentUser } = useAuth()
const config = useRuntimeConfig()

const search = ref('')
const selectedRole = ref(null)
const page = ref(1)
const loading = ref(false)
const users = ref([])
const total = ref(0)

const roleOptions = [
  { label: 'All Roles', value: null },
  { label: 'Patient', value: 'patient' },
  { label: 'Doctor', value: 'doctor' },
  { label: 'Admin', value: 'admin' }
]

const columns = [
  { key: 'id', label: 'ID' },
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
    admin: 'purple'
  }
  return colors[role] || 'gray'
}

const fetchUsers = async () => {
  loading.value = true
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
        Authorization: `Bearer ${useCookie('auth_token').value}`
      }
    })

    users.value = response.data || []
    total.value = response.total || 0
  } catch (error) {
    console.error('Failed to fetch users:', error)
  } finally {
    loading.value = false
  }
}

const viewUser = (user) => {
  // Navigate to user detail page
  navigateTo(`/users/${user.id}`)
}

const editUser = (user) => {
  // Open edit modal or navigate to edit page
  console.log('Edit user:', user)
}

watch([search, selectedRole, page], () => {
  fetchUsers()
})

onMounted(() => {
  fetchUsers()
})
</script>
