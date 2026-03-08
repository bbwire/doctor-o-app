<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        Audit trail
      </h1>
      <p class="text-gray-600 dark:text-gray-300">
        History of all actions in the system (who did what and when).
      </p>
    </div>

    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <div v-if="loading" class="py-12 text-center text-sm text-gray-500 dark:text-gray-400">
        Loading…
      </div>
      <div v-else-if="rows.length === 0" class="py-12 text-center text-sm text-gray-500 dark:text-gray-400">
        No changes yet.
      </div>
      <template v-else>
        <UTable
          :rows="rows"
          :columns="columns"
        >
          <template #description-data="{ row }">
            <span class="max-w-[280px] block" :title="row.description ?? ''">{{ row.description ?? '—' }}</span>
          </template>
          <template #created_at-data="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
          <template #user-data="{ row }">
            {{ row.user?.name ?? row.user?.email ?? '—' }}
          </template>
          <template #old_value-data="{ row }">
            <span class="max-w-[180px] truncate block" :title="row.old_value ?? ''">
              {{ row.old_value ?? '—' }}
            </span>
          </template>
          <template #new_value-data="{ row }">
            <span class="max-w-[180px] truncate block" :title="row.new_value ?? ''">
              {{ row.new_value ?? '—' }}
            </span>
          </template>
        </UTable>
        <div class="mt-4 flex justify-center">
          <UPagination v-model="page" :total="total" :page-size="pageSize" />
        </div>
      </template>
    </UCard>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth-admin'
})

const { get } = useAdminApi()

const loading = ref(true)
const rows = ref([])
const total = ref(0)
const page = ref(1)
const pageSize = ref(20)

const columns = [
  { key: 'description', label: 'Action' },
  { key: 'user', label: 'Who' },
  { key: 'created_at', label: 'When' },
  { key: 'old_value', label: 'Previous' },
  { key: 'new_value', label: 'New' }
]

function formatDate (val) {
  if (!val) return '—'
  try {
    return new Date(val).toLocaleString()
  } catch (_) {
    return String(val)
  }
}

async function fetchAudit () {
  loading.value = true
  try {
    const res = await get('admin/settings/audit', {
      query: { page: String(page.value), per_page: String(pageSize.value) }
    })
    rows.value = res?.data ?? []
    total.value = res?.meta?.total ?? rows.value.length
  } catch (_) {
    rows.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

watch(page, () => fetchAudit())

onMounted(() => {
  fetchAudit()
})
</script>
