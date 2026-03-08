<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        Finance
      </h1>
      <p class="text-gray-600 dark:text-gray-300">
        Track patient top-ups, consultation fees, and platform revenue. Platform share is {{ platformRevenuePercent }}% of each consultation fee (configurable in Settings).
      </p>
    </div>

    <UAlert
      v-if="errorMessage"
      icon="i-lucide-alert-triangle"
      color="red"
      variant="soft"
      :title="errorMessage"
    />

    <div v-if="loading" class="flex items-center justify-center py-16">
      <UIcon name="i-lucide-loader-2" class="size-8 animate-spin text-gray-400" />
    </div>

    <template v-else>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Patient top-ups</p>
          <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
            {{ formatMoney(summary.total_patient_top_ups) }}
          </p>
        </UCard>
        <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Consultation fees</p>
          <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
            {{ formatMoney(summary.total_consultation_fees) }}
          </p>
        </UCard>
        <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Platform revenue</p>
          <p class="mt-1 text-2xl font-bold text-primary-600 dark:text-primary-400">
            {{ formatMoney(summary.total_platform_revenue) }}
          </p>
        </UCard>
        <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Doctor earnings</p>
          <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
            {{ formatMoney(summary.total_doctor_earnings) }}
          </p>
        </UCard>
      </div>

      <div class="flex gap-2 items-center">
        <label class="text-sm text-gray-600 dark:text-gray-400">Trend period:</label>
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

      <div class="grid gap-6 lg:grid-cols-1">
        <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Trends
          </h2>
          <ClientOnly>
            <AdminFinanceTrendCharts
              v-if="trends.top_ups"
              :top-ups="trends.top_ups"
              :consultation-fees="trends.consultation_fees"
              :platform-revenue="trends.platform_revenue"
            />
            <template #fallback>
              <div class="h-80 flex items-center justify-center text-gray-500 dark:text-gray-400">
                Loading charts...
              </div>
            </template>
          </ClientOnly>
        </UCard>
      </div>

      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          Recent patient top-ups
        </h2>
        <div v-if="topUpsLoading" class="py-4 text-center text-sm text-gray-500">
          Loading...
        </div>
        <UTable
          v-else
          :rows="topUps"
          :columns="topUpColumns"
        />
        <p v-if="!topUps.length && !topUpsLoading" class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">
          No top-ups yet
        </p>
      </UCard>

      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          Consultation settlements (fees & platform revenue)
        </h2>
        <div v-if="settlementsLoading" class="py-4 text-center text-sm text-gray-500">
          Loading...
        </div>
        <UTable
          v-else
          :rows="settlements"
          :columns="settlementColumns"
        />
        <p v-if="!settlements.length && !settlementsLoading" class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">
          No settlements yet
        </p>
      </UCard>
    </template>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth-admin'
})

const { get } = useAdminApi()

const loading = ref(true)
const errorMessage = ref('')
const summary = ref({
  total_patient_top_ups: 0,
  total_consultation_fees: 0,
  total_platform_revenue: 0,
  total_doctor_earnings: 0
})
const platformRevenuePercent = ref(10)
const trends = ref({
  top_ups: null,
  consultation_fees: null,
  platform_revenue: null
})
const trendDays = ref(30)
const topUps = ref([])
const topUpsLoading = ref(true)
const settlements = ref([])
const settlementsLoading = ref(true)

const topUpColumns = [
  { key: 'created_at', label: 'Date' },
  { key: 'user', label: 'User' },
  { key: 'amount', label: 'Amount' }
]
const settlementColumns = [
  { key: 'created_at', label: 'Date' },
  { key: 'consultation_type', label: 'Type' },
  { key: 'patient', label: 'Patient' },
  { key: 'doctor', label: 'Doctor' },
  { key: 'amount_paid', label: 'Paid' },
  { key: 'platform_fee', label: 'Platform' },
  { key: 'doctor_earning', label: 'Doctor earning' }
]

function formatMoney (value) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Number(value) ?? 0)
}

async function fetchFinance () {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await get(`admin/finance?days=${trendDays.value}`)
    const data = res?.data ?? {}
    summary.value = data.summary ?? summary.value
    platformRevenuePercent.value = data.platform_revenue_percentage ?? 10
    trends.value = data.trends ?? { top_ups: null, consultation_fees: null, platform_revenue: null }
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to load finance data.'
  } finally {
    loading.value = false
  }
}

async function fetchTopUps () {
  topUpsLoading.value = true
  try {
    const res = await get('admin/finance/top-ups', { query: { per_page: '15' } })
    topUps.value = (res?.data ?? []).map((t) => ({
      ...t,
      created_at: t.created_at ? new Date(t.created_at).toLocaleString() : '',
      amount: formatMoney(t.amount),
      user: t.user ? `${t.user.name} (${t.user.email})` : `User #${t.user_id}`
    }))
  } catch (_) {
    topUps.value = []
  } finally {
    topUpsLoading.value = false
  }
}

async function fetchSettlements () {
  settlementsLoading.value = true
  try {
    const res = await get('admin/finance/settlements', { query: { per_page: '15' } })
    settlements.value = (res?.data ?? []).map((s) => ({
      ...s,
      created_at: s.created_at ? new Date(s.created_at).toLocaleString() : '',
      amount_paid: formatMoney(s.amount_paid),
      platform_fee: formatMoney(s.platform_fee),
      doctor_earning: formatMoney(s.doctor_earning),
      patient: s.patient ? `${s.patient.name}` : '–',
      doctor: s.doctor ? `${s.doctor.name}` : '–'
    }))
  } catch (_) {
    settlements.value = []
  } finally {
    settlementsLoading.value = false
  }
}

watch(trendDays, () => {
  fetchFinance()
})

onMounted(async () => {
  await fetchFinance()
  await Promise.all([fetchTopUps(), fetchSettlements()])
})
</script>
