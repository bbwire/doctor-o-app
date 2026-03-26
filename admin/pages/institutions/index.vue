<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Institutions
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
          Clinics, hospitals, and healthcare facilities.
        </p>
      </div>
      <UButton to="/institutions/create" icon="i-lucide-plus">
        Add institution
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
        <USelectMenu
          v-model="selectedType"
          :options="typeOptions"
          placeholder="Type"
          class="w-48"
        />
        <USelectMenu
          v-model="selectedActive"
          :options="activeOptions"
          placeholder="Status"
          class="w-40"
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
        <template #institution_number-data="{ row }">
          <AdminHumanId :value="row.institution_number" />
        </template>
        <template #is_active-data="{ row }">
          <UBadge :color="row.is_active ? 'green' : 'gray'" variant="soft">
            {{ row.is_active ? 'Active' : 'Inactive' }}
          </UBadge>
        </template>
        <template #practicing_certificate_url-data="{ row }">
          <div v-if="row.practicing_certificate_url" class="flex items-center gap-2">
            <a
              :href="row.practicing_certificate_url"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center gap-1 text-primary hover:underline text-sm"
            >
              <UIcon name="i-lucide-file-check" class="size-4" />
              View
            </a>
            <img
              :src="row.practicing_certificate_url"
              alt="Certificate"
              class="h-10 w-10 object-cover rounded border border-gray-200 dark:border-gray-700"
              @error="($event) => ($event.target.style.display = 'none')"
            >
          </div>
          <span v-else class="text-gray-400 dark:text-gray-500 text-sm">—</span>
        </template>
        <template #actions-data="{ row }">
          <UButton icon="i-lucide-eye" size="sm" variant="ghost" @click="navigateTo(`/institutions/${row.id}`)" />
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
const selectedType = ref(null)
const selectedActive = ref(null)
const page = ref(1)
const pageSize = 15
const loading = ref(false)
const rows = ref([])
const total = ref(0)
const errorMessage = ref('')

const typeOptions = [
  { label: 'All types', value: null },
  { label: 'Hospital', value: 'hospital' },
  { label: 'Lab', value: 'lab' },
  { label: 'Drugshop', value: 'drugshop' },
  { label: 'Pharmacy', value: 'pharmacy' },
  { label: 'Nursing Home', value: 'nursing_home' }
]

const activeOptions = [
  { label: 'All', value: null },
  { label: 'Active', value: true },
  { label: 'Inactive', value: false }
]

const columns = [
  { key: 'id', label: 'ID' },
  { key: 'institution_number', label: 'Institution no.' },
  { key: 'name', label: 'Name' },
  { key: 'type', label: 'Type' },
  { key: 'address', label: 'Address' },
  { key: 'phone', label: 'Phone' },
  { key: 'is_active', label: 'Status' },
  { key: 'practicing_certificate_url', label: 'Certificate' },
  { key: 'actions', label: 'Actions' }
]

async function fetchList () {
  loading.value = true
  errorMessage.value = ''
  try {
    const params = {
      page: String(page.value),
      per_page: String(pageSize)
    }
    if (search.value) params.search = search.value
    if (selectedType.value != null && selectedType.value !== '') params.type = selectedType.value
    if (selectedActive.value !== null && selectedActive.value !== undefined) params.is_active = selectedActive.value ? '1' : '0'

    const res = await get('admin/institutions', { query: params })
    const data = res?.data ?? []
    const meta = res?.meta ?? {}
    rows.value = Array.isArray(data) ? data : []
    total.value = typeof meta.total === 'number' ? meta.total : rows.value.length
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to load institutions.'
  } finally {
    loading.value = false
  }
}

watch([search, selectedType, selectedActive, page], () => {
  fetchList()
})

onMounted(() => {
  fetchList()
})
</script>
