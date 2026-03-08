<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Patients
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
          Add, edit, and manage patients. Chronic diseases are visible in patient profile and to doctors during consultations.
        </p>
      </div>
      <UButton to="/patients/create" icon="i-lucide-plus" size="sm">
        Add patient
      </UButton>
    </div>

    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <div class="mb-4 flex items-center justify-between gap-4">
        <UInput
          v-model="search"
          placeholder="Search patients..."
          icon="i-lucide-search"
          class="max-w-xs"
        />
      </div>

      <UAlert
        v-if="errorMessage"
        icon="i-lucide-alert-triangle"
        color="red"
        variant="soft"
        :title="errorMessage"
        class="mb-4"
      />

      <UTable
        :rows="patients"
        :columns="columns"
        :loading="loading"
      >
        <template #chronic_conditions-data="{ row }">
          <span v-if="row.chronic_conditions?.length" class="text-sm">
            {{ (row.chronic_conditions || []).join(', ') }}
          </span>
          <span v-else class="text-gray-400">—</span>
        </template>
        <template #actions-data="{ row }">
          <UButton
            icon="i-lucide-eye"
            size="sm"
            variant="ghost"
            @click="navigateTo(`/patients/${row.id}`)"
          />
          <UButton
            icon="i-lucide-edit"
            size="sm"
            variant="ghost"
            @click="navigateTo(`/patients/${row.id}`)"
          />
          <UButton
            icon="i-lucide-trash-2"
            size="sm"
            variant="ghost"
            color="red"
            @click="confirmDelete(row)"
          />
        </template>
      </UTable>

      <div class="mt-4 flex justify-center">
        <UPagination
          v-model="page"
          :total="total"
          :page-size="15"
        />
      </div>
    </UCard>

    <UModal v-model="deleteModalOpen">
      <template #content>
        <div class="p-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Delete patient
          </h3>
          <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Are you sure you want to delete <strong>{{ patientToDelete?.name || patientToDelete?.email }}</strong>? This cannot be undone.
          </p>
          <div class="mt-4 flex justify-end gap-2">
            <UButton variant="outline" @click="deleteModalOpen = false">
              Cancel
            </UButton>
            <UButton color="red" :loading="deleting" @click="doDelete">
              Delete
            </UButton>
          </div>
        </div>
      </template>
    </UModal>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth-admin'
})

const config = useRuntimeConfig()
const { get, del } = useAdminApi()
const toast = useToast()

const search = ref('')
const page = ref(1)
const loading = ref(false)
const patients = ref<any[]>([])
const total = ref(0)
const errorMessage = ref('')
const deleteModalOpen = ref(false)
const patientToDelete = ref<{ id: number; name?: string; email?: string } | null>(null)
const deleting = ref(false)

const columns = [
  { key: 'id', label: 'ID' },
  { key: 'name', label: 'Name' },
  { key: 'email', label: 'Email' },
  { key: 'phone', label: 'Phone' },
  { key: 'chronic_conditions', label: 'Chronic conditions' },
  { key: 'actions', label: 'Actions' }
]

async function fetchPatients () {
  loading.value = true
  errorMessage.value = ''
  try {
    const params: Record<string, string> = {
      page: page.value.toString(),
      per_page: '15',
      role: 'patient'
    }
    if (search.value) params.search = search.value

    const response = await $fetch<{ data?: any[]; meta?: { total?: number } }>(`/admin/users?${new URLSearchParams(params)}`, {
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${useCookie('auth_token').value || ''}`,
        Accept: 'application/json'
      }
    })

    const data = response?.data ?? []
    const meta = response?.meta ?? {}
    patients.value = Array.isArray(data) ? data : []
    total.value = typeof meta.total === 'number' ? meta.total : patients.value.length
  } catch (error: any) {
    errorMessage.value = error?.data?.message || 'Failed to fetch patients.'
  } finally {
    loading.value = false
  }
}

function confirmDelete (row: { id: number; name?: string; email?: string }) {
  patientToDelete.value = row
  deleteModalOpen.value = true
}

async function doDelete () {
  if (!patientToDelete.value) return
  deleting.value = true
  try {
    await del(`admin/users/${patientToDelete.value.id}`)
    toast.add({ title: 'Patient deleted', color: 'green' })
    deleteModalOpen.value = false
    patientToDelete.value = null
    await fetchPatients()
  } catch (e: any) {
    toast.add({ title: 'Delete failed', description: e?.data?.message || 'Could not delete patient.', color: 'red' })
  } finally {
    deleting.value = false
  }
}

watch([search, page], () => {
  fetchPatients()
})

onMounted(() => {
  fetchPatients()
})
</script>
