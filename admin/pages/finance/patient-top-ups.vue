<template>
  <div class="space-y-6">
    <AdminBreadcrumbs :items="[{ label: 'Finance', to: '/finance' }, { label: 'Patient Top-ups' }]" />
    <div class="flex items-center gap-3">
      <UButton to="/finance" variant="ghost" icon="i-lucide-arrow-left" size="sm">
        Back
      </UButton>
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Patient Top-ups
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
          View all patient wallet top-up transactions and details.
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

    <!-- Top-ups Trends Chart -->
    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }" class="mb-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
          Top-up Trends
        </h2>
        <USelect
          v-model="trendDays"
          :items="[
            { value: 7, label: '7 days' },
            { value: 30, label: '30 days' },
            { value: 90, label: '90 days' }
          ]"
          value-key="value"
          class="w-32"
        />
      </div>
      <div v-if="trends.dates?.length" ref="chartRef" class="h-80 w-full" />
      <p v-else class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
        No trend data yet for the selected period.
      </p>
    </UCard>

    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
          Top-up Transactions
        </h2>
        <div class="flex items-center gap-2">
          <UInput
            v-model="searchQuery"
            placeholder="Search by patient name or email..."
            icon="i-lucide-search"
            class="w-64"
          />
          <UButton
            @click="exportData"
            icon="i-lucide-download"
            variant="outline"
            size="sm"
          >
            Export
          </UButton>
        </div>
      </div>

      <div v-if="loading" class="py-8 text-center text-sm text-gray-500">
        Loading top-up transactions...
      </div>

      <UTable
        v-else
        :rows="filteredTopUps"
        :columns="topUpColumns"
        :loading="loading"
      />

      <div v-if="!filteredTopUps.length && !loading" class="py-8 text-center text-sm text-gray-500">
        No top-up transactions found.
      </div>

      <div v-if="filteredTopUps.length" class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200 dark:border-gray-800">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Showing {{ filteredTopUps.length }} of {{ totalTopUps }} transactions
        </p>
        <UPagination
          v-model="currentPage"
          :page-count="pageCount"
          :total="totalTopUps"
          :ui="{ wrapper: 'items-center' }"
        />
      </div>
    </UCard>
  </div>
</template>

<script setup>
import * as echarts from 'echarts'

definePageMeta({
  middleware: 'auth-admin'
})

const router = useRouter()
const { get } = useAdminApi()

const loading = ref(true)
const errorMessage = ref('')
const searchQuery = ref('')
const currentPage = ref(1)
const trendDays = ref(30)
const topUps = ref([])
const totalTopUps = ref(0)
const trends = ref({ dates: [], values: [] })
const chartRef = ref(null)
let chartInstance = null

const topUpColumns = [
  { key: 'created_at', label: 'Date & Time', sortable: true },
  { key: 'user', label: 'Patient', sortable: true },
  { key: 'amount', label: 'Amount', sortable: true },
  { key: 'payment_method', label: 'Payment Method', sortable: true },
  { key: 'status', label: 'Status', sortable: true }
]

const pageCount = computed(() => Math.ceil(totalTopUps.value / 15))

const filteredTopUps = computed(() => {
  if (!searchQuery.value) return topUps.value
  
  const query = searchQuery.value.toLowerCase()
  return topUps.value.filter(topUp => 
    topUp.user?.name?.toLowerCase().includes(query) ||
    topUp.user?.email?.toLowerCase().includes(query) ||
    topUp.payment_method?.toLowerCase().includes(query)
  )
})

function formatMoney (value) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Number(value) ?? 0)
}

function exportData () {
  const csvContent = [
    ['Date', 'Patient', 'Email', 'Amount', 'Payment Method', 'Status'].join(','),
    ...filteredTopUps.value.map(topUp => [
      topUp.created_at,
      topUp.user?.name || '',
      topUp.user?.email || '',
      topUp.amount,
      topUp.payment_method || '',
      topUp.status || ''
    ].map(field => `"${field}"`).join(','))
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `patient-top-ups-${new Date().toISOString().split('T')[0]}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

function renderChart () {
  if (typeof window === 'undefined' || !chartRef.value || !trends.value.dates?.length) return
  if (chartInstance) chartInstance.dispose()
  chartInstance = echarts.init(chartRef.value)
  
  const isDark = document.documentElement.classList.contains('dark')
  const textColor = isDark ? '#f3f4f6' : '#374151'
  const gridColor = isDark ? '#374151' : '#e5e7eb'
  const labelColor = isDark ? '#9ca3af' : '#6b7280'
  
  const shortDates = trends.value.dates.map((d) => (d && d.length >= 10 ? d.slice(5, 10) : d))
  chartInstance.setOption({
    backgroundColor: 'transparent',
    tooltip: { 
      trigger: 'axis',
      backgroundColor: '#1f2937',
      borderColor: '#374151',
      textStyle: { color: '#f3f4f6' }
    },
    xAxis: {
      type: 'category',
      data: shortDates,
      boundaryGap: false,
      axisLine: { lineStyle: { color: gridColor } },
      axisLabel: { color: labelColor }
    },
    yAxis: {
      type: 'value',
      name: 'Amount',
      axisLine: { lineStyle: { color: gridColor } },
      axisLabel: { color: labelColor },
      nameTextStyle: { color: labelColor }
    },
    series: [{
      name: 'Top-ups',
      type: 'line',
      smooth: true,
      data: trends.value.values ?? [],
      areaStyle: {
        color: {
          type: 'linear',
          x: 0, y: 0, x2: 0, y2: 1,
          colorStops: [
            { offset: 0, color: 'rgba(34, 197, 94, 0.3)' },
            { offset: 1, color: 'rgba(34, 197, 94, 0.1)' }
          ]
        }
      },
      itemStyle: { color: '#22c55e' },
      lineStyle: { color: '#22c55e' }
    }]
  })
}

async function fetchTopUps () {
  loading.value = true
  errorMessage.value = ''
  try {
    const [res, financeRes] = await Promise.all([
      get('admin/finance/top-ups', { query: { page: currentPage.value, per_page: '15' } }),
      get(`admin/finance?days=${trendDays.value}`)
    ])

    const financeData = financeRes?.data ?? {}
    trends.value = financeData.trends?.top_ups ?? { dates: [], values: [] }
    nextTick(() => renderChart())

    const data = res?.data ?? []
    topUps.value = data.map((t) => ({
      ...t,
      created_at: t.created_at ? new Date(t.created_at).toLocaleString() : '',
      amount: formatMoney(t.amount),
      user: t.user ? `${t.user.name} (${t.user.email})` : `User #${t.user_id}`,
      payment_method: t.payment_method || 'Unknown',
      status: t.status || 'completed'
    }))
    
    totalTopUps.value = res?.meta?.total || data.length
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to load top-up transactions.'
  } finally {
    loading.value = false
  }
}

watch(currentPage, () => {
  fetchTopUps()
})

watch(searchQuery, () => {
  currentPage.value = 1
})

watch(trendDays, () => {
  fetchTopUps()
})

onMounted(() => {
  fetchTopUps()
})

onBeforeUnmount(() => {
  chartInstance?.dispose()
})
</script>
