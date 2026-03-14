<template>
  <div class="space-y-6">
    <AdminBreadcrumbs :items="[{ label: 'Finance', to: '/finance' }, { label: 'Institution Revenue' }]" />
    <div class="flex items-center gap-3">
      <UButton to="/finance" variant="ghost" icon="i-lucide-arrow-left" size="sm">
        Back
      </UButton>
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Institution Revenue
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
          Track revenue from institution subscriptions and payments.
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
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Transactions</p>
        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
          {{ totalPayments }}
        </p>
      </UCard>
    </div>

    <!-- Institution Revenue Trends Chart -->
    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }" class="mb-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
          Institution Revenue Trends
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
      <div v-if="trends.dates?.length" ref="chartRef" class="h-80 w-full" />
      <p v-else class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
        No trend data yet for the selected period.
      </p>
    </UCard>

    <!-- Revenue Details Table -->
    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
          Payment Details
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
        Loading institution revenue data...
      </div>

      <UTable
        v-else
        :rows="payments"
        :columns="paymentColumns"
        :loading="loading"
      />

      <div v-if="!payments.length && !loading" class="py-8 text-center text-sm text-gray-500">
        No institution payments found.
      </div>

      <div v-if="payments.length" class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200 dark:border-gray-800">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Showing {{ payments.length }} of {{ totalPayments }} records
        </p>
        <UPagination
          v-model="currentPage"
          :page-count="pageCount"
          :total="totalPayments"
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

const { get } = useAdminApi()

const loading = ref(true)
const errorMessage = ref('')
const currentPage = ref(1)
const trendDays = ref(30)
const payments = ref([])
const totalPayments = ref(0)
const summary = ref({
  total_revenue: 0,
  this_month: 0
})
const trends = ref({ dates: [], values: [] })
const chartRef = ref(null)
let chartInstance = null

const paymentColumns = [
  { key: 'created_at', label: 'Date & Time', sortable: true },
  { key: 'institution', label: 'Institution', sortable: true },
  { key: 'amount', label: 'Amount', sortable: true },
  { key: 'type', label: 'Type', sortable: true },
  { key: 'description', label: 'Description', sortable: true }
]

const pageCount = computed(() => Math.ceil(totalPayments.value / 15))

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
  const shortDates = dates.map((d) => (d && d.length >= 10 ? d.slice(5, 10) : d))
  chartInstance.setOption({
    title: { text: 'Institution Revenue Trends', left: 0, textStyle: { fontSize: 14 } },
    tooltip: { trigger: 'axis' },
    xAxis: {
      type: 'category',
      data: shortDates,
      boundaryGap: false,
      axisLabel: { rotate: 45 }
    },
    yAxis: {
      type: 'value',
      name: 'Revenue (UGX)',
      axisLabel: { formatter: (value) => formatMoney(value) }
    },
    series: [{
      name: 'Institution Revenue',
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
      itemStyle: { color: 'rgb(99, 102, 241)' }
    }]
  })
}

function exportChart () {
  if (!chartInstance) return
  
  const dataURL = chartInstance.getDataURL()
  const a = document.createElement('a')
  a.href = dataURL
  a.download = `institution-revenue-chart-${new Date().toISOString().split('T')[0]}.png`
  a.click()
}

function exportData () {
  const csvContent = [
    ['Date', 'Institution', 'Amount', 'Type', 'Description'].join(','),
    ...payments.value.map(item => [
      item.created_at,
      item.institution,
      item.amount,
      item.type,
      item.description || ''
    ].map(field => `"${field}"`).join(','))
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `institution-revenue-${new Date().toISOString().split('T')[0]}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

async function fetchRevenue () {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await get('admin/finance/institution-revenue', {
      query: {
        page: currentPage.value,
        per_page: '15',
        days: trendDays.value
      }
    })
    
    const data = res?.data ?? []
    payments.value = data.map((item) => ({
      ...item,
      created_at: item.created_at ? new Date(item.created_at).toLocaleString() : '',
      institution: item.institution?.name || `Institution #${item.institution?.id || '–'}`,
      amount: formatMoney(item.amount),
      type: item.type || 'subscription',
      description: item.description || '–'
    }))
    
    totalPayments.value = res?.meta?.total || data.length
    summary.value = res?.summary || summary.value
    trends.value = res?.trends ?? { dates: [], values: [] }

    nextTick(() => renderChart())
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to load institution revenue data.'
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
