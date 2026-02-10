<template>
  <div class="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
    <header class="relative z-50 border-b border-gray-200/80 bg-white/80 backdrop-blur dark:border-gray-800 dark:bg-gray-900/80">
      <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
        <NuxtLink to="/" class="flex items-center space-x-2">
          <span class="text-xl font-bold text-primary-600">Dr. O</span>
        </NuxtLink>

        <div class="flex items-center gap-2">
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
            <UDropdown :items="userMenuItems">
              <UAvatar
                :alt="user?.name || 'User'"
                :src="user?.profile_photo_url || undefined"
                size="sm"
              />
            </UDropdown>
            <UButton
              color="gray"
              variant="ghost"
              icon="i-lucide-log-out"
              @click="handleLogout"
            >
              Sign out
            </UButton>
          </template>
        </div>
      </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-6">
      <slot />
    </main>

    <footer class="border-t border-gray-200 dark:border-gray-800 py-4 mt-10">
      <div class="max-w-6xl mx-auto px-4 text-sm text-gray-500 dark:text-gray-400">
        Dr. O Medical Services
      </div>
    </footer>
  </div>
</template>

<script setup>
const { user, logout } = useAuth()
const router = useRouter()

const userMenuItems = [
  [{
    label: 'Dashboard',
    icon: 'i-lucide-layout-dashboard',
    to: '/dashboard'
  }, {
    label: 'Book Consultation',
    icon: 'i-lucide-calendar-plus',
    to: '/consultations/book'
  }, {
    label: 'My Consultations',
    icon: 'i-lucide-calendar-days',
    to: '/consultations'
  }, {
    label: 'Prescriptions',
    icon: 'i-lucide-file-text',
    to: '/prescriptions'
  }, {
    label: 'Profile',
    icon: 'i-lucide-user',
    to: '/profile'
  }]
]

const handleLogout = async () => {
  await logout()
  await router.push('/login')
}
</script>
