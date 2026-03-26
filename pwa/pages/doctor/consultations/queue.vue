<template>
  <div class="space-y-6">
    <header class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
      <div class="space-y-1">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Waiting room
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
          Patients waiting for doctor assignment in your speciality.
        </p>
      </div>

      <UButton
        to="/doctor/consultations"
        variant="ghost"
        icon="i-lucide-arrow-left"
        size="sm"
        class="self-start sm:self-auto"
      >
        Back to consultations
      </UButton>
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
      <UTable
        :rows="consultations"
        :columns="columns"
        :loading="loading"
        class="cursor-default"
      >
        <template #scheduled_at-data="{ row }">
          {{ formatDateTime(row.scheduled_at) }}
        </template>

        <template #consultation_number-data="{ row }">
          <HumanIdBadge
            v-if="row.consultation_number"
            :value="row.consultation_number"
          />
          <span v-else class="font-mono text-xs text-gray-400 dark:text-gray-500">—</span>
        </template>

        <template #patient_number-data="{ row }">
          <PatientNumberBadge
            v-if="row.patient?.patient_number"
            :patient-number="row.patient.patient_number"
          />
          <span v-else class="font-mono text-sm font-semibold text-gray-400 dark:text-gray-500">—</span>
        </template>

        <template #patient-data="{ row }">
          <span class="font-medium text-gray-900 dark:text-white">
            {{ row.patient?.name || `Patient #${row.patient_id}` }}
          </span>
        </template>

        <template #category-data="{ row }">
          <UBadge color="amber" variant="soft" size="sm">
            {{ row.metadata?.requested_category || '—' }}
          </UBadge>
        </template>

        <template #action-data="{ row }">
          <UButton
            size="sm"
            variant="soft"
            color="green"
            icon="i-lucide-check-circle"
            :loading="actionLoading[row.id]"
            @click.stop="claim(row)"
          >
            Accept
          </UButton>
        </template>
      </UTable>

      <div class="mt-4 flex justify-center">
        <UPagination
          v-model="page"
          :total="total"
          :page-size="perPage"
        />
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'doctor',
})

const config = useRuntimeConfig()
const { token } = useAuth()
const router = useRouter()
const toast = useToast()
const { formatDateTime } = useDateFormat()

const page = ref(1)
const perPage = 15
const total = ref(0)
const loading = ref(false)
const errorMessage = ref('')
const consultations = ref<any[]>([])
const actionLoading = ref<Record<number, boolean>>({})

const columns = [
  { key: 'scheduled_at', label: 'Time' },
  { key: 'consultation_number', label: 'Consultation no.' },
  { key: 'patient_number', label: 'Patient no.' },
  { key: 'patient', label: 'Patient' },
  { key: 'category', label: 'Category' },
  { key: 'consultation_type', label: 'Type' },
  { key: 'action', label: '' },
]

watch([page], () => {
  fetchQueue()
}, { immediate: true })

async function fetchQueue () {
  loading.value = true
  errorMessage.value = ''

  try {
    const res = await $fetch<{ data: any[]; meta?: { total?: number } }>(
      '/doctor/consultations/queue',
      {
        baseURL: config.public.apiBase,
        query: {
          page: String(page.value),
          per_page: String(perPage),
        },
        headers: {
          Authorization: `Bearer ${token.value || ''}`,
          Accept: 'application/json',
        },
      }
    )

    consultations.value = res?.data ?? []
    total.value = res?.meta?.total ?? consultations.value.length
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'Failed to load waiting consultations.'
  } finally {
    loading.value = false
  }
}

async function claim (row: any) {
  if (!row?.id) return

  actionLoading.value[row.id] = true

  try {
    await $fetch(`/doctor/consultations/${row.id}/claim`, {
      method: 'POST',
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${token.value || ''}`,
        Accept: 'application/json',
      },
    })

    toast.add({ title: 'Patient accepted', color: 'green' })
    await fetchQueue()
    router.push(`/doctor/consultations/${row.id}`)
  } catch (e: any) {
    toast.add({
      title: 'Could not accept patient',
      description: e?.data?.message || 'Please try again.',
      color: 'red',
    })
  } finally {
    actionLoading.value[row.id] = false
  }
}
</script>

