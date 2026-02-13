<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Healthcare professionals
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
          Doctors and clinicians linked to institutions.
        </p>
      </div>
      <UButton to="/healthcare-professionals/create" icon="i-lucide-plus">
        Add professional
      </UButton>
    </div>

    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <div class="mb-4 flex flex-wrap items-center gap-4">
        <UInput
          v-model="search"
          placeholder="Search..."
          icon="i-lucide-search"
          class="max-w-xs"
        />
        <UInput
          v-model="institutionId"
          placeholder="Institution ID"
          type="number"
          class="w-32"
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
        :rows="rows"
        :columns="columns"
        :loading="loading"
      >
        <template #user-data="{ row }">
          <span class="text-gray-900 dark:text-white">{{ row.user?.name || '—' }}</span>
          <span class="block text-sm text-gray-500 dark:text-gray-400">{{ row.user?.email }}</span>
        </template>
        <template #institution-data="{ row }">
          {{ row.institution?.name || '—' }}
        </template>
        <template #is_active-data="{ row }">
          <UBadge :color="row.is_active ? 'green' : 'gray'" variant="soft">
            {{ row.is_active ? 'Active' : 'Inactive' }}
          </UBadge>
        </template>
        <template #actions-data="{ row }">
          <UButton icon="i-lucide-eye" size="sm" variant="ghost" @click="navigateTo(`/healthcare-professionals/${row.id}`)" />
        </template>
      </UTable>

      <div class="mt-4 flex justify-center">
        <UPagination v-model="page" :total="total" :page-size="pageSize" />
      </div>
    </UCard>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth-admin'
})

const { get } = useAdminApi()

const search = ref('')
const institutionId = ref('')
const page = ref(1)
const pageSize = 15
const loading = ref(false)
const rows = ref([])
const total = ref(0)
const errorMessage = ref('')

const columns = [
  { key: 'id', label: 'ID' },
  { key: 'user', label: 'User' },
  { key: 'speciality', label: 'Speciality' },
  { key: 'institution', label: 'Institution' },
  { key: 'license_number', label: 'License' },
  { key: 'is_active', label: 'Status' },
  { key: 'actions', label: 'Actions' }
]

async function fetchList () {
  loading.value = true
  errorMessage.value = ''
  try {
    const params = { page: String(page.value), per_page: String(pageSize) }
    if (search.value) params.search = search.value
    if (institutionId.value) params.institution_id = institutionId.value

    const res = await get('admin/healthcare-professionals', { query: params })
    const data = res?.data ?? []
    const meta = res?.meta ?? {}
    rows.value = Array.isArray(data) ? data : []
    total.value = typeof meta.total === 'number' ? meta.total : rows.value.length
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to load healthcare professionals.'
  } finally {
    loading.value = false
  }
}

watch([search, institutionId, page], () => {
  fetchList()
})

onMounted(() => {
  fetchList()
})
</script>
