<template>
  <div class="space-y-6">
    <header class="space-y-1">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        My prescriptions
      </h1>
      <p class="text-gray-600 dark:text-gray-300">
        Prescriptions you have issued.
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
      <UTable
        :rows="prescriptions"
        :columns="columns"
        :loading="loading"
      >
        <template #prescription_number-data="{ row }">
          <HumanIdBadge
            v-if="row.prescription_number"
            :value="row.prescription_number"
          />
          <span v-else class="font-mono text-xs text-gray-400 dark:text-gray-500">—</span>
        </template>
        <template #patient-data="{ row }">
          {{ row.patient?.name || `Patient #${row.patient_id}` }}
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
  middleware: 'doctor'
})

const config = useRuntimeConfig()
const { token } = useAuth()

const loading = ref(false)
const errorMessage = ref('')
const prescriptions = ref<any[]>([])
const page = ref(1)
const perPage = 15
const total = ref(0)

const columns = [
  { key: 'issued_at', label: 'Issued at' },
  { key: 'prescription_number', label: 'Prescription no.' },
  { key: 'patient', label: 'Patient' },
  { key: 'status', label: 'Status' }
]

watch(page, () => {
  fetchPrescriptions()
}, { immediate: true })

async function fetchPrescriptions () {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await $fetch<{ data: any[]; meta?: { total?: number } }>(
      '/doctor/prescriptions',
      {
        baseURL: config.public.apiBase,
        query: {
          page: String(page.value),
          per_page: String(perPage)
        },
        headers: {
          Authorization: `Bearer ${token.value || ''}`,
          Accept: 'application/json'
        }
      }
    )

    prescriptions.value = res?.data ?? []
    total.value = res?.meta?.total ?? prescriptions.value.length
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'Failed to load prescriptions.'
  } finally {
    loading.value = false
  }
}
</script>

