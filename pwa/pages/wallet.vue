<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Wallet
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
          Add credit and review your recent wallet activity.
        </p>
      </div>
      <UButton
        variant="ghost"
        icon="i-lucide-arrow-left"
        to="/dashboard"
        class="self-start sm:self-auto"
      >
        Back to dashboard
      </UButton>
    </div>

    <section>
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div class="flex flex-col lg:flex-row lg:items-start gap-8">
          <div class="flex-1 space-y-4">
            <div class="flex items-center gap-4">
              <div class="p-3 rounded-xl bg-primary-50 dark:bg-primary-900/20">
                <UIcon name="i-lucide-wallet" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
              </div>
              <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Available credit
                </p>
                <p class="text-3xl font-semibold text-gray-900 dark:text-white">
                  {{ formattedBalance }}
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
          </div>

          <div class="w-full lg:w-80">
            <h2 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
              Top up credit
            </h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
              Enter the amount, your mobile money number and provider. You will receive a prompt on your phone to enter your PIN.
            </p>
            <UForm :state="topUpState" class="space-y-3" @submit="submitTopUp">
              <UFormGroup label="Amount (UGX)" required>
                <UInput
                  v-model="topUpState.amount"
                  type="number"
                  min="1"
                  step="1"
                  icon="i-lucide-coins"
                />
              </UFormGroup>
              <UFormGroup label="Mobile money number" required>
                <UInput
                  v-model="topUpState.phone"
                  type="tel"
                  inputmode="tel"
                  icon="i-lucide-phone"
                />
              </UFormGroup>
              <UFormGroup label="Provider" required>
                <USelect
                  v-model="topUpState.provider"
                  :options="providerOptions"
                  option-attribute="label"
                  value-attribute="value"
                />
              </UFormGroup>
              <UButton
                type="submit"
                icon="i-lucide-plus-circle"
                :loading="toppingUp"
                :disabled="!canTopUp"
                block
              >
                {{ pendingTopUpId ? 'Waiting for payment...' : 'Add credit' }}
              </UButton>
              <p
                v-if="pendingTopUpId"
                class="text-xs text-amber-500 dark:text-amber-400 mt-1"
              >
                Please check your phone for a mobile money prompt and enter your PIN to approve the payment.
              </p>
            </UForm>
          </div>
        </div>
      </UCard>
    </section>

    <section>
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-base font-semibold text-gray-900 dark:text-white">
            Recent activity
          </h2>
        </div>

        <div v-if="loading" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
          Loading wallet activity...
        </div>
        <div v-else-if="transactions.length === 0" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
          No wallet activity yet.
        </div>
        <ul v-else class="divide-y divide-gray-200 dark:divide-gray-800">
          <li
            v-for="tx in transactions"
            :key="tx.id"
            class="flex items-center justify-between gap-3 py-3"
          >
            <div class="min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-white">
                {{ transactionTitle(tx) }}
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                Balance after: {{ formatAmount(tx.balance_after) }}
                <span v-if="tx.meta?.consultation_id"> · Consultation #{{ tx.meta.consultation_id }}</span>
              </p>
            </div>
            <div class="text-right">
              <p
                class="text-sm font-semibold"
                :class="tx.amount > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'"
              >
                {{ tx.amount > 0 ? '+' : '' }}{{ formatAmount(tx.amount) }}
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ formatDateTime(tx.created_at) }}
              </p>
            </div>
          </li>
        </ul>
      </UCard>
    </section>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth'
})

interface WalletTransaction {
  id: number
  type: string
  amount: number
  balance_after: number
  meta?: Record<string, any> | null
  created_at: string
}

const config = useRuntimeConfig()
const tokenCookie = useCookie<string | null>('auth_token')
const toast = useToast()
const { formatDateTime } = useDateFormat()

const balance = ref(0)
const transactions = ref<WalletTransaction[]>([])
const loading = ref(true)
const toppingUp = ref(false)
const errorMessage = ref('')
const pendingTopUpId = ref<number | null>(null)
const statusPollTimeout = ref<ReturnType<typeof setTimeout> | null>(null)

const topUpState = reactive({
  amount: '',
  phone: '',
  provider: 'mtn_momo'
})

const formattedBalance = computed(() => formatAmount(balance.value))
const canTopUp = computed(() => {
  const value = Number(topUpState.amount || 0)
  const hasPhone = Boolean(topUpState.phone && topUpState.phone.trim().length >= 9)
  const hasProvider = Boolean(topUpState.provider)
  return !Number.isNaN(value) && value >= 1 && hasPhone && hasProvider && !toppingUp.value
})

const providerOptions = [
  { value: 'mtn_momo', label: 'MTN Mobile Money' },
  { value: 'airtel_money', label: 'Airtel Money' }
]

const formatAmount = (value: number | string) => {
  const num = typeof value === 'string' ? Number(value) : value
  if (Number.isNaN(num)) return 'UGX 0'
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num)
}

const transactionTitle = (tx: WalletTransaction) => {
  if (tx.type === 'top_up') return 'Wallet top-up'
  if (tx.type === 'consultation_charge') return 'Consultation charge'
  return 'Wallet adjustment'
}

const apiHeaders = computed(() => ({
  Authorization: `Bearer ${tokenCookie.value || ''}`,
  Accept: 'application/json'
}))

const fetchWallet = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const res = await $fetch<{ data: { balance: number; transactions: WalletTransaction[] } }>('/wallet', {
      baseURL: config.public.apiBase,
      headers: apiHeaders.value
    })
    balance.value = res.data.balance ?? 0
    transactions.value = res.data.transactions ?? []
  } catch (error: any) {
    errorMessage.value = error?.data?.message || 'Failed to load wallet.'
  } finally {
    loading.value = false
  }
}

const submitTopUp = async () => {
  if (!canTopUp.value) return
  const amount = Number(topUpState.amount || 0)
  if (Number.isNaN(amount) || amount < 1) return

  toppingUp.value = true
  errorMessage.value = ''

  try {
    const res = await $fetch<{ data: { id: number; status: string; provider: string } }>('/wallet/top-up/initiate', {
      method: 'POST',
      baseURL: config.public.apiBase,
      headers: apiHeaders.value,
      body: {
        amount,
        phone_number: topUpState.phone,
        provider: topUpState.provider
      }
    })

    pendingTopUpId.value = res.data.id
    topUpState.amount = ''

    pollTopUpStatus()

    toast.add({
      title: 'Payment initiated',
      description: 'Please approve the mobile money prompt on your phone.',
      color: 'primary'
    })
  } catch (error: any) {
    errorMessage.value = error?.data?.message || 'Failed to top up wallet.'
    toast.add({
      title: 'Top-up failed',
      description: errorMessage.value,
      color: 'red'
    })
  } finally {
    toppingUp.value = false
  }
}

const pollTopUpStatus = async () => {
  if (!pendingTopUpId.value) return

  try {
    const res = await $fetch<{ data: { status: string; balance: number } }>(`/wallet/top-up/${pendingTopUpId.value}`, {
      baseURL: config.public.apiBase,
      headers: apiHeaders.value
    })

    const status = res.data.status
    if (status === 'successful') {
      balance.value = res.data.balance ?? balance.value
      await fetchWallet()
      pendingTopUpId.value = null
      if (statusPollTimeout.value) {
        clearTimeout(statusPollTimeout.value)
        statusPollTimeout.value = null
      }
      toast.add({
        title: 'Credit added',
        description: 'Your wallet has been topped up successfully.',
        color: 'green'
      })
      return
    }

    if (status === 'failed') {
      pendingTopUpId.value = null
      if (statusPollTimeout.value) {
        clearTimeout(statusPollTimeout.value)
        statusPollTimeout.value = null
      }
      toast.add({
        title: 'Payment failed',
        description: 'The mobile money payment was not completed.',
        color: 'red'
      })
      return
    }
  } catch {
    // Ignore transient errors during polling
  }

  statusPollTimeout.value = setTimeout(pollTopUpStatus, 5000)
}

onMounted(async () => {
  await fetchWallet()
})

onUnmounted(() => {
  if (statusPollTimeout.value) {
    clearTimeout(statusPollTimeout.value)
  }
})
</script>

