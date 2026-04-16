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
          <h3 class="text-base font-semibold text-gray-900 dark:text-white">No active prescriptions</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            New prescriptions appear here until you download them and mark them as received from the pharmacy. Your full record stays in each consultation’s clinical summary PDF.
          </p>
        </div>

        <div v-else class="space-y-4">
          <UCard
            v-for="prescription in prescriptions"
            :key="prescription.id"
            :ui="{ background: 'bg-gray-50 dark:bg-gray-800/60', ring: 'ring-1 ring-gray-200 dark:ring-gray-700' }"
          >
            <div class="flex flex-wrap items-start justify-between gap-4">
              <div class="min-w-0 flex-1">
                <p v-if="prescription.prescription_number" class="text-xs font-mono text-primary-600 dark:text-primary-400">
                  {{ prescription.prescription_number }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Issued {{ formatDateTime(prescription.issued_at) }}
                </p>
                <p class="font-semibold text-gray-900 dark:text-white mt-1">
                  Dr. {{ prescription.doctor?.name || 'Unknown Doctor' }}
                </p>
              </div>
              <div class="flex flex-wrap items-center gap-2 shrink-0">
                <UButton
                  size="sm"
                  variant="outline"
                  icon="i-lucide-download"
                  :loading="downloadingId === prescription.id"
                  @click="downloadPrescription(prescription)"
                >
                  Download PDF
                </UButton>
                <UButton
                  size="sm"
                  color="primary"
                  icon="i-lucide-circle-check"
                  :loading="acknowledgingId === prescription.id"
                  @click="acknowledgeReceipt(prescription)"
                >
                  Received
                </UButton>
                <UBadge :color="prescription.status === 'active' ? 'green' : 'gray'" variant="soft">
                  {{ prescription.status }}
                </UBadge>
              </div>
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
                  <span v-if="medication.form"> ({{ medication.form }})</span>
                  <span v-if="medication.dosage"> - {{ medication.dosage }}</span>
                  <span v-if="medication.frequency"> - {{ medication.frequency }}</span>
                  <span v-if="medication.duration"> - {{ medication.duration }}</span>
                  <span v-if="medication.instructions" class="block text-gray-600 dark:text-gray-400 mt-0.5">{{ medication.instructions }}</span>
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
  prescription_number?: string | null
  issued_at: string
  status: 'active' | 'completed' | 'cancelled'
  patient_received_at?: string | null
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
const downloadingId = ref<number | null>(null)
const acknowledgingId = ref<number | null>(null)
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

    prescriptions.value = Array.isArray(response.data) ? response.data : []
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

async function downloadPrescription (p: PrescriptionItem) {
  if (typeof document === 'undefined') return
  downloadingId.value = p.id
  try {
    const url = `${config.public.apiBase}/prescriptions/${p.id}/download`
    const res = await fetch(url, {
      headers: { Authorization: `Bearer ${tokenCookie.value || ''}` }
    })
    if (!res.ok) throw new Error('Download failed')
    const blob = await res.blob()
    const safeName = (p.prescription_number || `rx-${p.id}`).replace(/[^\w.-]+/g, '_')
    const a = document.createElement('a')
    a.href = URL.createObjectURL(blob)
    a.download = `${safeName}.pdf`
    a.click()
    URL.revokeObjectURL(a.href)
    toast.add({ title: 'Prescription downloaded', color: 'green' })
  } catch {
    toast.add({ title: 'Download failed', description: 'Please try again.', color: 'red' })
  } finally {
    downloadingId.value = null
  }
}

async function acknowledgeReceipt (p: PrescriptionItem) {
  acknowledgingId.value = p.id
  try {
    await $fetch(`/prescriptions/${p.id}/acknowledge-receipt`, {
      baseURL: config.public.apiBase,
      method: 'POST',
      headers: {
        Authorization: `Bearer ${tokenCookie.value || ''}`,
        Accept: 'application/json'
      }
    })
    toast.add({
      title: 'Marked as received',
      description: 'This prescription is no longer shown here. Your consultation clinical summary PDF still lists it for your records.',
      color: 'green'
    })
    await fetchPrescriptions()
  } catch (e: any) {
    const msg = e?.data?.message || e?.data?.errors?.prescription?.[0] || 'Please try again.'
    toast.add({ title: 'Could not update', description: String(msg), color: 'red' })
  } finally {
    acknowledgingId.value = null
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

