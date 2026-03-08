<template>
  <div class="min-h-screen min-h-[100dvh] bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
    <header class="sticky top-0 z-50 flex h-14 items-center justify-center border-b border-gray-200/80 bg-white/80 backdrop-blur dark:border-gray-800 dark:bg-gray-900/80 safe-area-top safe-area-top-balance">
      <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 flex h-14 items-center justify-between gap-3 sm:gap-4">
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


        <div class="flex items-center gap-2 shrink-0">
          <NuxtLink
            v-if="user && user.role === 'patient'"
            to="/wallet"
            class="inline-flex items-center gap-1.5 rounded-full border border-primary-500/70 bg-primary-50/80 px-2.5 py-1 text-[11px] font-medium text-primary-700 shadow-sm dark:border-primary-700/70 dark:bg-primary-900/40 dark:text-primary-100"
          >
            <UIcon name="i-lucide-wallet" class="w-3.5 h-3.5" />
            <span class="tabular-nums">
              {{ headerWalletLabel }}
            </span>
          </NuxtLink>
          <NuxtLink
            v-if="user && user.role === 'doctor'"
            to="/doctor/wallet"
            class="inline-flex items-center gap-1.5 rounded-full border border-primary-500/70 bg-primary-50/80 px-2.5 py-1 text-[11px] font-medium text-primary-700 shadow-sm dark:border-primary-700/70 dark:bg-primary-900/40 dark:text-primary-100"
          >
            <UIcon name="i-lucide-wallet" class="w-3.5 h-3.5" />
            <span class="tabular-nums">
              {{ headerDoctorWalletLabel }}
            </span>
          </NuxtLink>
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

    <NuxtLink
      v-if="user && user.role === 'doctor'"
      to="/profile"
      class="block border-b border-amber-200/60 dark:border-amber-800/70 bg-amber-50/80 dark:bg-amber-900/20 hover:bg-amber-100/80 dark:hover:bg-amber-900/30 transition-colors cursor-pointer"
    >
      <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3">
        <UAlert
          color="amber"
          icon="i-lucide-alert-circle"
          variant="soft"
          title="Complete your doctor profile"
          description="Add your speciality, licence number and documents so we can review and approve your account. Click to go to Profile."
        />
      </div>
    </NuxtLink>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-4 sm:py-6 pb-safe" :class="user ? 'pb-20 md:pb-6' : ''">
      <slot />
    </main>

    <!-- Mobile bottom nav -->
    <nav
      v-if="user"
      class="md:hidden fixed bottom-0 left-0 right-0 z-50 border-t border-gray-200/80 bg-white/95 backdrop-blur dark:border-gray-800 dark:bg-gray-900/95 safe-area-bottom shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] dark:shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.2)]"
    >
      <div class="flex items-center justify-around h-14 md:h-16 px-1">
        <NuxtLink
          v-for="item in mobileNavItems"
          :key="item.to"
          :to="item.to"
          class="flex flex-col items-center justify-center gap-1 min-w-0 flex-1 py-2 px-1 rounded-xl transition-colors duration-150 -mx-1"
          :class="isMobileNavActive(item)
            ? 'text-primary-600 dark:text-primary-400 bg-primary-50/80 dark:bg-primary-900/20'
            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 active:bg-gray-100 dark:active:bg-gray-800/50'"
        >
          <UIcon :name="item.icon" class="w-5 h-5 sm:w-6 sm:h-6 shrink-0" />
          <span class="text-[10px] font-medium truncate max-w-[4rem]">{{ item.label }}</span>
        </NuxtLink>
      </div>
    </nav>

    <footer class="border-t border-gray-200 dark:border-gray-800 py-4 mt-6 sm:mt-10 safe-area-bottom" :class="user ? 'mb-20 md:mb-0' : ''">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 text-sm text-gray-500 dark:text-gray-400 flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4">
        <span>Dr. O Medical Services</span>
        <span class="hidden sm:inline">·</span>
        <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1">
          <NuxtLink to="/about" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">About Us</NuxtLink>
          <NuxtLink to="/privacy" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Privacy Policy</NuxtLink>
          <NuxtLink to="/contact" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Contact Us</NuxtLink>
        </div>
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

const headerWalletBalance = ref(null)
const headerWalletLoading = ref(false)
const headerDoctorWalletBalance = ref(null)
const headerDoctorWalletLoading = ref(false)

const headerWalletLabel = computed(() => {
  if (!user.value || user.value.role !== 'patient') {
    return ''
  }
  if (headerWalletLoading.value && headerWalletBalance.value === null) {
    return 'Wallet …'
  }
  const value = Number(headerWalletBalance.value ?? 0)
  if (Number.isNaN(value)) return 'Wallet UGX 0'
  return `Wallet ${new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value)}`
})

const headerDoctorWalletLabel = computed(() => {
  if (!user.value || user.value.role !== 'doctor') {
    return ''
  }
  if (headerDoctorWalletLoading.value && headerDoctorWalletBalance.value === null) {
    return 'Revenue …'
  }
  const value = Number(headerDoctorWalletBalance.value ?? 0)
  if (Number.isNaN(value)) return 'Revenue UGX 0'
  return `Revenue ${new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value)}`
})

async function fetchHeaderWallet () {
  if (!user.value || user.value.role !== 'patient') return
  headerWalletLoading.value = true
  try {
    const res = await $fetch('/wallet', {
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${token.value || tokenCookie.value || ''}`,
        Accept: 'application/json'
      }
    })
    headerWalletBalance.value = typeof res?.data?.balance === 'number' ? res.data.balance : 0
  } catch {
    // keep previous balance or null
  } finally {
    headerWalletLoading.value = false
  }
}

async function fetchHeaderDoctorWallet () {
  if (!user.value || user.value.role !== 'doctor') return
  headerDoctorWalletLoading.value = true
  try {
    const res = await $fetch('/doctor/wallet', {
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${token.value || tokenCookie.value || ''}`,
        Accept: 'application/json'
      }
    })
    headerDoctorWalletBalance.value = typeof res?.data?.balance === 'number' ? res.data.balance : 0
  } catch {
    // keep previous balance or null
  } finally {
    headerDoctorWalletLoading.value = false
  }
}

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
  if (u) {
    fetchRecentNotifications()
    fetchHeaderWallet()
    fetchHeaderDoctorWallet()
  }
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
  { label: 'Home', icon: 'i-lucide-layout-dashboard', to: '/dashboard' },
  { label: 'Book', icon: 'i-lucide-calendar-plus', to: '/consultations/book' },
  { label: 'Visits', icon: 'i-lucide-calendar-days', to: '/consultations' },
  { label: 'Rx', icon: 'i-lucide-file-text', to: '/prescriptions' },
  { label: 'Alerts', icon: 'i-lucide-bell', to: '/notifications' }
]

const doctorNavItems = [
  { label: 'Home', icon: 'i-lucide-layout-dashboard', to: '/doctor/dashboard' },
  { label: 'Visits', icon: 'i-lucide-calendar-days', to: '/doctor/consultations' },
  { label: 'Revenue', icon: 'i-lucide-wallet', to: '/doctor/wallet' },
  { label: 'Rx', icon: 'i-lucide-file-text', to: '/doctor/prescriptions' },
  { label: 'Alerts', icon: 'i-lucide-bell', to: '/notifications' }
]

const mobileNavItems = computed(() => {
  return user.value?.role === 'doctor' ? doctorNavItems : patientNavItems
})

function isMobileNavActive (item) {
  const path = item.to
  if (path === '/consultations' || path === '/doctor/consultations') {
    const current = route.path
    if (path === '/consultations') {
      return current === '/consultations' || (current.startsWith('/consultations/') && !current.startsWith('/consultations/book'))
    }
    return current === '/doctor/consultations' || current.startsWith('/doctor/consultations/')
  }
  if (path === '/dashboard' || path === '/doctor/dashboard') {
    return route.path === path
  }
  return route.path === path || route.path.startsWith(path + '/')
}

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
