<template>
  <UApp>
    <UHeader>
      <template #logo>
        <NuxtLink to="/users" class="flex items-center space-x-2">
          <span class="text-xl font-bold text-primary-600">Dr. O Admin</span>
        </NuxtLink>
      </template>

      <template #right>
        <UDropdown :items="userMenuItems">
          <UAvatar
            :alt="user?.name || 'Admin'"
            size="sm"
          />
        </UDropdown>
      </template>
    </UHeader>

    <UMain>
      <slot />
    </UMain>
  </UApp>
</template>

<script setup>
const { user, logout } = useAuth()
const router = useRouter()

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
</script>
