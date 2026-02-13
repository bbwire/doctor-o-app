<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        Admin Dashboard
      </h1>
      <p class="text-gray-600 dark:text-gray-300">
        Manage users, institutions, consultations, prescriptions, and platform configuration.
      </p>
    </div>

    <UAlert
      v-if="errorMessage"
      icon="i-lucide-alert-triangle"
      color="red"
      variant="soft"
      :title="errorMessage"
      class="mb-4"
    />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <NuxtLink to="/users" class="block">
        <div class="rounded-xl border border-gray-200 bg-white p-5 transition hover:border-primary-500 hover:shadow dark:border-gray-800 dark:bg-gray-900 dark:hover:border-primary-400">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                Users
              </p>
              <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                {{ summary.users }}
              </p>
            </div>
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-100 text-primary-600 dark:bg-primary-900/40 dark:text-primary-400">
              <UIcon name="i-lucide-users" class="h-5 w-5" />
            </span>
          </div>
          <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Patients, doctors, admins
          </p>
        </div>
      </NuxtLink>

      <NuxtLink to="/institutions" class="block">
        <div class="rounded-xl border border-gray-200 bg-white p-5 transition hover:border-primary-500 hover:shadow dark:border-gray-800 dark:bg-gray-900 dark:hover:border-primary-400">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                Institutions
              </p>
              <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                {{ summary.institutions }}
              </p>
            </div>
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-100 text-primary-600 dark:bg-primary-900/40 dark:text-primary-400">
              <UIcon name="i-lucide-building-2" class="h-5 w-5" />
            </span>
          </div>
          <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Clinics and hospitals
          </p>
        </div>
      </NuxtLink>

      <NuxtLink to="/consultations" class="block">
        <div class="rounded-xl border border-gray-200 bg-white p-5 transition hover:border-primary-500 hover:shadow dark:border-gray-800 dark:bg-gray-900 dark:hover:border-primary-400">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                Consultations
              </p>
              <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                {{ summary.consultations }}
              </p>
            </div>
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-100 text-primary-600 dark:bg-primary-900/40 dark:text-primary-400">
              <UIcon name="i-lucide-calendar" class="h-5 w-5" />
            </span>
          </div>
          <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Appointments
          </p>
        </div>
      </NuxtLink>

      <NuxtLink to="/prescriptions" class="block">
        <div class="rounded-xl border border-gray-200 bg-white p-5 transition hover:border-primary-500 hover:shadow dark:border-gray-800 dark:bg-gray-900 dark:hover:border-primary-400">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                Prescriptions
              </p>
              <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                {{ summary.prescriptions }}
              </p>
            </div>
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-100 text-primary-600 dark:bg-primary-900/40 dark:text-primary-400">
              <UIcon name="i-lucide-file-text" class="h-5 w-5" />
            </span>
          </div>
          <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Prescription records
          </p>
        </div>
      </NuxtLink>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            Recent consultations
          </h2>
          <NuxtLink to="/consultations" class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-400">
            View all
          </NuxtLink>
        </div>
        <div v-if="recentConsultationsLoading" class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">
          Loading...
        </div>
        <ul v-else-if="recentConsultations.length" class="space-y-2">
          <li v-for="c in recentConsultations" :key="c.id" class="flex items-center justify-between gap-2 text-sm">
            <NuxtLink :to="`/consultations/${c.id}`" class="text-gray-900 dark:text-white hover:underline truncate">
              #{{ c.id }} {{ c.patient?.name || 'Patient' }} – {{ c.doctor?.name || 'Doctor' }}
            </NuxtLink>
            <UBadge :color="consultationStatusColor(c.status)" variant="soft" class="shrink-0 text-xs">
              {{ c.status }}
            </UBadge>
          </li>
        </ul>
        <p v-else class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">
          No consultations yet
        </p>
      </UCard>

      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            Active prescriptions
          </h2>
          <NuxtLink to="/prescriptions" class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-400">
            View all
          </NuxtLink>
        </div>
        <div v-if="recentPrescriptionsLoading" class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">
          Loading...
        </div>
        <ul v-else-if="recentPrescriptions.length" class="space-y-2">
          <li v-for="p in recentPrescriptions" :key="p.id" class="flex items-center justify-between gap-2 text-sm">
            <NuxtLink :to="`/prescriptions/${p.id}`" class="text-gray-900 dark:text-white hover:underline truncate">
              #{{ p.id }} {{ p.patient?.name || 'Patient' }}
            </NuxtLink>
            <UBadge color="blue" variant="soft" class="shrink-0 text-xs">
              {{ p.status }}
            </UBadge>
          </li>
        </ul>
        <p v-else class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">
          No active prescriptions
        </p>
      </UCard>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth-admin'
})

const { fetchDashboardSummary, get } = useAdminApi()

const summary = ref({
  users: 0,
  institutions: 0,
  consultations: 0,
  prescriptions: 0
})
const errorMessage = ref('')
const loading = ref(true)
const recentConsultations = ref([])
const recentConsultationsLoading = ref(true)
const recentPrescriptions = ref([])
const recentPrescriptionsLoading = ref(true)

function consultationStatusColor (s) {
  const map = { scheduled: 'blue', completed: 'green', cancelled: 'red' }
  return map[s] || 'gray'
}

onMounted(async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    summary.value = await fetchDashboardSummary()
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to load dashboard summary.'
  } finally {
    loading.value = false
  }

  recentConsultationsLoading.value = true
  try {
    const res = await get('admin/consultations', { query: { page: '1', per_page: '5' } })
    recentConsultations.value = res?.data ?? []
  } catch (_) {
    recentConsultations.value = []
  } finally {
    recentConsultationsLoading.value = false
  }

  recentPrescriptionsLoading.value = true
  try {
    const res = await get('admin/prescriptions', { query: { page: '1', per_page: '5', status: 'active' } })
    recentPrescriptions.value = res?.data ?? []
  } catch (_) {
    recentPrescriptions.value = []
  } finally {
    recentPrescriptionsLoading.value = false
  }
})
</script>
