<template>
  <div class="min-h-screen min-h-[100dvh] bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
    <header class="relative z-50 border-b border-gray-200/80 bg-white/80 backdrop-blur dark:border-gray-800 dark:bg-gray-900/80 safe-area-top">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between gap-3 sm:gap-4">
        <NuxtLink to="/" class="flex items-center space-x-2 shrink-0">
          <span class="text-xl font-bold text-primary-600">Dr. O</span>
        </NuxtLink>

        <nav v-if="user" class="hidden md:flex items-center gap-1 flex-1">
          <template v-if="user.role === 'doctor'">
            <NuxtLink
              to="/doctor/dashboard"
              class="nav-link"
              :class="{ 'nav-link-active': isNavActive('/doctor/dashboard', true) }"
            >
              Dashboard
            </NuxtLink>
            <NuxtLink
              to="/doctor/consultations"
              class="nav-link"
              :class="{ 'nav-link-active': isNavActive('/doctor/consultations', false) }"
            >
              Consultations
            </NuxtLink>
            <NuxtLink
              to="/doctor/prescriptions"
              class="nav-link"
              :class="{ 'nav-link-active': isNavActive('/doctor/prescriptions', false) }"
            >
              Prescriptions
            </NuxtLink>
          </template>
          <template v-else>
            <NuxtLink
              to="/dashboard"
              class="nav-link"
              :class="{ 'nav-link-active': isNavActive('/dashboard', true) }"
            >
              Dashboard
            </NuxtLink>
            <NuxtLink
              to="/consultations/book"
              class="nav-link"
              :class="{ 'nav-link-active': isNavActive('/consultations/book', true) }"
            >
              Book
            </NuxtLink>
            <NuxtLink
              to="/consultations"
              class="nav-link"
              :class="{ 'nav-link-active': isNavActive('/consultations', false) }"
            >
              Consultations
            </NuxtLink>
            <NuxtLink
              to="/prescriptions"
              class="nav-link"
              :class="{ 'nav-link-active': isNavActive('/prescriptions', false) }"
            >
              Prescriptions
            </NuxtLink>
          </template>
          <NuxtLink
            to="/notifications"
            class="nav-link"
            :class="{ 'nav-link-active': isNavActive('/notifications', false) }"
          >
            Notifications
          </NuxtLink>
        </nav>

        <UDropdown
          v-if="user"
          :items="mobileNavItems"
          :popper="{ placement: 'bottom-start' }"
          class="md:hidden"
        >
          <UButton icon="i-lucide-menu" variant="ghost" size="sm" color="neutral" aria-label="Menu" />
        </UDropdown>

        <div class="flex items-center gap-2 shrink-0">
          <UButton
            :icon="isDark ? 'i-lucide-sun-medium' : 'i-lucide-moon-star'"
            variant="ghost"
            size="sm"
            color="neutral"
            aria-label="Toggle theme"
            @click="toggleTheme"
          />
          <UButton
            v-if="!user"
            to="/login"
            variant="ghost"
          >
            Sign In
          </UButton>
          <UButton
            v-if="!user"
            to="/register"
          >
            Get Started
          </UButton>
          <template v-else>
            <UDropdown :items="notificationMenuItems" :popper="{ placement: 'bottom-end' }">
              <UButton
                icon="i-lucide-bell"
                variant="ghost"
                size="sm"
                color="neutral"
                aria-label="Notifications"
              />
            </UDropdown>
            <UDropdown :items="profileMenuItems" :popper="{ placement: 'bottom-end' }">
              <UAvatar
                :alt="user?.name || 'User'"
                :src="user?.profile_photo_url || undefined"
                size="sm"
                class="cursor-pointer"
              />
            </UDropdown>
          </template>
        </div>
      </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-4 sm:py-6 pb-safe">
      <slot />
    </main>

    <footer class="border-t border-gray-200 dark:border-gray-800 py-4 mt-6 sm:mt-10 safe-area-bottom">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 text-sm text-gray-500 dark:text-gray-400">
        Dr. O Medical Services
      </div>
    </footer>
  </div>
</template>

<script setup>
const { user, logout, token } = useAuth()
const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const tokenCookie = useCookie('auth_token')

const theme = useState('theme', () => 'light')
const isDark = computed(() => theme.value === 'dark')

function toggleTheme () {
  theme.value = isDark.value ? 'light' : 'dark'
}

function isNavActive (path, exact) {
  const current = route.path
  if (exact) return current === path
  if (path === '/consultations') {
    return current === '/consultations' || (current.startsWith('/consultations/') && !current.startsWith('/consultations/book'))
  }
  return current === path || current.startsWith(path + '/')
}

const recentNotifications = useState('layout.recentNotifications', () => [])

const notificationMenuItems = computed(() => {
  const list = recentNotifications.value || []
  const items = list.length
    ? list.slice(0, 5).map(n => {
        const consultationId = n.data?.consultation_id
        const to = consultationId && user.value?.role === 'doctor'
          ? `/doctor/consultations/${consultationId}`
          : consultationId
            ? `/consultations/${consultationId}`
            : '/notifications'
        return { label: n.title, icon: 'i-lucide-bell', to }
      })
    : [{ label: 'No notifications', icon: 'i-lucide-bell-off', to: '/notifications' }]
  return [items, [{ label: 'View all', icon: 'i-lucide-list', to: '/notifications' }]]
})

async function fetchRecentNotifications () {
  if (!user.value) return
  try {
    const res = await $fetch('/notifications', {
      baseURL: config.public.apiBase,
      query: { per_page: '5' },
      headers: {
        Authorization: `Bearer ${token.value || tokenCookie.value || ''}`,
        Accept: 'application/json'
      }
    })
    const data = res?.data ?? []
    recentNotifications.value = Array.isArray(data) ? data : []
  } catch {
    recentNotifications.value = []
  }
}

watch(user, (u) => {
  if (u) fetchRecentNotifications()
}, { immediate: true })

const profileMenuItems = [
  [{
    label: 'Profile',
    icon: 'i-lucide-user',
    to: '/profile'
  }, {
    label: 'Sign out',
    icon: 'i-lucide-log-out',
    click: () => handleLogout()
  }]
]

const patientNavItems = [
  { label: 'Dashboard', icon: 'i-lucide-layout-dashboard', to: '/dashboard' },
  { label: 'Book', icon: 'i-lucide-calendar-plus', to: '/consultations/book' },
  { label: 'Consultations', icon: 'i-lucide-calendar-days', to: '/consultations' },
  { label: 'Prescriptions', icon: 'i-lucide-file-text', to: '/prescriptions' },
  { label: 'Notifications', icon: 'i-lucide-bell', to: '/notifications' }
]

const doctorNavItems = [
  { label: 'Dashboard', icon: 'i-lucide-layout-dashboard', to: '/doctor/dashboard' },
  { label: 'Consultations', icon: 'i-lucide-calendar-days', to: '/doctor/consultations' },
  { label: 'Prescriptions', icon: 'i-lucide-file-text', to: '/doctor/prescriptions' },
  { label: 'Notifications', icon: 'i-lucide-bell', to: '/notifications' }
]

const mobileNavItems = computed(() => {
  const items = user.value?.role === 'doctor' ? doctorNavItems : patientNavItems
  return [items]
})

const handleLogout = async () => {
  await logout()
  await router.push('/login')
}
</script>

<style scoped>
.nav-link {
  @apply px-3 py-2 rounded-md text-sm font-medium text-gray-600 dark:text-gray-400
    transition-colors duration-150
    hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-gray-100;
}
.nav-link-active {
  @apply bg-primary-50 text-primary-700 dark:bg-primary-900/25 dark:text-primary-300
    hover:bg-primary-100 hover:text-primary-800 dark:hover:bg-primary-900/40 dark:hover:text-primary-200;
}
</style>
