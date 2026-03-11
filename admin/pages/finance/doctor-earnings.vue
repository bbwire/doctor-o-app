<template>
  <div class="space-y-6">
    <AdminBreadcrumbs :items="[{ label: 'Finance', to: '/finance' }, { label: 'Doctor Earnings' }]" />
    <div class="flex items-center gap-3">
      <UButton to="/finance" variant="ghost" icon="i-lucide-arrow-left" size="sm">
        Back
      </UButton>
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Doctor Earnings
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
          Track doctor earnings from consultations and view payout history.
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
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Earnings</p>
        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
          {{ formatMoney(summary.total_earnings) }}
        </p>
      </UCard>
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">This Month</p>
        <p class="mt-1 text-2xl font-bold text-primary-600 dark:text-primary-400">
          {{ formatMoney(summary.this_month) }}
        </p>
      </UCard>
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Doctors</p>
        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
          {{ summary.active_doctors }}
        </p>
      </UCard>
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pending Payouts</p>
        <p class="mt-1 text-2xl font-bold text-orange-600 dark:text-orange-400">
          {{ formatMoney(summary.pending_payouts) }}
        </p>
      </UCard>
    </div>

    <!-- Doctor Earnings Trends Chart -->
    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }" class="mb-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
          Doctor Earnings Trends
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
        <UFormGroup label="Speciality" class="flex-1">
          <USelectMenu
            v-model="selectedSpeciality"
            :options="specialityOptions"
            value-attribute="value"
            option-attribute="label"
            placeholder="All specialities"
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

    <!-- Earnings Table -->
    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
          Doctor Earnings
        </h2>
        <div class="flex items-center gap-2">
          <UButton
            @click="processPayouts"
            icon="i-lucide-banknote"
            variant="outline"
            size="sm"
            :loading="processingPayouts"
          >
            Process Payouts
          </UButton>
          <UButton
            @click="exportData"
            icon="i-lucide-download"
            variant="outline"
            size="sm"
          >
            Export CSV
          </UButton>
        </div>
      </div>

      <div v-if="loading" class="py-8 text-center text-sm text-gray-500">
        Loading doctor earnings data...
      </div>

      <UTable
        v-else
        :rows="filteredEarnings"
        :columns="earningsColumns"
        :loading="loading"
      />

      <div v-if="!filteredEarnings.length && !loading" class="py-8 text-center text-sm text-gray-500">
        No doctor earnings found.
      </div>

      <div v-if="filteredEarnings.length" class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200 dark:border-gray-800">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Showing {{ filteredEarnings.length }} of {{ totalEarnings }} records
        </p>
        <UPagination
          v-model="currentPage"
          :page-count="pageCount"
          :total="totalEarnings"
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
const { get, post } = useAdminApi()

const loading = ref(true)
const errorMessage = ref('')
const processingPayouts = ref(false)
const currentPage = ref(1)
const trendDays = ref(30)
const selectedPeriod = ref('30days')
const selectedSpeciality = ref('')
const selectedDoctor = ref('')

const earnings = ref([])
const trends = ref({ dates: [], values: [] })
const chartRef = ref(null)
let chartInstance = null
const totalEarnings = ref(0)
const summary = ref({
  total_earnings: 0,
  this_month: 0,
  active_doctors: 0,
  pending_payouts: 0
})

const periodOptions = [
  { label: 'Last 7 days', value: '7days' },
  { label: 'Last 30 days', value: '30days' },
  { label: 'Last 90 days', value: '90days' },
  { label: 'This year', value: 'year' },
  { label: 'All time', value: 'all' }
]

const specialityOptions = [
  { label: 'General Doctor', value: 'General Doctor' },
  { label: 'Physician', value: 'Physician' },
  { label: 'Surgeon', value: 'Surgeon' },
  { label: 'Paediatrician', value: 'Paediatrician' },
  { label: 'Nurse', value: 'Nurse' },
  { label: 'Pharmacist', value: 'Pharmacist' },
  { label: 'Gynecologist', value: 'Gynecologist' },
  { label: 'Dentist', value: 'Dentist' }
]

const doctorOptions = ref([])

const earningsColumns = [
  { key: 'created_at', label: 'Date & Time', sortable: true },
  { key: 'doctor', label: 'Doctor', sortable: true },
  { key: 'speciality', label: 'Speciality', sortable: true },
  { key: 'consultations', label: 'Consultations', sortable: true },
  { key: 'earnings', label: 'Earnings', sortable: true },
  { key: 'commission', label: 'Platform Fee', sortable: true },
  { key: 'net_earnings', label: 'Net Earnings', sortable: true },
  { key: 'status', label: 'Status', sortable: true }
]

const pageCount = computed(() => Math.ceil(totalEarnings.value / 15))

const filteredEarnings = computed(() => {
  let filtered = earnings.value

  if (selectedSpeciality.value) {
    filtered = filtered.filter(item => item.speciality === selectedSpeciality.value)
  }

  if (selectedDoctor.value) {
    filtered = filtered.filter(item => item.doctor_id === selectedDoctor.value)
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
      name: 'Doctor Earnings',
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
    ['Date', 'Doctor', 'Speciality', 'Consultations', 'Earnings', 'Platform Fee', 'Net Earnings', 'Status'].join(','),
    ...filteredEarnings.value.map(item => [
      item.created_at,
      item.doctor,
      item.speciality,
      item.consultations,
      item.earnings,
      item.commission,
      item.net_earnings,
      item.status
    ].map(field => `"${field}"`).join(','))
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `doctor-earnings-${new Date().toISOString().split('T')[0]}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

async function processPayouts () {
  processingPayouts.value = true
  try {
    await post('admin/finance/process-payouts')
    
    // Refresh data
    await fetchEarnings()
    
    const toast = useToast()
    toast.add({
      title: 'Payouts Processed',
      description: 'Doctor payouts have been processed successfully.',
      color: 'green'
    })
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to process payouts.'
  } finally {
    processingPayouts.value = false
  }
}

async function fetchEarnings () {
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

    if (selectedSpeciality.value) {
      queryParams.speciality = selectedSpeciality.value
    }

    if (selectedDoctor.value) {
      queryParams.doctor_id = selectedDoctor.value
    }

    const [res, financeRes] = await Promise.all([
      get('admin/finance/doctor-earnings', { query: queryParams }),
      get(`admin/finance?days=${trendDays.value}`)
    ])

    const financeData = financeRes?.data ?? {}
    trends.value = financeData.trends?.doctor_earnings ?? { dates: [], values: [] }
    nextTick(() => renderChart())

    const data = res?.data ?? []
    earnings.value = data.map((item) => ({
      ...item,
      created_at: item.created_at ? new Date(item.created_at).toLocaleString() : '',
      doctor: item.doctor ? `${item.doctor.name}` : '–',
      speciality: item.speciality || 'Unknown',
      consultations: item.consultation_count || 0,
      earnings: formatMoney(item.total_earnings),
      commission: formatMoney(item.platform_fees),
      net_earnings: formatMoney(item.net_earnings),
      status: item.payout_status || 'pending'
    }))
    
    totalEarnings.value = res?.meta?.total || data.length
    summary.value = res?.summary || summary.value
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to load doctor earnings data.'
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
      ...doctors.map(d => ({ label: `${d.name} (${d.speciality})`, value: d.id }))
    ]
  } catch (e) {
    console.error('Failed to load doctors:', e)
  }
}

function applyFilters () {
  currentPage.value = 1
  fetchEarnings()
}

watch(currentPage, () => {
  fetchEarnings()
})

watch(trendDays, () => {
  fetchEarnings()
})

onMounted(async () => {
  await fetchDoctors()
  await fetchEarnings()
})

onBeforeUnmount(() => {
  chartInstance?.dispose()
})
</script>
