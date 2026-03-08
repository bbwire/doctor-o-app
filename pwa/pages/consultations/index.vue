<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Consultations</h1>
      <p class="text-gray-600 dark:text-gray-300">Track your upcoming and past consultations</p>
    </div>

    <section>
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div class="flex flex-col md:flex-row gap-3 md:items-end md:justify-between mb-4">
          <UFormGroup label="Status" class="w-full md:w-64">
            <USelectMenu v-model="statusFilter" :options="statusOptions" />
          </UFormGroup>

          <UButton to="/consultations/book" icon="i-lucide-calendar-plus">
            Book Consultation
          </UButton>
        </div>

        <UAlert
          v-if="errorMessage"
          icon="i-lucide-alert-triangle"
          color="red"
          variant="soft"
          :title="errorMessage"
        />

        <div v-else-if="loading" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
          Loading consultations...
        </div>

        <div v-else-if="consultations.length === 0" class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-8 text-center">
          <UIcon name="i-lucide-calendar" class="w-8 h-8 mx-auto text-gray-400 dark:text-gray-500 mb-3" />
          <h3 class="text-base font-semibold text-gray-900 dark:text-white">No consultations yet</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Book your first consultation to get started.
          </p>
        </div>

        <div v-else class="space-y-4">
          <UCard
            v-for="consultation in consultations"
            :key="consultation.id"
            :ui="{ background: 'bg-gray-50 dark:bg-gray-800/60', ring: 'ring-1 ring-gray-200 dark:ring-gray-700' }"
          >
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ formatDateTime(consultation.scheduled_at) }}
                </p>
                <p class="font-semibold text-gray-900 dark:text-white mt-1">
                  Dr. {{ consultation.doctor?.name || 'Unknown Doctor' }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-300 capitalize">
                  {{ consultation.consultation_type }} consultation
                </p>
              </div>
              <UBadge :color="statusColor(consultation.status)" variant="soft">
                {{ consultation.status }}
              </UBadge>
            </div>

            <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
              <p class="font-medium mb-0.5">Reason:</p>
              <div
                class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2 prose prose-sm prose-slate dark:prose-invert max-w-none"
                v-html="consultation.reason || '<p>No reason provided</p>'"
              />
            </div>

            <div class="mt-4 flex gap-2">
              <UButton
                v-if="consultation.status === 'scheduled'"
                size="sm"
                icon="i-lucide-log-in"
                @click="openConsentModal(consultation)"
              >
                Join
              </UButton>
              <UButton
                :to="`/consultations/${consultation.id}`"
                size="sm"
                variant="outline"
                icon="i-lucide-arrow-right"
              >
                View details
              </UButton>
            </div>
          </UCard>
        </div>
      </UCard>
    </section>

    <UModal v-model="showConsentModal" :ui="{ width: 'max-w-md' }">
      <UCard :ui="{ ring: '', divide: 'divide-y divide-gray-200 dark:divide-gray-800' }">
        <template #header>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Consent to consultation</h3>
        </template>
        <p class="text-sm text-gray-600 dark:text-gray-300">
          By joining this consultation you consent to the collection and use of your data (including voice and video where applicable) for the purpose of this consultation and related documentation, in line with our
          <NuxtLink to="/privacy" target="_blank" class="text-primary-600 dark:text-primary-400 hover:underline">Privacy Policy</NuxtLink>.
        </p>
        <template #footer>
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" color="neutral" @click="showConsentModal = false">
              Cancel
            </UButton>
            <UButton
              icon="i-lucide-log-in"
              @click="onConsentAndJoin"
            >
              I agree & Join
            </UButton>
          </div>
        </template>
      </UCard>
    </UModal>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth'
})

interface ConsultationItem {
  id: number
  scheduled_at: string
  consultation_type: 'text' | 'audio' | 'video'
  status: 'scheduled' | 'completed' | 'cancelled'
  reason?: string
  doctor?: {
    name?: string
  }
}

const config = useRuntimeConfig()
const router = useRouter()
const tokenCookie = useCookie<string | null>('auth_token')
const { isApiReachable, hasApiStatusChecked } = useApiHealth()
const toast = useToast()
const { formatDateTime } = useDateFormat()

const showConsentModal = ref(false)
const consentConsultation = ref<ConsultationItem | null>(null)

const loading = ref(true)
const errorMessage = ref('')
const retryWhenOnline = ref(false)
const reconnectRetryInProgress = ref(false)
const consultations = ref<ConsultationItem[]>([])
const statusFilter = ref<'all' | 'scheduled' | 'completed' | 'cancelled'>('all')
const statusOptions = ['all', 'scheduled', 'completed', 'cancelled']

const fetchConsultations = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await $fetch<{ data: ConsultationItem[] }>('/consultations', {
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${tokenCookie.value || ''}`,
        Accept: 'application/json'
      },
      query: statusFilter.value === 'all' ? {} : { status: statusFilter.value }
    })

    consultations.value = response.data || []
    retryWhenOnline.value = false

    if (reconnectRetryInProgress.value) {
      toast.add({
        title: 'Connection restored',
        description: 'Consultations list has been refreshed.',
        color: 'green'
      })
    }
  } catch (error) {
    const err = error && typeof error === 'object' ? error : null

    if (hasApiStatusChecked.value && !isApiReachable.value) {
      retryWhenOnline.value = true
      errorMessage.value = 'API is currently unreachable. Consultations will retry when connection is restored.'
      return
    }

    if (err && 'data' in err && typeof err.data === 'string') {
      errorMessage.value = 'Unexpected non-JSON response from API. Please sign in again and retry.'
    } else {
      const message = err && 'data' in err && err.data && typeof err.data === 'object' && 'message' in err.data
        ? err.data.message
        : null
      errorMessage.value = typeof message === 'string' ? message : 'Unable to load consultations.'
    }
  } finally {
    loading.value = false
  }
}

const statusColor = (status: ConsultationItem['status']) => {
  if (status === 'scheduled') return 'blue'
  if (status === 'completed') return 'green'
  return 'gray'
}

function openConsentModal (consultation: ConsultationItem) {
  consentConsultation.value = consultation
  showConsentModal.value = true
}

function onConsentAndJoin () {
  const id = consentConsultation.value?.id
  showConsentModal.value = false
  consentConsultation.value = null
  if (id) {
    router.push(`/consultations/${id}/room`)
  }
}

watch(statusFilter, async () => {
  await fetchConsultations()
})

onMounted(async () => {
  await fetchConsultations()
})

watch([isApiReachable, hasApiStatusChecked], async ([reachable, checked]) => {
  if (checked && reachable && retryWhenOnline.value && !loading.value) {
    reconnectRetryInProgress.value = true
    await fetchConsultations()
    reconnectRetryInProgress.value = false
  }
})
</script>

