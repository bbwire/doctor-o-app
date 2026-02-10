export default defineNuxtRouteMiddleware((to, from) => {
  const { user } = useAuth()

  if (!user.value) {
    return navigateTo('/login')
  }

  // Check admin role for admin routes
  if (to.path.startsWith('/users') && user.value.role !== 'admin') {
    return navigateTo('/login')
  }
})
