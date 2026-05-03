<template>
  <div class="h-screen flex overflow-hidden bg-gray-50 dark:bg-gray-950">
    <!-- Mobile overlay when sidebar open -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-20 bg-black/50 lg:hidden"
      aria-hidden="true"
      @click="sidebarOpen = false"
    />

    <!-- Sidebar: fixed so it doesn't scroll with content -->
    <aside
      class="fixed inset-y-0 left-0 z-30 flex w-64 shrink-0 flex-col border-r border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900/95 lg:static lg:z-auto lg:shadow-none"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    >
      <div class="flex h-14 shrink-0 items-center justify-between border-b border-gray-200 px-4 dark:border-gray-800">
        <NuxtLink
          to="/"
          class="flex min-w-0 items-center gap-2 text-primary-600 dark:text-primary-400"
          aria-label="Dr. O Admin — Home"
        >
          <AppLogo height-class="h-9 sm:h-10" width-class="w-auto max-w-[10rem]" />
          <span class="truncate text-sm font-semibold tracking-tight text-gray-700 dark:text-gray-200">Admin</span>
        </NuxtLink>
        <UButton
          icon="i-lucide-x"
          variant="ghost"
          size="sm"
          class="lg:hidden"
          @click="sidebarOpen = false"
        />
      </div>

      <nav class="admin-sidebar-nav flex-1 overflow-y-auto px-3 py-4">
        <ul class="space-y-1">
          <li>
            <NuxtLink
              to="/"
              class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
              :class="navLinkClass('/')"
              @click="sidebarOpen = false"
            >
              <UIcon name="i-lucide-layout-dashboard" class="h-4 w-4 shrink-0" />
              Dashboard
            </NuxtLink>
          </li>
          <li v-if="can('manage_institutions')">
            <NuxtLink
              to="/institutions"
              class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
              :class="navLinkClass('/institutions')"
              @click="sidebarOpen = false"
            >
              <UIcon name="i-lucide-building-2" class="h-4 w-4 shrink-0" />
              Institutions
            </NuxtLink>
          </li>
          <li v-if="can('manage_healthcare_professionals')">
            <NuxtLink
              to="/healthcare-professionals"
              class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
              :class="navLinkClass('/healthcare-professionals')"
              @click="sidebarOpen = false"
            >
              <UIcon name="i-lucide-stethoscope" class="h-4 w-4 shrink-0" />
              Professionals
            </NuxtLink>
          </li>
          <li v-if="can('manage_consultations')">
            <NuxtLink
              to="/consultations"
              class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
              :class="isActive('/consultations') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/25 dark:text-primary-300 border-l-2 border-primary-500 -ml-px pl-[11px]' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'"
              @click="sidebarOpen = false"
            >
              <UIcon name="i-lucide-calendar" class="h-4 w-4 shrink-0" />
              Consultations
            </NuxtLink>
          </li>
          <li v-if="can('manage_prescriptions')">
            <NuxtLink
              to="/prescriptions"
              class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
              :class="navLinkClass('/prescriptions')"
              @click="sidebarOpen = false"
            >
              <UIcon name="i-lucide-file-text" class="h-4 w-4 shrink-0" />
              Prescriptions
            </NuxtLink>
          </li>
          <li v-if="can('view_notifications')">
            <NuxtLink
              to="/notifications"
              class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
              :class="navLinkClass('/notifications')"
              @click="sidebarOpen = false"
            >
              <UIcon name="i-lucide-bell" class="h-4 w-4 shrink-0" />
              Notifications
            </NuxtLink>
          </li>
          <li v-if="can('manage_users')">
            <NuxtLink
              to="/users"
              class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
              :class="navLinkClass('/users')"
              @click="sidebarOpen = false"
            >
              <UIcon name="i-lucide-users" class="h-4 w-4 shrink-0" />
              Users
            </NuxtLink>
          </li>
          <li v-if="can('manage_users')">
            <NuxtLink
              to="/patients"
              class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
              :class="navLinkClass('/patients')"
              @click="sidebarOpen = false"
            >
              <UIcon name="i-lucide-circle-user" class="h-4 w-4 shrink-0" />
              Patients
            </NuxtLink>
          </li>
          <li v-if="can('manage_finance')">
            <NuxtLink
              to="/finance"
              class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
              :class="navLinkClass('/finance')"
              @click="sidebarOpen = false"
            >
              <UIcon name="i-lucide-wallet" class="h-4 w-4 shrink-0" />
              Finance
            </NuxtLink>
          </li>
          <li v-if="can('manage_settings')">
            <NuxtLink
              to="/settings"
              class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
              :class="navLinkClass('/settings')"
              @click="sidebarOpen = false"
            >
              <UIcon name="i-lucide-settings" class="h-4 w-4 shrink-0" />
              Settings
            </NuxtLink>
          </li>
          <li v-if="can('manage_settings')">
            <NuxtLink
              to="/audit-trail"
              class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
              :class="navLinkClass('/audit-trail')"
              @click="sidebarOpen = false"
            >
              <UIcon name="i-lucide-history" class="h-4 w-4 shrink-0" />
              Audit trail
            </NuxtLink>
          </li>
        </ul>
      </nav>
    </aside>

    <!-- Main content: only this area scrolls -->
    <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
      <header class="sticky top-0 z-10 flex h-14 shrink-0 items-center justify-between gap-3 border-b border-gray-200 bg-white px-4 dark:border-gray-800 dark:bg-gray-900 lg:px-6">
        <div class="flex items-center gap-3">
          <UButton
            icon="i-lucide-menu"
            variant="ghost"
            size="sm"
            class="lg:hidden"
            @click="sidebarOpen = true"
          />
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ currentPageTitle }}
          </span>
        </div>
        <div class="flex items-center gap-3">
          <UDropdown :items="notificationMenuItems" :popper="{ placement: 'bottom-end' }">
            <UButton
              icon="i-lucide-bell"
              variant="ghost"
              size="sm"
              color="neutral"
              aria-label="Notifications"
            />
          </UDropdown>
          <UButton
            :icon="isDark ? 'i-lucide-sun-medium' : 'i-lucide-moon-star'"
            variant="ghost"
            size="sm"
            color="neutral"
            aria-label="Toggle theme"
            @click="toggleTheme"
          />
          <UDropdown :items="userMenuItems">
            <UAvatar :alt="user?.name || 'Admin'" size="sm" class="cursor-pointer" />
          </UDropdown>
        </div>
      </header>

      <main class="flex-1 overflow-y-auto px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
const { user, logout, clearAuth } = useAuth()
const router = useRouter()
const toast = useToast()
const route = useRoute()
const theme = useState('theme')
const isDark = computed(() => theme.value === 'dark')
const sidebarOpen = ref(false)

/** Super admin has all permissions; admin has permission-based access. */
function can (permission: string) {
  const u = user.value
  if (!u) return false
  if (u.is_super_admin || u.role === 'super_admin') return true
  if (u.role !== 'admin') return false
  return Array.isArray(u.permissions) && u.permissions.includes(permission)
}

function isActive (path) {
  const p = route.path
  if (path === '/') return p === '/'
  return p === path || p.startsWith(path + '/')
}

// Single source of truth for nav link styling so every item is identical
const NAV_LINK_ACTIVE = 'bg-primary-50 text-primary-700 dark:bg-primary-900/25 dark:text-primary-300 border-l-2 border-primary-500 -ml-px pl-[11px]'
const NAV_LINK_INACTIVE = 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'
function navLinkClass (path) {
  return isActive(path) ? NAV_LINK_ACTIVE : NAV_LINK_INACTIVE
}

const currentPageTitle = computed(() => {
  const path = route.path
  if (path === '/') return 'Dashboard'
  if (path.startsWith('/patients')) return 'Patients'
  if (path.startsWith('/users')) return 'Users'
  if (path.startsWith('/institutions')) return 'Institutions'
  if (path.startsWith('/healthcare-professionals')) return 'Professionals'
  if (path.startsWith('/consultations')) return 'Consultations'
  if (path.startsWith('/prescriptions')) return 'Prescriptions'
  if (path.startsWith('/notifications')) return 'Notifications'
  if (path.startsWith('/audit-trail')) return 'Audit trail'
  if (path.startsWith('/settings')) return 'Settings'
  return 'Admin'
})

const toggleTheme = () => {
  theme.value = isDark.value ? 'light' : 'dark'
}

const recentNotifications = useState<Array<{ id: number; title: string; body?: string; read_at?: string; created_at?: string }>>('layout.recentNotifications', () => [])
const { get } = useAdminApi()

const notificationMenuItems = computed(() => {
  const list = recentNotifications.value || []
  const items = list.length
    ? list.slice(0, 5).map(n => ({ label: n.title, icon: 'i-lucide-bell', to: '/notifications' }))
    : [{ label: 'No notifications', icon: 'i-lucide-bell-off', to: '/notifications' }]
  return [items, [{ label: 'View all', icon: 'i-lucide-list', to: '/notifications' }]]
})

onMounted(async () => {
  try {
    const res = await get('notifications', { query: { per_page: '5' } })
    const data = (res as { data?: unknown[] })?.data ?? []
    recentNotifications.value = Array.isArray(data) ? data : []
  } catch (_) {
    recentNotifications.value = []
  }
})

const userMenuItems = [
  [{
    label: 'Users',
    icon: 'i-lucide-users',
    to: '/users'
  }],
  [{
    label: 'Sign out',
    icon: 'i-lucide-log-out',
    click: async () => {
      await logout()
      await router.push('/login')
    }
  }]
]

// Idle timeout: after 30 min inactivity, clear session and redirect to login
let stopIdle = null
watch(user, (u) => {
  if (stopIdle) {
    stopIdle()
    stopIdle = null
  }
  if (u) {
    const { start } = useIdleTimeout({
      timeoutMs: 30 * 60 * 1000,
      onIdle: () => {
        clearAuth()
        toast.add({ title: 'Session expired', description: 'Please sign in again.', color: 'amber' })
        navigateTo('/login')
      }
    })
    stopIdle = start()
  }
}, { immediate: true })

onUnmounted(() => {
  if (stopIdle) stopIdle()
})
</script>

<style scoped>
/* Ensure active nav link in dark mode always uses our background (overrides any NuxtLink default) */
.admin-sidebar-nav :deep(a.router-link-active) {
  @apply dark:!bg-primary-900/25;
}
</style>
