<template>
  <div class="space-y-6">
    <AdminBreadcrumbs :items="[{ label: 'Finance', to: '/finance' }, { label: 'Consultation Revenue' }]" />
    <div class="flex items-center gap-3">
      <UButton to="/finance" variant="ghost" icon="i-lucide-arrow-left" size="sm">
        Back
      </UButton>
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Consultation Revenue
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
          Track consultation fees and revenue by type, doctor, and time period.
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
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Consultations</p>
        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
          {{ summary.total_consultations }}
        </p>
      </UCard>
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Average Fee</p>
        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
          {{ formatMoney(summary.average_fee) }}
        </p>
      </UCard>
    </div>

    <!-- Consultation Revenue Trends Chart -->
    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }" class="mb-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
          Consultation Revenue Trends
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

    <!-- Filters -->
    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }" class="mb-6">
      <div class="flex items-center gap-4">
        <UFormGroup label="Period" class="flex-1">
          <USelectMenu
            v-model="selectedPeriod"
            :options="periodOptions"
            value-attribute="value"
            option-attribute="label"
          />
        </UFormGroup>
        <UFormGroup label="Consultation Type" class="flex-1">
          <USelectMenu
            v-model="selectedType"
            :options="typeOptions"
            value-attribute="value"
            option-attribute="label"
            placeholder="All types"
          />
        </UFormGroup>
        <UFormGroup label="Doctor" class="flex-1">
          <USelectMenu
            v-model="selectedDoctor"
            :options="doctorOptions"
            value-attribute="value"
            option-attribute="label"
            placeholder="All doctors"
            searchable
          />
        </UFormGroup>
        <UButton @click="applyFilters" icon="i-lucide-filter">
          Apply Filters
        </UButton>
      </div>
    </UCard>

    <!-- Revenue Table -->
    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
          Consultation Revenue
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
        Loading consultation revenue data...
      </div>

      <UTable
        v-else
        :rows="filteredRevenue"
        :columns="revenueColumns"
        :loading="loading"
      >
        <template #invoice_number-data="{ row }">
          <AdminHumanId :value="row.invoice_number" />
        </template>
        <template #consultation_number-data="{ row }">
          <AdminHumanId :value="row.consultation_number" />
        </template>
        <template #patient-data="{ row }">
          <div class="flex min-w-0 max-w-xs flex-col gap-1">
            <AdminPatientNumber :patient-number="row.patient?.patient_number" />
            <span class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ row.patient?.name || '—' }}</span>
            <span class="truncate text-xs text-gray-500 dark:text-gray-400">{{ row.patient?.email || '' }}</span>
          </div>
        </template>
      </UTable>

      <div v-if="!filteredRevenue.length && !loading" class="py-8 text-center text-sm text-gray-500">
        No consultation revenue found.
      </div>

      <div v-if="filteredRevenue.length" class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200 dark:border-gray-800">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Showing {{ filteredRevenue.length }} of {{ totalRevenue }} records
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
const selectedPeriod = ref('30days')
const selectedType = ref('')
const selectedDoctor = ref('')

const revenue = ref([])
const trends = ref({ dates: [], values: [] })
const chartRef = ref(null)
let chartInstance = null
const totalRevenue = ref(0)
const summary = ref({
  total_revenue: 0,
  this_month: 0,
  total_consultations: 0,
  average_fee: 0
})

const periodOptions = [
  { label: 'Last 7 days', value: '7days' },
  { label: 'Last 30 days', value: '30days' },
  { label: 'Last 90 days', value: '90days' },
  { label: 'This year', value: 'year' },
  { label: 'All time', value: 'all' }
]

const typeOptions = [
  { label: 'Video', value: 'video' },
  { label: 'Audio', value: 'audio' },
  { label: 'Text', value: 'text' }
]

const doctorOptions = ref([])

const revenueColumns = [
  { key: 'created_at', label: 'Date & Time', sortable: true },
  { key: 'invoice_number', label: 'Invoice no.', sortable: false },
  { key: 'consultation_number', label: 'Consultation no.', sortable: false },
  { key: 'consultation_id', label: 'Consultation ID', sortable: true },
  { key: 'patient', label: 'Patient', sortable: false },
  { key: 'doctor', label: 'Doctor', sortable: true },
  { key: 'type', label: 'Type', sortable: true },
  { key: 'amount', label: 'Amount', sortable: true },
  { key: 'platform_fee', label: 'Platform Fee', sortable: true },
  { key: 'doctor_earning', label: 'Doctor Earning', sortable: true }
]

const pageCount = computed(() => Math.ceil(totalRevenue.value / 15))

const filteredRevenue = computed(() => {
  let filtered = revenue.value

  if (selectedType.value) {
    filtered = filtered.filter(item => item.type === selectedType.value)
  }

  if (selectedDoctor.value) {
    filtered = filtered.filter(item => String(item.doctor_id ?? '') === String(selectedDoctor.value))
  }

  return filtered
})

function formatMoney (value) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Number(value) ?? 0)
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
      name: 'Consultation Revenue',
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

function exportData () {
  const csvContent = [
    ['Date', 'Invoice no.', 'Consultation no.', 'Consultation ID', 'Patient', 'Doctor', 'Type', 'Amount', 'Platform Fee', 'Doctor Earning'].join(','),
    ...filteredRevenue.value.map(item => [
      item.created_at,
      item.invoice_number || '—',
      item.consultation_number || '—',
      item.consultation_id,
      item.patient,
      item.doctor,
      item.type,
      item.amount,
      item.platform_fee,
      item.doctor_earning
    ].map(field => `"${field}"`).join(','))
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `consultation-revenue-${new Date().toISOString().split('T')[0]}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

async function fetchRevenue () {
  loading.value = true
  errorMessage.value = ''
  try {
    const queryParams = {
      page: currentPage.value,
      per_page: '15'
    }

    if (selectedPeriod.value !== 'all') {
      queryParams.period = selectedPeriod.value
    }

    if (selectedType.value) {
      queryParams.type = selectedType.value
    }

    if (selectedDoctor.value) {
      queryParams.doctor_id = selectedDoctor.value
    }

    const [res, financeRes] = await Promise.all([
      get('admin/finance/consultation-revenue', { query: queryParams }),
      get(`admin/finance?days=${trendDays.value}`)
    ])

    const financeData = financeRes?.data ?? {}
    trends.value = financeData.trends?.consultation_fees ?? { dates: [], values: [] }
    nextTick(() => renderChart())

    const data = res?.data ?? []
    revenue.value = data.map((item) => ({
      ...item,
      doctor_id: item.doctor_id,
      created_at: item.created_at ? new Date(item.created_at).toLocaleString() : '',
      invoice_number: item.invoice_number || null,
      consultation_number: item.consultation_number || null,
      consultation_id: `#${item.consultation_id}`,
      patient: item.patient || null,
      doctor: item.doctor ? `${item.doctor.name}` : '–',
      type: item.consultation_type || 'Unknown',
      amount: formatMoney(item.amount_paid),
      platform_fee: formatMoney(item.platform_fee),
      doctor_earning: formatMoney(item.doctor_earning)
    }))
    
    totalRevenue.value = res?.meta?.total || data.length
    summary.value = res?.summary || summary.value
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to load consultation revenue data.'
  } finally {
    loading.value = false
  }
}

async function fetchDoctors () {
  try {
    const res = await get('admin/doctors', { query: { per_page: '100' } })
    const doctors = res?.data ?? []
    doctorOptions.value = [
      { label: 'All Doctors', value: '' },
      ...doctors.map(d => ({ label: d.name, value: d.id }))
    ]
  } catch (e) {
    console.error('Failed to load doctors:', e)
  }
}

function applyFilters () {
  currentPage.value = 1
  fetchRevenue()
}

watch(currentPage, () => {
  fetchRevenue()
})

watch(trendDays, () => {
  fetchRevenue()
})

onMounted(async () => {
  await fetchDoctors()
  await fetchRevenue()
})

onBeforeUnmount(() => {
  chartInstance?.dispose()
})
</script>
