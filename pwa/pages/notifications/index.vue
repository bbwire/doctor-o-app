<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Notifications
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
          Your recent notifications
        </p>
      </div>
      <UButton
        v-if="notifications.length && hasUnread"
        variant="outline"
        size="sm"
        :loading="markingAllRead"
        @click="markAllRead"
      >
        Mark all as read
      </UButton>
    </div>

    <UAlert
      v-if="errorMessage"
      icon="i-lucide-alert-triangle"
      color="red"
      variant="soft"
      :title="errorMessage"
    />

    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <div v-if="loading" class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
        Loading...
      </div>
      <ul v-else-if="notifications.length" class="divide-y divide-gray-200 dark:divide-gray-800">
        <li
          v-for="n in notifications"
          :key="n.id"
          class="flex items-start justify-between gap-4 py-4 first:pt-0 last:pb-0"
          :class="n.read_at ? 'opacity-75' : ''"
        >
          <NuxtLink
            :to="notificationLink(n)"
            class="min-w-0 flex-1 block hover:opacity-90"
          >
            <p class="font-medium text-gray-900 dark:text-white">
              {{ n.title }}
            </p>
            <p v-if="n.body" class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">
              {{ n.body }}
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">
              {{ formatDate(n.created_at) }}
            </p>
          </NuxtLink>
          <UButton
            v-if="!n.read_at"
            variant="ghost"
            size="xs"
            @click.stop="markRead(n.id)"
          >
            Mark read
          </UButton>
        </li>
      </ul>
      <p v-else class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
        No notifications
      </p>
      <div v-if="total > pageSize" class="mt-4 flex justify-center border-t border-gray-200 pt-4 dark:border-gray-800">
        <UPagination v-model="page" :total="total" :page-size="pageSize" />
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth'
})

const config = useRuntimeConfig()
const { user, token } = useAuth()
const tokenCookie = useCookie('auth_token')

const notifications = ref<any[]>([])
const total = ref(0)
const page = ref(1)
const pageSize = 15
const loading = ref(true)
const errorMessage = ref('')
const markingAllRead = ref(false)

const hasUnread = computed(() => notifications.value.some(n => !n.read_at))

function getHeaders () {
  const authToken = token.value || tokenCookie.value
  return {
    Authorization: `Bearer ${authToken || ''}`,
    Accept: 'application/json'
  }
}

function formatDate (val: string) {
  if (!val) return '—'
  try {
    return new Date(val).toLocaleString()
  } catch {
    return val
  }
}

function notificationLink (n: { data?: { consultation_id?: number } }) {
  const consultationId = n.data?.consultation_id
  if (consultationId && user.value?.role === 'doctor') {
    return `/doctor/consultations/${consultationId}`
  }
  if (consultationId) {
    return `/consultations/${consultationId}`
  }
  return '/notifications'
}

async function fetchNotifications () {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await $fetch<{ data?: any[]; meta?: { total?: number } }>('/notifications', {
      baseURL: config.public.apiBase,
      query: { page: String(page.value), per_page: String(pageSize) },
      headers: getHeaders()
    })
    const data = res?.data ?? []
    const meta = res?.meta ?? {}
    notifications.value = Array.isArray(data) ? data : []
    total.value = typeof meta.total === 'number' ? meta.total : notifications.value.length
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'Failed to load notifications.'
  } finally {
    loading.value = false
  }
}

async function markRead (id: number) {
  try {
    await $fetch(`/notifications/${id}`, {
      baseURL: config.public.apiBase,
      method: 'PATCH',
      headers: getHeaders()
    })
    const n = notifications.value.find(n => n.id === id)
    if (n) n.read_at = new Date().toISOString()
  } catch { /* ignore */ }
}

async function markAllRead () {
  markingAllRead.value = true
  try {
    await $fetch('/notifications/read-all', {
      baseURL: config.public.apiBase,
      method: 'PATCH',
      headers: getHeaders()
    })
    notifications.value.forEach(n => { n.read_at = n.read_at || new Date().toISOString() })
  } catch { /* ignore */ }
  finally {
    markingAllRead.value = false
  }
}

watch(page, fetchNotifications)

onMounted(fetchNotifications)
</script>
