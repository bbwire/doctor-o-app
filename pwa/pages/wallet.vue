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
              Enter an amount to add to your wallet. (Demo only, no real payment is processed.)
            </p>
            <UForm :state="topUpState" class="space-y-3" @submit="submitTopUp">
              <UFormGroup label="Amount" required>
                <UInput
                  v-model="topUpState.amount"
                  type="number"
                  min="1"
                  step="1"
                  icon="i-lucide-coins"
                />
              </UFormGroup>
              <UButton
                type="submit"
                icon="i-lucide-plus-circle"
                :loading="toppingUp"
                :disabled="!canTopUp"
                block
              >
                Add credit
              </UButton>
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

const topUpState = reactive({
  amount: ''
})

const formattedBalance = computed(() => formatAmount(balance.value))
const canTopUp = computed(() => {
  const value = Number(topUpState.amount || 0)
  return !Number.isNaN(value) && value >= 1 && !toppingUp.value
})

const formatAmount = (value: number | string) => {
  const num = typeof value === 'string' ? Number(value) : value
  if (Number.isNaN(num)) return '0.00'
  return num.toFixed(2)
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
    const res = await $fetch<{ data: { balance: number; transaction: WalletTransaction } }>('/wallet/top-up', {
      method: 'POST',
      baseURL: config.public.apiBase,
      headers: apiHeaders.value,
      body: {
        amount
      }
    })

    balance.value = res.data.balance ?? balance.value
    if (res.data.transaction) {
      transactions.value = [res.data.transaction, ...transactions.value].slice(0, 20)
    }
    topUpState.amount = ''

    toast.add({
      title: 'Credit added',
      description: `Your wallet has been topped up by ${formatAmount(amount)}.`,
      color: 'green'
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

onMounted(async () => {
  await fetchWallet()
})
</script>

