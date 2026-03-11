<template>
  <div class="space-y-6">
    <AdminBreadcrumbs :items="[{ label: 'Finance', to: '/finance' }, { label: 'Platform Revenue' }]" />
    <div class="flex items-center gap-3">
      <UButton to="/finance" variant="ghost" icon="i-lucide-arrow-left" size="sm">
        Back
      </UButton>
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Platform Revenue
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
          Track platform earnings from consultation fees and other revenue streams.
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

    <!-- Summary Cards -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Revenue</p>
        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
          {{ formatMoney(summary.total_revenue) }}
        </p>
      </UCard>
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">This Month</p>
        <p class="mt-1 text-2xl font-bold text-primary-600 dark:text-primary-400">
          {{ formatMoney(summary.this_month) }}
        </p>
      </UCard>
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Platform Rate</p>
        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
          {{ platformRate }}%
        </p>
      </UCard>
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Transactions</p>
        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
          {{ summary.total_transactions }}
        </p>
      </UCard>
    </div>

    <!-- Platform Revenue Trends Chart -->
    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }" class="mb-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
          Platform Revenue Trends
        </h2>
        <div class="flex items-center gap-2">
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
          <UButton
            @click="exportChart"
            icon="i-lucide-download"
            variant="outline"
            size="sm"
          >
            Export Chart
          </UButton>
        </div>
      </div>
      
      <div v-if="trends.dates?.length" ref="chartRef" class="h-64 w-full" />
      <p v-else class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
        No trend data yet for the selected period.
      </p>
    </UCard>

    <!-- Revenue Details Table -->
    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
          Revenue Details
        </h2>
        <UButton
          @click="exportData"
          icon="i-lucide-download"
          variant="outline"
          size="sm"
        >
          Export CSV
        </UButton>
      </div>

      <div v-if="loading" class="py-8 text-center text-sm text-gray-500">
        Loading platform revenue data...
      </div>

      <UTable
        v-else
        :rows="revenue"
        :columns="revenueColumns"
        :loading="loading"
      />

      <div v-if="!revenue.length && !loading" class="py-8 text-center text-sm text-gray-500">
        No platform revenue found.
      </div>

      <div v-if="revenue.length" class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200 dark:border-gray-800">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Showing {{ revenue.length }} of {{ totalRevenue }} records
        </p>
        <UPagination
          v-model="currentPage"
          :page-count="pageCount"
          :total="totalRevenue"
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
const currentPage = ref(1)
const trendDays = ref(30)
const platformRate = ref(10)

const revenue = ref([])
const totalRevenue = ref(0)
const summary = ref({
  total_revenue: 0,
  this_month: 0,
  total_transactions: 0
})

const chartRef = ref(null)
let chartInstance = null
const trends = ref({ dates: [], values: [] })

const revenueColumns = [
  { key: 'created_at', label: 'Date & Time', sortable: true },
  { key: 'source', label: 'Source', sortable: true },
  { key: 'consultation_id', label: 'Consultation ID', sortable: true },
  { key: 'amount', label: 'Amount', sortable: true },
  { key: 'rate', label: 'Rate (%)', sortable: true },
  { key: 'status', label: 'Status', sortable: true }
]

const pageCount = computed(() => Math.ceil(totalRevenue.value / 15))

function formatMoney (value) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Number(value) ?? 0)
}

function renderChart () {
  if (typeof window === 'undefined' || !chartRef.value) return
  
  const { dates = [], values = [] } = trends.value
  if (!dates.length) return

  if (chartInstance) {
    chartInstance.dispose()
  }
  chartInstance = echarts.init(chartRef.value)
  
  const isDark = document.documentElement.classList.contains('dark')
  const textColor = isDark ? '#f3f4f6' : '#374151'
  const gridColor = isDark ? '#374151' : '#e5e7eb'
  const labelColor = isDark ? '#9ca3af' : '#6b7280'
  
  const shortDates = dates.map((d) => (d && d.length >= 10 ? d.slice(5, 10) : d))
  chartInstance.setOption({
    backgroundColor: 'transparent',
    tooltip: { 
      trigger: 'axis',
      backgroundColor: '#1f2937',
      borderColor: '#374151',
      textStyle: { color: '#f3f4f6' }
    },
    title: { text: 'Platform Revenue Trends', left: 0, textStyle: { fontSize: 14, color: '#6b7280' } },
    grid: {
      left: 10,
      right: 10,
      top: 60,
      bottom: 10
    },
    xAxis: {
      type: 'category',
      data: shortDates,
      boundaryGap: false,
      axisLine: { lineStyle: { color: gridColor } },
      axisLabel: { rotate: 45, color: labelColor },
      axisPointer: { type: 'shadow' }
    },
    yAxis: {
      type: 'value',
      name: 'Revenue (UGX)',
      axisLine: { lineStyle: { color: gridColor } },
      axisLabel: { color: labelColor },
      nameTextStyle: { color: labelColor },
      splitLine: {
        show: true,
        lineStyle: { color: gridColor }
      }
    },
    series: [{
      name: 'Platform Revenue',
      type: 'line',
      smooth: true,
      data: values,
      areaStyle: {
        color: {
          type: 'linear',
          x: 0, y: 0, x2: 0, y2: 1,
          colorStops: [
            { offset: 0, color: 'rgba(99, 102, 241, 0.3)' },
            { offset: 1, color: 'rgba(99, 102, 241, 0.1)' }
          ]
        }
      },
      itemStyle: { color: '#22c55e' },
      lineStyle: { color: '#22c55e', width: 3 },
      emphasis: {
        focus: 'series',
        itemStyle: { color: '#fbbf24' }
      }
    }]
  })
}

function exportChart () {
  if (!chartInstance) return
  
  const dataURL = chartInstance.getDataURL()
  const a = document.createElement('a')
  a.href = dataURL
  a.download = `platform-revenue-chart-${new Date().toISOString().split('T')[0]}.png`
  a.click()
}

function exportData () {
  const csvContent = [
    ['Date', 'Source', 'Consultation ID', 'Amount', 'Rate (%)', 'Status'].join(','),
    ...revenue.value.map(item => [
      item.created_at,
      item.source,
      item.consultation_id,
      item.amount,
      item.rate,
      item.status
    ].map(field => `"${field}"`).join(','))
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `platform-revenue-${new Date().toISOString().split('T')[0]}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

async function fetchRevenue () {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await get('admin/finance/platform-revenue', {
      query: { 
        page: currentPage.value,
        per_page: '15',
        days: trendDays.value
      }
    })
    
    const data = res?.data ?? []
    revenue.value = data.map((item) => ({
      ...item,
      created_at: item.created_at ? new Date(item.created_at).toLocaleString() : '',
      consultation_id: item.consultation_id ? `#${item.consultation_id}` : '–',
      source: item.source || 'Consultation Fee',
      amount: formatMoney(item.amount),
      rate: item.rate || platformRate.value,
      status: item.status || 'completed'
    }))
    
    totalRevenue.value = res?.meta?.total || data.length
    summary.value = res?.summary || summary.value
    platformRate.value = res?.summary?.platform_rate || 10

    const financeRes = await get(`admin/finance?days=${trendDays.value}`)
    const financeData = financeRes?.data ?? {}
    trends.value = financeData.trends?.platform_revenue ?? { dates: [], values: [] }

    nextTick(() => renderChart())
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to load platform revenue data.'
  } finally {
    loading.value = false
  }
}

watch([currentPage, trendDays], () => {
  fetchRevenue()
})

onMounted(() => {
  fetchRevenue()
})

onBeforeUnmount(() => {
  if (chartInstance) {
    chartInstance.dispose()
  }
})
</script>
