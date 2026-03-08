<template>
  <div class="space-y-6">
    <header class="space-y-1">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        Revenue wallet
      </h1>
      <p class="text-gray-600 dark:text-gray-300">
        Your consultation earnings and payout requests.
      </p>
    </header>

    <UAlert
      v-if="errorMessage"
      color="red"
      icon="i-lucide-alert-triangle"
      variant="soft"
      :title="errorMessage"
      class="mb-4"
    />

    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600 dark:text-gray-400">Available balance</p>
          <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(wallet.balance) }}</p>
        </div>
        <UIcon name="i-lucide-wallet" class="w-10 h-10 text-primary-500" />
      </div>
      <template #footer>
        <UButton size="sm" variant="soft" @click="showPayoutModal = true">
          Request payout
        </UButton>
      </template>
    </UCard>

    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
        Payout history
      </h3>
      <UTable
        :rows="payoutRequests"
        :columns="payoutColumns"
        :loading="loadingPayouts"
      >
        <template #amount-data="{ row }">
          {{ formatCurrency(row.amount) }}
        </template>
        <template #status-data="{ row }">
          <UBadge :color="payoutStatusColor(row.status)" variant="soft" size="xs">
            {{ row.status }}
          </UBadge>
        </template>
        <template #requested_at-data="{ row }">
          {{ row.requested_at ? formatDateTime(row.requested_at) : '–' }}
        </template>
      </UTable>
      <div v-if="!payoutRequests.length && !loadingPayouts" class="mt-2 text-gray-500 dark:text-gray-400">
        No payout requests yet.
      </div>
    </UCard>

    <UModal v-model="showPayoutModal" :ui="{ width: 'max-w-sm' }">
      <UCard>
        <template #header>
          <h3 class="font-semibold text-gray-900 dark:text-white">Request payout</h3>
        </template>
        <form class="space-y-4" @submit.prevent="submitPayout">
          <UFormField label="Amount" required>
            <UInput v-model.number="payoutAmount" type="number" min="0.01" step="0.01" placeholder="0.00" />
          </UFormField>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            Available: {{ formatCurrency(wallet.balance) }}
          </p>
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" @click="showPayoutModal = false">Cancel</UButton>
            <UButton type="submit" :loading="submittingPayout">Submit request</UButton>
          </div>
        </form>
      </UCard>
    </UModal>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'doctor'
})

const config = useRuntimeConfig()
const { formatDateTime } = useDateFormat()
const { token } = useAuth()
const tokenCookie = useCookie('auth_token')

function formatCurrency (value: number) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value ?? 0)
}

function payoutStatusColor (status: string) {
  switch (status) {
    case 'paid': return 'green'
    case 'approved': return 'blue'
    case 'rejected': return 'red'
    default: return 'gray'
  }
}

const errorMessage = ref('')
const wallet = reactive({ balance: 0 })
const payoutRequests = ref<any[]>([])
const showPayoutModal = ref(false)
const payoutAmount = ref<number>(0)
const submittingPayout = ref(false)
const loadingPayouts = ref(true)

const payoutColumns = [
  { key: 'amount', label: 'Amount' },
  { key: 'status', label: 'Status' },
  { key: 'requested_at', label: 'Requested' },
  { key: 'processed_at', label: 'Processed' }
]

function apiHeaders () {
  return {
    Authorization: `Bearer ${token.value || tokenCookie.value || ''}`,
    Accept: 'application/json'
  }
}

async function fetchWallet () {
  try {
    const res = await $fetch<{ data: { balance: number } }>('/doctor/wallet', {
      baseURL: config.public.apiBase,
      headers: apiHeaders()
    })
    wallet.balance = Number(res?.data?.balance ?? 0)
  } catch {
    wallet.balance = 0
  }
}

async function fetchPayoutRequests () {
  loadingPayouts.value = true
  try {
    const res = await $fetch<{ data: { data: any[] } }>('/doctor/payout-requests', {
      baseURL: config.public.apiBase,
      query: { per_page: 20 },
      headers: apiHeaders()
    })
    const d = (res as any)?.data
    payoutRequests.value = Array.isArray(d?.data) ? d.data : (Array.isArray(d) ? d : [])
  } catch {
    payoutRequests.value = []
  } finally {
    loadingPayouts.value = false
  }
}

async function submitPayout () {
  const amount = Number(payoutAmount.value)
  if (!amount || amount <= 0) {
    errorMessage.value = 'Enter a valid amount.'
    return
  }
  submittingPayout.value = true
  errorMessage.value = ''
  try {
    await $fetch('/doctor/payout-requests', {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: apiHeaders(),
      body: { amount }
    })
    showPayoutModal.value = false
    payoutAmount.value = 0
    await Promise.all([fetchWallet(), fetchPayoutRequests()])
  } catch (e: any) {
    errorMessage.value = e?.data?.message || e?.data?.errors?.amount?.[0] || 'Failed to submit payout request.'
  } finally {
    submittingPayout.value = false
  }
}

onMounted(async () => {
  await Promise.all([fetchWallet(), fetchPayoutRequests()])
})
</script>
