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
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <UCard @click="navigateToTopUps" :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }" class="cursor-pointer hover:shadow-lg transition-shadow">
          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Patient top-ups</p>
          <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
            {{ formatMoney(summary.total_patient_top_ups) }}
          </p>
        </UCard>
        <UCard @click="navigateToConsultationRevenue" :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }" class="cursor-pointer hover:shadow-lg transition-shadow">
          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Consultation Revenue</p>
          <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
            {{ formatMoney(summary.total_consultation_fees) }}
          </p>
        </UCard>
        <UCard @click="navigateToPlatformRevenue" :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }" class="cursor-pointer hover:shadow-lg transition-shadow">
          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Platform revenue</p>
          <p class="mt-1 text-2xl font-bold text-primary-600 dark:text-primary-400">
            {{ formatMoney(summary.total_platform_revenue) }}
          </p>
        </UCard>
        <UCard @click="navigateToDoctorEarnings" :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }" class="cursor-pointer hover:shadow-lg transition-shadow">
          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Doctor earnings</p>
          <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
            {{ formatMoney(summary.total_doctor_earnings) }}
          </p>
        </UCard>
        <UCard @click="navigateToInstitutionRevenue" :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }" class="cursor-pointer hover:shadow-lg transition-shadow">
          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Institution revenue</p>
          <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
            {{ formatMoney(summary.total_institution_revenue) }}
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

      <div class="space-y-8">
        <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Trends
          </h2>
          <ClientOnly>
            <AdminFinanceTrendCharts
              :top-ups="trends.top_ups || { dates: [], values: [] }"
              :consultation-revenue="trends.consultation_fees || { dates: [], values: [] }"
              :platform-revenue="trends.platform_revenue || { dates: [], values: [] }"
              :doctor-earnings="trends.doctor_earnings || { dates: [], values: [] }"
              :institution-revenue="trends.institution_revenue || { dates: [], values: [] }"
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
        >
          <template #user-data="{ row }">
            <div class="flex min-w-0 max-w-xs flex-col gap-1">
              <AdminPatientNumber :patient-number="row.user?.patient_number" />
              <span class="truncate text-sm text-gray-900 dark:text-white">
                {{ row.user ? `${row.user.name} (${row.user.email})` : `User #${row.user_id}` }}
              </span>
            </div>
          </template>
        </UTable>
        <p v-if="!topUps.length && !topUpsLoading" class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">
          No top-ups yet
        </p>
      </UCard>

      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          Consultation settlements (revenue & platform earnings)
        </h2>
        <div v-if="settlementsLoading" class="py-4 text-center text-sm text-gray-500">
          Loading...
        </div>
        <UTable
          v-else
          :rows="settlements"
          :columns="settlementColumns"
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
            </div>
          </template>
        </UTable>
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
  total_doctor_earnings: 0,
  total_institution_revenue: 0
})
const platformRevenuePercent = ref(10)
const trends = ref({
  top_ups: null,
  consultation_fees: null,
  platform_revenue: null,
  doctor_earnings: null,
  institution_revenue: null
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
  { key: 'invoice_number', label: 'Invoice no.' },
  { key: 'consultation_number', label: 'Consultation no.' },
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

const router = useRouter()

function navigateToTopUps () {
  router.push('/finance/patient-top-ups')
}

function navigateToConsultationRevenue () {
  router.push('/finance/consultation-revenue')
}

function navigateToPlatformRevenue () {
  router.push('/finance/platform-revenue')
}

function navigateToDoctorEarnings () {
  router.push('/finance/doctor-earnings')
}

function navigateToInstitutionRevenue () {
  router.push('/finance/institution-revenue')
}

async function fetchFinance () {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await get(`admin/finance?days=${trendDays.value}`)
    const data = res?.data ?? {}
    summary.value = data.summary ?? summary.value
    platformRevenuePercent.value = data.platform_revenue_percentage ?? 10
    trends.value = data.trends ?? { top_ups: null, consultation_fees: null, platform_revenue: null, doctor_earnings: null, institution_revenue: null }
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
      amount: formatMoney(t.amount)
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
      invoice_number: s.invoice_number || null,
      consultation_number: s.consultation_number || null,
      created_at: s.created_at ? new Date(s.created_at).toLocaleString() : '',
      amount_paid: formatMoney(s.amount_paid),
      platform_fee: formatMoney(s.platform_fee),
      doctor_earning: formatMoney(s.doctor_earning),
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
