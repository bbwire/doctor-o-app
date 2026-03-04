<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Prescriptions</h1>
      <p class="text-gray-600 dark:text-gray-300">View and manage your prescriptions</p>
    </div>

    <section>
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <UAlert
          v-if="errorMessage"
          icon="i-lucide-alert-triangle"
          color="red"
          variant="soft"
          :title="errorMessage"
        />

        <div v-else-if="loading" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
          Loading prescriptions...
        </div>

        <div v-else-if="prescriptions.length === 0" class="mt-2 rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-8 text-center">
          <UIcon name="i-lucide-file-text" class="w-8 h-8 mx-auto text-gray-400 dark:text-gray-500 mb-3" />
          <h3 class="text-base font-semibold text-gray-900 dark:text-white">No prescriptions to display yet</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Prescriptions issued to your account will appear here.
          </p>
        </div>

        <div v-else class="space-y-4">
          <UCard
            v-for="prescription in prescriptions"
            :key="prescription.id"
            :ui="{ background: 'bg-gray-50 dark:bg-gray-800/60', ring: 'ring-1 ring-gray-200 dark:ring-gray-700' }"
          >
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Issued {{ formatDateTime(prescription.issued_at) }}
                </p>
                <p class="font-semibold text-gray-900 dark:text-white mt-1">
                  Dr. {{ prescription.doctor?.name || 'Unknown Doctor' }}
                </p>
              </div>
              <UBadge :color="prescription.status === 'active' ? 'green' : 'gray'" variant="soft">
                {{ prescription.status }}
              </UBadge>
            </div>

            <div class="mt-4">
              <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Medications</h4>
              <ul class="space-y-2">
                <li
                  v-for="(medication, index) in prescription.medications"
                  :key="`${prescription.id}-${index}`"
                  class="text-sm text-gray-700 dark:text-gray-300"
                >
                  <span class="font-medium">{{ medication.name }}</span>
                  <span v-if="medication.dosage"> - {{ medication.dosage }}</span>
                  <span v-if="medication.frequency"> - {{ medication.frequency }}</span>
                  <span v-if="medication.duration"> - {{ medication.duration }}</span>
                </li>
              </ul>
            </div>

            <p v-if="prescription.instructions" class="mt-3 text-sm text-gray-600 dark:text-gray-400">
              <span class="font-medium">Instructions:</span> {{ prescription.instructions }}
            </p>
          </UCard>
        </div>
      </UCard>
    </section>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth'
})

interface Medication {
  name: string
  dosage?: string
  frequency?: string
  duration?: string
}

interface PrescriptionItem {
  id: number
  issued_at: string
  status: 'active' | 'completed' | 'cancelled'
  instructions?: string
  medications: Medication[]
  doctor?: {
    name?: string
  }
}

const config = useRuntimeConfig()
const { formatDateTime } = useDateFormat()
const tokenCookie = useCookie<string | null>('auth_token')
const { isApiReachable, hasApiStatusChecked } = useApiHealth()
const toast = useToast()

const prescriptions = ref<PrescriptionItem[]>([])
const loading = ref(true)
const errorMessage = ref('')
const retryWhenOnline = ref(false)
const reconnectRetryInProgress = ref(false)

const fetchPrescriptions = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await $fetch<{ data: PrescriptionItem[] }>('/prescriptions', {
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${tokenCookie.value || ''}`,
        Accept: 'application/json'
      }
    })

    prescriptions.value = response.data || []
    retryWhenOnline.value = false

    if (reconnectRetryInProgress.value) {
      toast.add({
        title: 'Connection restored',
        description: 'Prescriptions list has been refreshed.',
        color: 'green'
      })
    }
  } catch (error) {
    const err = error && typeof error === 'object' ? error : null

    if (hasApiStatusChecked.value && !isApiReachable.value) {
      retryWhenOnline.value = true
      errorMessage.value = 'API is currently unreachable. Prescriptions will retry when connection is restored.'
      return
    }

    const message = err && 'data' in err && err.data && typeof err.data === 'object' && 'message' in err.data
      ? err.data.message
      : null
    errorMessage.value = typeof message === 'string' ? message : 'Unable to load prescriptions.'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await fetchPrescriptions()
})

watch([isApiReachable, hasApiStatusChecked], async ([reachable, checked]) => {
  if (checked && reachable && retryWhenOnline.value && !loading.value) {
    reconnectRetryInProgress.value = true
    await fetchPrescriptions()
    reconnectRetryInProgress.value = false
  }
})
</script>

